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

namespace Teknoo\Tests\East\Website\Tools\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\ConnectionFactory;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function array_map;

/**
 * Tests of the connection built from the configuration file written by the login: the only global option is the
 * file to use, and there is no other source of configuration
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConnectionFactory::class)]
class ConnectionFactoryTest extends TestCase
{
    private TempDir $temp;

    protected function setUp(): void
    {
        $this->temp = new TempDir();
    }

    protected function tearDown(): void
    {
        $this->temp->remove();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function input(array $parameters = [], bool $withAnonymous = false): InputInterface
    {
        $definition = new InputDefinition(ConnectionFactory::options());
        if ($withAnonymous) {
            $definition->addOption(new InputOption('anonymous', null, InputOption::VALUE_NONE));
        }

        return new ArrayInput($parameters, $definition);
    }

    private function factory(): ConnectionFactory
    {
        return new ConnectionFactory($this->temp->path());
    }

    private function writeConfig(string $file = ConfigFile::DEFAULT_NAME, string $url = 'https://site.test'): string
    {
        return $this->temp->write(
            $file,
            '{"version":1,"url":"' . $url . '","username":"key:me@site.test","apiKey":"secret","token":"jwt",'
            . '"expiresAt":1800003600,"insecure":true,"timeout":12,"adminPrefix":"/cms/admin"}',
        );
    }

    public function testTheOnlyGlobalOptionIsTheConfigurationFile(): void
    {
        $options = ConnectionFactory::options();

        self::assertSame(['config'], array_map(static fn (InputOption $option): string => $option->getName(), $options));
        self::assertTrue($options[0]->isValueRequired());
        self::assertStringContainsString('website:auth:login', $options[0]->getDescription());
        self::assertStringContainsString(ConfigFile::DEFAULT_NAME, $options[0]->getDescription());
    }

    public function testTheDefaultFileIsInTheWorkingDirectory(): void
    {
        self::assertSame(
            $this->temp->path(ConfigFile::DEFAULT_NAME),
            $this->factory()->file($this->input())->path(),
        );
    }

    public function testARelativeFileIsRelativeToTheWorkingDirectory(): void
    {
        self::assertSame(
            $this->temp->path('sites/other.json'),
            $this->factory()->file($this->input(['--config' => 'sites/other.json']))->path(),
        );
    }

    public function testAnAbsoluteFileIsKept(): void
    {
        self::assertSame(
            '/etc/east/site.json',
            $this->factory()->file($this->input(['--config' => '/etc/east/site.json']))->path(),
        );
    }

    public function testTheConnectionIsReadFromTheDefaultFile(): void
    {
        $path = $this->writeConfig();

        $connection = $this->factory()->create($this->input());

        self::assertTrue($connection->configured);
        self::assertSame($path, $connection->configFile);
        self::assertSame('https://site.test', $connection->baseUrl);
        self::assertSame('key:me@site.test', $connection->credentials->username);
        self::assertSame('secret', $connection->credentials->apiKey());
        self::assertSame('jwt', $connection->credentials->token);
        self::assertSame(1_800_003_600, $connection->credentials->expiresAt);
        self::assertTrue($connection->insecure);
        self::assertSame(12, $connection->timeout);
        self::assertSame('/cms/admin', $connection->endpoints->adminPrefix());
        self::assertSame(Endpoints::DEFAULT_API_PREFIX, $connection->endpoints->apiPrefix());
        self::assertFalse($connection->anonymous);
    }

    public function testTheConnectionIsReadFromTheFileOfTheOption(): void
    {
        $this->writeConfig();
        $path = $this->writeConfig('other.json', 'https://other.test');

        $connection = $this->factory()->create($this->input(['--config' => 'other.json']));

        self::assertTrue($connection->configured);
        self::assertSame($path, $connection->configFile);
        self::assertSame('https://other.test', $connection->baseUrl);
    }

    public function testWithoutFileTheConnectionIsNotConfigured(): void
    {
        $connection = $this->factory()->create($this->input());

        self::assertFalse($connection->configured);
        self::assertSame($this->temp->path(ConfigFile::DEFAULT_NAME), $connection->configFile);
        self::assertSame('', $connection->baseUrl);
        self::assertNull($connection->credentials->username);
        self::assertNull($connection->credentials->apiKey());
        self::assertNull($connection->credentials->token);
        self::assertFalse($connection->insecure);
        self::assertFalse($connection->anonymous);
        self::assertSame(Connection::DEFAULT_TIMEOUT, $connection->timeout);
        self::assertSame(Endpoints::DEFAULT_ADMIN_PREFIX, $connection->endpoints->adminPrefix());
    }

    public function testAMissingFileOfTheOptionIsNotConfiguredEvenWithADefaultFile(): void
    {
        $this->writeConfig();

        $connection = $this->factory()->create($this->input(['--config' => 'missing.json']));

        self::assertFalse($connection->configured);
        self::assertSame($this->temp->path('missing.json'), $connection->configFile);
    }

    public function testAnInvalidFileIsNotConfigured(): void
    {
        $this->temp->write(ConfigFile::DEFAULT_NAME, '{"version":1}');

        self::assertFalse($this->factory()->create($this->input())->configured);
    }

    public function testAnonymousIsAppliedWhenTheCommandDefinesIt(): void
    {
        $this->writeConfig();

        $connection = $this->factory()->create($this->input(['--anonymous' => true], true));

        self::assertTrue($connection->anonymous);
        self::assertTrue($connection->configured);
        self::assertSame('jwt', $connection->credentials->token, 'The configuration is kept, only the JWT is not sent');
    }

    public function testAnonymousIsOffWhenTheCommandDefinesItWithoutTheFlag(): void
    {
        $this->writeConfig();

        self::assertFalse($this->factory()->create($this->input([], true))->anonymous);
    }

    public function testAnonymousOfANotConfiguredConnection(): void
    {
        $connection = $this->factory()->create($this->input(['--anonymous' => true], true));

        self::assertTrue($connection->anonymous);
        self::assertFalse($connection->configured);
    }

    public function testTheOptionsAreReadOnlyWhenTheInputDefinesThem(): void
    {
        $this->writeConfig();
        $input = new ArrayInput([], new InputDefinition());

        $connection = $this->factory()->create($input);

        self::assertTrue($connection->configured);
        self::assertFalse($connection->anonymous);
        self::assertSame($this->temp->path(ConfigFile::DEFAULT_NAME), $connection->configFile);
    }
}
