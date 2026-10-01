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

namespace Teknoo\Tests\East\Website\Tools\Command\Media;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Application;
use Teknoo\East\Website\Tools\Command\Media\UploadCommand;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;
use Teknoo\Tests\East\Website\Tools\Support\FixedClock;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function explode;
use function strtolower;
use function trim;

/**
 * Tests of the upload of a media: multipart request, fetch of the created media, validation of the file
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(UploadCommand::class)]
class UploadCommandTest extends TestCase
{
    private const string FILE_CONTENT = "PNG-CONTENT-\x00\x01\x02-END";

    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    private function file(): string
    {
        $this->temp = new TempDir();

        return $this->temp->write('logo.png', self::FILE_CONTENT);
    }

    private function harness(): ApiHarness
    {
        return new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']);
    }

    /**
     * An application whose client reads the whole multipart body of the requests, before the upload stream is closed.
     *
     * @param list<array{method: string, url: string, headers: array<string, string>, body: mixed}> $captured
     */
    private function capturingApplication(array &$captured): ApplicationTester
    {
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $body = $options['body'] ?? null;
            if ($body instanceof Closure) {
                $data = '';
                while ('' !== $chunk = $body()) {
                    $data .= $chunk;
                }

                $body = $data;
            }

            $headers = [];
            foreach ($options['headers'] ?? [] as $header) {
                [$name, $value] = explode(':', (string) $header, 2);
                $headers[strtolower($name)] = trim($value);
            }

            $captured[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

            if ('POST' === $method) {
                return new MockResponse('', [
                    'http_code' => 302,
                    'response_headers' => ['Location: /api/v1/admin/media/m-1'],
                ]);
            }

            return new MockResponse(
                '{"meta":{"id":"m-1"},"data":{"id":"m-1","name":"Logo"}}',
                ['http_code' => 200],
            );
        });

        $application = Application::create($http, [
            'EAST_WEBSITE_URL' => ApiHarness::URL,
            'EAST_WEBSITE_TOKEN' => 'jwt',
            'EAST_WEBSITE_SESSION_FILE' => '',
        ], new FixedClock());
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return new ApplicationTester($application);
    }

    public function testTheFileIsUploadedAsAMultipartRequestThenTheMediaIsFetched(): void
    {
        $file = $this->file();
        $captured = [];
        $tester = $this->capturingApplication($captured);

        $code = $tester->run(
            ['command' => 'website:media:create', '--file' => $file, '--name' => 'Logo', '--alternative' => 'Alt text', '--compact' => true],
            ['capture_stderr_separately' => true],
        );

        self::assertSame(0, $code, $tester->getErrorOutput());
        self::assertSame(
            ['meta' => ['id' => 'm-1'], 'data' => ['id' => 'm-1', 'name' => 'Logo']],
            AbstractCommandTest::decode($tester->getDisplay()),
        );
        self::assertCount(2, $captured);

        $upload = $captured[0];
        self::assertSame('POST', $upload['method']);
        self::assertSame('https://site.test/api/v1/admin/media/new', $upload['url']);
        self::assertSame('Bearer jwt', $upload['headers']['authorization']);
        self::assertMatchesRegularExpression('#^multipart/form-data; boundary=\S+$#', $upload['headers']['content-type']);
        self::assertIsString($upload['body']);
        self::assertStringContainsString("name=\"media[name]\"\r\n\r\nLogo", $upload['body']);
        self::assertStringContainsString("name=\"media[alternative]\"\r\n\r\nAlt text", $upload['body']);
        self::assertStringContainsString('name="media[image]"; filename="logo.png"', $upload['body']);
        self::assertStringContainsString(self::FILE_CONTENT, $upload['body']);

        self::assertSame('GET', $captured[1]['method']);
        self::assertSame('https://site.test/api/v1/admin/media/m-1', $captured[1]['url']);
        self::assertSame('Bearer jwt', $captured[1]['headers']['authorization']);
    }

    public function testTheNameDefaultsToTheNameOfTheFileAndTheAlternativeIsOptional(): void
    {
        $file = $this->file();
        $captured = [];
        $tester = $this->capturingApplication($captured);

        $code = $tester->run(['command' => 'website:media:create', '--file' => $file]);

        self::assertSame(0, $code);
        self::assertIsString($captured[0]['body']);
        self::assertStringContainsString("name=\"media[name]\"\r\n\r\nlogo.png", $captured[0]['body']);
        self::assertStringNotContainsString('media[alternative]', $captured[0]['body']);
    }

    public function testAnEmptyAlternativeIsSent(): void
    {
        $file = $this->file();
        $captured = [];
        $tester = $this->capturingApplication($captured);

        $code = $tester->run(['command' => 'website:media:create', '--file' => $file, '--alternative' => '']);

        self::assertSame(0, $code);
        self::assertIsString($captured[0]['body']);
        self::assertStringContainsString("name=\"media[alternative]\"\r\n\r\n\r\n", $captured[0]['body']);
    }

    public function testTheUploadIsRecordedAsAMultipartRequestByTheHarness(): void
    {
        $file = $this->file();
        $harness = $this->harness()
            ->respond('POST /api/v1/admin/media/new', 302, [], ['Location' => '/api/v1/admin/media/m-1'])
            ->respond('GET /api/v1/admin/media/m-1', 200, ['meta' => ['id' => 'm-1'], 'data' => ['id' => 'm-1']]);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=' . $file, '--compact']);

        self::assertSame(0, $code, $stderr);
        self::assertSame(['meta' => ['id' => 'm-1'], 'data' => ['id' => 'm-1']], AbstractCommandTest::decode($stdout));
        self::assertCount(2, $harness->requests);
        self::assertSame('POST', $harness->requests[0]['method']);
        self::assertSame('/api/v1/admin/media/new', $harness->requests[0]['path']);
        self::assertStringStartsWith('multipart/form-data; boundary=', $harness->requests[0]['headers']['content-type']);
        self::assertSame('GET', $harness->requests[1]['method']);
    }

    public function testTheFetchFailureGivesASyntheticDocument(): void
    {
        $file = $this->file();
        $harness = $this->harness()
            ->respond('POST /api/v1/admin/media/new', 302, [], ['Location' => '/api/v1/admin/media/m-1'])
            ->respond('GET /api/v1/admin/media/m-1', 500, ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Boom']]);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=' . $file]);

        self::assertSame(0, $code);
        $document = AbstractCommandTest::decode($stdout);
        self::assertSame('m-1', $document['data']['id']);
        self::assertStringContainsString('Created, but the object can not be fetched', $document['meta']['warning']);
    }

    public function testTheFileIsRequired(): void
    {
        $harness = $this->harness();

        [$missing, , $missingError] = AbstractCommandTest::execute($harness, ['website:media:create']);
        [$empty] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=']);

        self::assertSame(2, $missing);
        self::assertSame(2, $empty);
        self::assertSame('The option --file is required', AbstractCommandTest::decode($missingError)['data']['message']);
        self::assertSame([], $harness->requests);
    }

    public function testAnUnreadableFileIsAUsageErrorAndNothingIsSent(): void
    {
        $harness = $this->harness();

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=/not/here.png']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertSame(
            'The file "/not/here.png" does not exist or is not readable',
            AbstractCommandTest::decode($stderr)['data']['message'],
        );
    }

    public function testADirectoryIsNotAFile(): void
    {
        $this->temp = new TempDir();
        $harness = $this->harness();

        [$code] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=' . $this->temp->path()]);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    public function testDryRunDescribesTheMultipartRequestAndSendsNothing(): void
    {
        $file = $this->file();
        $harness = $this->harness();

        [$code, $stdout] = AbstractCommandTest::execute(
            $harness,
            ['website:media:create', '--file=' . $file, '--alternative=Alt', '--dry-run', '--compact'],
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        self::assertSame(
            [
                'dryRun' => true,
                'requests' => [[
                    'method' => 'POST',
                    'url' => 'https://site.test/api/v1/admin/media/new',
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'multipart/form-data',
                        'Authorization' => 'Bearer ***',
                    ],
                    'multipart' => [
                        'media[name]' => 'logo.png',
                        'media[alternative]' => 'Alt',
                        'media[image]' => '@' . $file,
                    ],
                ]],
            ],
            AbstractCommandTest::decode($stdout),
        );
    }

    public function testValidationErrorOfTheUpload(): void
    {
        $file = $this->file();
        $harness = $this->harness()->respond(
            'POST /api/v1/admin/media/new',
            400,
            ['meta' => ['errors' => true], 'data' => ['.image' => 'The file is too large.']],
        );

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=' . $file]);

        self::assertSame(2, $code);
        self::assertSame(['.image' => 'The file is too large.'], AbstractCommandTest::decode($stderr)['data']['fields']);
    }

    public function testUnauthorizedUpload(): void
    {
        $file = $this->file();
        $harness = $this->harness()->respond(
            'POST /api/v1/admin/media/new',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid JWT Token']],
        );

        [$code] = AbstractCommandTest::execute($harness, ['website:media:create', '--file=' . $file]);

        self::assertSame(3, $code);
    }
}
