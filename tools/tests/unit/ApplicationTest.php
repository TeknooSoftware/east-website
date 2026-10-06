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

namespace Teknoo\Tests\East\Website\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Teknoo\East\Website\Tools\Application;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Http\Json;
use Teknoo\East\Website\Tools\Version;
use Teknoo\Tests\East\Website\Tools\Support\FixedClock;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function array_map;
use function array_values;
use function getcwd;
use function is_array;
use function is_string;
use function putenv;
use function sort;
use function str_starts_with;

/**
 * Tests of the application: catalogue of the commands, global options, configuration only by the file of the login
 * (never by the environment) and contract of the usage errors
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Application::class)]
class ApplicationTest extends TestCase
{
    private const int COMMANDS = 48;

    /**
     * @var list<array{method: string, url: string, headers: list<string>}>
     */
    private array $requests = [];

    private ?TempDir $temp = null;

    protected function tearDown(): void
    {
        $this->temp?->remove();
        $this->temp = null;
    }

    /**
     * Application working in a temporary directory, with the configuration file of a login when $token is given.
     */
    private function application(?string $token = null, string $responseBody = '{"meta":{},"data":[]}'): Application
    {
        $this->temp = new TempDir();
        if (null !== $token) {
            $this->temp->write(
                ConfigFile::DEFAULT_NAME,
                Json::encode(['version' => 1, 'url' => 'https://site.test', 'token' => $token]),
            );
        }

        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($responseBody): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $options['headers'] ?? []];

            return new MockResponse($responseBody, ['http_code' => 200]);
        });

        $application = Application::create($http, new FixedClock(), $this->temp->path());
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return $application;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{int, string, string} exit code, stdout, stderr
     */
    private function execute(Application $application, array $input): array
    {
        $tester = new ApplicationTester($application);
        $code = $tester->run($input, ['capture_stderr_separately' => true, 'interactive' => false]);

        return [$code, $tester->getDisplay(), $tester->getErrorOutput()];
    }

    /**
     * @return list<string>
     */
    private static function expectedCommands(): array
    {
        $commands = [];
        foreach (['tag', 'type', 'content', 'post', 'item', 'user'] as $resource) {
            foreach (['list', 'get', 'create', 'update', 'delete'] as $operation) {
                $commands[] = 'website:' . $resource . ':' . $operation;
            }
        }

        foreach (['list', 'get', 'create', 'delete'] as $operation) {
            $commands[] = 'website:media:' . $operation;
        }

        foreach (['list', 'get', 'update', 'delete'] as $operation) {
            $commands[] = 'website:comment:' . $operation;
        }

        foreach (['comment:create', 'content:get', 'post:get', 'post:list', 'post:list-by-tag'] as $endpoint) {
            $commands[] = 'website:front:' . $endpoint;
        }

        foreach (['login', 'logout', 'renew', 'status'] as $operation) {
            $commands[] = 'website:auth:' . $operation;
        }

        $commands[] = 'website:schema';
        sort($commands);

        return $commands;
    }

    /**
     * @return list<string>
     */
    private function registeredCommands(Application $application): array
    {
        $names = [];
        foreach ($application->all() as $name => $command) {
            if (str_starts_with($name, 'website:')) {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }

    public function testCatalogueOfTheCommands(): void
    {
        self::assertSame(self::expectedCommands(), $this->registeredCommands($this->application()));
        self::assertCount(self::COMMANDS, self::expectedCommands());
    }

    public function testNameAndVersion(): void
    {
        $application = $this->application();

        self::assertSame(Version::NAME, $application->getName());
        self::assertSame(Version::VERSION, $application->getVersion());
    }

    public function testVersionOption(): void
    {
        [$code, $stdout] = $this->execute($this->application(), ['--version' => true]);

        self::assertSame(0, $code);
        self::assertStringContainsString(Version::NAME . ' ' . Version::VERSION, $stdout);
    }

    public function testCreateWithTheDefaultServices(): void
    {
        self::assertSame(self::expectedCommands(), $this->registeredCommands(Application::create()));
    }

    public function testTheDefaultConfigurationFileIsInTheCurrentDirectory(): void
    {
        $application = Application::create();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        [$code, $stdout] = $this->execute($application, ['command' => 'website:auth:status', '--compact' => true]);

        self::assertSame(0, $code);
        self::assertSame(
            getcwd() . '/' . ConfigFile::DEFAULT_NAME,
            Json::decode($stdout)['data']['configFile'] ?? null,
        );
    }

    public function testTheOnlyGlobalOptionIsTheConfigurationFile(): void
    {
        $definition = $this->application()->getDefinition();

        self::assertTrue($definition->hasOption('config'));
        foreach ([
            'url',
            'username',
            'api-key-file',
            'token-file',
            'session-file',
            'no-session',
            'anonymous',
            'insecure',
            'allow-http',
            'timeout',
        ] as $option) {
            self::assertFalse($definition->hasOption($option), $option);
        }
    }

    public function testTheConnectionIsConfiguredOnlyByTheLogin(): void
    {
        $application = $this->application();
        $login = $application->find('website:auth:login');
        $list = $application->find('website:tag:list');
        $login->mergeApplicationDefinition();
        $list->mergeApplicationDefinition();

        foreach (['url', 'username', 'api-key-file', 'insecure', 'allow-http', 'timeout', 'api-prefix', 'admin-prefix', 'login-path'] as $option) {
            self::assertTrue($login->getDefinition()->hasOption($option), $option);
            self::assertFalse($list->getDefinition()->hasOption($option), $option);
        }
    }

    public function testTheAnonymousOptionIsOnlyOnTheCommandsOfThePublicApi(): void
    {
        foreach ($this->application()->all() as $name => $command) {
            if (str_starts_with($name, 'website:')) {
                self::assertSame(
                    str_starts_with($name, 'website:front:'),
                    $command->getDefinition()->hasOption('anonymous'),
                    $name,
                );
            }
        }
    }

    public function testSecretsAreNeverAcceptedAsOptionValues(): void
    {
        $application = $this->application();
        $login = $application->find('website:auth:login');
        $login->mergeApplicationDefinition();

        foreach ([$application->getDefinition(), $login->getDefinition()] as $definition) {
            self::assertFalse($definition->hasOption('api-key'));
            self::assertFalse($definition->hasOption('token'));
            self::assertFalse($definition->hasOption('password'));
        }
    }

    public function testTheEnvironmentIsNeverRead(): void
    {
        $application = $this->application();
        putenv('EAST_WEBSITE_URL=https://env.test');
        putenv('EAST_WEBSITE_TOKEN=env-token');
        putenv('EAST_WEBSITE_USERNAME=key:env@env.test');
        putenv('EAST_WEBSITE_API_KEY=env-key');

        try {
            [$code, , $stderr] = $this->execute($application, ['command' => 'website:tag:list']);
            [$statusCode, $status] = $this->execute($application, ['command' => 'website:auth:status', '--compact' => true]);
        } finally {
            putenv('EAST_WEBSITE_URL');
            putenv('EAST_WEBSITE_TOKEN');
            putenv('EAST_WEBSITE_USERNAME');
            putenv('EAST_WEBSITE_API_KEY');
        }

        self::assertSame(3, $code);
        self::assertSame('auth', Json::decode($stderr)['data']['kind'] ?? null);
        self::assertSame([], $this->requests);
        self::assertSame(0, $statusCode);
        $data = Json::decode($status)['data'] ?? [];
        self::assertFalse($data['configured']);
        self::assertArrayHasKey('url', $data);
        self::assertNull($data['url']);
        self::assertNull($data['username']);
    }

    /**
     * The options of the fields of the resources must never collide with the global options or the options of
     * the Console component: the Console component throws when a definition is merged with a collision.
     */
    public function testNoOptionCollisionInAnyCommand(): void
    {
        $checked = 0;
        foreach ($this->application()->all() as $name => $command) {
            if (!str_starts_with($name, 'website:')) {
                continue;
            }

            $command->mergeApplicationDefinition();
            $definition = $command->getDefinition();

            self::assertTrue($definition->hasOption('config'), $name);
            self::assertTrue($definition->hasOption('format'), $name);
            self::assertTrue($definition->hasOption('compact'), $name);
            ++$checked;
        }

        self::assertSame(self::COMMANDS, $checked);
    }

    public function testTheFormatOfTheCommandsIsJsonByDefault(): void
    {
        $command = $this->application()->find('website:tag:list');

        self::assertSame('json', $command->getDefinition()->getOption('format')->getDefault());
    }

    public function testEveryCommandIsDescribed(): void
    {
        foreach ($this->application()->all() as $name => $command) {
            if (str_starts_with($name, 'website:')) {
                self::assertNotSame('', $command->getDescription(), $name);
            }
        }
    }

    public function testHelpInJsonDescribesTheOptionsOfACommand(): void
    {
        [$code, $stdout, $stderr] = $this->execute(
            $this->application(),
            ['command' => 'help', 'command_name' => 'website:tag:delete', '--format' => 'json'],
        );

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        $help = Json::decode($stdout);
        self::assertIsArray($help);
        self::assertSame('website:tag:delete', $help['name']);
        $options = $help['definition']['options'] ?? [];
        foreach (['yes', 'config', 'format', 'compact', 'dry-run'] as $option) {
            self::assertArrayHasKey($option, $options);
        }

        self::assertSame('-y', $options['yes']['shortcut']);
    }

    public function testListInJsonContainsTheCommandsOfTheCli(): void
    {
        [$code, $stdout] = $this->execute($this->application(), ['command' => 'list', '--format' => 'json']);

        self::assertSame(0, $code);
        $list = Json::decode($stdout);
        self::assertIsArray($list);
        $names = array_map(
            static fn (mixed $command): string => is_array($command) && is_string($command['name'] ?? null)
                ? $command['name']
                : '',
            array_values(is_array($list['commands'] ?? null) ? $list['commands'] : []),
        );

        foreach (self::expectedCommands() as $expected) {
            self::assertContains($expected, $names);
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function usageErrors(): iterable
    {
        yield 'unknown command' => [['command' => 'website:nothing:list'], 'website:nothing'];
        yield 'unknown option' => [['command' => 'website:tag:list', '--nothing' => true], '--nothing'];
        yield 'missing argument' => [['command' => 'website:tag:get'], 'Not enough arguments'];
        yield 'unknown argument' => [['command' => 'website:tag:get', 'id' => 'a', 'extra' => 'b'], 'extra'];
        yield 'invalid page' => [['command' => 'website:tag:list', '--page' => ''], '--page expects an integer'];
    }

    /**
     * @param array<string, mixed> $input
     */
    #[DataProvider('usageErrors')]
    public function testUsageErrorsFollowTheErrorContract(array $input, string $message): void
    {
        [$code, $stdout, $stderr] = $this->execute($this->application(), $input);

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        $error = Json::decode($stderr);
        self::assertIsArray($error);
        self::assertSame(['error' => true], $error['meta']);
        self::assertSame('usage', $error['data']['kind']);
        self::assertSame(0, $error['data']['code']);
        self::assertStringContainsString($message, $error['data']['message']);
    }

    public function testAnInvalidFormatIsAUsageErrorBeforeAnyRequest(): void
    {
        [$code, $stdout, $stderr] = $this->execute(
            $this->application('jwt'),
            ['command' => 'website:tag:list', '--format' => 'xml'],
        );

        self::assertSame(2, $code);
        self::assertSame('', $stdout);
        self::assertSame('usage', Json::decode($stderr)['data']['kind'] ?? null);
        self::assertSame([], $this->requests);
    }

    public function testACommandCanBeRunThroughTheApplication(): void
    {
        $application = $this->application(
            'jwt',
            '{"meta":{"totalPages":1,"page":1,"count":1},"data":[{"id":"tag-1","name":"News"}]}',
        );

        [$code, $stdout, $stderr] = $this->execute($application, ['command' => 'website:tag:list', '--compact' => true]);

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        self::assertSame('tag-1', Json::decode($stdout)['data'][0]['id'] ?? null);
        self::assertSame('GET', $this->requests[0]['method']);
        self::assertSame('https://site.test/api/v1/admin/tags', $this->requests[0]['url']);
        self::assertContains('Authorization: Bearer jwt', $this->requests[0]['headers']);
    }
}
