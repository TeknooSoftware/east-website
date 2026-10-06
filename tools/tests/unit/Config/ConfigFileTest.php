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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Website\Tools\Config\ConfigFile;
use Teknoo\East\Website\Tools\Config\Connection;
use Teknoo\East\Website\Tools\Config\Credentials;
use Teknoo\East\Website\Tools\Http\Endpoints;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

use function array_diff;
use function array_values;
use function chmod;
use function decoct;
use function file_get_contents;
use function fileperms;
use function glob;
use function is_dir;
use function is_file;
use function is_readable;
use function is_writable;
use function json_decode;
use function mkdir;
use function scandir;
use function str_ends_with;
use function sys_get_temp_dir;
use function unlink;

use const PHP_OS_FAMILY;

/**
 * Tests of the configuration file written by the login: location, private file, atomic write, tolerant read, and PHP
 * warnings converted to exceptions
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigFile::class)]
class ConfigFileTest extends TestCase
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

    private function connection(string $path = ''): Connection
    {
        return new Connection(
            'https://site.test',
            new Endpoints('/cms/api', '/cms/api/admin', '/cms/login'),
            new Credentials('key:me@site.test', 'api-key-value', 'jwt-token', 1_800_003_600),
            $path,
            true,
            insecure: true,
            allowHttp: true,
            anonymous: true,
            timeout: 12,
            usernameField: 'login',
            tokenField: 'secret',
        );
    }

    private function minimal(): Connection
    {
        return new Connection('https://site.test', new Endpoints(), new Credentials('key:me@site.test'));
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function resolvedPaths(): iterable
    {
        yield 'default file' => [null, '/work/dir/east-website.json'];
        yield 'empty option is the default file' => ['', '/work/dir/east-website.json'];
        yield 'relative file' => ['site.json', '/work/dir/site.json'];
        yield 'relative file in a sub directory' => ['config/site.json', '/work/dir/config/site.json'];
        yield 'absolute file' => ['/etc/east/site.json', '/etc/east/site.json'];
        yield 'windows absolute file' => ['C:\\east\\site.json', 'C:\\east\\site.json'];
        yield 'windows absolute file with slashes' => ['c:/east/site.json', 'c:/east/site.json'];
        yield 'windows root file' => ['\\east\\site.json', '\\east\\site.json'];
    }

    #[DataProvider('resolvedPaths')]
    public function testResolve(?string $option, string $expected): void
    {
        self::assertSame($expected, ConfigFile::resolve('/work/dir', $option)->path());
    }

    public function testResolveIgnoresTheTrailingSeparatorOfTheWorkingDirectory(): void
    {
        self::assertSame('/work/dir/east-website.json', ConfigFile::resolve('/work/dir/', null)->path());
        self::assertSame('C:\\work/east-website.json', ConfigFile::resolve('C:\\work\\', null)->path());
    }

    public function testDefaultName(): void
    {
        self::assertSame('east-website.json', ConfigFile::DEFAULT_NAME);
    }

    public function testPath(): void
    {
        self::assertSame('/tmp/s.json', (new ConfigFile('/tmp/s.json'))->path());
    }

    public function testWriteThenReadKeepsEveryField(): void
    {
        $path = $this->temp->path('east-website.json');
        $file = new ConfigFile($path);

        $file->write($this->connection('/somewhere/else.json'));
        $read = $file->read();

        self::assertNotNull($read);
        self::assertSame('https://site.test', $read->baseUrl);
        self::assertSame('/cms/api', $read->endpoints->apiPrefix());
        self::assertSame('/cms/api/admin', $read->endpoints->adminPrefix());
        self::assertSame('/cms/login', $read->endpoints->login());
        self::assertSame('key:me@site.test', $read->credentials->username);
        self::assertSame('api-key-value', $read->credentials->apiKey());
        self::assertSame('jwt-token', $read->credentials->token);
        self::assertSame(1_800_003_600, $read->credentials->expiresAt);
        self::assertTrue($read->insecure);
        self::assertTrue($read->allowHttp);
        self::assertSame(12, $read->timeout);
        self::assertSame('login', $read->usernameField);
        self::assertSame('secret', $read->tokenField);
        self::assertSame($path, $read->configFile, 'The path of the read file, not the one of the written connection');
        self::assertTrue($read->configured);
        self::assertFalse($read->anonymous, 'The anonymous mode is an option of a command, never stored');
    }

    public function testWriteThenReadOfAConnectionWithoutSecrets(): void
    {
        $file = new ConfigFile($this->temp->path('east-website.json'));

        $file->write($this->minimal());
        $read = $file->read();

        self::assertNotNull($read);
        self::assertSame('key:me@site.test', $read->credentials->username);
        self::assertNull($read->credentials->apiKey());
        self::assertNull($read->credentials->token);
        self::assertNull($read->credentials->expiresAt);
        self::assertFalse($read->insecure);
        self::assertFalse($read->allowHttp);
        self::assertSame(Connection::DEFAULT_TIMEOUT, $read->timeout);
        self::assertSame(Endpoints::DEFAULT_API_PREFIX, $read->endpoints->apiPrefix());
        self::assertSame(Endpoints::DEFAULT_ADMIN_PREFIX, $read->endpoints->adminPrefix());
        self::assertSame(Endpoints::DEFAULT_LOGIN_PATH, $read->endpoints->login());
        self::assertSame('username', $read->usernameField);
        self::assertSame('token', $read->tokenField);
    }

    public function testStoredContentIsVersionedAndReadableJson(): void
    {
        $path = $this->temp->path('east-website.json');
        (new ConfigFile($path))->write($this->connection());

        $content = (string) file_get_contents($path);

        self::assertSame(
            [
                'version' => 1,
                'url' => 'https://site.test',
                'username' => 'key:me@site.test',
                'apiKey' => 'api-key-value',
                'token' => 'jwt-token',
                'expiresAt' => 1_800_003_600,
                'insecure' => true,
                'allowHttp' => true,
                'timeout' => 12,
                'apiPrefix' => '/cms/api',
                'adminPrefix' => '/cms/api/admin',
                'loginPath' => '/cms/login',
                'usernameField' => 'login',
                'tokenField' => 'secret',
            ],
            json_decode($content, true),
        );
        self::assertStringContainsString("\n    \"url\": \"https://site.test\",\n", $content, 'Pretty printed');
        self::assertTrue(str_ends_with($content, "}\n"));
    }

    public function testWriteCreatesTheDirectoriesPrivately(): void
    {
        $file = new ConfigFile($this->temp->path('config/east/east-website.json'));

        $file->write($this->connection());

        self::assertTrue(is_file($this->temp->path('config/east/east-website.json')));
        if ('Windows' !== PHP_OS_FAMILY) {
            self::assertSame('700', decoct(fileperms($this->temp->path('config/east')) & 0777));
        }
    }

    public function testFileIsPrivate(): void
    {
        if ('Windows' === PHP_OS_FAMILY) {
            self::markTestSkipped('The permissions of the files are not supported on Windows');
        }

        $path = $this->temp->path('east-website.json');
        (new ConfigFile($path))->write($this->connection());

        self::assertSame('600', decoct(fileperms($path) & 0777));
    }

    public function testWriteReplacesTheExistingFileAndLeavesNoTemporaryFile(): void
    {
        $file = new ConfigFile($this->temp->path('east-website.json'));
        $file->write($this->connection());
        $file->write($this->minimal());

        self::assertNull($file->read()?->credentials->token);
        self::assertSame(
            ['east-website.json'],
            array_values(array_diff((array) scandir($this->temp->path()), ['.', '..'])),
        );
    }

    public function testReadOfAMissingFileIsNotConfigured(): void
    {
        self::assertNull((new ConfigFile($this->temp->path('missing.json')))->read());
    }

    public function testReadOfADirectoryIsNotConfigured(): void
    {
        self::assertNull((new ConfigFile($this->temp->path()))->read());
    }

    public function testReadOfAnUnreadableFileIsNotConfigured(): void
    {
        $path = $this->temp->write('east-website.json', '{"version":1,"url":"https://site.test"}');
        chmod($path, 0000);
        if (is_readable($path)) {
            chmod($path, 0600);
            self::markTestSkipped('The file stays readable (running as root or without POSIX permissions)');
        }

        self::assertNull((new ConfigFile($path))->read());
        chmod($path, 0600);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidContents(): iterable
    {
        yield 'empty' => [''];
        yield 'not json' => ['<html>'];
        yield 'scalar json' => ['12'];
        yield 'wrong version' => ['{"version":2,"url":"https://site.test"}'];
        yield 'version as a string' => ['{"version":"1","url":"https://site.test"}'];
        yield 'no version' => ['{"url":"https://site.test"}'];
        yield 'missing url' => ['{"version":1,"username":"key:me@site.test","token":"t"}'];
        yield 'empty url' => ['{"version":1,"url":""}'];
        yield 'url is not a string' => ['{"version":1,"url":["https://site.test"]}'];
    }

    #[DataProvider('invalidContents')]
    public function testReadOfAnInvalidFileIsNotConfigured(string $content): void
    {
        $path = $this->temp->write('east-website.json', $content);

        self::assertNull((new ConfigFile($path))->read());
    }

    public function testReadOfAMinimalFileUsesTheDefaults(): void
    {
        $path = $this->temp->write('east-website.json', '{"version":1,"url":"https://site.test"}');

        $read = (new ConfigFile($path))->read();

        self::assertNotNull($read);
        self::assertSame('https://site.test', $read->baseUrl);
        self::assertTrue($read->configured);
        self::assertSame($path, $read->configFile);
        self::assertNull($read->credentials->username);
        self::assertNull($read->credentials->apiKey());
        self::assertNull($read->credentials->token);
        self::assertNull($read->credentials->expiresAt);
        self::assertFalse($read->insecure);
        self::assertFalse($read->allowHttp);
        self::assertSame(Connection::DEFAULT_TIMEOUT, $read->timeout);
        self::assertSame(Endpoints::DEFAULT_API_PREFIX, $read->endpoints->apiPrefix());
        self::assertSame(Endpoints::DEFAULT_ADMIN_PREFIX, $read->endpoints->adminPrefix());
        self::assertSame(Endpoints::DEFAULT_LOGIN_PATH, $read->endpoints->login());
        self::assertSame('username', $read->usernameField);
        self::assertSame('token', $read->tokenField);
    }

    public function testReadIgnoresTheValuesOfAWrongType(): void
    {
        $path = $this->temp->write(
            'east-website.json',
            '{"version":1,"url":"https://site.test","username":12,"apiKey":"","token":["t"],"expiresAt":"tomorrow",'
            . '"insecure":"yes","allowHttp":1,"timeout":"12","apiPrefix":null,"adminPrefix":"","loginPath":false,'
            . '"usernameField":{},"tokenField":""}',
        );

        $read = (new ConfigFile($path))->read();

        self::assertNotNull($read);
        self::assertNull($read->credentials->username);
        self::assertNull($read->credentials->apiKey());
        self::assertNull($read->credentials->token);
        self::assertNull($read->credentials->expiresAt);
        self::assertFalse($read->insecure, 'Only the boolean true disables the verification of the certificate');
        self::assertFalse($read->allowHttp);
        self::assertSame(Connection::DEFAULT_TIMEOUT, $read->timeout);
        self::assertSame(Endpoints::DEFAULT_API_PREFIX, $read->endpoints->apiPrefix());
        self::assertSame(Endpoints::DEFAULT_ADMIN_PREFIX, $read->endpoints->adminPrefix());
        self::assertSame(Endpoints::DEFAULT_LOGIN_PATH, $read->endpoints->login());
        self::assertSame('username', $read->usernameField);
        self::assertSame('token', $read->tokenField);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidTimeouts(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-5];
    }

    #[DataProvider('invalidTimeouts')]
    public function testAnInvalidTimeoutIsTheDefault(int $timeout): void
    {
        $path = $this->temp->write('east-website.json', '{"version":1,"url":"https://site.test","timeout":' . $timeout . '}');

        self::assertSame(Connection::DEFAULT_TIMEOUT, (new ConfigFile($path))->read()?->timeout);
    }

    public function testWriteBelowARegularFileFailsWithARuntimeException(): void
    {
        $blocker = $this->temp->write('blocker', 'I am a file');
        $file = new ConfigFile($blocker . '/config/east-website.json');

        try {
            $file->write($this->connection());
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('The configuration file', $error->getMessage());
            self::assertStringContainsString('can not be written', $error->getMessage());
            self::assertStringContainsString('east-website.json', $error->getMessage());
        }

        self::assertSame('I am a file', file_get_contents($blocker));
    }

    public function testWriteOverADirectoryFailsAndRemovesTheTemporaryFile(): void
    {
        $target = $this->temp->path('east-website.json');
        mkdir($target);

        try {
            (new ConfigFile($target))->write($this->connection());
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('can not be written', $error->getMessage());
        }

        self::assertTrue(is_dir($target));
        self::assertSame(
            ['east-website.json'],
            array_values(array_diff((array) scandir($this->temp->path()), ['.', '..'])),
        );
    }

    public function testWriteIntoAnUnwritableDirectoryFailsWithARuntimeException(): void
    {
        $directory = $this->temp->path('locked');
        mkdir($directory, 0700);
        chmod($directory, 0500);
        if (is_writable($directory)) {
            chmod($directory, 0700);
            self::markTestSkipped('The directory stays writable (running as root or without POSIX permissions)');
        }

        // tempnam() falls back to the system temporary directory when the directory is not writable
        $before = glob(sys_get_temp_dir() . '/east-website*') ?: [];

        try {
            (new ConfigFile($directory . '/east-website.json'))->write($this->connection());
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('can not be written', $error->getMessage());
            self::assertStringContainsString('is not writable', $error->getMessage());
        } finally {
            chmod($directory, 0700);
            foreach (array_diff(glob(sys_get_temp_dir() . '/east-website*') ?: [], $before) as $leak) {
                if (is_file($leak)) {
                    unlink($leak);
                }
            }
        }
    }

    public function testDelete(): void
    {
        $path = $this->temp->write('east-website.json', '{}');
        $file = new ConfigFile($path);

        self::assertTrue($file->delete());
        self::assertFalse(is_file($path));
        self::assertFalse($file->delete());
    }

    public function testDeleteOfADirectoryIsRefused(): void
    {
        self::assertFalse((new ConfigFile($this->temp->path()))->delete());
        self::assertTrue(is_dir($this->temp->path()));
    }
}
