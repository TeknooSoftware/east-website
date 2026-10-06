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

namespace Teknoo\Tests\East\Website\Tools\Input;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Teknoo\East\Website\Tools\Http\ApiException;
use Teknoo\East\Website\Tools\Http\ErrorKind;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\Tests\East\Website\Tools\Support\TempDir;

/**
 * Tests of the body built from the typed options and from the raw JSON of a command
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(PayloadBuilder::class)]
class PayloadBuilderTest extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    private function build(string $resource, array $parameters = []): Payload
    {
        $definition = (new Registry())->resource($resource) ?? throw new LogicException('Unknown resource');
        $command = new Command('test');
        $builder = new PayloadBuilder();
        $builder->configure($command, $definition);

        return $builder->build($definition, new ArrayInput($parameters, $command->getDefinition()));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function assertUsageError(string $resource, array $parameters, string $message): void
    {
        try {
            $this->build($resource, $parameters);
            self::fail('An exception was expected');
        } catch (ApiException $error) {
            self::assertSame(ErrorKind::Usage, $error->kind);
            self::assertStringContainsString($message, $error->getMessage());
        }
    }

    public function testConfigureAddsTheOptionsOfTheFields(): void
    {
        $definition = (new Registry())->resource('content');
        self::assertNotNull($definition);
        $command = new Command('test');
        (new PayloadBuilder())->configure($command, $definition);

        foreach (['author', 'type', 'tag', 'title', 'subtitle', 'slug', 'description', 'locale-field'] as $option) {
            self::assertTrue($command->getDefinition()->hasOption($option), $option);
        }

        foreach (['data', 'data-file', 'part', 'part-file', 'publish'] as $option) {
            self::assertTrue($command->getDefinition()->hasOption($option), $option);
        }
    }

    public function testConfigureDoesNotAddThePartsOptionsToAResourceWithoutParts(): void
    {
        $definition = (new Registry())->resource('tag');
        self::assertNotNull($definition);
        $command = new Command('test');
        (new PayloadBuilder())->configure($command, $definition);

        self::assertTrue($command->getDefinition()->hasOption('is-highlighted'));
        self::assertTrue($command->getDefinition()->hasOption('data'));
        self::assertFalse($command->getDefinition()->hasOption('part'));
        self::assertFalse($command->getDefinition()->hasOption('part-file'));
        self::assertFalse($command->getDefinition()->hasOption('publish'));
    }

    public function testNothingIsSentWhenNoOptionIsProvided(): void
    {
        $payload = $this->build('tag');

        self::assertSame([], $payload->fields);
        self::assertSame([], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testOnlyTheProvidedOptionsAreSent(): void
    {
        $payload = $this->build('tag', ['--name' => 'News']);

        self::assertSame(['name' => 'News'], $payload->fields);
    }

    public function testAnEmptyStringIsSentWhenExplicitlyProvided(): void
    {
        self::assertSame(['slug' => ''], $this->build('tag', ['--slug' => ''])->fields);
    }

    public function testBooleanTriState(): void
    {
        self::assertSame(['isHighlighted' => true], $this->build('tag', ['--is-highlighted' => true])->fields);
        self::assertSame(['isHighlighted' => false], $this->build('tag', ['--no-is-highlighted' => true])->fields);
        self::assertSame([], $this->build('tag')->fields);
    }

    public function testIdsAndIntegers(): void
    {
        $payload = $this->build('item', [
            '--parent' => 'parent-1',
            '--content' => '',
            '--position' => '3',
            '--hidden' => true,
            '--name' => 'Home',
        ]);

        self::assertSame(
            ['name' => 'Home', 'parent' => 'parent-1', 'content' => null, 'hidden' => true, 'position' => 3],
            $payload->fields,
        );
    }

    public function testAnEmptyIdIsSentAsNull(): void
    {
        self::assertSame(['parent' => null], $this->build('item', ['--parent' => ''])->fields);
    }

    public function testAnInvalidIntegerIsAUsageError(): void
    {
        $this->assertUsageError('item', ['--position' => 'first'], '--position expects an integer');
    }

    public function testListOfIds(): void
    {
        self::assertSame(['tags' => ['a', 'b']], $this->build('content', ['--tag' => ['a', 'b']])->fields);
    }

    public function testAnEmptyValueClearsTheList(): void
    {
        self::assertSame(['tags' => []], $this->build('content', ['--tag' => ['']])->fields);
    }

    public function testEmptyValuesAreIgnoredInAListWithValues(): void
    {
        self::assertSame(['tags' => ['a']], $this->build('content', ['--tag' => ['a', '']])->fields);
    }

    public function testListIsNotSentWhenNotProvided(): void
    {
        self::assertArrayNotHasKey('tags', $this->build('content', ['--title' => 'T'])->fields);
    }

    public function testRolesAreValidated(): void
    {
        self::assertSame(
            ['roles' => ['ROLE_USER', 'ROLE_ADMIN']],
            $this->build('user', ['--role' => ['ROLE_USER', 'ROLE_ADMIN']])->fields,
        );
        self::assertSame(['roles' => []], $this->build('user', ['--role' => ['']])->fields);
        $this->assertUsageError('user', ['--role' => ['ROLE_ROOT']], 'expects one of ROLE_USER, ROLE_ADMIN');
    }

    public function testUserBooleanAndStrings(): void
    {
        $payload = $this->build('user', [
            '--first-name' => 'Ada',
            '--last-name' => 'Lovelace',
            '--email' => 'ada@site.test',
            '--no-active' => true,
        ]);

        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'email' => 'ada@site.test', 'active' => false],
            $payload->fields,
        );
    }

    public function testBlocksOfAType(): void
    {
        $payload = $this->build('type', [
            '--name' => 'Page',
            '--block' => ['a:textarea', 'b:raw', 'c:text', 'd:numeric', 'e:image'],
        ]);

        self::assertSame(
            [
                'name' => 'Page',
                'blocks' => [
                    ['name' => 'a', 'type' => '0'],
                    ['name' => 'b', 'type' => '1'],
                    ['name' => 'c', 'type' => '2'],
                    ['name' => 'd', 'type' => '3'],
                    ['name' => 'e', 'type' => '4'],
                ],
            ],
            $payload->fields,
        );
    }

    public function testTheKindOfABlockIsTheTextAfterTheLastColon(): void
    {
        self::assertSame(
            ['blocks' => [['name' => 'hero:title', 'type' => '2']]],
            $this->build('type', ['--block' => ['hero:title:text']])->fields,
        );
    }

    public function testAnEmptyBlockValueClearsTheBlocks(): void
    {
        self::assertSame(['blocks' => []], $this->build('type', ['--block' => ['']])->fields);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBlocks(): iterable
    {
        yield 'unknown kind' => ['a:video'];
        yield 'no kind' => ['a'];
        yield 'empty kind' => ['a:'];
        yield 'no name' => [':text'];
    }

    #[DataProvider('invalidBlocks')]
    public function testInvalidBlocksAreUsageErrors(string $block): void
    {
        $this->assertUsageError('type', ['--block' => [$block]], 'expects <name>:<kind> with kind in textarea|raw|text');
    }

    public function testPartsPublicationAndFields(): void
    {
        $payload = $this->build('content', [
            '--title' => 'T',
            '--part' => ['intro=Hello', 'equation=a=b'],
            '--publish' => true,
        ]);

        self::assertSame(['title' => 'T'], $payload->fields);
        self::assertSame(['intro' => 'Hello', 'equation' => 'a=b'], $payload->parts);
        self::assertTrue($payload->publish);
    }

    public function testAPartCanBeEmpty(): void
    {
        self::assertSame(['intro' => ''], $this->build('post', ['--part' => ['intro=']])->parts);
    }

    public function testPartsFromFiles(): void
    {
        $temp = new TempDir();
        $file = $temp->write('intro.html', '<p>Hello</p>');

        $payload = $this->build('content', ['--part-file' => ['intro=' . $file]]);
        $temp->remove();

        self::assertSame(['intro' => '<p>Hello</p>'], $payload->parts);
    }

    public function testAMissingPartFileIsAUsageError(): void
    {
        $this->assertUsageError('content', ['--part-file' => ['intro=/nonexistent/intro.html']], 'does not exist');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPairs(): iterable
    {
        yield 'no separator' => ['--part', 'intro'];
        yield 'empty name' => ['--part', '=value'];
        yield 'file without separator' => ['--part-file', 'intro'];
    }

    #[DataProvider('invalidPairs')]
    public function testInvalidPairsAreUsageErrors(string $option, string $value): void
    {
        $this->assertUsageError('content', [$option => [$value]], 'expects <name>=<value>');
    }

    public function testRawJsonIsSplitInFieldsPartsAndPublication(): void
    {
        $payload = $this->build('content', [
            '--data' => '{"title":"T","block_intro":"Hello","block_count":2,"publish":true}',
        ]);

        self::assertSame(['title' => 'T'], $payload->fields);
        self::assertSame(['intro' => 'Hello', 'count' => 2], $payload->parts);
        self::assertTrue($payload->publish);
    }

    public function testOnlyAnExactTruePublishesFromRawJson(): void
    {
        self::assertFalse($this->build('content', ['--data' => '{"publish":"yes"}'])->publish);
        self::assertFalse($this->build('content', ['--data' => '{"publish":false}'])->publish);
    }

    public function testTheFlagPublishesEvenWithRawJsonNotPublishing(): void
    {
        self::assertTrue($this->build('content', ['--data' => '{"publish":false}', '--publish' => true])->publish);
    }

    public function testTypedOptionsOverrideTheRawJson(): void
    {
        $payload = $this->build('content', [
            '--data' => '{"title":"from data","subtitle":"kept","block_intro":"from data"}',
            '--title' => 'from option',
            '--part' => ['intro=from option'],
        ]);

        self::assertSame(['title' => 'from option', 'subtitle' => 'kept'], $payload->fields);
        self::assertSame(['intro' => 'from option'], $payload->parts);
    }

    public function testAResourceWithoutPartsKeepsPublishAndBlocksAsPlainFields(): void
    {
        $payload = $this->build('tag', ['--data' => '{"name":"N","publish":true,"block_x":1}']);

        self::assertSame(['name' => 'N', 'publish' => true, 'block_x' => 1], $payload->fields);
        self::assertSame([], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testRawJsonFromAFile(): void
    {
        $temp = new TempDir();
        $file = $temp->write('payload.json', '{"name":"From file"}');

        $payload = $this->build('tag', ['--data-file' => $file]);
        $temp->remove();

        self::assertSame(['name' => 'From file'], $payload->fields);
    }

    public function testInvalidRawJsonIsAUsageError(): void
    {
        $this->assertUsageError('tag', ['--data' => '[1,2]'], 'The payload must be a valid JSON object');
    }

    public function testCommentModeration(): void
    {
        $payload = $this->build('comment', ['--moderated-author' => 'A', '--moderated-content' => 'C']);

        self::assertSame(['moderatedAuthor' => 'A', 'moderatedContent' => 'C'], $payload->fields);
    }
}
