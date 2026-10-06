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

namespace Teknoo\Tests\East\Website\Tools\Tui\Screen;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Tools\Input\Payload;
use Teknoo\East\Website\Tools\Resource\FieldDefinition;
use Teknoo\East\Website\Tools\Resource\FieldKind;
use Teknoo\East\Website\Tools\Resource\Operation;
use Teknoo\East\Website\Tools\Resource\ResourceDefinition;
use Teknoo\East\Website\Tools\Tui\Screen\ConfirmScreen;
use Teknoo\East\Website\Tools\Tui\Screen\FormMode;
use Teknoo\East\Website\Tools\Tui\Screen\FormScreen;
use Teknoo\East\Website\Tools\Tui\Screen\ListScreen;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\RecordingScreen;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the form of an object: read only, to update it or to create it
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FormScreen::class)]
class FormScreenTest extends TestCase
{
    private const array TAG = ['id' => 't1', 'name' => 'PHP', 'slug' => 'php', 'isHighlighted' => false];

    private const array TYPE = [
        'id' => 'ty1',
        'name' => 'Page',
        'template' => 'page.html',
        'blocks' => [['name' => 'intro', 'type' => 'textarea'], ['name' => 'count', 'type' => 'numeric']],
    ];

    private const array CONTENT = [
        'id' => 'c1',
        'publishedAt' => null,
        'author' => ['id' => 'u1', 'name' => 'Ada L'],
        'title' => 'Home',
        'subtitle' => '',
        'slug' => 'home',
        'type' => self::TYPE,
        'tags' => [['id' => 't1', 'name' => 'PHP']],
        'description' => 'Welcome',
        'parts' => ['intro' => "Hello\nworld", 'count' => '3'],
    ];

    /**
     * @param array<string, string> $params
     * @param array<mixed> $document
     * @param array<string, scalar> $query
     */
    private function open(
        TuiHarness $harness,
        string $resource,
        FormMode $mode,
        array $params = [],
        array $document = [],
        ?Payload $prefill = null,
        array $query = [],
    ): FormScreen {
        $screen = new FormScreen(
            $harness->session,
            $harness->definition($resource),
            $params,
            $query,
            $mode,
            $document,
            $prefill,
        );
        $harness->open($screen);

        return $screen;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private static function document(array $data): array
    {
        return ['meta' => ['id' => $data['id'] ?? null], 'data' => $data];
    }

    private static function line(TuiHarness $harness, string $start): string
    {
        foreach ($harness->lines() as $line) {
            if (str_starts_with(ltrim($line, '> '), ltrim($start))) {
                return $line;
            }
        }

        self::fail(sprintf("No line starting by \"%s\" on the screen:\n%s", $start, $harness->screen()));
    }

    public function testAnObjectIsDisplayedReadOnly(): void
    {
        $harness = new TuiHarness(80, 10);
        $screen = $this->open($harness, 'tag', FormMode::View, ['id' => 't1'], self::TAG + ['createdAt' => '2026-01-01']);

        self::assertSame(
            [
                'tag · PHP',
                '> id             t1',
                '  name           PHP',
                '  slug           php',
                '  isHighlighted  no',
                '  createdAt      2026-01-01',
                '',
                '',
                '',
                'e edit · d delete · ↑↓ move · Esc back · q quit',
            ],
            $harness->lines(),
        );
        self::assertSame('tag · PHP', $screen->title());

        // Nothing can be typed nor saved
        $harness->keys(Keys::DOWN, 'x', Keys::CTRL_S);
        self::assertSame('> name           PHP', $harness->lines()[2]);
        self::assertSame([], $harness->api->calls());
    }

    public function testTheBlocksOfAContentAreDisplayedReadOnlyWithoutLoadingItsType(): void
    {
        $harness = new TuiHarness(80, 20);
        $this->open($harness, 'content', FormMode::View, ['id' => 'c1'], ['type' => ['id' => 'ty1', 'name' => 'Page']] + self::CONTENT);

        self::assertSame('  author       Ada L', self::line($harness, 'author'));
        self::assertSame('  type         Page', self::line($harness, 'type'));
        self::assertSame('  block intro  Hello … (2 lines)', self::line($harness, 'block intro'));
        self::assertSame([], $harness->api->calls());
    }

    public function testEscapeClosesAReadOnlyFormAndQStopsTheInterface(): void
    {
        $harness = new TuiHarness(80, 10);
        $below = new RecordingScreen();
        $harness->open($below);

        $this->open($harness, 'tag', FormMode::View, ['id' => 't1'], self::TAG);
        $harness->keys(Keys::ESCAPE);
        self::assertSame([null], $below->results);

        $this->open($harness, 'tag', FormMode::View, ['id' => 't1'], self::TAG);
        $harness->keys('q');
        self::assertTrue($harness->navigator->isStopped());
    }

    public function testAReadOnlyFormBecomesEditable(): void
    {
        $harness = new TuiHarness(80, 10);
        $screen = $this->open($harness, 'tag', FormMode::View, ['id' => 't1'], self::TAG);

        $harness->keys('e');

        self::assertSame('tag · edit PHP', $harness->lines()[0]);
        self::assertSame('> name           PHP', $harness->lines()[2]);
        self::assertSame('Ctrl+S or F2 save · Tab/Shift+Tab field · Esc back', $screen->hints());
    }

    public function testTheOperationsNotSupportedByTheResourceAreIgnoredInAReadOnlyForm(): void
    {
        $harness = new TuiHarness(80, 10);
        $media = $harness->definition('media');
        $screen = new FormScreen($harness->session, $media, ['id' => 'm1'], [], FormMode::View, ['id' => 'm1', 'name' => 'logo.png']);
        $harness->open($screen);

        self::assertSame('d delete · ↑↓ move · Esc back · q quit', $screen->hints());
        $harness->keys('e');
        self::assertSame('media · logo.png', $harness->lines()[0]);

        $comment = new FormScreen(
            $harness->session,
            new ResourceDefinition('note', 'note', 'note', 'notes', [], []),
            ['id' => 'n1'],
            [],
            FormMode::View,
            ['id' => 'n1'],
        );
        $harness->open($comment);
        self::assertSame('↑↓ move · Esc back · q quit', $comment->hints());
        $harness->keys('d', 'e');
        self::assertSame($comment, $harness->navigator->current());
        self::assertSame('note · n1', $harness->lines()[0]);
    }

    public function testAnObjectIsDeletedFromItsFormAfterAConfirmation(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(500, ['meta' => ['error' => true], 'data' => ['message' => 'Locked']])
            ->queue(200, ['meta' => ['deleted' => 'success'], 'data' => []]);
        $below = new RecordingScreen();
        $harness->open($below);
        $screen = $this->open($harness, 'tag', FormMode::View, ['id' => 't1'], self::TAG);

        $harness->keys('d');
        self::assertInstanceOf(ConfirmScreen::class, $harness->navigator->current());
        self::assertSame('Delete the tag "PHP"?', $harness->lines()[2]);

        $harness->keys('n');
        self::assertSame($screen, $harness->navigator->current());
        self::assertSame([], $harness->api->calls());

        $harness->keys('d', 'y');
        self::assertSame($screen, $harness->navigator->current());
        self::assertStringContainsString('Locked', $harness->lines()[8]);

        $harness->keys('d', 'y');
        self::assertSame([FormScreen::CHANGED], $below->results);
        self::assertSame($below, $harness->navigator->current());
        self::assertSame('The tag was deleted', $harness->navigator->statusText());
        self::assertSame(
            ['DELETE /api/v1/admin/tag/t1/delete', 'DELETE /api/v1/admin/tag/t1/delete'],
            $harness->api->calls(),
        );
    }

    public function testOnlyTheChangedFieldsOfAnUpdateAreSentAndTheSavedObjectReplacesTheForm(): void
    {
        $harness = new TuiHarness(80, 12);
        $harness->api->queue(200, self::document(['name' => 'PHP 8', 'slug' => 'php-8', 'isHighlighted' => true] + self::TAG));
        $below = new RecordingScreen();
        $harness->open($below);
        $this->open($harness, 'tag', FormMode::Edit, ['id' => 't1'], self::TAG, null, ['locale' => 'fr']);

        self::assertSame('tag · edit PHP', $harness->lines()[0]);
        self::assertSame('  id             t1', $harness->lines()[1]);
        self::assertSame('> name           PHP', $harness->lines()[2]);
        self::assertSame('    Name of the tag', $harness->lines()[3]);

        $harness->type(' 8')->keys(Keys::TAB, Keys::TAB, Keys::SPACE, Keys::CTRL_S);

        self::assertSame(['PUT /api/v1/admin/tag/t1?locale=fr'], $harness->api->calls());
        self::assertSame(['name' => 'PHP 8', 'isHighlighted' => true], $harness->api->requests[0]['body']);
        self::assertSame('tag · edit PHP 8', $harness->lines()[0]);
        self::assertSame('  slug           php-8', self::line($harness, 'slug'));
        self::assertSame('  isHighlighted  [x]', self::line($harness, 'isHighlighted'));
        self::assertSame('The tag was saved', $harness->lines()[10]);

        // Nothing is changed any more: nothing is sent, and the form is closed without any confirmation
        $harness->keys(Keys::F2);
        self::assertSame('Nothing was changed', $harness->lines()[10]);
        self::assertCount(1, $harness->api->requests);

        $harness->keys(Keys::ESCAPE);
        self::assertSame([FormScreen::CHANGED], $below->results);
    }

    public function testTheErrorsOfValidationOfTheApiAreDisplayedUnderTheirFieldsAndTheFormCanBeSentAgain(): void
    {
        $harness = new TuiHarness(80, 14);
        $harness->api
            ->queue(400, ['meta' => ['errors' => true], 'data' => ['.slug' => 'This slug is already used', '.' => 'Refused', '.unknown' => 'Odd']])
            ->queue(200, self::document(['slug' => 'php-lang'] + self::TAG));
        $this->open($harness, 'tag', FormMode::Edit, ['id' => 't1'], self::TAG);

        $harness->keys(Keys::TAB)->type('x')->keys(Keys::CTRL_S);

        self::assertSame(['slug' => 'phpx'], $harness->api->requests[0]['body']);
        self::assertSame('> slug           phpx', self::line($harness, 'slug'));
        self::assertSame('    ! This slug is already used', self::line($harness, '    !'));
        self::assertSame('Validation failed — Refused — unknown: Odd', $harness->lines()[12]);

        // The error disappears when the field is changed
        $harness->keys(Keys::BACKSPACE);
        self::assertStringNotContainsString('already used', $harness->screen());

        $harness->type('-lang')->keys(Keys::CTRL_S);
        self::assertSame(['slug' => 'php-lang'], $harness->api->requests[1]['body']);
        self::assertSame('The tag was saved', $harness->lines()[12]);
    }

    public function testAValueRefusedByItsFieldIsNotSent(): void
    {
        $harness = new TuiHarness(80, 16);
        $item = ['id' => 'i1', 'name' => 'Home', 'location' => 'top', 'slug' => 'home', 'hidden' => false, 'position' => 1];
        $this->open($harness, 'item', FormMode::Edit, ['id' => 'i1'], $item);

        $harness->keys(Keys::TAB, Keys::TAB, Keys::TAB, Keys::TAB, Keys::TAB, Keys::TAB)->type('x')->keys(Keys::CTRL_S);

        self::assertSame([], $harness->api->calls());
        self::assertSame('> position     1x', self::line($harness, 'position'));
        self::assertStringContainsString('expects an integer, "1x" given', self::line($harness, '    !'));
        self::assertSame('Some values are not valid, nothing was sent', $harness->lines()[14]);
    }

    public function testUnsavedChangesAreDiscardedOnlyAfterASecondEscape(): void
    {
        $harness = new TuiHarness(80, 12);
        $below = new RecordingScreen();
        $harness->open($below);
        $screen = $this->open($harness, 'tag', FormMode::Edit, ['id' => 't1'], self::TAG);

        $harness->type('x')->keys(Keys::ESCAPE);
        self::assertSame($screen, $harness->navigator->current());
        self::assertSame('Unsaved changes: press Esc again to discard them, Ctrl+S to save', $harness->lines()[10]);

        // A change after the warning needs a new warning
        $harness->type('y')->keys(Keys::ESCAPE);
        self::assertSame($screen, $harness->navigator->current());

        $harness->keys(Keys::ESCAPE);
        self::assertSame([null], $below->results);
        self::assertSame('', $harness->navigator->statusText());
    }

    public function testAServerFailureOfASaveIsReported(): void
    {
        $harness = new TuiHarness(80, 12);
        $harness->api->queue(500, ['meta' => ['error' => true], 'data' => ['message' => 'Database is down']]);
        $this->open($harness, 'tag', FormMode::Edit, ['id' => 't1'], self::TAG);

        $harness->type('x')->keys(Keys::CTRL_S);

        self::assertStringContainsString('Database is down', $harness->lines()[10]);
        self::assertSame('> name           PHPx', self::line($harness, 'name'));
    }

    public function testAnObjectIsCreatedThenItsFormGoesOnAsAnUpdate(): void
    {
        $harness = new TuiHarness(80, 12);
        $harness->api
            ->queue(302, [], ['Location' => '/api/v1/admin/tag/t9'])
            ->queue(200, self::document(['id' => 't9', 'name' => 'Twig', 'slug' => 'twig', 'isHighlighted' => false]))
            ->queue(200, self::document(['id' => 't9', 'name' => 'Twig', 'slug' => 'twig', 'isHighlighted' => true]));
        $below = new RecordingScreen();
        $harness->open($below);
        $screen = $this->open($harness, 'tag', FormMode::Create);

        self::assertSame('tag · new', $screen->title());
        self::assertSame('> name', $harness->lines()[1]);

        $harness->keys(Keys::CTRL_S);
        self::assertSame('Nothing to create: fill at least one field', $harness->lines()[10]);
        self::assertSame([], $harness->api->calls());

        $harness->type('Twig')->keys(Keys::CTRL_S);
        self::assertSame(['POST /api/v1/admin/tag/new', 'GET /api/v1/admin/tag/t9'], $harness->api->calls());
        self::assertSame(['name' => 'Twig'], $harness->api->requests[0]['body']);
        self::assertSame('tag · edit Twig', $harness->lines()[0]);
        self::assertSame('  id             t9', $harness->lines()[1]);
        self::assertSame('  slug           twig', self::line($harness, 'slug'));
        self::assertSame('The tag was created', $harness->lines()[10]);

        $harness->keys(Keys::TAB, Keys::TAB, Keys::ENTER, Keys::CTRL_S);
        self::assertSame('PUT /api/v1/admin/tag/t9', $harness->api->calls()[2]);
        self::assertSame(['isHighlighted' => true], $harness->api->requests[2]['body']);

        $harness->keys(Keys::ESCAPE);
        self::assertSame([FormScreen::CHANGED], $below->results);
    }

    public function testACreatedObjectWhichCanNotBeFetchedKeepsTheTypedValues(): void
    {
        $harness = new TuiHarness(80, 12);
        $harness->api
            ->queue(302, [], ['Location' => '/api/v1/admin/tag/t9'])
            ->queue(500, ['meta' => ['error' => true], 'data' => ['message' => 'Oops']]);
        $this->open($harness, 'tag', FormMode::Create);

        $harness->type('Twig')->keys(Keys::CTRL_S);

        self::assertSame('tag · edit t9', $harness->lines()[0]);
        self::assertSame('> name           Twig', self::line($harness, 'name'));
        self::assertStringContainsString('Created, but the object can not be fetched', $harness->lines()[10]);

        // The values are saved: nothing more to send
        $harness->keys(Keys::CTRL_S);
        self::assertSame('Nothing was changed', $harness->lines()[10]);
    }

    public function testTheValuesOfTheCommandLineFillTheFormAndAreAlwaysSent(): void
    {
        $harness = new TuiHarness(80, 12);
        $harness->api->queue(200, self::document(['name' => 'PHP', 'isHighlighted' => true] + self::TAG));
        $prefill = new Payload(['name' => 'PHP', 'isHighlighted' => true, 'custom' => 'kept'], [], false);
        $this->open($harness, 'tag', FormMode::Edit, ['id' => 't1'], self::TAG, $prefill);

        self::assertSame('  isHighlighted  [x]', self::line($harness, 'isHighlighted'));

        $harness->keys(Keys::CTRL_S);
        self::assertSame(
            ['custom' => 'kept', 'name' => 'PHP', 'isHighlighted' => true],
            $harness->api->requests[0]['body'],
        );

        // Once saved, they are not sent again
        $harness->keys(Keys::CTRL_S);
        self::assertSame('Nothing was changed', $harness->lines()[10]);
    }

    public function testTheObjectOfARelationIsChosenInTheTableOfItsResource(): void
    {
        $harness = new TuiHarness(80, 20);
        $users = ['meta' => ['page' => 1, 'totalPages' => 1, 'count' => 2], 'data' => [
            ['id' => 'u1', 'firstName' => 'Ada', 'lastName' => 'L', 'email' => 'ada@site.test'],
            ['id' => 'u2', 'firstName' => 'Bob', 'lastName' => 'M', 'email' => 'bob@site.test'],
        ]];
        $harness->api->queue(200, $users)->queue(200, $users)
            ->queue(200, self::document(['author' => ['id' => 'u2', 'name' => 'Bob M']] + self::CONTENT));
        $screen = $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], self::CONTENT);

        self::assertSame('> author            Ada L (u1)', self::line($harness, 'author'));
        self::assertSame('    Enter: choose · Backspace: none', $harness->lines()[3]);

        // A cancelled choice changes nothing
        $harness->keys(Keys::ENTER);
        self::assertInstanceOf(ListScreen::class, $harness->navigator->current());
        self::assertSame('Choose a user', $harness->lines()[0]);
        $harness->keys(Keys::ESCAPE);
        self::assertSame($screen, $harness->navigator->current());
        self::assertSame('> author            Ada L (u1)', self::line($harness, 'author'));

        $harness->keys(Keys::ENTER, Keys::DOWN, Keys::ENTER);
        self::assertSame('> author            bob@site.test (u2)', self::line($harness, 'author'));

        $harness->keys(Keys::CTRL_S);
        self::assertSame(
            ['GET /api/v1/admin/users?page=1', 'GET /api/v1/admin/users?page=1', 'PUT /api/v1/admin/content/c1'],
            $harness->api->calls(),
        );
        self::assertSame(['author' => 'u2'], $harness->api->requests[2]['body']);
    }

    public function testARelationCanBeEmptiedAndSeveralObjectsCanBeChosen(): void
    {
        $harness = new TuiHarness(80, 20);
        $tags = ['meta' => ['page' => 1, 'totalPages' => 1, 'count' => 2], 'data' => [
            ['id' => 't1', 'name' => 'PHP', 'slug' => 'php', 'isHighlighted' => true],
            ['id' => 't2', 'name' => 'Symfony', 'slug' => 'sf', 'isHighlighted' => false],
        ]];
        $harness->api->queue(200, $tags)->queue(200, self::document(self::CONTENT));
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], self::CONTENT);

        $harness->keys(Keys::BACKSPACE, Keys::TAB, Keys::TAB);
        self::assertSame('  author            (none)', self::line($harness, 'author'));
        self::assertSame('> tags              PHP (t1)', self::line($harness, 'tags'));

        $harness->keys(Keys::ENTER);
        self::assertSame('Choose the tag objects', $harness->lines()[0]);
        self::assertSame('> [x] t1    PHP      php   yes', $harness->lines()[3]);

        $harness->keys(Keys::DOWN, Keys::SPACE, Keys::ENTER);
        self::assertSame('> tags              PHP (t1), Symfony (t2)', self::line($harness, 'tags'));

        $harness->keys(Keys::CTRL_S);
        self::assertSame(['author' => null, 'tags' => ['t1', 't2']], $harness->api->requests[1]['body']);
    }

    public function testTheBlocksOfAContentAreEditedAndSentInTheSameRequestWhenItsTypeIsNotChanged(): void
    {
        $harness = new TuiHarness(80, 24);
        $harness->api->queue(200, self::document(['parts' => ['intro' => "!Hello\nworld", 'count' => '3']] + self::CONTENT));
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], self::CONTENT);

        self::assertSame('  intro (textarea)  Hello … (2 lines)', self::line($harness, 'intro (textarea)'));
        self::assertSame('  count (numeric)   3', self::line($harness, 'count (numeric)'));
        self::assertSame('  publish           [ ]', self::line($harness, 'publish'));
        self::assertSame([], $harness->api->calls());

        // author, type, tags, title, subtitle, slug, description, localeField, then the first block
        $harness->keys(...array_fill(0, 8, Keys::TAB));
        self::assertSame('> intro (textarea)', self::line($harness, 'intro (textarea)'));

        // Like a document opened in an editor, the cursor of a text on several lines is at its start
        $harness->type('!')->keys(Keys::TAB, Keys::TAB, Keys::SPACE, Keys::CTRL_S);

        self::assertSame(['PUT /api/v1/admin/content/c1'], $harness->api->calls());
        self::assertSame(['block_intro' => "!Hello\nworld", 'publish' => true], $harness->api->requests[0]['body']);
        self::assertSame('The content was saved', $harness->lines()[22]);
    }

    public function testTheTypeOfAContentIsFetchedWhenItsBlocksAreNotInTheDocument(): void
    {
        $harness = new TuiHarness(80, 24);
        $harness->api->queue(200, self::document(self::TYPE));
        $content = ['type' => ['id' => 'ty1', 'name' => 'Page'], 'publishedAt' => '2026-03-01'] + self::CONTENT;
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], $content);

        self::assertSame(['GET /api/v1/admin/type/ty1'], $harness->api->calls());
        self::assertSame('  count (numeric)   3', self::line($harness, 'count (numeric)'));

        $harness->keys(...array_fill(0, 10, Keys::TAB));
        self::assertSame('> publish           [ ]', self::line($harness, 'publish'));
        self::assertSame('    Publish the content again (published at 2026-03-01)', $harness->lines()[13]);
    }

    public function testAContentWhoseTypeCanNotBeLoadedIsEditedWithoutItsBlocks(): void
    {
        $harness = new TuiHarness(80, 24);
        $harness->api->queue(404, ['meta' => ['error' => true], 'data' => ['message' => 'Unknown type']]);
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], ['type' => 'ty1'] + self::CONTENT);

        self::assertStringNotContainsString('intro (textarea)', $harness->screen());
        self::assertSame('  publish      [ ]', self::line($harness, 'publish'));
        self::assertStringContainsString('The blocks of the type can not be loaded: Unknown type', $harness->lines()[22]);
    }

    public function testChoosingTheTypeOfANewContentGivesItsBlocksSentWithASecondRequest(): void
    {
        $harness = new TuiHarness(80, 24);
        $types = ['meta' => ['page' => 1, 'totalPages' => 1, 'count' => 1], 'data' => [['id' => 'ty1', 'name' => 'Page']]];
        $created = ['id' => 'c9', 'title' => 'New', 'type' => self::TYPE, 'author' => null, 'tags' => [], 'parts' => []];
        $harness->api
            ->queue(200, $types)
            ->queue(200, self::document(self::TYPE))
            ->queue(302, [], ['Location' => '/api/v1/admin/content/c9'])
            ->queue(200, self::document($created))
            ->queue(200, self::document(['parts' => ['count' => '7']] + $created));
        $this->open($harness, 'content', FormMode::Create);

        self::assertStringNotContainsString('count (numeric)', $harness->screen());

        // The title, then the type: the typed title is kept when the blocks are added to the form
        $harness->keys(Keys::TAB, Keys::TAB, Keys::TAB)->type('New')->keys(Keys::SHIFT_TAB, Keys::SHIFT_TAB, Keys::ENTER, Keys::ENTER);
        self::assertSame('> type              Page (ty1)', self::line($harness, 'type'));
        self::assertSame('  title             New', self::line($harness, 'title'));
        self::assertSame('  count (numeric)', self::line($harness, 'count (numeric)'));

        // tags, title, subtitle, slug, description, localeField, intro, count
        $harness->keys(...array_fill(0, 8, Keys::TAB));
        $harness->type('7')->keys(Keys::CTRL_S);

        self::assertSame(
            [
                'GET /api/v1/admin/types?page=1',
                'GET /api/v1/admin/type/ty1',
                'POST /api/v1/admin/content/new',
                'GET /api/v1/admin/content/c9',
                'PUT /api/v1/admin/content/c9',
            ],
            $harness->api->calls(),
        );
        self::assertSame(['type' => 'ty1', 'title' => 'New'], $harness->api->requests[2]['body']);
        self::assertSame(['block_count' => '7'], $harness->api->requests[4]['body']);
        self::assertSame('content · edit New', $harness->lines()[0]);
        self::assertSame('  count (numeric)   7', self::line($harness, 'count (numeric)'));
        self::assertSame('The content was created', $harness->lines()[22]);
    }

    public function testRemovingTheTypeOfAContentRemovesItsBlocks(): void
    {
        $harness = new TuiHarness(80, 24);
        $harness->api->queue(200, ['meta' => ['page' => 1, 'totalPages' => 1, 'count' => 0], 'data' => []]);
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], self::CONTENT);

        $harness->keys(Keys::TAB, Keys::ENTER, Keys::BACKSPACE);

        self::assertSame('> type         (none)', self::line($harness, 'type'));
        self::assertStringNotContainsString('intro (textarea)', $harness->screen());
    }

    public function testTheTypeGivenOnTheCommandLineGivesTheBlocksAndTheirValues(): void
    {
        $harness = new TuiHarness(80, 24);
        $harness->api->queue(200, self::document(self::TYPE));
        $prefill = new Payload(['type' => 'ty1', 'title' => 'From CLI'], ['count' => '5', 'other' => 'x'], true);
        $this->open($harness, 'content', FormMode::Create, [], [], $prefill);

        self::assertSame(['GET /api/v1/admin/type/ty1'], $harness->api->calls());
        self::assertSame('  type              ty1', self::line($harness, 'type'));
        self::assertSame('  title             From CLI', self::line($harness, 'title'));
        self::assertSame('  count (numeric)   5', self::line($harness, 'count (numeric)'));
        self::assertSame('  publish           [x]', self::line($harness, 'publish'));
    }

    public function testNoTypeIsLoadedForAnEmptyTypeOfTheCommandLine(): void
    {
        $harness = new TuiHarness(80, 24);
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], self::CONTENT, new Payload(['type' => null], [], false));

        self::assertSame([], $harness->api->calls());
        self::assertSame('  type         (none)', self::line($harness, 'type'));
        self::assertStringNotContainsString('intro (textarea)', $harness->screen());
    }

    public function testAfterAPartialFailureTheFormGoesOnAsAnUpdateOfTheCreatedObject(): void
    {
        $harness = new TuiHarness(80, 24);
        $created = ['id' => 'c9', 'title' => 'New', 'type' => self::TYPE, 'parts' => []];
        $harness->api
            ->queue(200, self::document(self::TYPE))
            ->queue(302, [], ['Location' => '/api/v1/admin/content/c9'])
            ->queue(200, self::document($created))
            ->queue(400, ['meta' => ['errors' => true], 'data' => ['.block_count' => 'Not a number']])
            ->queue(200, self::document(['parts' => ['count' => '8']] + $created));
        $prefill = new Payload(['type' => 'ty1', 'title' => 'New'], ['count' => 'abc'], false);
        $below = new RecordingScreen();
        $harness->open($below);
        $this->open($harness, 'content', FormMode::Create, [], [], $prefill);

        $harness->keys(Keys::CTRL_S);

        self::assertSame('content · edit c9', $harness->lines()[0]);
        self::assertSame('> count (numeric)   abc', self::line($harness, 'count (numeric)'));
        self::assertSame('    ! Not a number', self::line($harness, '    !'));
        self::assertSame(
            'The object was saved, but not its blocks: Validation failed',
            $harness->lines()[22],
        );

        // Only the blocks are sent again, on the created object
        $harness->keys(Keys::BACKSPACE, Keys::BACKSPACE, Keys::BACKSPACE)->type('8')->keys(Keys::CTRL_S);
        self::assertSame(
            [
                'GET /api/v1/admin/type/ty1',
                'POST /api/v1/admin/content/new',
                'GET /api/v1/admin/content/c9',
                'PUT /api/v1/admin/content/c9',
                'PUT /api/v1/admin/content/c9',
            ],
            $harness->api->calls(),
        );
        self::assertSame(['block_count' => '8'], $harness->api->requests[4]['body']);
        self::assertSame('The content was saved', $harness->lines()[22]);

        $harness->keys(Keys::ESCAPE);
        self::assertSame([FormScreen::CHANGED], $below->results);
    }

    public function testAResultWhichIsNotAChoiceIsIgnored(): void
    {
        $harness = new TuiHarness(80, 20);
        $screen = $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], self::CONTENT);

        $screen->resume([['id' => 'u9', 'label' => 'Nobody']]);
        $screen->resume('anything');
        $harness->render();

        self::assertSame('> author            Ada L (u1)', self::line($harness, 'author'));
    }

    public function testAContentWhoseTypeHasNoIdIsEditedWithoutBlocks(): void
    {
        $harness = new TuiHarness(80, 20);
        $this->open($harness, 'content', FormMode::Edit, ['id' => 'c1'], ['type' => ['name' => 'Page']] + self::CONTENT);

        self::assertSame([], $harness->api->calls());
        self::assertStringNotContainsString('intro (textarea)', $harness->screen());
        self::assertSame('  type         (none)', self::line($harness, 'type'));
    }

    public function testARelationToAnUnknownResourceCanNotBeChosen(): void
    {
        $harness = new TuiHarness(80, 12);
        $definition = new ResourceDefinition(
            'note',
            'note',
            'note',
            'notes',
            [new FieldDefinition('owner', FieldKind::Id, 'Owner of the note', target: 'ghost')],
            [Operation::Update],
        );
        $screen = new FormScreen($harness->session, $definition, ['id' => 'n1'], [], FormMode::Edit, ['id' => 'n1']);
        $harness->open($screen);

        $harness->keys(Keys::ENTER);

        self::assertSame($screen, $harness->navigator->current());
        self::assertSame('> owner  (none)', self::line($harness, 'owner'));
        self::assertSame([], $harness->api->calls());
    }
}
