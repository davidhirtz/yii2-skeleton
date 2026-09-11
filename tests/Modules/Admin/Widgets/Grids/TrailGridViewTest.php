<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\TrailGridView;
use Hirtz\Skeleton\Test\TestCase;
use Stringable;

class TrailGridViewTest extends TestCase
{
    public function testAStructuredValueIsCreatedAsItsOwnRows(): void
    {
        $content = (string)TestTrailGridView::make()->createdAttributeContent([
            '1. Label' => 'One',
            '1. Url' => 'https://one.example.com',
        ]);

        self::assertSame(
            '<table class="trail-values-table table"><tbody>'
            . '<tr><td class="trail-property-col">1. Label</td><td>One</td></tr>'
            . '<tr><td class="trail-property-col">1. Url</td><td>https://one.example.com</td></tr>'
            . '</tbody></table>',
            $content
        );
    }

    public function testAListIsStillCreatedAsAnUnorderedList(): void
    {
        $content = (string)TestTrailGridView::make()->createdAttributeContent(['One', 'Two']);

        self::assertStringStartsWith('<ul>', $content);
        self::assertStringContainsString('<li>One</li>', $content);
    }

    public function testAStructuredValueIsUpdatedAsAttributeOldAndNewColumns(): void
    {
        $content = (string)TestTrailGridView::make()->updatedAttributeContent(
            ['1. Label' => 'One', '1. Url' => 'https://one.example.com'],
            ['1. Label' => 'Uno', '1. Url' => 'https://one.example.com', '2. Label' => 'Two'],
        );

        self::assertStringStartsWith('<table class="trail-diff-table trail-diff-values table">', $content);

        // Only the labels that differ, with the unchanged url left out.
        self::assertSame(
            '<tr><td class="trail-property-col">1. Label</td><td class="old">One</td><td class="new">Uno</td></tr>'
            . '<tr><td class="trail-property-col">2. Label</td><td class="old"></td><td class="new">Two</td></tr>',
            $this->getBody($content)
        );
    }

    public function testAStructuredValueAddedToAnEmptyAttributeIsUpdatedAsRows(): void
    {
        $content = (string)TestTrailGridView::make()->updatedAttributeContent(null, ['Title' => 'Title']);

        self::assertSame(
            '<tr><td class="trail-property-col">Title</td><td class="old"></td><td class="new">Title</td></tr>',
            $this->getBody($content)
        );
    }

    public function testAStructuredValueEscapesItsContent(): void
    {
        $content = (string)TestTrailGridView::make()->updatedAttributeContent([], ['Title' => '<b>Title</b>']);

        self::assertStringContainsString('<td class="new">&lt;b&gt;Title&lt;/b&gt;</td>', $content);
    }

    public function testAScalarValueIsStillDiffed(): void
    {
        $content = (string)TestTrailGridView::make()->updatedAttributeContent('One', 'Two');

        self::assertStringContainsString('trail-diff-table', $content);
        self::assertStringNotContainsString('trail-diff-values', $content);
    }

    public function testTheDeletedUserLinkIsParsedByTheTrailIndex(): void
    {
        $trail = Trail::create();
        $trail->user_id = 9;

        $route = TestTrailGridView::make()->userTrailRoute($trail);

        self::assertSame([User::class, '9'], explode('@', $route['model']));
    }

    protected function getBody(string $content): string
    {
        return (string)preg_replace('/^.*<tbody>|<\/tbody>.*$/s', '', $content);
    }
}

class TestTrailGridView extends TrailGridView
{
    public function createdAttributeContent(mixed $value): string|Stringable|null
    {
        return $this->getCreatedAttributeContent($value);
    }

    public function updatedAttributeContent(mixed $oldValue, mixed $newValue): string|Stringable
    {
        return $this->getUpdatedAttributeContent($oldValue, $newValue);
    }

    public function userTrailRoute(Trail $trail): array
    {
        return $this->getUserTrailRoute($trail);
    }
}
