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

namespace Teknoo\Tests\East\Website\Tools\Tui\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Input\PayloadBuilder;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Resource\Registry;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\Form\DocumentMapper;
use Teknoo\East\Website\Tools\Tui\Form\FormRow;
use Teknoo\East\Website\Tools\Tui\Form\FormState;
use Teknoo\East\Website\Tools\Tui\Form\RowKind;
use Teknoo\East\Website\Tools\Tui\Screen\FormMode;

use function array_keys;
use function array_map;

/**
 * Tests of the rows of the forms of the interactive mode built from the documents of the API, and from what was given
 * on the command line
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DocumentMapper::class)]
class DocumentMapperTest extends TestCase
{
    private const array BLOCKS = [
        ['name' => 'intro', 'kind' => 'textarea'],
        ['name' => 'body', 'kind' => 'raw'],
        ['name' => 'headline', 'kind' => 'text'],
        ['name' => 'count', 'kind' => 'numeric'],
        ['name' => 'photo', 'kind' => 'image'],
    ];

    private function mapper(): DocumentMapper
    {
        return new DocumentMapper(new Registry());
    }

    private function definition(string $name): ResourceDefinition
    {
        $definition = (new Registry())->resource($name);
        self::assertInstanceOf(ResourceDefinition::class, $definition, $name);

        return $definition;
    }

    /**
     * @param list<FormRow> $rows
     * @return array<string, FormRow>
     */
    private function named(array $rows): array
    {
        $named = [];
        foreach ($rows as $row) {
            self::assertArrayNotHasKey($row->name, $named, 'Several rows are named ' . $row->name);
            $named[$row->name] = $row;
        }

        return $named;
    }

    /**
     * @param list<FormRow> $rows
     * @return array<string, mixed>
     */
    private function values(array $rows): array
    {
        return array_map(static fn (FormRow $row): mixed => $row->value, $this->named($rows));
    }

    /**
     * @param list<FormRow> $rows
     * @return array<string, RowKind>
     */
    private function kinds(array $rows): array
    {
        return array_map(static fn (FormRow $row): RowKind => $row->kind, $this->named($rows));
    }

    private function row(FormState $state, string $name): FormRow
    {
        $row = $state->row($name);
        self::assertInstanceOf(FormRow::class, $row, $name);

        return $row;
    }

    private function payload(FormState $state): Payload
    {
        $payload = $state->payload(new PayloadBuilder());
        self::assertInstanceOf(Payload::class, $payload);

        return $payload;
    }

    /**
     * A content or a post, as the API returns it alone: the related objects are nested.
     *
     * @return array<string, mixed>
     */
    private static function content(string $class = 'Content'): array
    {
        return [
            '@class' => 'Teknoo\\East\\Website\\Object\\' . $class,
            'id' => 'c1',
            'title' => 'Hello',
            'subtitle' => 'World',
            'slug' => 'hello',
            'author' => ['@class' => 'Teknoo\\East\\Common\\Object\\User', 'id' => 'u1', 'name' => 'Richard Déloge'],
            'type' => [
                '@class' => 'Teknoo\\East\\Website\\Object\\Type',
                'id' => 'ty1',
                'name' => 'Page',
                'template' => 'page.html.twig',
                'blocks' => [
                    ['name' => 'intro', 'type' => 'textarea'],
                    ['name' => 'body', 'type' => 'raw'],
                    ['name' => 'headline', 'type' => 'text'],
                    ['name' => 'count', 'type' => 'numeric'],
                    ['name' => 'photo', 'type' => 'image'],
                ],
            ],
            'tags' => [
                ['@class' => 'Teknoo\\East\\Website\\Object\\Tag', 'id' => 't1', 'name' => 'php', 'slug' => 'php-slug'],
                ['@class' => 'Teknoo\\East\\Website\\Object\\Tag', 'id' => 't2', 'name' => 'east'],
            ],
            'description' => "First\nSecond",
            'parts' => [
                'intro' => "<p>Hi</p>\n<p>there</p>",
                'headline' => 'Read this',
                'count' => 3,
                'legacy' => 'Not in the type anymore',
            ],
            'environment' => 'validation',
            'localeField' => 'en',
            'publishedAt' => '2026-01-02T03:04:05+00:00',
            'updatedAt' => '2026-01-03T00:00:00+00:00',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function resourcesWithParts(): iterable
    {
        yield 'content' => ['content', 'Content'];
        yield 'post' => ['post', 'Post'];
    }

    #[DataProvider('resourcesWithParts')]
    public function testAContentIsViewedWithItsFieldsItsOtherKeysAndItsBlocks(string $resource, string $class): void
    {
        $rows = $this->mapper()->rows($this->definition($resource), self::content($class), FormMode::View);

        self::assertSame(
            [
                'id' => 'c1',
                'author' => 'Richard Déloge',
                'type' => 'Page',
                'tags' => 'php, east',
                'title' => 'Hello',
                'subtitle' => 'World',
                'slug' => 'hello',
                'description' => "First\nSecond",
                'environment' => 'validation',
                'localeField' => 'en',
                'publishedAt' => '2026-01-02T03:04:05+00:00',
                'updatedAt' => '2026-01-03T00:00:00+00:00',
                'block intro' => "<p>Hi</p>\n<p>there</p>",
                'block headline' => 'Read this',
                'block count' => '3',
                'block legacy' => 'Not in the type anymore',
            ],
            $this->values($rows),
        );

        foreach ($rows as $row) {
            self::assertSame(RowKind::ReadOnly, $row->kind, $row->name);
            self::assertFalse($row->isEditable(), $row->name);
            self::assertFalse($row->mustBeSent(), $row->name);
            self::assertSame($row->name, $row->label);
        }
    }

    public function testTheBlocksOfTheTypeAreNotNeededToViewAContent(): void
    {
        $definition = $this->definition('content');
        $mapper = $this->mapper();

        self::assertSame(
            $this->values($mapper->rows($definition, self::content(), FormMode::View)),
            $this->values($mapper->rows($definition, self::content(), FormMode::View, self::BLOCKS)),
        );
    }

    public function testOnlyTheFieldsOfTheDocumentAreViewed(): void
    {
        $rows = $this->mapper()->rows(
            $this->definition('content'),
            ['@class' => 'Teknoo\\East\\Website\\Object\\Content', 'slug' => 'hello', 'author' => null, 'parts' => null],
            FormMode::View,
        );

        self::assertSame(['author' => '', 'slug' => 'hello'], $this->values($rows));
    }

    public function testATagIsViewed(): void
    {
        $rows = $this->mapper()->rows(
            $this->definition('tag'),
            ['@class' => 'Teknoo\\East\\Website\\Object\\Tag', 'id' => 't1', 'name' => 'php', 'slug' => 'php', 'isHighlighted' => true],
            FormMode::View,
        );

        self::assertSame(['id' => 't1', 'name' => 'php', 'slug' => 'php', 'isHighlighted' => 'yes'], $this->values($rows));
    }

    public function testATypeIsViewedWithItsBlocksOneByLine(): void
    {
        $rows = $this->mapper()->rows(
            $this->definition('type'),
            [
                'id' => 'ty1',
                'name' => 'Page',
                'template' => 'page.html.twig',
                'blocks' => [['name' => 'intro', 'type' => 'textarea'], ['name' => 'count', 'type' => 'numeric']],
            ],
            FormMode::View,
        );

        self::assertSame(
            ['id' => 'ty1', 'name' => 'Page', 'template' => 'page.html.twig', 'blocks' => "intro:textarea\ncount:numeric"],
            $this->values($rows),
        );
        self::assertSame('intro:textarea … (2 lines)', $this->named($rows)['blocks']->text());
    }

    public function testAnItemIsViewedWithTheLabelsOfItsRelatedObjects(): void
    {
        $rows = $this->mapper()->rows($this->definition('item'), self::item(), FormMode::View);

        self::assertSame(
            [
                'id' => 'i1',
                'name' => 'Blog',
                'location' => 'top',
                'parent' => 'Home',
                'content' => 'Hello',
                'slug' => 'blog',
                'hidden' => 'no',
                'position' => '3',
                'environment' => 'validation',
                'localeField' => 'en',
            ],
            $this->values($rows),
        );
    }

    public function testAMediaIsViewedWithAllItsKeys(): void
    {
        $rows = $this->mapper()->rows(
            $this->definition('media'),
            [
                '@class' => 'Teknoo\\East\\Website\\Object\\Media',
                'id' => 'm1',
                'name' => 'logo.png',
                'length' => 1234,
                'metadata' => ['contentType' => 'image/png'],
            ],
            FormMode::View,
        );

        self::assertSame(
            ['id' => 'm1', 'name' => 'logo.png', 'length' => '1234', 'metadata' => '{"contentType":"image/png"}'],
            $this->values($rows),
        );
    }

    public function testAUserAndACommentAreViewed(): void
    {
        self::assertSame(
            [
                'id' => 'u1',
                'firstName' => 'Richard',
                'lastName' => 'Déloge',
                'email' => 'richard@teknoo.software',
                'roles' => 'ROLE_USER, ROLE_ADMIN',
                'active' => 'yes',
            ],
            $this->values($this->mapper()->rows($this->definition('user'), self::user(), FormMode::View)),
        );

        self::assertSame(
            [
                'id' => 'cm1',
                'moderatedAuthor' => 'Bob',
                'moderatedTitle' => 'Nice',
                'moderatedContent' => "Line 1\nLine 2",
                'author' => 'Bob',
                'title' => 'Nice post',
                'content' => "Line 1\nLine 2\nLine 3",
                'postAt' => '2026-02-03T04:05:06+00:00',
            ],
            $this->values($this->mapper()->rows($this->definition('comment'), self::comment(), FormMode::View)),
        );
    }

    public function testTheControlCharactersOfADocumentAreNeverKept(): void
    {
        $mapper = $this->mapper();
        $data = [
            'id' => "c\x1b[2J1",
            'title' => "Hel\x1b[31mlo\tWorld\n!",
            'description' => "First\r\nSe\x07cond\tline",
            'publishedAt' => "2026\x1b[0m",
            'parts' => ['intro' => "In\x1btro\r\nSecond", 'headline' => "Head\nline\x00"],
        ];

        $view = $this->values($mapper->rows($this->definition('content'), $data, FormMode::View));
        self::assertSame('c[2J1', $view['id'] ?? null);
        self::assertSame("Hel[31mlo    World\n!", $view['title'] ?? null);
        self::assertSame("First\nSecond    line", $view['description'] ?? null);
        self::assertSame("Intro\nSecond", $view['block intro'] ?? null);

        $rows = $this->named($mapper->rows($this->definition('content'), $data, FormMode::Edit, self::BLOCKS));
        self::assertSame('c[2J1', $rows['id']->value);
        self::assertSame('Hel[31mlo World !', $rows['title']->value);
        self::assertSame("First\nSecond    line", $rows['description']->value);
        self::assertSame("Intro\nSecond", $rows['block_intro']->value);
        self::assertSame('Head line', $rows['block_headline']->value);
        self::assertStringNotContainsString("\x1b", $rows['publish']->hint);
        self::assertStringContainsString('2026[0m', $rows['publish']->hint);
    }

    #[DataProvider('resourcesWithParts')]
    public function testAContentIsEditedWithARowByFieldItsBlocksAndItsPublication(string $resource, string $class): void
    {
        $definition = $this->definition($resource);
        $rows = $this->mapper()->rows($definition, self::content($class), FormMode::Edit, self::BLOCKS);

        self::assertSame(
            [
                'id' => RowKind::ReadOnly,
                'author' => RowKind::Relation,
                'type' => RowKind::Relation,
                'tags' => RowKind::RelationList,
                'title' => RowKind::Text,
                'subtitle' => RowKind::Text,
                'slug' => RowKind::Text,
                'description' => RowKind::Multiline,
                'environment' => RowKind::Text,
                'localeField' => RowKind::Text,
                'block_intro' => RowKind::Multiline,
                'block_body' => RowKind::Multiline,
                'block_headline' => RowKind::Text,
                'block_count' => RowKind::Text,
                'block_photo' => RowKind::Text,
                'publish' => RowKind::Bool,
            ],
            $this->kinds($rows),
        );

        self::assertSame(
            [
                'id' => 'c1',
                'author' => ['id' => 'u1', 'label' => 'Richard Déloge'],
                'type' => ['id' => 'ty1', 'label' => 'Page'],
                'tags' => [['id' => 't1', 'label' => 'php'], ['id' => 't2', 'label' => 'east']],
                'title' => 'Hello',
                'subtitle' => 'World',
                'slug' => 'hello',
                'description' => "First\nSecond",
                'environment' => 'validation',
                'localeField' => 'en',
                'block_intro' => "<p>Hi</p>\n<p>there</p>",
                'block_body' => '',
                'block_headline' => 'Read this',
                'block_count' => '3',
                'block_photo' => '',
                'publish' => false,
            ],
            $this->values($rows),
        );

        $named = $this->named($rows);
        self::assertNull($named['id']->field);
        self::assertFalse($named['id']->isEditable());

        foreach ($definition->fields as $field) {
            self::assertSame($field, $named[$field->name]->field, $field->name);
            self::assertSame($field->name, $named[$field->name]->label, $field->name);
            self::assertTrue($named[$field->name]->isEditable(), $field->name);
        }

        self::assertSame('Title', $named['title']->hint);
        self::assertSame('Description', $named['description']->hint);

        foreach (self::BLOCKS as $block) {
            $row = $named['block_' . $block['name']];
            self::assertTrue($row->isPart(), $row->name);
            self::assertSame($block['name'], $row->partName());
            self::assertSame($block['name'] . ' (' . $block['kind'] . ')', $row->label);
            self::assertNull($row->field);
        }

        self::assertNull($named['publish']->field);
        self::assertSame('publish', $named['publish']->label);

        $state = new FormState($rows);
        self::assertFalse($state->isDirty());
        self::assertSame([], $this->payload($state)->body());
    }

    public function testTheHintOfThePublicationTellsWhenTheContentWasPublished(): void
    {
        $mapper = $this->mapper();
        $definition = $this->definition('post');

        $published = $this->named($mapper->rows($definition, self::content('Post'), FormMode::Edit));
        self::assertStringContainsString('2026-01-02T03:04:05+00:00', $published['publish']->hint);
        self::assertStringContainsString('again', $published['publish']->hint);

        foreach ([null, '', 12] as $publishedAt) {
            $draft = $this->named($mapper->rows(
                $definition,
                ['publishedAt' => $publishedAt] + self::content('Post'),
                FormMode::Edit,
            ));
            self::assertSame('Publish the content now', $draft['publish']->hint);
            self::assertFalse($draft['publish']->value);
        }

        $data = self::content('Post');
        unset($data['publishedAt']);
        self::assertSame('Publish the content now', $this->named($mapper->rows($definition, $data, FormMode::Edit))['publish']->hint);
    }

    public function testAContentWithoutBlocksHasOnlyItsFieldsAndItsPublication(): void
    {
        $rows = $this->mapper()->rows($this->definition('content'), self::content(), FormMode::Edit);

        self::assertSame(
            ['id', 'author', 'type', 'tags', 'title', 'subtitle', 'slug', 'description', 'environment', 'localeField', 'publish'],
            array_keys($this->named($rows)),
        );
    }

    public function testTheBlocksWithoutValueAreEmpty(): void
    {
        $mapper = $this->mapper();
        $definition = $this->definition('content');

        foreach ([null, 'text', []] as $parts) {
            $values = $this->values($mapper->rows($definition, ['id' => 'c1', 'parts' => $parts], FormMode::Edit, self::BLOCKS));
            foreach (self::BLOCKS as $block) {
                self::assertSame('', $values['block_' . $block['name']] ?? null, $block['name']);
            }
        }

        $values = $this->values($mapper->rows(
            $definition,
            ['id' => 'c1', 'parts' => ['intro' => true, 'body' => ['a'], 'headline' => null, 'count' => 1.5, 'photo' => 0]],
            FormMode::Edit,
            self::BLOCKS,
        ));
        self::assertSame('', $values['block_intro'] ?? null);
        self::assertSame('', $values['block_body'] ?? null);
        self::assertSame('', $values['block_headline'] ?? null);
        self::assertSame('1.5', $values['block_count'] ?? null);
        self::assertSame('0', $values['block_photo'] ?? null);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function namesOfTheResourcesWithParts(): iterable
    {
        yield 'content' => ['content'];
        yield 'post' => ['post'];
    }

    #[DataProvider('namesOfTheResourcesWithParts')]
    public function testAContentIsCreatedWithEmptyRowsAndWithoutId(string $resource): void
    {
        $rows = $this->mapper()->rows($this->definition($resource), [], FormMode::Create, self::BLOCKS);

        self::assertSame(
            [
                'author' => null,
                'type' => null,
                'tags' => [],
                'title' => '',
                'subtitle' => '',
                'slug' => '',
                'description' => '',
                'environment' => '',
                'localeField' => '',
                'block_intro' => '',
                'block_body' => '',
                'block_headline' => '',
                'block_count' => '',
                'block_photo' => '',
                'publish' => false,
            ],
            $this->values($rows),
        );
        self::assertSame('Publish the content now', $this->named($rows)['publish']->hint);
        self::assertSame(RowKind::Relation, $this->named($rows)['author']->kind);
        self::assertSame(RowKind::RelationList, $this->named($rows)['tags']->kind);
    }

    public function testTheIdOfADocumentIsNeverARowOfACreation(): void
    {
        $rows = $this->mapper()->rows($this->definition('tag'), ['id' => 't1', 'name' => 'php'], FormMode::Create);

        self::assertSame(['name' => 'php', 'slug' => '', 'isHighlighted' => false], $this->values($rows));
    }

    public function testADocumentWithoutIdIsEditedWithoutIdRow(): void
    {
        $rows = $this->mapper()->rows($this->definition('tag'), ['name' => 'php'], FormMode::Edit);

        self::assertSame(['name', 'slug', 'isHighlighted'], array_keys($this->named($rows)));
    }

    /**
     * @return iterable<string, array{mixed, array{id: string, label: string}|null}>
     */
    public static function authors(): iterable
    {
        yield 'by its email' => [
            ['id' => 'u1', 'name' => 'Richard', 'title' => 'Mr', 'email' => 'richard@teknoo.software', 'slug' => 'r'],
            ['id' => 'u1', 'label' => 'richard@teknoo.software'],
        ];
        yield 'by its name' => [['id' => 'u1', 'name' => 'Richard', 'title' => 'Mr', 'slug' => 'r'], ['id' => 'u1', 'label' => 'Richard']];
        yield 'by its name when its email is empty' => [['id' => 'u1', 'name' => 'Richard', 'email' => ''], ['id' => 'u1', 'label' => 'Richard']];
        yield 'by its title' => [['id' => 'u1', 'title' => 'Mr', 'slug' => 'r'], ['id' => 'u1', 'label' => 'Mr']];
        yield 'by its slug' => [['id' => 'u1', 'slug' => 'r'], ['id' => 'u1', 'label' => 'r']];
        yield 'by its id' => [['id' => 'u1'], ['id' => 'u1', 'label' => 'u1']];
        yield 'by its id when it is given alone' => ['u1', ['id' => 'u1', 'label' => 'u1']];
        yield 'none' => [null, null];
        yield 'without id' => [['name' => 'Richard'], null];
    }

    /**
     * @param array{id: string, label: string}|null $expected
     */
    #[DataProvider('authors')]
    public function testAnAuthorIsLabelledByItsEmailOrByTheFirstKeyNamingIt(mixed $author, ?array $expected): void
    {
        $rows = $this->named($this->mapper()->rows(
            $this->definition('content'),
            ['id' => 'c1', 'author' => $author],
            FormMode::Edit,
        ));

        self::assertSame(RowKind::Relation, $rows['author']->kind);
        self::assertSame($expected, $rows['author']->value);
    }

    public function testTheRelationsGivenByTheirIdsAreLabelledByThem(): void
    {
        $values = $this->values($this->mapper()->rows(
            $this->definition('content'),
            ['id' => 'c1', 'author' => 'u1', 'type' => 'ty1', 'tags' => ['t1', ['id' => 't2', 'name' => 'east'], null, ['name' => 'lost'], '']],
            FormMode::Edit,
        ));

        self::assertSame(['id' => 'u1', 'label' => 'u1'], $values['author'] ?? null);
        self::assertSame(['id' => 'ty1', 'label' => 'ty1'], $values['type'] ?? null);
        self::assertSame([['id' => 't1', 'label' => 't1'], ['id' => 't2', 'label' => 'east']], $values['tags'] ?? null);
    }

    public function testTheTypeOfAContentIsARelationWithOrWithoutItsBlocks(): void
    {
        $mapper = $this->mapper();
        $definition = $this->definition('content');

        $with = $this->values($mapper->rows($definition, self::content(), FormMode::Edit));
        $without = $this->values($mapper->rows(
            $definition,
            ['type' => ['id' => 'ty1', 'name' => 'Page']] + self::content(),
            FormMode::Edit,
        ));

        self::assertSame(['id' => 'ty1', 'label' => 'Page'], $with['type'] ?? null);
        self::assertSame($with, $without);
    }

    /**
     * @return array<string, mixed>
     */
    private static function item(): array
    {
        return [
            '@class' => 'Teknoo\\East\\Website\\Object\\Item',
            'id' => 'i1',
            'name' => 'Blog',
            'slug' => 'blog',
            'location' => 'top',
            'content' => ['@class' => 'Teknoo\\East\\Website\\Object\\Content', 'id' => 'c1', 'title' => 'Hello'],
            'parent' => ['@class' => 'Teknoo\\East\\Website\\Object\\Item', 'id' => 'i0', 'name' => 'Home'],
            'hidden' => false,
            'position' => 3,
            'environment' => 'validation',
            'localeField' => 'en',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function user(): array
    {
        return [
            '@class' => 'Teknoo\\East\\Common\\Object\\User',
            'id' => 'u1',
            'firstName' => 'Richard',
            'lastName' => 'Déloge',
            'email' => 'richard@teknoo.software',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            'active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function comment(): array
    {
        return [
            '@class' => 'Teknoo\\East\\Website\\Object\\Comment',
            'id' => 'cm1',
            'author' => 'Bob',
            'title' => 'Nice post',
            'content' => "Line 1\nLine 2\nLine 3",
            'moderatedAuthor' => 'Bob',
            'moderatedTitle' => 'Nice',
            'moderatedContent' => "Line 1\r\nLine 2",
            'postAt' => '2026-02-03T04:05:06+00:00',
        ];
    }

    public function testAnItemIsEditedWithItsParentAndItsContentAsRelations(): void
    {
        $rows = $this->mapper()->rows($this->definition('item'), self::item(), FormMode::Edit, self::BLOCKS);

        self::assertSame(
            [
                'id' => RowKind::ReadOnly,
                'name' => RowKind::Text,
                'location' => RowKind::Text,
                'parent' => RowKind::Relation,
                'content' => RowKind::Relation,
                'slug' => RowKind::Text,
                'hidden' => RowKind::Bool,
                'position' => RowKind::Text,
                'environment' => RowKind::Text,
                'localeField' => RowKind::Text,
            ],
            $this->kinds($rows),
        );
        self::assertSame(
            [
                'id' => 'i1',
                'name' => 'Blog',
                'location' => 'top',
                'parent' => ['id' => 'i0', 'label' => 'Home'],
                'content' => ['id' => 'c1', 'label' => 'Hello'],
                'slug' => 'blog',
                'hidden' => false,
                'position' => '3',
                'environment' => 'validation',
                'localeField' => 'en',
            ],
            $this->values($rows),
        );
    }

    public function testAnItemWithoutParentAndWithoutContent(): void
    {
        $values = $this->values($this->mapper()->rows(
            $this->definition('item'),
            ['parent' => null, 'hidden' => true] + self::item(),
            FormMode::Edit,
        ));

        self::assertNull($values['parent'] ?? null);
        self::assertTrue($values['hidden'] ?? null);

        $created = $this->values($this->mapper()->rows($this->definition('item'), [], FormMode::Create));
        self::assertSame(
            [
                'name' => '',
                'location' => '',
                'parent' => null,
                'content' => null,
                'slug' => '',
                'hidden' => false,
                'position' => '',
                'environment' => '',
                'localeField' => '',
            ],
            $created,
        );
    }

    public function testATagIsEdited(): void
    {
        $rows = $this->mapper()->rows(
            $this->definition('tag'),
            ['@class' => 'Teknoo\\East\\Website\\Object\\Tag', 'id' => 't1', 'name' => 'php', 'slug' => 'php', 'isHighlighted' => true],
            FormMode::Edit,
        );

        self::assertSame(
            ['id' => RowKind::ReadOnly, 'name' => RowKind::Text, 'slug' => RowKind::Text, 'isHighlighted' => RowKind::Bool],
            $this->kinds($rows),
        );
        self::assertSame(['id' => 't1', 'name' => 'php', 'slug' => 'php', 'isHighlighted' => true], $this->values($rows));
    }

    public function testAUserIsEditedWithItsRolesAsChoices(): void
    {
        $definition = $this->definition('user');
        $rows = $this->mapper()->rows($definition, self::user(), FormMode::Edit);

        self::assertSame(
            [
                'id' => RowKind::ReadOnly,
                'firstName' => RowKind::Text,
                'lastName' => RowKind::Text,
                'email' => RowKind::Text,
                'roles' => RowKind::Choices,
                'active' => RowKind::Bool,
            ],
            $this->kinds($rows),
        );
        self::assertSame(
            [
                'id' => 'u1',
                'firstName' => 'Richard',
                'lastName' => 'Déloge',
                'email' => 'richard@teknoo.software',
                'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
                'active' => true,
            ],
            $this->values($rows),
        );

        $named = $this->named($rows);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $named['roles']->choices);
        self::assertSame([], $named['email']->choices);

        $created = $this->values($this->mapper()->rows($definition, ['roles' => 'ROLE_USER', 'active' => 1], FormMode::Create));
        self::assertSame([], $created['roles'] ?? null);
        self::assertFalse($created['active'] ?? null);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function blocksOfAType(): iterable
    {
        yield 'kinds of a document' => [
            [
                ['name' => 'intro', 'type' => 'textarea'],
                ['name' => 'body', 'type' => 'raw'],
                ['name' => 'headline', 'type' => 'text'],
                ['name' => 'count', 'type' => 'numeric'],
                ['name' => 'photo', 'type' => 'image'],
            ],
            "intro:textarea\nbody:raw\nheadline:text\ncount:numeric\nphoto:image",
        ];
        yield 'indexes of a request' => [
            [
                ['name' => 'intro', 'type' => '0'],
                ['name' => 'body', 'type' => '1'],
                ['name' => 'headline', 'type' => '2'],
                ['name' => 'count', 'type' => '3'],
                ['name' => 'photo', 'type' => '4'],
            ],
            "intro:textarea\nbody:raw\nheadline:text\ncount:numeric\nphoto:image",
        ];
        yield 'indexes given as numbers' => [[['name' => 'intro', 'type' => 0], ['name' => 'photo', 'type' => 4]], "intro:textarea\nphoto:image"];
        yield 'unknown kinds' => [
            [['name' => 'a', 'type' => 'video'], ['name' => 'b', 'type' => '9'], ['name' => 'c'], ['name' => 'd', 'type' => null]],
            "a:text\nb:text\nc:text\nd:text",
        ];
        yield 'no block' => [[], ''];
        yield 'not a list' => ['intro:textarea', ''];
        yield 'nothing' => [null, ''];
    }

    #[DataProvider('blocksOfAType')]
    public function testATypeIsEditedWithItsBlocksOneByLine(mixed $blocks, string $expected): void
    {
        $rows = $this->named($this->mapper()->rows(
            $this->definition('type'),
            ['id' => 'ty1', 'name' => 'Page', 'template' => 'page.html.twig', 'blocks' => $blocks],
            FormMode::Edit,
        ));

        self::assertSame(['id', 'name', 'template', 'blocks'], array_keys($rows));
        self::assertSame(RowKind::Multiline, $rows['blocks']->kind);
        self::assertSame($expected, $rows['blocks']->value);
        self::assertStringContainsString('textarea|raw|text|numeric|image', $rows['blocks']->hint);
        self::assertSame('Page', $rows['name']->value);
        self::assertSame('page.html.twig', $rows['template']->value);
    }

    public function testTheBlocksOfATypeReadFromADocumentAreNotChanges(): void
    {
        $state = $this->mapper()->state(
            $this->definition('type'),
            ['id' => 'ty1', 'name' => 'Page', 'blocks' => [['name' => 'intro', 'type' => 'textarea']]],
            FormMode::Edit,
        );

        self::assertSame([], $this->payload($state)->body());

        $this->row($state, 'blocks')->value = "intro:textarea\ncount:numeric";
        self::assertSame(
            ['blocks' => [['name' => 'intro', 'type' => '0'], ['name' => 'count', 'type' => '3']]],
            $this->payload($state)->body(),
        );
    }

    public function testACommentIsEditedWithItsContentOnSeveralLines(): void
    {
        $rows = $this->mapper()->rows($this->definition('comment'), self::comment(), FormMode::Edit);

        self::assertSame(
            [
                'id' => RowKind::ReadOnly,
                'moderatedAuthor' => RowKind::Text,
                'moderatedTitle' => RowKind::Text,
                'moderatedContent' => RowKind::Multiline,
            ],
            $this->kinds($rows),
        );
        self::assertSame(
            ['id' => 'cm1', 'moderatedAuthor' => 'Bob', 'moderatedTitle' => 'Nice', 'moderatedContent' => "Line 1\nLine 2"],
            $this->values($rows),
        );
    }

    public function testAMediaHasNoRowToEdit(): void
    {
        $mapper = $this->mapper();
        $media = $this->definition('media');

        self::assertSame(['id' => 'm1'], $this->values($mapper->rows($media, ['id' => 'm1', 'name' => 'logo.png'], FormMode::Edit)));
        self::assertSame([], $mapper->rows($media, ['id' => 'm1', 'name' => 'logo.png'], FormMode::Create));
    }

    public function testTheResourcesWithoutPartsHaveNeitherBlocksNorPublication(): void
    {
        $mapper = $this->mapper();
        foreach ((new Registry())->resources() as $definition) {
            foreach ([FormMode::Edit, FormMode::Create] as $mode) {
                $rows = $this->named($mapper->rows(
                    $definition,
                    ['id' => 'x1', 'parts' => ['intro' => 'Introduction'], 'publishedAt' => '2026-01-02'],
                    $mode,
                    self::BLOCKS,
                ));

                self::assertSame($definition->hasParts, isset($rows['publish']), $definition->name);
                self::assertSame($definition->hasParts, isset($rows['block_intro']), $definition->name);
                self::assertSame(FormMode::Edit === $mode, isset($rows['id']), $definition->name);
                foreach ($definition->fields as $field) {
                    self::assertArrayHasKey($field->name, $rows, $definition->name);
                    self::assertSame($field, $rows[$field->name]->field);
                }
            }
        }
    }

    public function testTheFieldsWithoutTargetNorChoicesAreEditedAsTexts(): void
    {
        // No resource of the registry has such fields: an id without target, a list of ids without target, a free list
        $definition = new ResourceDefinition('link', 'link', 'link', 'links', [
            new FieldDefinition('reference', FieldKind::Id, 'Id of an object'),
            new FieldDefinition('references', FieldKind::IdList, 'Id of an object'),
            new FieldDefinition('keywords', FieldKind::StringList, 'Keyword'),
        ], []);
        $mapper = $this->mapper();

        $rows = $mapper->rows(
            $definition,
            ['reference' => 'r1', 'references' => ['r1', '', 3, 'r2'], 'keywords' => ['php', 'east']],
            FormMode::Edit,
        );

        self::assertSame(
            ['reference' => RowKind::Text, 'references' => RowKind::Multiline, 'keywords' => RowKind::Multiline],
            $this->kinds($rows),
        );
        self::assertSame(['reference' => 'r1', 'references' => "r1\nr2", 'keywords' => "php\neast"], $this->values($rows));
        self::assertSame('Id of an object', $this->named($rows)['reference']->hint);

        $state = new FormState($rows);
        $mapper->overlay($state, new Payload(['reference' => 'r9', 'references' => ['r7', 'r8'], 'keywords' => []], [], false));

        self::assertSame('r9', $this->row($state, 'reference')->value);
        self::assertSame("r7\nr8", $this->row($state, 'references')->value);
        self::assertSame('', $this->row($state, 'keywords')->value);
        self::assertSame(
            ['reference' => 'r9', 'references' => ['r7', 'r8'], 'keywords' => []],
            $this->payload($state)->fields,
        );
    }

    public function testTheValuesGivenOnTheCommandLineAreConvertedBackAndForced(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('content'), self::content(), FormMode::Edit, self::BLOCKS);

        $mapper->overlay($state, new Payload(
            [
                'author' => 'u9',
                'type' => 'ty2',
                'tags' => ['t3', 't4'],
                'title' => 'New title',
                'description' => "A\r\nB",
            ],
            ['intro' => "New\r\nintroduction", 'count' => 4],
            true,
        ));

        self::assertSame(['id' => 'u9', 'label' => 'u9'], $this->row($state, 'author')->value);
        self::assertSame(['id' => 'ty2', 'label' => 'ty2'], $this->row($state, 'type')->value);
        self::assertSame([['id' => 't3', 'label' => 't3'], ['id' => 't4', 'label' => 't4']], $this->row($state, 'tags')->value);
        self::assertSame('New title', $this->row($state, 'title')->value);
        self::assertSame("A\nB", $this->row($state, 'description')->value);
        self::assertSame("New\nintroduction", $this->row($state, 'block_intro')->value);
        self::assertSame('4', $this->row($state, 'block_count')->value);
        self::assertTrue($this->row($state, 'publish')->value);

        $forced = [];
        foreach ($state->rows() as $row) {
            if ($row->forced) {
                $forced[] = $row->name;
            }
        }

        self::assertSame(['author', 'type', 'tags', 'title', 'description', 'block_intro', 'block_count', 'publish'], $forced);
        self::assertSame('hello', $this->row($state, 'slug')->value);
        self::assertSame('Read this', $this->row($state, 'block_headline')->value);

        // While they are not changed in the form, the values are sent as they were given, not as they are displayed
        $payload = $this->payload($state);
        self::assertSame(
            ['author' => 'u9', 'type' => 'ty2', 'tags' => ['t3', 't4'], 'title' => 'New title', 'description' => "A\r\nB"],
            $payload->fields,
        );
        self::assertSame(['intro' => "New\r\nintroduction", 'count' => 4], $payload->parts);
        self::assertTrue($payload->publish);

        // Once changed in the form, the value of the form is sent
        $this->row($state, 'description')->value = "A\nB\nC";
        $this->row($state, 'block_count')->value = '5';
        $this->row($state, 'tags')->value = [['id' => 't3', 'label' => 't3']];
        $payload = $this->payload($state);
        self::assertSame("A\nB\nC", $payload->fields['description']);
        self::assertSame(['t3'], $payload->fields['tags']);
        self::assertSame(['intro' => "New\r\nintroduction", 'count' => '5'], $payload->parts);
    }

    public function testAValueGivenOnTheCommandLineIsSentEvenWhenItIsTheOneOfTheDocument(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('content'), self::content(), FormMode::Edit, self::BLOCKS);

        $mapper->overlay($state, new Payload(['title' => 'Hello', 'slug' => 'hello'], ['headline' => 'Read this'], false));

        self::assertFalse($state->isDirty());
        self::assertFalse($this->row($state, 'publish')->forced);

        $payload = $this->payload($state);
        self::assertSame(['title' => 'Hello', 'slug' => 'hello'], $payload->fields);
        self::assertSame(['headline' => 'Read this'], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testARelationClearedOnTheCommandLineIsSentAsNull(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('content'), self::content(), FormMode::Edit);

        $mapper->overlay($state, new Payload(['author' => null, 'tags' => []], [], false));

        self::assertNull($this->row($state, 'author')->value);
        self::assertSame([], $this->row($state, 'tags')->value);
        self::assertSame(['author' => null, 'tags' => []], $this->payload($state)->fields);
    }

    public function testBooleansChoicesAndNumbersGivenOnTheCommandLine(): void
    {
        $mapper = $this->mapper();

        $user = $mapper->state($this->definition('user'), [], FormMode::Create);
        $mapper->overlay($user, new Payload(['roles' => ['ROLE_ADMIN'], 'active' => true, 'email' => 'a@teknoo.software'], [], false));
        self::assertSame(['ROLE_ADMIN'], $this->row($user, 'roles')->value);
        self::assertTrue($this->row($user, 'active')->value);
        self::assertSame(
            ['email' => 'a@teknoo.software', 'roles' => ['ROLE_ADMIN'], 'active' => true],
            $this->payload($user)->fields,
        );

        $item = $mapper->state($this->definition('item'), self::item(), FormMode::Edit);
        $mapper->overlay($item, new Payload(['hidden' => false, 'position' => 7, 'parent' => 'i9', 'content' => null], [], false));
        self::assertFalse($this->row($item, 'hidden')->value);
        self::assertSame('7', $this->row($item, 'position')->value);
        self::assertSame(['id' => 'i9', 'label' => 'i9'], $this->row($item, 'parent')->value);
        self::assertNull($this->row($item, 'content')->value);
        self::assertSame(
            ['parent' => 'i9', 'content' => null, 'hidden' => false, 'position' => 7],
            $this->payload($item)->fields,
        );
    }

    public function testTheBlocksOfATypeGivenOnTheCommandLineAreConvertedBackToLines(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('type'), [], FormMode::Create);

        $blocks = [['name' => 'intro', 'type' => '0'], ['name' => 'count', 'type' => '3']];
        $mapper->overlay($state, new Payload(['name' => 'Page', 'blocks' => $blocks], [], false));

        self::assertSame("intro:textarea\ncount:numeric", $this->row($state, 'blocks')->value);
        self::assertTrue($this->row($state, 'blocks')->forced);
        self::assertSame(['name' => 'Page', 'blocks' => $blocks], $this->payload($state)->fields);

        $single = $mapper->state($this->definition('type'), [], FormMode::Create);
        $mapper->overlay($single, new Payload(['blocks' => [['name' => 'name', 'type' => '0']]], [], false));
        self::assertSame('name:textarea', $this->row($single, 'blocks')->value);
    }

    public function testTheFieldsAndThePartsWithoutRowAreSentAsTheyAre(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('content'), self::content(), FormMode::Edit, self::BLOCKS);

        $mapper->overlay($state, new Payload(
            ['unknown' => ['nested' => true], 'id' => 'c2', 'publish' => 'not the row', 'title' => 'New title'],
            ['footer' => 'Unknown block', 'intro' => 'New'],
            false,
        ));

        self::assertSame('c1', $this->row($state, 'id')->value);
        self::assertFalse($this->row($state, 'publish')->value);
        self::assertFalse($this->row($state, 'publish')->forced);

        $payload = $this->payload($state);
        self::assertSame(
            ['unknown' => ['nested' => true], 'id' => 'c2', 'publish' => 'not the row', 'title' => 'New title'],
            $payload->fields,
        );
        self::assertSame(['footer' => 'Unknown block', 'intro' => 'New'], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testABlockGivenOnTheCommandLineWhichIsNotATextIsDisplayedEmptyAndSentAsItWasGiven(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('content'), self::content(), FormMode::Edit, self::BLOCKS);

        $mapper->overlay($state, new Payload([], ['intro' => null, 'headline' => ['a'], 'count' => true, 'photo' => 1.5], false));

        self::assertSame('', $this->row($state, 'block_intro')->value);
        self::assertSame('', $this->row($state, 'block_headline')->value);
        self::assertSame('', $this->row($state, 'block_count')->value);
        self::assertSame('1.5', $this->row($state, 'block_photo')->value);
        self::assertSame(
            ['intro' => null, 'headline' => ['a'], 'count' => true, 'photo' => 1.5],
            $this->payload($state)->parts,
        );
    }

    public function testAFieldWithANumericNameGivenOnTheCommandLineIsSentAsItIs(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('tag'), [], FormMode::Create);

        // PHP casts the numeric keys of an array to integers: --data '{"12": "x", "name": "php"}'
        $mapper->overlay($state, new Payload(['12' => 'x', 'name' => 'php'], [], false));

        self::assertSame('php', $this->row($state, 'name')->value);
        self::assertSame([12 => 'x', 'name' => 'php'], $this->payload($state)->fields);
    }

    public function testABlockWithANumericName(): void
    {
        $mapper = $this->mapper();
        $blocks = $mapper->blocks(['blocks' => [['name' => '1', 'type' => 'text']]]);
        self::assertSame([['name' => '1', 'kind' => 'text']], $blocks);

        $state = $mapper->state($this->definition('content'), ['id' => 'c1', 'parts' => ['1' => 'one']], FormMode::Edit, $blocks);
        self::assertSame('one', $this->row($state, 'block_1')->value);

        $mapper->overlay($state, new Payload([], ['1' => 'uno', '2' => 'dos'], false));

        self::assertSame('uno', $this->row($state, 'block_1')->value);
        self::assertSame([2 => 'dos', 1 => 'uno'], $this->payload($state)->parts);
    }

    public function testASecondOverlayDoesNotOverwriteWhatWasAlreadyGiven(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('content'), self::content(), FormMode::Edit, self::BLOCKS);
        $prefill = new Payload(['title' => 'From the command line', 'author' => 'u9'], ['intro' => 'From the command line'], true);

        $mapper->overlay($state, $prefill);

        $this->row($state, 'title')->value = 'Typed';
        $this->row($state, 'author')->value = null;
        $this->row($state, 'block_intro')->value = 'Typed';
        $this->row($state, 'publish')->value = false;

        $mapper->overlay($state, $prefill);

        self::assertSame('Typed', $this->row($state, 'title')->value);
        self::assertNull($this->row($state, 'author')->value);
        self::assertSame('Typed', $this->row($state, 'block_intro')->value);
        self::assertFalse($this->row($state, 'publish')->value);

        $payload = $this->payload($state);
        self::assertSame(['author' => null, 'title' => 'Typed'], $payload->fields);
        self::assertSame(['intro' => 'Typed'], $payload->parts);
        self::assertFalse($payload->publish);
    }

    public function testTheBlocksOfANewTypeAreFilledByASecondOverlay(): void
    {
        $mapper = $this->mapper();
        $definition = $this->definition('content');
        $prefill = new Payload(['title' => 'New title'], ['intro' => 'Introduction', 'footer' => 'Footer'], true);

        $state = $mapper->state($definition, [], FormMode::Create, [], $prefill);
        self::assertNull($state->row('block_intro'));
        self::assertSame(['intro' => 'Introduction', 'footer' => 'Footer'], $this->payload($state)->parts);

        $title = $this->row($state, 'title');
        $title->value = 'Typed';

        $state->replace($mapper->rows($definition, [], FormMode::Create, [['name' => 'intro', 'kind' => 'textarea']]));
        $mapper->overlay($state, $prefill);

        self::assertSame($title, $state->row('title'));
        self::assertSame('Typed', $title->value);
        self::assertSame('Introduction', $this->row($state, 'block_intro')->value);
        self::assertTrue($this->row($state, 'block_intro')->forced);

        $payload = $this->payload($state);
        self::assertSame(['title' => 'Typed'], $payload->fields);
        self::assertSame(['footer' => 'Footer', 'intro' => 'Introduction'], $payload->parts);
        self::assertTrue($payload->publish);
    }

    public function testThePublicationGivenOnTheCommandLineNeedsItsRow(): void
    {
        $mapper = $this->mapper();
        $state = $mapper->state($this->definition('tag'), [], FormMode::Create);

        $mapper->overlay($state, new Payload(['name' => 'php'], [], true));

        self::assertNull($state->row('publish'));
        self::assertFalse($this->payload($state)->publish);
        self::assertSame(['name' => 'php'], $this->payload($state)->fields);
    }

    public function testState(): void
    {
        $mapper = $this->mapper();
        $definition = $this->definition('content');

        $state = $mapper->state($definition, self::content(), FormMode::Edit, self::BLOCKS);

        self::assertSame(
            $this->values($mapper->rows($definition, self::content(), FormMode::Edit, self::BLOCKS)),
            $this->values($state->rows()),
        );
        self::assertFalse($state->isDirty());
        self::assertSame([], $this->payload($state)->body());

        $viewed = $mapper->state($definition, self::content(), FormMode::View);
        self::assertSame(
            $this->values($mapper->rows($definition, self::content(), FormMode::View)),
            $this->values($viewed->rows()),
        );

        self::assertSame([], $mapper->state($this->definition('media'), [], FormMode::Create)->rows());
    }

    public function testStateWithWhatWasGivenOnTheCommandLine(): void
    {
        $state = $this->mapper()->state(
            $this->definition('post'),
            [],
            FormMode::Create,
            self::BLOCKS,
            new Payload(['title' => 'Hello', 'type' => 'ty1', 'other' => 1], ['intro' => 'Introduction', 'footer' => 'Footer'], true),
        );

        self::assertNull($state->row('id'));
        self::assertSame('Hello', $this->row($state, 'title')->value);
        self::assertTrue($this->row($state, 'title')->forced);
        self::assertSame(['id' => 'ty1', 'label' => 'ty1'], $this->row($state, 'type')->value);
        self::assertSame('Introduction', $this->row($state, 'block_intro')->value);
        self::assertTrue($this->row($state, 'publish')->value);

        $payload = $this->payload($state);
        self::assertSame(['other' => 1, 'type' => 'ty1', 'title' => 'Hello'], $payload->fields);
        self::assertSame(['footer' => 'Footer', 'intro' => 'Introduction'], $payload->parts);
        self::assertTrue($payload->publish);
        self::assertTrue($payload->needsTwoSteps(true));
    }

    public function testBlocksOfAType(): void
    {
        self::assertSame(self::BLOCKS, $this->mapper()->blocks(self::content()['type']));
        self::assertSame(
            [['name' => 'intro', 'kind' => 'textarea'], ['name' => 'count', 'kind' => 'numeric']],
            $this->mapper()->blocks(['id' => 'ty1', 'blocks' => [['name' => 'intro', 'type' => '0'], ['name' => 'count', 'type' => 3]]]),
        );
    }

    /**
     * @return iterable<string, array{array<mixed>, list<array{name: string, kind: string}>}>
     */
    public static function malformedTypes(): iterable
    {
        yield 'an empty document' => [[], []];
        yield 'no blocks' => [['id' => 'ty1', 'name' => 'Page'], []];
        yield 'null blocks' => [['blocks' => null], []];
        yield 'blocks which are a text' => [['blocks' => 'intro:textarea'], []];
        yield 'empty blocks' => [['blocks' => []], []];
        yield 'blocks which are not objects' => [['blocks' => ['intro', null, 12, true]], []];
        yield 'blocks without name' => [['blocks' => [[], ['type' => 'raw'], ['name' => ''], ['name' => null, 'type' => 'raw']]], []];
        yield 'names which are not texts' => [['blocks' => [['name' => 12, 'type' => 'raw'], ['name' => ['intro']]]], []];
        yield 'the valid blocks are kept' => [
            ['blocks' => ['junk', ['name' => 'intro', 'type' => 'raw'], ['type' => 'raw'], ['name' => 'count', 'type' => 'numeric']]],
            [['name' => 'intro', 'kind' => 'raw'], ['name' => 'count', 'kind' => 'numeric']],
        ];
        yield 'unknown kinds are texts' => [
            [
                'blocks' => [
                    ['name' => 'a'],
                    ['name' => 'b', 'type' => null],
                    ['name' => 'c', 'type' => 'video'],
                    ['name' => 'd', 'type' => '5'],
                    ['name' => 'e', 'type' => true],
                    ['name' => 'f', 'type' => ['raw']],
                    ['name' => 'g', 'type' => 'Raw'],
                    ['name' => 'h', 'type' => ''],
                ],
            ],
            [
                ['name' => 'a', 'kind' => 'text'],
                ['name' => 'b', 'kind' => 'text'],
                ['name' => 'c', 'kind' => 'text'],
                ['name' => 'd', 'kind' => 'text'],
                ['name' => 'e', 'kind' => 'text'],
                ['name' => 'f', 'kind' => 'text'],
                ['name' => 'g', 'kind' => 'text'],
                ['name' => 'h', 'kind' => 'text'],
            ],
        ];
        yield 'the keys of the blocks are ignored' => [
            ['blocks' => ['first' => ['name' => 'intro', 'type' => 'textarea']]],
            [['name' => 'intro', 'kind' => 'textarea']],
        ];
        yield 'the names are cleaned' => [
            ['blocks' => [['name' => "in\x1b[1mtro\n", 'type' => 'textarea']]],
            [['name' => 'in[1mtro ', 'kind' => 'textarea']],
        ];
    }

    /**
     * @param array<mixed> $type
     * @param list<array{name: string, kind: string}> $expected
     */
    #[DataProvider('malformedTypes')]
    public function testBlocksOfAMalformedType(array $type, array $expected): void
    {
        self::assertSame($expected, $this->mapper()->blocks($type));
    }

    /**
     * @return iterable<string, array{mixed, string|null, array{id: string, label: string}|null}>
     */
    public static function references(): iterable
    {
        yield 'an id' => ['u1', null, ['id' => 'u1', 'label' => 'u1']];
        yield 'an id with a target' => ['u1', 'user', ['id' => 'u1', 'label' => 'u1']];
        yield 'a number' => [12, null, ['id' => '12', 'label' => '12']];
        yield 'zero' => [0, null, ['id' => '0', 'label' => '0']];
        yield 'an empty id' => ['', null, null];
        yield 'nothing' => [null, 'user', null];
        yield 'a boolean' => [true, null, null];
        yield 'false' => [false, null, null];
        yield 'an object' => [['id' => 'ty1', 'name' => 'Page'], null, ['id' => 'ty1', 'label' => 'Page']];
        yield 'an object with a target' => [['id' => 'ty1', 'name' => 'Page', 'slug' => 'page'], 'type', ['id' => 'ty1', 'label' => 'Page']];
        yield 'a user' => [['id' => 'u1', 'name' => 'Richard', 'email' => 'r@teknoo.software'], 'user', ['id' => 'u1', 'label' => 'r@teknoo.software']];
        yield 'a user without target' => [['id' => 'u1', 'name' => 'Richard', 'email' => 'r@teknoo.software'], null, ['id' => 'u1', 'label' => 'Richard']];
        yield 'a content' => [['id' => 'c1', 'name' => 'Name', 'title' => 'Hello'], 'content', ['id' => 'c1', 'label' => 'Hello']];
        yield 'a comment' => [['id' => 'cm1', 'title' => 'Nice', 'slug' => 'nice'], 'comment', ['id' => 'cm1', 'label' => 'Nice']];
        yield 'an unknown target' => [['id' => 'x1', 'title' => 'Hello', 'slug' => 'hello'], 'unknown', ['id' => 'x1', 'label' => 'Hello']];
        yield 'an object with its id only' => [['id' => 'ty1'], 'type', ['id' => 'ty1', 'label' => 'ty1']];
        yield 'an object with a numeric id' => [['id' => 7, 'name' => 'Seven'], null, ['id' => '7', 'label' => 'Seven']];
        yield 'an object with an empty label' => [['id' => 'ty1', 'name' => ''], 'type', ['id' => 'ty1', 'label' => 'ty1']];
        yield 'an object without id' => [['name' => 'Page'], 'type', null];
        yield 'an object with an empty id' => [['id' => '', 'name' => 'Page'], null, null];
        yield 'an object with a null id' => [['id' => null, 'name' => 'Page'], null, null];
        yield 'an object with a boolean id' => [['id' => true, 'name' => 'Page'], null, null];
        yield 'an object with an id which is an object' => [['id' => ['x'], 'name' => 'Page'], null, null];
        yield 'an empty object' => [[], 'type', null];
        yield 'a list' => [['ty1', 'ty2'], 'type', null];
    }

    /**
     * @param array{id: string, label: string}|null $expected
     */
    #[DataProvider('references')]
    public function testReference(mixed $value, ?string $target, ?array $expected): void
    {
        self::assertSame($expected, $this->mapper()->reference($value, $target));
    }

    public function testTheLabelOfAReferenceIsCleaned(): void
    {
        self::assertSame(
            ['id' => 'ty1', 'label' => 'Pa[31mge two'],
            $this->mapper()->reference(['id' => 'ty1', 'name' => "Pa\x1b[31mge\ntwo"], 'type'),
        );
    }

    public function testASingleValueGivenForAListOnTheCommandLineIsOneValueOfTheRow(): void
    {
        $mapper = $this->mapper();

        // --data '{"tags": "t1"}' and --data '{"roles": "ROLE_ADMIN"}'
        $content = $mapper->state($this->definition('content'), self::content(), FormMode::Edit, self::BLOCKS);
        $mapper->overlay($content, new Payload(['tags' => 't1'], [], false));
        self::assertSame([['id' => 't1', 'label' => 't1']], $this->row($content, 'tags')->value);
        self::assertSame(['tags' => 't1'], $this->payload($content)->fields);

        $user = $mapper->state($this->definition('user'), ['id' => 'u1', 'roles' => ['ROLE_USER']], FormMode::Edit);
        $mapper->overlay($user, new Payload(['roles' => 'ROLE_ADMIN'], [], false));
        self::assertSame(['ROLE_ADMIN'], $this->row($user, 'roles')->value);
        self::assertSame(['roles' => 'ROLE_ADMIN'], $this->payload($user)->fields);

        $this->row($user, 'roles')->value = ['ROLE_USER', 'ROLE_ADMIN'];
        self::assertSame(['roles' => ['ROLE_USER', 'ROLE_ADMIN']], $this->payload($user)->fields);
    }
}
