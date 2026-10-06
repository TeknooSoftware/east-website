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

namespace Teknoo\Tests\East\Website\Tools\Command\Resource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Command\Resource\CreateCommand;
use Teknoo\East\Website\Tools\Command\Resource\WriteCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

use function file_put_contents;

/**
 * Tests of the creation of the objects: explicit fetch of the redirection, two steps for the blocks, raw payloads
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CreateCommand::class)]
#[CoversClass(WriteCommand::class)]
class CreateCommandTest extends TestCase
{
    private function harness(): ApiHarness
    {
        return new ApiHarness(['token' => 'jwt']);
    }

    /**
     * Queues the answers of the API to a creation: the redirection, then the created object.
     */
    private function created(ApiHarness $harness, string $resource, string $id = 'id-1'): ApiHarness
    {
        return $harness
            ->respond('POST /api/v1/admin/' . $resource . '/new', 302, [], ['Location' => '/api/v1/admin/' . $resource . '/' . $id])
            ->respond(
                'GET /api/v1/admin/' . $resource . '/' . $id,
                200,
                ['meta' => ['id' => $id, '@class' => 'X'], 'data' => ['@class' => 'X', 'id' => $id, 'name' => 'created']],
            );
    }

    public function testCreationPostsTheJsonBodyThenFetchesTheCreatedObject(): void
    {
        $harness = $this->created($this->harness(), 'tag', 'tag-1');

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:tag:create', '--name=Hello', '--slug=hello', '--is-highlighted', '--compact'],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame(
            ['meta' => ['id' => 'tag-1', '@class' => 'X'], 'data' => ['@class' => 'X', 'id' => 'tag-1', 'name' => 'created']],
            AbstractCommandTest::decode($stdout),
        );
        self::assertCount(2, $harness->requests);

        self::assertSame('POST', $harness->requests[0]['method']);
        self::assertSame('/api/v1/admin/tag/new', $harness->requests[0]['path']);
        self::assertSame('application/json', $harness->requests[0]['headers']['content-type']);
        self::assertSame('Bearer jwt', $harness->requests[0]['headers']['authorization']);
        self::assertSame('{"name":"Hello","slug":"hello","isHighlighted":true}', $harness->requests[0]['body']);

        self::assertSame('GET', $harness->requests[1]['method']);
        self::assertSame('/api/v1/admin/tag/tag-1', $harness->requests[1]['path']);
        self::assertSame('Bearer jwt', $harness->requests[1]['headers']['authorization']);
    }

    public function testAnEmptyCreationSendsAnEmptyJsonObject(): void
    {
        $harness = $this->created($this->harness(), 'tag');

        [$code] = AbstractCommandTest::execute($harness, ['website:tag:create']);

        self::assertSame(0, $code);
        self::assertSame('{}', $harness->requests[0]['body']);
        self::assertSame('application/json', $harness->requests[0]['headers']['content-type']);
    }

    public function testBooleansAreSentOnlyWhenProvided(): void
    {
        $harness = $this->created($this->harness(), 'tag');

        [$code] = AbstractCommandTest::execute($harness, ['website:tag:create', '--no-is-highlighted']);

        self::assertSame(0, $code);
        self::assertSame('{"isHighlighted":false}', $harness->requests[0]['body']);
    }

    public function testTheFetchFailureGivesASyntheticDocumentWithAWarningAndNoError(): void
    {
        $harness = $this->harness()
            ->respond('POST /api/v1/admin/tag/new', 302, [], ['Location' => '/api/v1/admin/tag/tag-9'])
            ->respond(
                'GET /api/v1/admin/tag/tag-9',
                500,
                ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Boom']],
            );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:create', '--name=x', '--compact']);

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        $document = AbstractCommandTest::decode($stdout);
        self::assertSame('tag-9', $document['data']['id']);
        self::assertSame('tag-9', $document['meta']['id']);
        self::assertFalse($document['meta']['error']);
        self::assertSame('/api/v1/admin/tag/tag-9', $document['meta']['location']);
        self::assertSame('Created, but the object can not be fetched: Boom', $document['meta']['warning']);
    }

    public function testTheLocaleIsSentToTheCreationAndToTheFetch(): void
    {
        $harness = $this->created($this->harness(), 'content', 'c-1');

        [$code] = AbstractCommandTest::execute($harness, ['website:content:create', '--title=T', '--locale=fr']);

        self::assertSame(0, $code);
        self::assertSame('locale=fr', $harness->requests[0]['query']);
        self::assertSame('locale=fr', $harness->requests[1]['query']);
        self::assertSame('{"title":"T"}', $harness->requests[0]['body']);
    }

    public function testTheBlocksAndThePublicationAreSentByASecondRequest(): void
    {
        $harness = $this->created($this->harness(), 'content', 'content-1')
            ->respond(
                'PUT /api/v1/admin/content/content-1',
                200,
                ['meta' => ['id' => 'content-1'], 'data' => ['id' => 'content-1', 'title' => 'final']],
            );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, [
            'website:content:create',
            '--title=T',
            '--type=type-1',
            '--tag=t1',
            '--tag=t2',
            '--part=intro=Hello',
            '--part=body=World',
            '--publish',
            '--compact',
        ]);

        self::assertSame(0, $code, $stderr);
        self::assertCount(3, $harness->requests);

        self::assertSame('POST', $harness->requests[0]['method']);
        self::assertSame('/api/v1/admin/content/new', $harness->requests[0]['path']);
        self::assertSame('{"type":"type-1","tags":["t1","t2"],"title":"T"}', $harness->requests[0]['body']);

        self::assertSame('GET', $harness->requests[1]['method']);
        self::assertSame('/api/v1/admin/content/content-1', $harness->requests[1]['path']);

        self::assertSame('PUT', $harness->requests[2]['method']);
        self::assertSame('/api/v1/admin/content/content-1', $harness->requests[2]['path']);
        self::assertSame('application/json', $harness->requests[2]['headers']['content-type']);
        self::assertSame('{"block_intro":"Hello","block_body":"World","publish":true}', $harness->requests[2]['body']);

        self::assertSame('final', AbstractCommandTest::decode($stdout)['data']['title']);
    }

    public function testAPostIsCreatedLikeAContent(): void
    {
        $harness = $this->created($this->harness(), 'post', 'post-1')
            ->respond('PUT /api/v1/admin/post/post-1', 200, ['meta' => ['id' => 'post-1'], 'data' => ['id' => 'post-1']]);

        [$code] = AbstractCommandTest::execute($harness, ['website:post:create', '--title=P', '--part=intro=Hi']);

        self::assertSame(0, $code);
        self::assertSame('{"block_intro":"Hi"}', $harness->requests[2]['body']);
    }

    public function testPublishWithoutBlockIsSentInTheCreationRequest(): void
    {
        $harness = $this->created($this->harness(), 'content');

        [$code] = AbstractCommandTest::execute($harness, ['website:content:create', '--title=T', '--publish']);

        self::assertSame(0, $code);
        self::assertCount(2, $harness->requests);
        self::assertSame('{"title":"T","publish":true}', $harness->requests[0]['body']);
    }

    public function testBlocksFromTheRawPayloadAlsoNeedTwoSteps(): void
    {
        $harness = $this->created($this->harness(), 'content', 'c-1')
            ->respond('PUT /api/v1/admin/content/c-1', 200, ['meta' => ['id' => 'c-1'], 'data' => ['id' => 'c-1']]);

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:content:create', '--data={"title":"T","block_intro":"Hi","publish":true}'],
        );

        self::assertSame(0, $code);
        self::assertSame('{"title":"T"}', $harness->requests[0]['body']);
        self::assertSame('{"block_intro":"Hi","publish":true}', $harness->requests[2]['body']);
    }

    public function testPartsFromFilesAreReadFromTheDisk(): void
    {
        $harness = $this->created($this->harness(), 'content', 'c-1')
            ->respond('PUT /api/v1/admin/content/c-1', 200, ['meta' => ['id' => 'c-1'], 'data' => ['id' => 'c-1']]);
        $file = $harness->temp()->write('intro.html', '<p>Hello "world"</p>');

        [$code, , $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:content:create', '--part-file=intro=' . $file],
        );

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"block_intro":"<p>Hello \"world\"</p>"}', $harness->requests[2]['body'] ?? null);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidParts(): iterable
    {
        yield 'part without value' => ['--part=intro', 'The option --part expects <name>=<value>, "intro" given'];
        yield 'part without name' => ['--part==value', 'The option --part expects <name>=<value>, "=value" given'];
        yield 'missing file' => ['--part-file=intro=/not/here.html', 'The file "/not/here.html" does not exist or is not readable'];
    }

    #[DataProvider('invalidParts')]
    public function testInvalidPartsAreUsageErrorsWithoutAnyRequest(string $option, string $message): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:content:create', $option]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testAFailureOfTheSecondRequestReportsThePartialState(): void
    {
        $harness = $this->created($this->harness(), 'content', 'content-1')
            ->respond(
                'PUT /api/v1/admin/content/content-1',
                400,
                ['meta' => ['errors' => true], 'data' => ['.block_intro' => 'This form should not contain extra fields.']],
            );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:content:create', '--title=T', '--part=intro=Hello'],
        );

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        $error = AbstractCommandTest::decode($stderr)['data'];
        self::assertSame('validation', $error['kind']);
        self::assertSame(['.block_intro' => 'This form should not contain extra fields.'], $error['fields']);
        self::assertSame(['id' => 'content-1', 'failedStep' => 2, 'appliedStep' => 1], $error['partial']);
    }

    public function testAServerFailureOfTheSecondRequestKeepsItsExitCode(): void
    {
        $harness = $this->created($this->harness(), 'content', 'content-1')
            ->respond(
                'PUT /api/v1/admin/content/content-1',
                500,
                ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Boom']],
            );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:content:create', '--part=a=b']);

        self::assertSame(1, $code);
        self::assertSame('content-1', AbstractCommandTest::decode($stderr)['data']['partial']['id']);
    }

    public function testCreationWithoutKnownIdentifierCanNotSendTheBlocks(): void
    {
        $harness = $this->harness()
            ->respond('POST /api/v1/admin/content/new', 302, [], ['Location' => '/api/v1/admin/content/content-1'])
            ->respond('GET /api/v1/admin/content/content-1', 200, ['meta' => [], 'data' => ['title' => 'no id']]);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:content:create', '--part=a=b']);

        self::assertSame(1, $code);
        self::assertSame('', $stdout);
        self::assertCount(2, $harness->requests);
        self::assertStringContainsString(
            'the id of the object is unknown',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testDryRunOfASimpleCreationSendsNothing(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:tag:create', '--name=x', '--dry-run', '--compact']);

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            [
                'dryRun' => true,
                'requests' => [[
                    'method' => 'POST',
                    'url' => 'https://site.test/api/v1/admin/tag/new',
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ***',
                    ],
                    'body' => ['name' => 'x'],
                ]],
            ],
            AbstractCommandTest::decode($stdout),
        );
    }

    public function testDryRunOfAnEmptyCreationShowsAnEmptyObject(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:tag:create', '--dry-run', '--compact']);

        self::assertSame(0, $code);
        self::assertStringContainsString('"body":{}', $stdout);
    }

    public function testDryRunOfATwoStepsCreationShowsBothRequestsWithAPlaceholder(): void
    {
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:content:create', '--title=T', '--part=intro=Hi', '--publish', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        $requests = AbstractCommandTest::decode($stdout)['requests'];
        self::assertCount(2, $requests);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame(['title' => 'T'], $requests[0]['body']);
        self::assertSame('PUT', $requests[1]['method']);
        self::assertSame('https://site.test/api/v1/admin/content/ID_FROM_STEP_1', $requests[1]['url']);
        self::assertSame(['block_intro' => 'Hi', 'publish' => true], $requests[1]['body']);
        self::assertSame('Bearer ***', $requests[1]['headers']['Authorization']);
    }

    public function testTheRawPayloadIsTheBaseAndTypedOptionsOverrideIt(): void
    {
        $harness = $this->created($this->harness(), 'tag');

        [$code] = AbstractCommandTest::execute(
            $harness,
            ['website:tag:create', '--data={"name":"A","slug":"a","extra":1}', '--name=B'],
        );

        self::assertSame(0, $code);
        self::assertSame('{"name":"B","slug":"a","extra":1}', $harness->requests[0]['body']);
    }

    public function testTheRawPayloadCanComeFromAFile(): void
    {
        $harness = $this->created($this->harness(), 'tag');
        $file = $harness->temp()->write('tag.json', '{"name":"From file"}');

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:create', '--data-file=' . $file]);

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"name":"From file"}', $harness->requests[0]['body']);
    }

    public function testTheRawPayloadCanComeFromStdin(): void
    {
        $harness = $this->created($this->harness(), 'tag');

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:create', '--data=-'], ['{"name":"From stdin"}']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"name":"From stdin"}', $harness->requests[0]['body']);
    }

    public function testTheRawPayloadCanComeFromStdinWithTheDataFileOption(): void
    {
        $harness = $this->created($this->harness(), 'tag');

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:create', '--data-file=-'], ['{"slug":"s"}']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('{"slug":"s"}', $harness->requests[0]['body']);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'invalid json' => [['--data={"name"'], 'The payload must be a valid JSON object'];
        yield 'json list' => [['--data=["a","b"]'], 'The payload must be a valid JSON object'];
        yield 'json scalar' => [['--data="text"'], 'The payload must be a valid JSON object'];
        yield 'both sources' => [['--data={}', '--data-file=/tmp/x.json'], 'The options --data and --data-file are mutually exclusive'];
        yield 'missing file' => [['--data-file=/not/here.json'], 'The file "/not/here.json" does not exist or is not readable'];
    }

    /**
     * @param list<string> $options
     */
    #[DataProvider('invalidPayloads')]
    public function testInvalidRawPayloadsAreUsageErrorsWithoutAnyRequest(array $options, string $message): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:create', ...$options]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame($message, AbstractCommandTest::decode($stderr)['data']['message']);
    }

    public function testAnEmptyRawPayloadFileIsNotAnObject(): void
    {
        $harness = $this->harness();
        $file = $harness->temp()->path('empty.json');
        file_put_contents($file, '');

        [$code] = AbstractCommandTest::execute($harness, ['website:tag:create', '--data-file=' . $file]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    public function testTypeBlocksAreSentAsTheIndexOfTheChoicesOfTheForm(): void
    {
        $harness = $this->created($this->harness(), 'type');

        [$code] = AbstractCommandTest::execute($harness, [
            'website:type:create',
            '--name=Landing',
            '--template=landing.html.twig',
            '--block=a:textarea',
            '--block=b:raw',
            '--block=c:text',
            '--block=d:numeric',
            '--block=e:image',
        ]);

        self::assertSame(0, $code);
        self::assertSame(
            '{"name":"Landing","template":"landing.html.twig","blocks":['
            . '{"name":"a","type":"0"},{"name":"b","type":"1"},{"name":"c","type":"2"},'
            . '{"name":"d","type":"3"},{"name":"e","type":"4"}]}',
            $harness->requests[0]['body'],
        );
    }

    public function testABlockNameCanContainAColon(): void
    {
        $harness = $this->created($this->harness(), 'type');

        [$code] = AbstractCommandTest::execute($harness, ['website:type:create', '--block=a:b:text']);

        self::assertSame(0, $code);
        self::assertSame('{"blocks":[{"name":"a:b","type":"2"}]}', $harness->requests[0]['body']);
    }

    public function testAnEmptyBlockOptionClearsTheList(): void
    {
        $harness = $this->created($this->harness(), 'type');

        [$code] = AbstractCommandTest::execute($harness, ['website:type:create', '--block=']);

        self::assertSame(0, $code);
        self::assertSame('{"blocks":[]}', $harness->requests[0]['body']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBlocks(): iterable
    {
        yield 'unknown kind' => ['--block=a:bad'];
        yield 'no kind' => ['--block=nocolon'];
        yield 'no name' => ['--block=:text'];
    }

    #[DataProvider('invalidBlocks')]
    public function testInvalidBlocksAreUsageErrors(string $option): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:type:create', $option]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertStringContainsString(
            'kind in textarea|raw|text|numeric|image',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testItemFieldsAreTypedAndEmptyIdentifiersAreNull(): void
    {
        $harness = $this->created($this->harness(), 'item');

        [$code] = AbstractCommandTest::execute($harness, [
            'website:item:create',
            '--name=Home',
            '--location=header',
            '--parent=',
            '--content=c-1',
            '--position=3',
            '--no-hidden',
            '--locale-field=fr',
        ]);

        self::assertSame(0, $code);
        self::assertSame(
            '{"name":"Home","location":"header","parent":null,"content":"c-1","hidden":false,"position":3,"localeField":"fr"}',
            $harness->requests[0]['body'],
        );
    }

    public function testNonNumericPositionIsAUsageError(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:item:create', '--position=first']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The option --position expects an integer, "first" given',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testUserRolesAreValidatedAndRepeatable(): void
    {
        $harness = $this->created($this->harness(), 'user');

        [$code] = AbstractCommandTest::execute($harness, [
            'website:user:create',
            '--first-name=Jane',
            '--last-name=Doe',
            '--email=jane@site.test',
            '--role=ROLE_ADMIN',
            '--role=ROLE_USER',
            '--active',
        ]);

        self::assertSame(0, $code);
        self::assertSame(
            '{"firstName":"Jane","lastName":"Doe","email":"jane@site.test","roles":["ROLE_ADMIN","ROLE_USER"],"active":true}',
            $harness->requests[0]['body'],
        );
    }

    public function testAnUnknownRoleIsAUsageError(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:user:create', '--role=ROLE_ROOT']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The option --role expects one of ROLE_USER, ROLE_ADMIN, "ROLE_ROOT" given',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testAnEmptyListOptionSendsAnEmptyList(): void
    {
        $harness = $this->created($this->harness(), 'user');

        [$code] = AbstractCommandTest::execute($harness, ['website:user:create', '--role=']);

        self::assertSame(0, $code);
        self::assertSame('{"roles":[]}', $harness->requests[0]['body']);
    }

    public function testCreationOfAResourceWithoutCreationIsNotACommand(): void
    {
        $harness = $this->harness();

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:comment:create']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
        self::assertSame('usage', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testValidationErrorsOfTheCreation(): void
    {
        $harness = $this->harness()->respond(
            'POST /api/v1/admin/type/new',
            400,
            ['meta' => ['errors' => true], 'data' => ['.blocks.0.type' => 'This value is not valid.']],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:type:create', '--data={"blocks":[{"name":"a","type":"bad"}]}']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame(['.blocks.0.type' => 'This value is not valid.'], AbstractCommandTest::decode($stderr)['data']['fields']);
        self::assertCount(1, $harness->requests);
    }
}
