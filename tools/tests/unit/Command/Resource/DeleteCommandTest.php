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
use ReflectionProperty;
use Teknoo\East\Website\Tools\Command\AbstractCommand;
use Teknoo\East\Website\Tools\Command\Resource\DeleteCommand;
use Teknoo\East\Website\Tools\Command\Resource\ResourceCommand;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Runtime;
use Teknoo\Tests\East\Website\Tools\Command\AbstractCommandTest;
use Teknoo\Tests\East\Website\Tools\Support\ApiHarness;

/**
 * Tests of the deletion of the objects: no prompt for a script or an agent, confirmation on an interactive terminal
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DeleteCommand::class)]
class DeleteCommandTest extends TestCase
{
    private const array DELETED = ['meta' => ['id' => 'id-1', 'deleted' => 'success'], 'data' => ['id' => 'id-1']];

    private function harness(): ApiHarness
    {
        return new ApiHarness(['EAST_WEBSITE_TOKEN' => 'jwt']);
    }

    /**
     * Replaces the delete command of a resource by a command simulating a terminal (or not).
     */
    private function withTerminal(ApiHarness $harness, string $resource, bool $terminal): void
    {
        $application = $harness->application();
        $existing = $application->find('website:' . $resource . ':delete');
        $runtime = (new ReflectionProperty(AbstractCommand::class, 'runtime'))->getValue($existing);
        $definition = (new ReflectionProperty(ResourceCommand::class, 'definition'))->getValue($existing);
        self::assertInstanceOf(Runtime::class, $runtime);
        self::assertInstanceOf(ResourceDefinition::class, $definition);

        $application->addCommand(new class ($runtime, $definition, $terminal) extends DeleteCommand {
            public function __construct(Runtime $runtime, ResourceDefinition $definition, private readonly bool $terminal)
            {
                parent::__construct($runtime, $definition);
            }

            protected function isTerminal(): bool
            {
                return $this->terminal;
            }
        });
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function resources(): iterable
    {
        yield 'tag' => [['website:tag:delete', 'id-1'], '/api/v1/admin/tag/id-1/delete'];
        yield 'type' => [['website:type:delete', 'id-1'], '/api/v1/admin/type/id-1/delete'];
        yield 'content' => [['website:content:delete', 'id-1'], '/api/v1/admin/content/id-1/delete'];
        yield 'post' => [['website:post:delete', 'id-1'], '/api/v1/admin/post/id-1/delete'];
        yield 'item' => [['website:item:delete', 'id-1'], '/api/v1/admin/item/id-1/delete'];
        yield 'user' => [['website:user:delete', 'id-1'], '/api/v1/admin/user/id-1/delete'];
        yield 'media' => [['website:media:delete', 'id-1'], '/api/v1/admin/media/id-1/delete'];
        yield 'comment' => [['website:comment:delete', 'post-9', 'id-1'], '/api/v1/admin/post/post-9/comment/id-1/delete'];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('resources')]
    public function testDeletionOfEachResourceNeedsNoConfirmationWhenNotInteractive(array $tokens, string $path): void
    {
        $harness = $this->harness()->respond('DELETE ' . $path, 200, self::DELETED);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, $tokens);

        self::assertSame(0, $code, $stderr);
        self::assertSame(self::DELETED, AbstractCommandTest::decode($stdout));
        self::assertCount(1, $harness->requests);
        self::assertSame('DELETE', $harness->requests[0]['method']);
        self::assertSame($path, $harness->requests[0]['path']);
        self::assertSame('Bearer jwt', $harness->requests[0]['headers']['authorization']);
        self::assertNull($harness->requests[0]['body']);
        self::assertArrayNotHasKey('content-type', $harness->requests[0]['headers']);
    }

    public function testYesOptionsAreAccepted(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);

        [$long] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1', '--yes']);
        [$short] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1', '-y']);

        self::assertSame(0, $long);
        self::assertSame(0, $short);
        self::assertCount(2, $harness->requests);
    }

    public function testNotFound(): void
    {
        $harness = $this->harness()->respond(
            'DELETE /api/v1/admin/tag/missing/delete',
            404,
            ['meta' => ['error' => true], 'data' => ['code' => 404, 'message' => 'Not found']],
        );

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:delete', 'missing']);

        self::assertSame(4, $code);
        self::assertSame('', $stdout);
        self::assertSame('not_found', AbstractCommandTest::decode($stderr)['data']['kind']);
    }

    public function testMissingIdentifierIsAUsageError(): void
    {
        $harness = $this->harness();

        [$code] = AbstractCommandTest::execute($harness, ['website:tag:delete']);

        self::assertSame(2, $code);
        self::assertSame([], $harness->requests);
    }

    public function testDryRunSendsNothingAndAsksNothing(): void
    {
        $harness = $this->harness();
        $this->withTerminal($harness, 'tag', true);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute(
            $harness,
            ['website:tag:delete', 't-1', '--dry-run', '--compact'],
            [],
            true,
        );

        self::assertSame(0, $code);
        self::assertSame([], $harness->requests);
        self::assertSame('', $stderr);
        $request = AbstractCommandTest::decode($stdout)['requests'][0];
        self::assertSame('DELETE', $request['method']);
        self::assertSame('https://site.test/api/v1/admin/tag/t-1/delete', $request['url']);
        self::assertArrayNotHasKey('body', $request);
    }

    public function testTheConfirmationIsAskedOnAnInteractiveTerminalAndAcceptedWithYes(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);
        $this->withTerminal($harness, 'tag', true);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1'], ['yes'], true);

        self::assertSame(0, $code);
        self::assertStringContainsString('Delete the tag "t-1"? [y/N]', $stderr);
        self::assertSame(self::DELETED, AbstractCommandTest::decode($stdout));
        self::assertCount(1, $harness->requests);
    }

    public function testTheDeletionIsCancelledWhenTheConfirmationIsDeclined(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);
        $this->withTerminal($harness, 'tag', true);

        [$code, $stdout, $stderr] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1'], ['no'], true);

        self::assertSame(0, $code);
        self::assertStringContainsString('Delete the tag "t-1"? [y/N]', $stderr);
        self::assertSame(['meta' => ['error' => false, 'deleted' => false], 'data' => null], AbstractCommandTest::decode($stdout));
        self::assertSame([], $harness->requests);
    }

    public function testTheDefaultAnswerOfTheConfirmationIsNo(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);
        $this->withTerminal($harness, 'tag', true);

        [$code, $stdout] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1'], [''], true);

        self::assertSame(0, $code);
        self::assertFalse(AbstractCommandTest::decode($stdout)['meta']['deleted']);
        self::assertSame([], $harness->requests);
    }

    public function testYesSkipsTheConfirmationOnATerminal(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);
        $this->withTerminal($harness, 'tag', true);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1', '--yes'], [], true);

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        self::assertCount(1, $harness->requests);
    }

    public function testNoConfirmationWithoutATerminalEvenWhenTheInputIsInteractive(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);
        $this->withTerminal($harness, 'tag', false);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1'], [], true);

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        self::assertCount(1, $harness->requests);
    }

    public function testNoConfirmationOnATerminalWhenTheInputIsNotInteractive(): void
    {
        $harness = $this->harness()->respond('DELETE /api/v1/admin/tag/t-1/delete', 200, self::DELETED);
        $this->withTerminal($harness, 'tag', true);

        [$code, , $stderr] = AbstractCommandTest::execute($harness, ['website:tag:delete', 't-1'], [], false);

        self::assertSame(0, $code);
        self::assertSame('', $stderr);
        self::assertCount(1, $harness->requests);
    }
}
