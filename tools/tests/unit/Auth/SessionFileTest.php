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

namespace Teknoo\Tests\East\Website\Tools\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Website\Tools\Auth\Session;
use Teknoo\East\Website\Tools\Auth\SessionFile;
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
use function sys_get_temp_dir;
use function unlink;

use const PHP_OS_FAMILY;

/**
 * Tests of the storage of the session: private file, atomic write, tolerant read, and PHP warnings converted to exceptions
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SessionFile::class)]
class SessionFileTest extends TestCase
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

    private function session(): Session
    {
        return new Session('https://site.test', 'key:me@site.test', 'jwt-token', 1_800_003_600);
    }

    /**
     * @return iterable<string, array{array<string, string>, string|null}>
     */
    public static function defaultPaths(): iterable
    {
        yield 'explicit file' => [
            ['EAST_WEBSITE_SESSION_FILE' => '/tmp/custom/session.json', 'XDG_STATE_HOME' => '/x', 'HOME' => '/h'],
            '/tmp/custom/session.json',
        ];
        yield 'xdg state home' => [
            ['XDG_STATE_HOME' => '/x/state', 'HOME' => '/h'],
            '/x/state/east-website-cli/session.json',
        ];
        yield 'xdg state home with trailing slash' => [
            ['XDG_STATE_HOME' => '/x/state/'],
            '/x/state/east-website-cli/session.json',
        ];
        yield 'home' => [
            ['HOME' => '/home/agent'],
            '/home/agent/.local/state/east-website-cli/session.json',
        ];
        yield 'home with trailing slash' => [
            ['HOME' => '/home/agent/'],
            '/home/agent/.local/state/east-website-cli/session.json',
        ];
        yield 'user profile' => [
            ['USERPROFILE' => '/users/agent'],
            '/users/agent/.local/state/east-website-cli/session.json',
        ];
        yield 'home wins over user profile' => [
            ['HOME' => '/home/agent', 'USERPROFILE' => '/users/agent'],
            '/home/agent/.local/state/east-website-cli/session.json',
        ];
        yield 'empty explicit file is ignored' => [
            ['EAST_WEBSITE_SESSION_FILE' => '', 'HOME' => '/home/agent'],
            '/home/agent/.local/state/east-website-cli/session.json',
        ];
        yield 'empty state home is ignored' => [
            ['XDG_STATE_HOME' => '', 'HOME' => '/home/agent'],
            '/home/agent/.local/state/east-website-cli/session.json',
        ];
        yield 'nothing' => [[], null];
        yield 'empty home' => [['HOME' => ''], null];
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('defaultPaths')]
    public function testDefaultPath(array $env, ?string $expected): void
    {
        if ('Windows' === PHP_OS_FAMILY) {
            self::markTestSkipped('The paths of the environment are not portable');
        }

        self::assertSame($expected, SessionFile::defaultPath($env));
    }

    public function testLocalAppDataIsUsedOnlyOnWindows(): void
    {
        $path = SessionFile::defaultPath(['LOCALAPPDATA' => 'C:\\Users\\agent\\AppData\\Local']);

        if ('Windows' === PHP_OS_FAMILY) {
            self::assertSame('C:\\Users\\agent\\AppData\\Local/east-website-cli/session.json', $path);

            return;
        }

        self::assertNull($path);
    }

    public function testPath(): void
    {
        self::assertSame('/tmp/s.json', (new SessionFile('/tmp/s.json'))->path());
    }

    public function testWriteThenRead(): void
    {
        $file = new SessionFile($this->temp->path('session.json'));

        $file->write($this->session());
        $read = $file->read();

        self::assertNotNull($read);
        self::assertSame('https://site.test', $read->baseUrl);
        self::assertSame('key:me@site.test', $read->username);
        self::assertSame('jwt-token', $read->token);
        self::assertSame(1_800_003_600, $read->expiresAt);
    }

    public function testWriteWithoutExpiration(): void
    {
        $file = new SessionFile($this->temp->path('session.json'));

        $file->write(new Session('https://site.test', 'key:me@site.test', 'jwt-token', null));

        self::assertNull($file->read()?->expiresAt);
    }

    public function testWriteCreatesTheDirectoriesPrivately(): void
    {
        $file = new SessionFile($this->temp->path('state/east-website-cli/session.json'));

        $file->write($this->session());

        self::assertTrue(is_file($this->temp->path('state/east-website-cli/session.json')));
        if ('Windows' !== PHP_OS_FAMILY) {
            self::assertSame('700', decoct(fileperms($this->temp->path('state/east-website-cli')) & 0777));
        }
    }

    public function testFileIsPrivate(): void
    {
        if ('Windows' === PHP_OS_FAMILY) {
            self::markTestSkipped('The permissions of the files are not supported on Windows');
        }

        $path = $this->temp->path('session.json');
        (new SessionFile($path))->write($this->session());

        self::assertSame('600', decoct(fileperms($path) & 0777));
    }

    public function testWriteReplacesTheExistingSessionAndLeavesNoTemporaryFile(): void
    {
        $file = new SessionFile($this->temp->path('session.json'));
        $file->write($this->session());
        $file->write(new Session('https://other.test', 'other:me@site.test', 'new-jwt', null));

        self::assertSame('new-jwt', $file->read()?->token);
        self::assertSame(['session.json'], array_values(array_diff((array) scandir($this->temp->path()), ['.', '..'])));
    }

    public function testStoredContentIsVersionedJson(): void
    {
        $path = $this->temp->path('session.json');
        (new SessionFile($path))->write($this->session());

        self::assertSame(
            [
                'version' => 1,
                'baseUrl' => 'https://site.test',
                'username' => 'key:me@site.test',
                'token' => 'jwt-token',
                'expiresAt' => 1_800_003_600,
            ],
            json_decode((string) file_get_contents($path), true),
        );
    }

    public function testReadOfAMissingFileIsNoSession(): void
    {
        self::assertNull((new SessionFile($this->temp->path('missing.json')))->read());
    }

    public function testReadOfADirectoryIsNoSession(): void
    {
        self::assertNull((new SessionFile($this->temp->path()))->read());
    }

    public function testReadOfAnUnreadableFileIsNoSession(): void
    {
        $path = $this->temp->write('session.json', '{}');
        chmod($path, 0000);
        if (is_readable($path)) {
            self::markTestSkipped('The file stays readable (running as root or without POSIX permissions)');
        }

        self::assertNull((new SessionFile($path))->read());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidContents(): iterable
    {
        yield 'empty' => [''];
        yield 'not json' => ['<html>'];
        yield 'scalar json' => ['12'];
        yield 'wrong version' => ['{"version":2,"baseUrl":"u","username":"n","token":"t","expiresAt":1}'];
        yield 'no version' => ['{"baseUrl":"u","username":"n","token":"t","expiresAt":1}'];
        yield 'missing base url' => ['{"version":1,"username":"n","token":"t","expiresAt":1}'];
        yield 'missing username' => ['{"version":1,"baseUrl":"u","token":"t","expiresAt":1}'];
        yield 'missing token' => ['{"version":1,"baseUrl":"u","username":"n","expiresAt":1}'];
        yield 'empty token' => ['{"version":1,"baseUrl":"u","username":"n","token":"","expiresAt":1}'];
        yield 'token is not a string' => ['{"version":1,"baseUrl":"u","username":"n","token":12,"expiresAt":1}'];
        yield 'base url is not a string' => ['{"version":1,"baseUrl":[],"username":"n","token":"t","expiresAt":1}'];
        yield 'username is not a string' => ['{"version":1,"baseUrl":"u","username":null,"token":"t","expiresAt":1}'];
    }

    #[DataProvider('invalidContents')]
    public function testReadOfAnInvalidFileIsNoSession(string $content): void
    {
        $path = $this->temp->write('session.json', $content);

        self::assertNull((new SessionFile($path))->read());
    }

    public function testReadIgnoresAnInvalidExpiration(): void
    {
        $path = $this->temp->write(
            'session.json',
            '{"version":1,"baseUrl":"u","username":"n","token":"t","expiresAt":"tomorrow"}',
        );

        $session = (new SessionFile($path))->read();

        self::assertNotNull($session);
        self::assertNull($session->expiresAt);
    }

    public function testWriteBelowARegularFileFailsWithARuntimeException(): void
    {
        $blocker = $this->temp->write('blocker', 'I am a file');
        $file = new SessionFile($blocker . '/state/session.json');

        try {
            $file->write($this->session());
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('can not be written', $error->getMessage());
            self::assertStringContainsString('session.json', $error->getMessage());
        }

        self::assertSame('I am a file', file_get_contents($blocker));
    }

    public function testWriteOverADirectoryFailsAndRemovesTheTemporaryFile(): void
    {
        $target = $this->temp->path('session.json');
        mkdir($target);

        try {
            (new SessionFile($target))->write($this->session());
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('can not be written', $error->getMessage());
        }

        self::assertTrue(is_dir($target));
        self::assertSame(['session.json'], array_values(array_diff((array) scandir($this->temp->path()), ['.', '..'])));
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
        $before = glob(sys_get_temp_dir() . '/session*') ?: [];

        try {
            (new SessionFile($directory . '/session.json'))->write($this->session());
            self::fail('An exception was expected');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('can not be written', $error->getMessage());
        } finally {
            chmod($directory, 0700);
            foreach (array_diff(glob(sys_get_temp_dir() . '/session*') ?: [], $before) as $leak) {
                unlink($leak);
            }
        }
    }

    public function testDelete(): void
    {
        $path = $this->temp->write('session.json', '{}');
        $file = new SessionFile($path);

        self::assertTrue($file->delete());
        self::assertFalse(is_file($path));
        self::assertFalse($file->delete());
    }

    public function testDeleteOfADirectoryIsRefused(): void
    {
        self::assertFalse((new SessionFile($this->temp->path()))->delete());
        self::assertTrue(is_dir($this->temp->path()));
    }
}
