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
use Teknoo\East\Website\Tools\Http\ApiResponse;
use Teknoo\East\Website\Tools\Http\Json;
use Teknoo\East\Website\Tools\Tui\Screen\ConfirmScreen;
use Teknoo\East\Website\Tools\Tui\Screen\FormScreen;
use Teknoo\East\Website\Tools\Tui\Screen\ListMode;
use Teknoo\East\Website\Tools\Tui\Screen\ListScreen;
use Teknoo\Tests\East\Website\Tools\Support\Keys;
use Teknoo\Tests\East\Website\Tools\Support\RecordingScreen;
use Teknoo\Tests\East\Website\Tools\Support\TuiHarness;

/**
 * Tests of the table of the objects of a resource, to manage them or to choose the objects of a relation
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListScreen::class)]
class ListScreenTest extends TestCase
{
    private const array TAGS = [
        ['id' => 't1', 'name' => 'PHP', 'slug' => 'php', 'isHighlighted' => true],
        ['id' => 't2', 'name' => 'Symfony', 'slug' => 'symfony', 'isHighlighted' => false],
    ];

    /**
     * @param list<array<mixed>> $data
     * @return array<mixed>
     */
    private static function page(array $data, int $page = 1, int $totalPages = 1, ?int $count = null): array
    {
        return ['meta' => ['page' => $page, 'totalPages' => $totalPages, 'count' => $count ?? count($data)], 'data' => $data];
    }

    /**
     * @param array<mixed> $body
     */
    private static function response(array $body): ApiResponse
    {
        return new ApiResponse(200, [], Json::encode($body), $body);
    }

    /**
     * @param array<string, string> $params
     * @param array<string, scalar> $query
     * @param array<mixed>|null $first
     */
    private function browse(
        TuiHarness $harness,
        string $resource = 'tag',
        ?array $first = null,
        array $params = [],
        array $query = [],
    ): ListScreen {
        $screen = new ListScreen(
            $harness->session,
            $harness->definition($resource),
            $params,
            $query,
            ListMode::Browse,
            [],
            self::response($first ?? self::page(self::TAGS, 1, 3, 25)),
        );
        $harness->open($screen);

        return $screen;
    }

    public function testTheFirstPageIsDisplayedWithoutAnyRequest(): void
    {
        $harness = new TuiHarness(80, 10);
        $screen = $this->browse($harness);

        self::assertSame(
            [
                'tag · list',
                '  id    name     slug     isHighlighted',
                '  ────  ───────  ───────  ─────────────',
                '> t1    PHP      php      yes',
                '  t2    Symfony  symfony  no',
                '',
                '',
                'page 1/3 · 25 item(s)',
                '',
                'Enter view · e edit · n new · d delete · ←→ page · r reload · Esc back · q quit',
            ],
            $harness->lines(),
        );
        self::assertSame([], $harness->api->calls());
        self::assertSame('tag · list', $screen->title());
    }

    public function testTheColumnsOfAResourceAndTheLabelsOfTheNestedObjects(): void
    {
        $harness = new TuiHarness(100, 8);
        $this->browse($harness, 'content', self::page([[
            'id' => 'c1',
            'updatedAt' => '2026-02-01',
            'publishedAt' => null,
            'author' => ['id' => 'u1', 'name' => 'Ada L'],
            'title' => "Home\x1b[31m",
            'subtitle' => 'Welcome',
            'slug' => 'home',
            'type' => ['id' => 'ty1', 'name' => 'Page'],
            'tags' => [['id' => 't1', 'name' => 'PHP'], ['id' => 't2', 'name' => 'Symfony']],
        ]]));

        $lines = $harness->lines();
        self::assertSame('  id    title     slug  type  author  tags          publishedAt', $lines[1]);
        self::assertSame('> c1    Home[31m  home  Page  Ada L   PHP, Symfony', $lines[3]);
    }

    public function testTheHintsFollowTheOperationsOfTheResource(): void
    {
        $harness = new TuiHarness(80, 8);

        $media = $this->browse($harness, 'media', self::page([['id' => 'm1', 'name' => 'logo.png', 'length' => 12]]));
        self::assertSame('Enter view · d delete · ←→ page · r reload · Esc back · q quit', $media->hints());
        self::assertSame('  id    name      length', $harness->lines()[1]);

        $comment = $this->browse($harness, 'comment', self::page([]), ['post-id' => 'p1']);
        self::assertSame('Enter view · e edit · d delete · ←→ page · r reload · Esc back · q quit', $comment->hints());
        self::assertSame('No comment of a blog post', $harness->lines()[1]);
    }

    public function testThePagesAreLoadedWithTheQueryOfTheList(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api
            ->queue(200, self::page([['id' => 't3', 'name' => 'Doctrine', 'slug' => 'doctrine', 'isHighlighted' => false]], 2, 3, 25))
            ->queue(200, self::page([['id' => 't5', 'name' => 'Twig', 'slug' => 'twig', 'isHighlighted' => false]], 3, 3, 25))
            ->queue(200, self::page([['id' => 't3', 'name' => 'Doctrine', 'slug' => 'doctrine', 'isHighlighted' => false]], 2, 3, 25))
            ->queue(200, self::page([['id' => 't3', 'name' => 'ORM', 'slug' => 'doctrine', 'isHighlighted' => false]], 2, 3, 25));

        $this->browse($harness, 'tag', null, [], ['order' => 'name', 'direction' => 'ASC']);

        $harness->keys(Keys::LEFT);
        self::assertSame([], $harness->api->calls());

        $harness->keys(Keys::RIGHT);
        self::assertSame('> t3    Doctrine  doctrine  no', $harness->lines()[3]);
        self::assertSame('page 2/3 · 25 item(s)', $harness->lines()[7]);

        $harness->keys(Keys::RIGHT, Keys::RIGHT);
        self::assertSame('> t5    Twig  twig  no', $harness->lines()[3]);

        $harness->keys(Keys::LEFT, 'r');
        self::assertSame('> t3    ORM   doctrine  no', $harness->lines()[3]);

        self::assertSame(
            [
                'GET /api/v1/admin/tags?page=2&order=name&direction=ASC',
                'GET /api/v1/admin/tags?page=3&order=name&direction=ASC',
                'GET /api/v1/admin/tags?page=2&order=name&direction=ASC',
                'GET /api/v1/admin/tags?page=2&order=name&direction=ASC',
            ],
            $harness->api->calls(),
        );
    }

    public function testAPageWhichCanNotBeLoadedLeavesTheTableAsItIs(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(500, ['meta' => ['error' => true], 'data' => ['message' => 'Database is down']]);
        $this->browse($harness);

        $harness->keys(Keys::RIGHT);

        $lines = $harness->lines();
        self::assertSame('> t1    PHP      php      yes', $lines[3]);
        self::assertSame('page 1/3 · 25 item(s)', $lines[7]);
        self::assertStringContainsString('Database is down', $lines[8]);
    }

    public function testEnterOpensTheSelectedObjectReadOnly(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(200, ['meta' => ['id' => 't2'], 'data' => self::TAGS[1] + ['createdAt' => '2026-01-01']]);
        $this->browse($harness, 'tag', null, [], ['locale' => 'fr', 'order' => 'name']);

        $harness->keys(Keys::DOWN, Keys::ENTER);

        self::assertInstanceOf(FormScreen::class, $harness->navigator->current());
        self::assertSame('tag · Symfony', $harness->lines()[0]);
        self::assertSame('  name           Symfony', $harness->lines()[2]);
        self::assertSame(['GET /api/v1/admin/tag/t2?locale=fr'], $harness->api->calls());
    }

    public function testAnObjectWhichCanNotBeFetchedIsNotOpened(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(404, ['meta' => ['error' => true], 'data' => ['message' => 'Gone']])
            ->queue(404, ['meta' => ['error' => true], 'data' => ['message' => 'Gone']]);
        $screen = $this->browse($harness);

        $harness->keys(Keys::ENTER);
        self::assertSame($screen, $harness->navigator->current());
        self::assertStringContainsString('Gone', $harness->lines()[8]);

        $harness->keys('e');
        self::assertSame($screen, $harness->navigator->current());
        self::assertCount(2, $harness->api->requests);
    }

    public function testTheSelectedObjectIsEditedAndANewOneIsCreatedInAForm(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(200, ['meta' => ['id' => 't1'], 'data' => self::TAGS[0]]);
        $screen = $this->browse($harness);

        $harness->keys('e');
        self::assertSame('tag · edit PHP', $harness->lines()[0]);

        $harness->keys(Keys::ESCAPE);
        self::assertSame($screen, $harness->navigator->current());

        $harness->keys('n');
        self::assertSame('tag · new', $harness->lines()[0]);
        self::assertSame(['GET /api/v1/admin/tag/t1'], $harness->api->calls());
    }

    public function testTheOperationsNotSupportedByTheResourceAreIgnored(): void
    {
        $harness = new TuiHarness(80, 10);
        $media = $this->browse($harness, 'media', self::page([['id' => 'm1', 'name' => 'logo.png', 'length' => 12]]));

        $harness->keys('e', 'n', Keys::SPACE, Keys::BACKSPACE);

        self::assertSame($media, $harness->navigator->current());
        self::assertSame([], $harness->api->calls());
    }

    public function testNothingIsOpenedFromAnEmptyTableNorFromAnObjectWithoutId(): void
    {
        $harness = new TuiHarness(80, 10);
        $empty = $this->browse($harness, 'tag', self::page([]));
        $harness->keys(Keys::ENTER, 'e', 'd');
        self::assertSame($empty, $harness->navigator->current());

        $withoutId = $this->browse($harness, 'tag', ['data' => [['name' => 'PHP'], 'not an object']]);
        self::assertSame('page 1/1 · 1 item(s)', $harness->lines()[7]);
        $harness->keys(Keys::ENTER, 'e', 'd');
        self::assertSame($withoutId, $harness->navigator->current());
        self::assertSame([], $harness->api->calls());
    }

    public function testADeletionIsConfirmedThenThePageIsReloaded(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(200, ['meta' => ['deleted' => 'success'], 'data' => []])
            ->queue(200, self::page([self::TAGS[1]], 1, 3, 24));
        $screen = $this->browse($harness);

        $harness->keys('d');
        self::assertInstanceOf(ConfirmScreen::class, $harness->navigator->current());
        self::assertSame('tag · delete', $harness->lines()[0]);
        self::assertSame('Delete the tag "PHP"?', $harness->lines()[2]);

        $harness->keys('n');
        self::assertSame($screen, $harness->navigator->current());
        self::assertSame([], $harness->api->calls());

        $harness->keys('d', 'y');
        self::assertSame($screen, $harness->navigator->current());
        self::assertSame(['DELETE /api/v1/admin/tag/t1/delete', 'GET /api/v1/admin/tags?page=1'], $harness->api->calls());
        self::assertSame('> t2    Symfony  symfony  no', $harness->lines()[3]);
        self::assertSame('The tag "t1" was deleted', $harness->lines()[8]);
    }

    public function testAFailedDeletionIsReported(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(403, ['meta' => ['error' => true], 'data' => ['message' => 'Forbidden']]);
        $this->browse($harness, 'comment', self::page([['id' => 'c1', 'title' => 'Hi']]), ['post-id' => 'p1']);

        $harness->keys('d', 'y');

        self::assertSame(['DELETE /api/v1/admin/post/p1/comment/c1/delete'], $harness->api->calls());
        self::assertStringContainsString('Forbidden', $harness->lines()[8]);
        self::assertSame('> c1    Hi', $harness->lines()[3]);
    }

    public function testThePageIsReloadedWhenAnObjectWasChangedInItsForm(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(200, self::page([['id' => 't1', 'name' => 'PHP 8', 'slug' => 'php', 'isHighlighted' => true]]));
        $screen = $this->browse($harness);

        $screen->resume(null);
        self::assertSame([], $harness->api->calls());

        $screen->resume(FormScreen::CHANGED);
        $harness->render();
        self::assertSame(['GET /api/v1/admin/tags?page=1'], $harness->api->calls());
        self::assertSame('> t1    PHP 8  php   yes', $harness->lines()[3]);
    }

    public function testEscapeClosesTheTableAndQStopsTheInterface(): void
    {
        $harness = new TuiHarness(80, 10);
        $below = new RecordingScreen();
        $harness->open($below);
        $this->browse($harness);

        $harness->keys(Keys::ESCAPE);
        self::assertSame([null], $below->results);
        self::assertFalse($harness->navigator->isStopped());

        $this->browse($harness);
        $harness->keys('q');
        self::assertTrue($harness->navigator->isStopped());
    }

    public function testATableOpenedWithoutItsFirstPageLoadsIt(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api->queue(200, self::page(self::TAGS, 4, 5, 50))->queue(404, ['meta' => ['error' => true], 'data' => ['message' => 'No page']]);

        $harness->open(new ListScreen($harness->session, $harness->definition('tag'), [], ['page' => 4]));
        self::assertSame('page 4/5 · 50 item(s)', $harness->lines()[7]);

        $harness->open(new ListScreen($harness->session, $harness->definition('tag')));
        self::assertSame('No tag', $harness->lines()[1]);
        self::assertStringContainsString('No page', $harness->lines()[8]);

        self::assertSame(['GET /api/v1/admin/tags?page=4', 'GET /api/v1/admin/tags?page=1'], $harness->api->calls());
    }

    public function testOneObjectIsChosenForARelation(): void
    {
        $harness = new TuiHarness(80, 10);
        $below = new RecordingScreen();
        $users = self::page([
            ['id' => 'u1', 'firstName' => 'Ada', 'lastName' => 'L', 'email' => 'ada@site.test'],
            ['id' => 'u2', 'firstName' => 'Bob', 'lastName' => 'M', 'email' => 'bob@site.test'],
        ]);
        $pick = fn (): ListScreen => new ListScreen(
            $harness->session,
            $harness->definition('user'),
            [],
            [],
            ListMode::PickOne,
            [],
            self::response($users),
        );

        $harness->open($below)->open($screen = $pick());
        self::assertSame('Choose a user', $harness->lines()[0]);
        self::assertSame('Enter choose · Backspace none · ←→ page · Esc cancel', $screen->hints());

        // The keys of the management of the objects do nothing while choosing
        $harness->keys('e', 'n', 'd', 'q', Keys::SPACE);
        self::assertSame($screen, $harness->navigator->current());

        $harness->keys(Keys::DOWN, Keys::ENTER);
        self::assertSame([[['id' => 'u2', 'label' => 'bob@site.test']]], $below->results);

        $harness->open($pick())->keys(Keys::BACKSPACE);
        self::assertSame([], $below->results[1]);

        $harness->open($pick())->keys(Keys::ESCAPE);
        self::assertNull($below->results[2]);
        self::assertSame([], $harness->api->calls());
    }

    public function testSeveralObjectsAreChosenAcrossThePages(): void
    {
        $harness = new TuiHarness(80, 10);
        $harness->api
            ->queue(200, self::page([['id' => 't3', 'name' => 'Twig', 'slug' => 'twig', 'isHighlighted' => false]], 2, 2, 3))
            ->queue(200, self::page(self::TAGS, 1, 2, 3));
        $below = new RecordingScreen();
        $screen = new ListScreen(
            $harness->session,
            $harness->definition('tag'),
            [],
            [],
            ListMode::PickMany,
            [['id' => 't2', 'label' => 'Symfony'], ['id' => 't9', 'label' => 'Elsewhere']],
            self::response(self::page(self::TAGS, 1, 2, 3)),
        );

        $harness->open($below)->open($screen);
        self::assertSame('Choose the tag objects', $harness->lines()[0]);
        self::assertSame('Space check · Enter confirm · Backspace none · ←→ page · Esc cancel', $screen->hints());
        self::assertSame('> [ ] t1    PHP      php      yes', $harness->lines()[3]);
        self::assertSame('  [x] t2    Symfony  symfony  no', $harness->lines()[4]);

        // Check the first one, uncheck the second one, then check the one of the second page
        $harness->keys(Keys::SPACE, Keys::DOWN, Keys::SPACE, Keys::RIGHT, Keys::SPACE);
        self::assertSame('> [x] t3    Twig  twig  no', $harness->lines()[3]);

        $harness->keys(Keys::LEFT);
        self::assertSame('> [x] t1    PHP      php      yes', $harness->lines()[3]);
        self::assertSame('  [ ] t2    Symfony  symfony  no', $harness->lines()[4]);

        $harness->keys(Keys::ENTER);
        self::assertSame(
            [[
                ['id' => 't9', 'label' => 'Elsewhere'],
                ['id' => 't1', 'label' => 'PHP'],
                ['id' => 't3', 'label' => 'Twig'],
            ]],
            $below->results,
        );
    }

    public function testTheChoiceOfSeveralObjectsCanBeEmptiedOrCancelled(): void
    {
        $harness = new TuiHarness(80, 10);
        $below = new RecordingScreen();
        $pick = fn (): ListScreen => new ListScreen(
            $harness->session,
            $harness->definition('tag'),
            [],
            [],
            ListMode::PickMany,
            [['id' => 't1', 'label' => 'PHP']],
            self::response(self::page(self::TAGS)),
        );

        $harness->open($below)->open($pick())->keys(Keys::BACKSPACE);
        self::assertSame('> [ ] t1    PHP      php      yes', $harness->lines()[3]);
        $harness->keys(Keys::ENTER);
        self::assertSame([], $below->results[0]);

        $harness->open($pick())->keys(Keys::SPACE, Keys::ESCAPE);
        self::assertNull($below->results[1]);
    }
}
