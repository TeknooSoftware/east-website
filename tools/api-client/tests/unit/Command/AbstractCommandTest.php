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

namespace Teknoo\Tests\East\Website\Tools\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Application;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;
use Teknoo\Tests\East\Website\Tools\Support\FixedClock;

use function array_keys;
use function array_shift;
use function array_slice;
use function chmod;
use function explode;
use function is_array;
use function is_writable;
use function json_decode;
use function ltrim;
use function str_contains;
use function str_starts_with;

/**
 * Tests of the contract shared by all the commands: JSON on stdout, JSON error on stderr, exit codes, formats and warnings
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AbstractCommand::class)]
class AbstractCommandTest extends TestCase
{
    private const array TAGS = [
        'meta' => ['totalPages' => 1, 'page' => 1, 'count' => 1],
        'data' => [['id' => 'tag-1', 'name' => 'Tag', 'slug' => 'tag', 'isHighlighted' => false]],
    ];

    /**
     * Runs a command through the tester of the harness, with stdin and interactivity, and gives a list to the
     * repeatable options even when they are given once (an ArrayInput gives them a scalar). The positional tokens are
     * mapped to the names of the arguments of the command. Shared by the tests of the commands.
     *
     * @param list<string> $tokens command, positional arguments, then --options (repeat an option to give a list)
     * @param list<string> $inputs lines available on stdin (and to the interactive questions)
     * @return array{int, string, string} exit code, stdout, stderr
     */
    public static function execute(
        ApiHarness $harness,
        array $tokens,
        array $inputs = [],
        bool $interactive = false,
    ): array {
        $tester = (new ReflectionProperty(ApiHarness::class, 'tester'))->getValue($harness);
        self::assertInstanceOf(ApplicationTester::class, $tester);
        $application = $harness->application();

        try {
            $definition = $application->find($tokens[0])->getNativeDefinition();
        } catch (CommandNotFoundException) {
            $definition = new InputDefinition(); // the application reports the unknown command
        }

        $names = array_keys($definition->getArguments());
        $input = ['command' => $tokens[0]];
        foreach (array_slice($tokens, 1) as $token) {
            if (str_starts_with($token, '-')) {
                [$name, $value] = str_contains($token, '=') ? explode('=', $token, 2) : [$token, true];
                $option = ltrim($name, '-');
                if ($definition->hasOption($option) && $definition->getOption($option)->isArray()) {
                    // An ArrayInput gives a scalar to an option, even repeatable: a list is required
                    $input[$name] = [...(is_array($input[$name] ?? null) ? $input[$name] : []), $value];
                } else {
                    $input[$name] = $value;
                }
            } else {
                $input[(string) array_shift($names)] = $token;
            }
        }

        $tester->setInputs($inputs);
        $code = $tester->run(
            $input,
            ['capture_stderr_separately' => true, 'interactive' => $interactive, 'decorated' => false],
        );

        return [$code, $tester->getDisplay(), $tester->getErrorOutput()];
    }

    /**
     * @return array<mixed> the decoded JSON body of a recorded request
     */
    public static function body(ApiHarness $harness, int $index): array
    {
        $body = $harness->requests[$index]['body'] ?? null;
        self::assertIsString($body, 'The request #' . $index . ' has no JSON body');

        return self::decode($body);
    }

    /**
     * @return array<mixed>
     */
    public static function decode(string $json): array
    {
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded, 'Not a JSON document: ' . $json);

        return $decoded;
    }

    private function harness(): ApiHarness
    {
        return new ApiHarness(['token' => 'jwt']);
    }

    /**
     * A configuration file without valid JWT, in a directory where it can be read but not written again: the new
     * JWT of the automatic login can not be stored.
     */
    private function lockedConfiguration(ApiHarness $harness): string
    {
        $harness->writeConfig(['username' => 'key:me@site.test', 'apiKey' => 'secret'], 'locked/site.json');
        $directory = $harness->temp()->path('locked');
        chmod($directory, 0500);
        if (is_writable($directory)) {
            chmod($directory, 0700);
            self::markTestSkipped('The directory stays writable (running as root or without POSIX permissions)');
        }

        return $directory;
    }

    public function testSuccessPrintsAPrettyJsonDocumentOnStdoutAndNothingOnStderr(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, self::TAGS);

        [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        self::assertSame(self::TAGS, self::decode($stdout));
        self::assertStringContainsString("\n    \"meta\"", $stdout);
    }

    public function testCompactPrintsTheDocumentOnASingleLine(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, self::TAGS);

        [$code, $stdout] = $this->execute($harness, ['website:tag:list', '--compact']);

        self::assertSame(0, $code);
        self::assertSame(1, \substr_count(rtrim($stdout, "\n"), "\n") + 1);
        self::assertSame(self::TAGS, self::decode($stdout));
    }

    public function testTheContentOfTheWebsiteIsNeverAlteredByTheConsoleFormatter(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tag/tag-1', 200, [
            'meta' => ['id' => 'tag-1'],
            'data' => ['name' => '<info>bold</info> <p>x</p> [y] \\<b>'],
        ]);

        [$code, $stdout] = $this->execute($harness, ['website:tag:get', 'tag-1']);

        self::assertSame(0, $code);
        self::assertSame('<info>bold</info> <p>x</p> [y] \\<b>', self::decode($stdout)['data']['name']);
    }

    public function testNotFoundIsAJsonErrorOnStderrWithExitCode4(): void
    {
        $harness = $this->harness()->respond(
            'GET /api/v1/admin/tag/missing',
            404,
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'message' => 'Tag not found']],
        );

        [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:get', 'missing']);

        self::assertSame(4, $code);
        self::assertSame('', $stdout);
        self::assertSame(
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'kind' => 'not_found', 'message' => 'Tag not found']],
            self::decode($stderr),
        );
    }

    public function testValidationErrorsWithFieldsAreExitCode2(): void
    {
        $harness = $this->harness()->respond(
            'POST /api/v1/admin/tag/new',
            400,
            ['meta' => ['errors' => true], 'data' => ['.name' => 'This value is not valid.']],
        );

        [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:create', '--name=x']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        $error = self::decode($stderr);
        self::assertSame('validation', $error['data']['kind']);
        self::assertSame(400, $error['data']['code']);
        self::assertSame(['.name' => 'This value is not valid.'], $error['data']['fields']);
    }

    public function testValidationErrorWithoutDetailExposesTheRejectedObject(): void
    {
        $harness = $this->harness()->respond(
            'POST /api/v1/admin/tag/new',
            400,
            ['meta' => ['id' => 'x'], 'data' => ['name' => 'echoed']],
        );

        [$code, , $stderr] = $this->execute($harness, ['website:tag:create', '--name=x']);

        self::assertSame(2, $code);
        $error = self::decode($stderr);
        self::assertStringContainsString('without detailing the errors', $error['data']['message']);
        self::assertSame(['name' => 'echoed'], $error['data']['rejected']);
        self::assertArrayNotHasKey('fields', $error['data']);
    }

    public function testUnauthorizedIsExitCode3(): void
    {
        $harness = $this->harness()->respond(
            'GET /api/v1/admin/tags',
            401,
            ['meta' => ['error' => true], 'data' => ['code' => 401, 'message' => 'Invalid JWT Token']],
        );

        [$code, , $stderr] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(3, $code);
        self::assertSame('auth', self::decode($stderr)['data']['kind']);
    }

    public function testMissingConfigurationFileIsExitCode3WithoutAnyRequest(): void
    {
        $harness = new ApiHarness(null);

        [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        $error = self::decode($stderr)['data'];
        self::assertStringContainsString($harness->configPath(), $error['message']);
        self::assertStringContainsString('website:auth:login', $error['message']);
        self::assertStringContainsString('<keyName>:<email>', $error['hint']);
    }

    public function testAConfigurationFileWithoutJwtNorApiKeyIsExitCode3WithoutAnyRequest(): void
    {
        $harness = new ApiHarness();

        [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(3, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertStringContainsString('no valid JWT', self::decode($stderr)['data']['message']);
    }

    public function testTheConfigurationFileCanBeChosen(): void
    {
        $harness = new ApiHarness(null);
        $harness->writeConfig(['token' => 'other-jwt'], 'site.json');
        $harness->respond('GET /api/v1/admin/tags', 200, self::TAGS);

        [$code, , $stderr] = $this->execute($harness, ['website:tag:list', '--config=site.json']);

        self::assertSame(0, $code, $stderr);
        self::assertSame('Bearer other-jwt', $harness->requests[0]['headers']['authorization']);
    }

    public function testServerErrorIsExitCode1(): void
    {
        $harness = $this->harness()->respond(
            'GET /api/v1/admin/tags',
            500,
            ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Internal Server Error']],
        );

        [$code, , $stderr] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(1, $code);
        self::assertSame('server', self::decode($stderr)['data']['kind']);
    }

    public function testANonJsonErrorBodyIsSummarizedInTheMessage(): void
    {
        $harness = $this->harness()->respondRaw('GET /api/v1/admin/tags', 502, '<html>Bad Gateway</html>');

        [$code, , $stderr] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(1, $code);
        self::assertSame(
            'Unexpected HTTP status 502: <html>Bad Gateway</html>',
            self::decode($stderr)['data']['message'],
        );
    }

    public function testAnUnreachableServerIsExitCode1(): void
    {
        $http = new MockHttpClient(static fn (): MockResponse => new MockResponse('', ['error' => 'Connection refused']));
        $harness = $this->harness();
        $application = Application::create($http, new FixedClock(), $harness->temp()->path());
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $tester = new ApplicationTester($application);

        $code = $tester->run(
            ['command' => 'website:tag:list'],
            ['capture_stderr_separately' => true, 'decorated' => false],
        );

        self::assertSame(1, $code);
        self::assertSame('', $tester->getDisplay());
        $error = self::decode($tester->getErrorOutput());
        self::assertSame('transport', $error['data']['kind']);
        self::assertStringContainsString('Unable to reach the server', $error['data']['message']);
    }

    public function testAnUnknownFormatIsAUsageErrorBeforeAnyRequest(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, self::TAGS);

        [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:list', '--format=xml']);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame([], $harness->requests);
        self::assertStringContainsString('The format "xml" is not supported', self::decode($stderr)['data']['message']);
    }

    public function testTheTableFormatRendersAListWithItsPagination(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, self::TAGS);

        [$code, $stdout] = $this->execute($harness, ['website:tag:list', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('| id    | name | slug | isHighlighted |', $stdout);
        self::assertStringContainsString('| tag-1 | Tag  | tag  | false         |', $stdout);
        self::assertStringContainsString('page 1/1, 1 item(s)', $stdout);
    }

    public function testTheTableFormatRendersAnObjectAsFieldsAndValues(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tag/tag-1', 200, [
            'meta' => ['id' => 'tag-1'],
            'data' => ['id' => 'tag-1', 'name' => 'Tag', 'isHighlighted' => true, 'parent' => null, 'blocks' => [['a' => 1]]],
        ]);

        [$code, $stdout] = $this->execute($harness, ['website:tag:get', 'tag-1', '--format=table']);

        self::assertSame(0, $code);
        self::assertStringContainsString('| field', $stdout);
        self::assertStringContainsString('| isHighlighted | true', $stdout);
        self::assertStringContainsString('| blocks        | [{"a":1}]', $stdout);
    }

    public function testTheTableFormatFallsBackToJsonWhenThereIsNothingToTabulate(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, [
            'meta' => ['totalPages' => 0, 'page' => 1, 'count' => 0],
            'data' => [],
        ]);

        [$code, $stdout] = $this->execute($harness, ['website:tag:list', '--format=table']);

        self::assertSame(0, $code);
        self::assertSame(0, self::decode($stdout)['meta']['count']);
    }

    public function testTheTableFormatFallsBackToJsonForAListOfScalars(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 200, ['meta' => [], 'data' => ['a', 'b']]);

        [$code, $stdout] = $this->execute($harness, ['website:tag:list', '--format=table']);

        self::assertSame(0, $code);
        self::assertSame(['a', 'b'], self::decode($stdout)['data']);
    }

    public function testAnEmptySuccessBodyIsWrappedInADocument(): void
    {
        $harness = $this->harness()->respondRaw('GET /api/v1/admin/tags', 200, 'plain text');

        [$code, $stdout] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(0, $code);
        self::assertSame(['meta' => ['error' => false], 'data' => 'plain text'], self::decode($stdout));
    }

    public function testAnEmptyBodyIsWrappedInADocumentWithoutData(): void
    {
        $harness = $this->harness()->respond('GET /api/v1/admin/tags', 204);

        [$code, $stdout] = $this->execute($harness, ['website:tag:list']);

        self::assertSame(0, $code);
        self::assertSame(['meta' => ['error' => false], 'data' => null], self::decode($stdout));
    }

    public function testWarningsAreWrittenOnStderrAndDoNotPolluteStdout(): void
    {
        $harness = new ApiHarness(null);
        $directory = $this->lockedConfiguration($harness);
        $harness
            ->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(1_800_003_600)]])
            ->respond('GET /api/v1/admin/tags', 200, self::TAGS);

        try {
            [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:list', '--config=locked/site.json']);
        } finally {
            chmod($directory, 0700);
        }

        self::assertSame(0, $code);
        self::assertSame(self::TAGS, self::decode($stdout));
        self::assertStringStartsWith('warning: The configuration file "' . $directory . '/site.json" can not be written', $stderr);
    }

    public function testWarningsAreIncludedInTheErrorDocumentWhenTheCommandFails(): void
    {
        $harness = new ApiHarness(null);
        $directory = $this->lockedConfiguration($harness);
        $harness
            ->respond('POST /api/v1/login', 200, ['meta' => ['error' => false], 'data' => ['token' => ApiHarness::jwt(1_800_003_600)]])
            ->respond('GET /api/v1/admin/tags', 500, ['meta' => ['error' => true], 'data' => ['code' => 500, 'message' => 'Boom']]);

        try {
            [$code, $stdout, $stderr] = $this->execute($harness, ['website:tag:list', '--config=locked/site.json']);
        } finally {
            chmod($directory, 0700);
        }

        self::assertSame(1, $code);
        self::assertSame('', $stdout);
        // stderr stays a single JSON document, the warnings are part of it
        $error = self::decode($stderr);
        self::assertSame('Boom', $error['data']['message']);
        self::assertCount(1, $error['data']['warnings']);
        self::assertStringStartsWith('The configuration file', $error['data']['warnings'][0]);
        self::assertStringNotContainsString('warning:', $stderr);
    }
}
