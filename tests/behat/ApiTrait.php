<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use DateTime;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Teknoo\East\Common\Doctrine\Object\Media;
use Teknoo\East\Common\Object\Media as BaseMedia;
use Teknoo\East\Common\Object\MediaMetadata;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Website\Doctrine\Object\Comment;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Post;
use Teknoo\East\Website\Object\Block;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Object\Environment as WebsiteEnvironment;

use function array_keys;
use function base64_decode;
use function bin2hex;
use function explode;
use function file_put_contents;
use function is_array;
use function json_decode;
use function parse_str;
use function random_bytes;
use function sort;
use function str_starts_with;
use function strtoupper;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * Steps to test the JSON API of East Website, with the real Twig engine and the real Symfony Serializer
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait ApiTrait
{
    private ?Type $apiType = null;

    private Content|Post|null $apiContent = null;

    /**
     * @var array<string, Tag>
     */
    private array $apiTags = [];

    private function sendApiRequest(
        string $method,
        string $url,
        array $parameters = [],
        array $files = [],
        array $server = [],
        ?string $content = null,
    ): void {
        $this->runSymfony(
            SfRequest::create(
                uri: $url,
                method: strtoupper($method),
                parameters: $parameters,
                files: $files,
                server: $server,
                content: $content,
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonResponse(): array
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);
        Assert::assertIsArray($decoded, "The response is not a JSON document : {$body}");

        return $decoded;
    }

    /**
     * @param array<int|string, mixed> $expected
     * @param array<int|string, mixed> $actual
     */
    private function assertJsonContains(array $expected, array $actual, string $path = ''): void
    {
        foreach ($expected as $key => $value) {
            Assert::assertArrayHasKey($key, $actual, "Missing key `{$path}.{$key}` in the response");

            if (is_array($value)) {
                Assert::assertIsArray($actual[$key], "`{$path}.{$key}` must be an array in the response");
                $this->assertJsonContains($value, $actual[$key], "{$path}.{$key}");

                continue;
            }

            Assert::assertSame($value, $actual[$key], "Bad value for `{$path}.{$key}` in the response");
        }
    }

    public function registerMedia(BaseMedia $media, bool $created = false): void
    {
        if (empty($media->getId())) {
            $media->setId(bin2hex(random_bytes(12)));
        }

        if ($created) {
            $this->createdObjects['Media'][] = $media;
        }

        //Criteria used by the MediaLoader
        $this->getObjectRepository(Media::class)->setObject(
            [
                'or' => [
                    ['id' => $media->getId()],
                    ['metadata.legacyId' => $media->getId()],
                ],
            ],
            $media,
        );
    }

    #[Given('a type :id named :name with the blocks :blocks')]
    public function aTypeNamedWithTheBlocks(string $id, string $name, string $blocks): void
    {
        $blocksList = [];
        foreach (explode(',', $blocks) as $blockName) {
            $blocksList[] = new Block(trim($blockName), BlockType::Text);
        }

        $this->apiType = new Type()
            ->setName($name)
            ->setTemplate('Acme:MyBundle:' . $name . '.html.twig')
            ->setBlocks($blocksList);
        $this->apiType->setId($id);

        $this->getObjectRepository(Type::class)->setObject(['id' => $id], $this->apiType);
    }

    private function buildContent(
        string $kind,
        string $id,
        string $slug,
        string $title,
    ): Content|Post {
        $content = match ($kind) {
            'post' => new Post(),
            default => new Content(),
        };

        $content->setId($id);
        $content->setSlug($slug)
            ->setTitle($title)
            ->setType($this->apiType);

        return $this->apiContent = $content;
    }

    #[Given('a published :kind :id with the slug :slug, the title :title, the parts :parts and the sanitized parts :sanitizedParts')]
    public function aPublishedContentWithTheParts(
        string $kind,
        string $id,
        string $slug,
        string $title,
        string $parts,
        string $sanitizedParts,
    ): void {
        $content = $this->buildContent($kind, $id, $slug, $title)
            ->setParts(json_decode($parts, true))
            ->setSanitizedParts(json_decode($sanitizedParts, true), 'fooBar');
        $content->setPublishedAt(new DateTime('2017-11-25'));

        //Criteria used by the queries `PublishedContentFromSlugQuery` and `PublishedPostFromSlugQuery`
        $this->getObjectRepository($content::class)->setObject(
            [
                'slug' => $slug,
                'publishedAt' => [
                    'lte' => $this->getCurrentDate(),
                ],
            ],
            $content,
        );
    }

    #[Given('it is in the environment :environment')]
    public function itIsInTheEnvironment(string $environment): void
    {
        $this->apiContent->setEnvironment(WebsiteEnvironment::get($environment));
    }

    #[Given('a draft :kind :id with the slug :slug and the title :title')]
    public function aDraftContent(string $kind, string $id, string $slug, string $title): void
    {
        $content = $this->buildContent($kind, $id, $slug, $title);

        $this->getObjectRepository($content::class)->setObject(['id' => $id], $content);
    }

    #[Given('it is written by :firstName :lastName with the email :email')]
    public function itIsWrittenBy(string $firstName, string $lastName, string $email): void
    {
        $author = new User()
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email);
        $author->setId('author-id');

        $this->apiContent->setAuthor($author);
    }

    #[Given('a tag :id named :name with the slug :slug')]
    public function aTagNamedWithTheSlug(string $id, string $name, string $slug): void
    {
        $tag = new Tag()->setName($name)->setSlug($slug);
        $tag->setId($id);

        $this->apiTags[$slug] = $tag;

        //Criteria used by the query `TagFromSlugQuery`
        $this->getObjectRepository(Tag::class)->setObject(['slug' => $slug], $tag);
    }

    #[Given('it is tagged with :slug')]
    public function itIsTaggedWith(string $slug): void
    {
        $this->apiContent->setTags([$this->apiTags[$slug]]);
    }

    #[Given('it has the comments:')]
    public function itHasTheComments(TableNode $table): void
    {
        $comments = [];
        foreach ($table->getHash() as $row) {
            $comment = new Comment(
                post: $this->apiContent,
                author: $row['author'],
                remoteIp: '127.0.0.1',
                title: $row['title'],
                content: $row['content'],
                postAt: new DateTime('2017-11-26'),
            );
            $comment->setId($row['id']);

            if (!empty($row['moderatedContent'])) {
                $comment->moderate(
                    new DateTime('2017-11-27'),
                    $row['moderatedAuthor'],
                    $row['moderatedTitle'],
                    $row['moderatedContent'],
                );
            }

            if ('yes' === ($row['deleted'] ?? 'no')) {
                $comment->setDeletedAt(new DateTime('2017-11-28'));
            }

            $comments[] = $comment;
            $this->getObjectRepository(Comment::class)->setObject(['id' => $row['id']], $comment);
        }

        $this->apiContent->setComments($comments);
    }

    #[Given('a media :id named :name')]
    public function aMediaNamed(string $id, string $name): void
    {
        $media = new Media()
            ->setName($name)
            ->setLength(123)
            ->setMetadata(new MediaMetadata('image/png', $name . '.png', 'Alt ' . $name, '/tmp/' . $name, ''));
        $media->setId($id);

        $this->registerMedia($media);
    }

    #[When('the API client sends a :method request to :url')]
    public function theApiClientSendsARequestTo(string $method, string $url): void
    {
        $this->sendApiRequest($method, $url);
    }

    #[When('the API client sends a :method JSON request to :url with:')]
    public function theApiClientSendsAJsonRequestToWith(string $method, string $url, PyStringNode $body): void
    {
        $this->sendApiRequest(
            method: $method,
            url: $url,
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body->getRaw(),
        );
    }

    #[When('the API client sends a :method form request to :url with :body')]
    public function theApiClientSendsAFormRequestToWith(string $method, string $url, string $body): void
    {
        $parameters = [];
        parse_str($body, $parameters);

        $this->sendApiRequest(
            method: $method,
            url: $url,
            parameters: $parameters,
            server: [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
        );
    }

    #[When('the API client uploads the file :fileName in the field :field of the form :form to :url with :body')]
    public function theApiClientUploadsTheFileTo(
        string $fileName,
        string $field,
        string $form,
        string $url,
        string $body,
    ): void {
        $parameters = [];
        parse_str($body, $parameters);

        //A PNG image of 1x1 pixel
        $path = tempnam(sys_get_temp_dir(), 'east-website-behat');
        file_put_contents(
            $path,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            ),
        );

        $this->sendApiRequest(
            method: 'POST',
            url: $url,
            parameters: $parameters,
            files: [
                $form => [
                    $field => new UploadedFile($path, $fileName, 'image/png', null, true),
                ],
            ],
            server: [
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
    }

    #[Then('the API response status code is :code')]
    public function theApiResponseStatusCodeIs(int $code): void
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);
        Assert::assertEquals($code, $this->response->getStatusCode(), (string) $this->response->getBody());
    }

    #[Then('the API response is a JSON response')]
    public function theApiResponseIsAJsonResponse(): void
    {
        Assert::assertInstanceOf(ResponseInterface::class, $this->response);
        Assert::assertTrue(
            str_starts_with($this->response->getHeaderLine('content-type'), 'application/json'),
            'The response content type is ' . $this->response->getHeaderLine('content-type'),
        );

        $this->getJsonResponse();
    }

    #[Then('the API response is:')]
    public function theApiResponseIs(PyStringNode $json): void
    {
        Assert::assertEquals(
            json_decode(json: $json->getRaw(), associative: true, flags: JSON_THROW_ON_ERROR),
            $this->getJsonResponse(),
        );
    }

    #[Then('the API response contains:')]
    public function theApiResponseContains(PyStringNode $json): void
    {
        $expected = json_decode(json: $json->getRaw(), associative: true, flags: JSON_THROW_ON_ERROR);
        Assert::assertIsArray($expected, 'The expected value is not a JSON object');

        $this->assertJsonContains($expected, $this->getJsonResponse());
    }

    #[Then('the API response does not contain the key :key in :path')]
    public function theApiResponseDoesNotContainTheKeyIn(string $key, string $path): void
    {
        $data = $this->getJsonResponse();
        foreach (explode('.', $path) as $part) {
            Assert::assertArrayHasKey($part, $data);
            $data = $data[$part];
        }

        Assert::assertIsArray($data);
        Assert::assertArrayNotHasKey($key, $data);
    }

    #[Then('the API response has :count elements in :path')]
    public function theApiResponseHasElementsIn(int $count, string $path): void
    {
        $data = $this->getJsonResponse();
        foreach (explode('.', $path) as $part) {
            Assert::assertArrayHasKey($part, $data);
            $data = $data[$part];
        }

        Assert::assertIsList($data);
        Assert::assertCount($count, $data);
    }

    #[Then('the API response is the page :page of :totalPages with :count elements')]
    public function theApiResponseIsThePageOfWithElements(int $page, int $totalPages, int $count): void
    {
        $response = $this->getJsonResponse();

        Assert::assertEquals(
            [
                'totalPages' => $totalPages,
                'page' => $page,
                'count' => $count,
            ],
            $response['meta'] ?? null,
        );
        Assert::assertIsList($response['data']);
        Assert::assertCount($count, $response['data']);
    }

    #[Then('the API response contains errors on the fields :fields')]
    public function theApiResponseContainsErrorsOnTheFields(string $fields): void
    {
        $response = $this->getJsonResponse();

        Assert::assertTrue($response['meta']['errors'] ?? false);
        Assert::assertIsArray($response['data']);

        $expected = explode(',', $fields);
        $actual = array_keys($response['data']);
        sort($expected);
        sort($actual);
        Assert::assertEquals($expected, $actual);
    }

    #[Then('the API response is the error :code')]
    public function theApiResponseIsTheError(int $code): void
    {
        $this->theApiResponseStatusCodeIs($code);
        $this->theApiResponseIsAJsonResponse();

        $response = $this->getJsonResponse();
        Assert::assertTrue($response['meta']['error'] ?? false);
        Assert::assertEquals($code, $response['data']['code'] ?? null);
    }

    #[Then('the post :id must be published')]
    public function thePostMustBePublished(string $id): void
    {
        Assert::assertNotEmpty($this->updatedObjects[$id]);
        Assert::assertNotNull($this->updatedObjects[$id]->getPublishedAt());
    }

    #[Then('the created :class must be published')]
    public function theCreatedMustBePublished(string $class): void
    {
        Assert::assertNotEmpty($this->createdObjects[$class] ?? []);

        foreach ($this->createdObjects[$class] as $object) {
            Assert::assertNotNull($object->getPublishedAt());
        }
    }

    #[Then('there are :count objects :class persisted')]
    public function thereAreObjectsPersisted(int $count, string $class): void
    {
        Assert::assertCount($count, $this->createdObjects[$class] ?? []);
    }
}
