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

namespace Teknoo\Tests\East\Website\Tools\Support;

use LogicException;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Teknoo\East\Website\Tools\Application;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Http\Json;

use function array_keys;
use function array_shift;
use function array_slice;
use function base64_encode;
use function count;
use function explode;
use function file_get_contents;
use function is_array;
use function is_file;
use function json_encode;
use function parse_url;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;
use function strtolower;
use function strtr;
use function trim;

use const PHP_URL_PATH;
use const PHP_URL_QUERY;

/**
 * Runs the whole application against a MockHttpClient: the responses are queued by route, each request is recorded. No network is used.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ApiHarness
{
    public const string URL = 'https://site.test';

    /**
     * @var list<array{method: string, url: string, path: string, query: string, headers: array<string, string>, body: mixed}>
     */
    public array $requests = [];

    /**
     * @var array<string, list<callable(): ResponseInterface>>
     */
    private array $routes = [];

    private readonly TempDir $temp;

    private readonly ApplicationTester $tester;

    private readonly Application $application;

    /**
     * The application runs in a temporary working directory, with a configuration file written like the login does:
     * its content is $config, completed by the version and the base URL of the harness (null: no configuration file at
     * all, like before any login).
     *
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = [], public readonly FixedClock $clock = new FixedClock())
    {
        $this->temp = new TempDir();
        $http = new MockHttpClient($this->handle(...), self::URL);

        if (null !== $config) {
            $this->writeConfig($config);
        }

        $application = Application::create($http, $this->clock, $this->temp->path());
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $this->application = $application;
        $this->tester = new ApplicationTester($application);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function writeConfig(array $config, string $file = ConfigFile::DEFAULT_NAME): string
    {
        return $this->temp->write($file, Json::encode($config + ['version' => 1, 'url' => self::URL]));
    }

    /**
     * Content of the configuration file, null when it does not exist.
     *
     * @return array<mixed>|null
     */
    public function config(string $file = ConfigFile::DEFAULT_NAME): ?array
    {
        $path = $this->configPath($file);

        return is_file($path) ? Json::decode((string) file_get_contents($path)) : null;
    }

    public function configPath(string $file = ConfigFile::DEFAULT_NAME): string
    {
        return $this->temp->path($file);
    }

    public function temp(): TempDir
    {
        return $this->temp;
    }

    public function application(): Application
    {
        return $this->application;
    }

    public function __destruct()
    {
        $this->temp->remove();
    }

    /**
     * Queues a JSON response for the next request matching "<METHOD> <path>", the last one is kept for the next ones.
     *
     * @param array<mixed> $body
     * @param array<string, string> $headers
     */
    public function respond(string $route, int $status, array $body = [], array $headers = []): self
    {
        $response = [] === $body ? '' : Json::encode($body);
        $responseHeaders = [];
        foreach ($headers as $name => $value) {
            $responseHeaders[] = $name . ': ' . $value;
        }

        $this->routes[$route][] = static fn (): ResponseInterface => new MockResponse(
            $response,
            ['http_code' => $status, 'response_headers' => $responseHeaders],
        );

        return $this;
    }

    public function respondRaw(string $route, int $status, string $body): self
    {
        $this->routes[$route][] = static fn (): ResponseInterface => new MockResponse($body, ['http_code' => $status]);

        return $this;
    }

    public static function jwt(int $expiresAt): string
    {
        $encode = static fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        return $encode('{"alg":"none"}') . '.' . $encode((string) json_encode(['exp' => $expiresAt])) . '.signature';
    }

    /**
     * @param list<string> $arguments the command, then its arguments and options (--name=value, --flag)
     * @param array<string, mixed> $options options of the ApplicationTester
     * @return array{int, string, string} exit code, stdout, stderr
     */
    public function run(array $arguments, array $options = []): array
    {
        $code = $this->tester->run(['command' => $arguments[0]] + $this->parse($arguments), $options + [
            'capture_stderr_separately' => true,
            'interactive' => false,
        ]);

        return [$code, $this->tester->getDisplay(), $this->tester->getErrorOutput()];
    }

    /**
     * The positional arguments are bound by name, from the definition of the command.
     *
     * @param list<string> $arguments
     * @return array<string, mixed>
     */
    private function parse(array $arguments): array
    {
        $names = [];
        $definition = null;
        if ($this->application->has($arguments[0])) {
            $definition = $this->application->find($arguments[0])->getDefinition();
            $names = array_keys($definition->getArguments());
        }

        $parsed = [];
        $position = 0;
        foreach (array_slice($arguments, 1) as $argument) {
            if (str_starts_with($argument, '--') && str_contains($argument, '=')) {
                [$name, $value] = explode('=', $argument, 2);
                $isArray = null !== $definition && $definition->hasOption(substr($name, 2))
                    && $definition->getOption(substr($name, 2))->isArray();
                $existing = $parsed[$name] ?? [];
                $parsed[$name] = $isArray ? [...(is_array($existing) ? $existing : []), $value] : $value;
            } elseif (str_starts_with($argument, '-')) {
                $parsed[$argument] = true;
            } else {
                $parsed[$names[$position] ?? 'argument' . $position] = $argument;
                ++$position;
            }
        }

        return $parsed;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function handle(string $method, string $url, array $options): ResponseInterface
    {
        $headers = [];
        foreach ($options['headers'] ?? [] as $header) {
            [$name, $value] = explode(':', (string) $header, 2);
            $headers[strtolower($name)] = trim($value);
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'path' => $path,
            'query' => (string) parse_url($url, PHP_URL_QUERY),
            'headers' => $headers,
            'body' => $options['body'] ?? null,
        ];

        $route = $method . ' ' . $path;
        if (!isset($this->routes[$route]) || [] === $this->routes[$route]) {
            throw new LogicException(sprintf('No response queued for "%s"', $route));
        }

        $factory = 1 === count($this->routes[$route]) ? $this->routes[$route][0] : array_shift($this->routes[$route]);

        return $factory();
    }
}
