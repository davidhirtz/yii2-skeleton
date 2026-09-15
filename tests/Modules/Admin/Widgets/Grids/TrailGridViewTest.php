<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\TrailGridView;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Stringable;
use Yii;

class TrailGridViewTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'en-US';
    }

    public function testAnAssignTrailRendersTheItemsCurrentLabel(): void
    {
        Yii::$app->language = 'de';

        $trail = Trail::create();
        $trail->type = Trail::TYPE_ASSIGN;
        $trail->data = ['name' => User::AUTH_USER, 'type' => 2];

        self::assertStringContainsString('<ins>Benutzer verwalten</ins>', TestTrailGridView::make()->dataContent($trail));
    }

    /**
     * A create trail carries the record's attributes as they are, so a JSON column reaches the nested table with
     * whatever it holds — an `int` included, which used to be a `TypeError` on the whole trail page.
     */
    public function testACreateTrailRendersANestedHashOfAnyScalar(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_CREATE;
        $trail->data = [
            'custom_attributes' => [
                'position' => 3,
                'subtitle' => 'A subtitle',
                'enabled' => true,
            ],
        ];

        $html = TestTrailGridView::make()->dataContent($trail);

        self::assertStringContainsString('trail-values-table', $html);
        self::assertStringContainsString('>3<', $html);
        self::assertStringContainsString('>A subtitle<', $html);
    }

    public function testACreateTrailRendersAListOfValues(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_CREATE;
        $trail->data = ['category_ids' => [1, 2, 3]];

        $html = TestTrailGridView::make()->dataContent($trail);

        self::assertStringContainsString('<li>1</li>', $html);
        self::assertStringContainsString('<li>3</li>', $html);
    }

    /**
     * The type is a range attribute, resolved through the model's definitions — the declaration it used to read is
     * indexed by offset and handed the formatter a {@see \Hirtz\Skeleton\Models\Types\Type} to subscript.
     */
    public function testAnUpdateTrailRendersTheNamesOfAChangedType(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_UPDATE;
        $trail->model_class = Redirect::class;
        $trail->data = ['type' => [Redirect::TYPE_MOVED_PERMANENTLY, Redirect::TYPE_FOUND]];

        // The differ marks up the shared prefix, so the names are only contiguous without the tags.
        $text = strip_tags(TestTrailGridView::make()->dataContent($trail));

        self::assertStringContainsString('301 - Moved permanently', $text);
        self::assertStringContainsString('302 - Temporary redirect', $text);
    }

    public function testARevokeTrailOfADeletedItemFallsBackToItsName(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_REVOKE;
        $trail->data = ['name' => 'entryUpdate', 'type' => 2];

        self::assertStringContainsString('<del>entryUpdate</del>', TestTrailGridView::make()->dataContent($trail));
    }

    public function testAnAssignTrailWrittenBeforeTheChangeStillRenders(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_ASSIGN;
        $trail->message = 'Update users';

        self::assertStringContainsString('<ins>Update users</ins>', TestTrailGridView::make()->dataContent($trail));
    }

    public function testAnOrderTrailIsRenderedInTheReadersLanguage(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_ORDER;
        $trail->message = Message::make('skeleton', 'TRAIL_ORDERED')->toJson();

        self::assertSame('Ordered', TestTrailGridView::make()->dataContent($trail));

        Yii::$app->language = 'de';
        self::assertSame('Sortiert', TestTrailGridView::make()->dataContent($trail));
    }

    public function testADeleteIsRenderedInTheReadersLanguage(): void
    {
        $trail = Trail::create();
        $trail->type = Trail::TYPE_DELETE;
        $trail->model_class = User::class;

        self::assertStringContainsString('was deleted', TestTrailGridView::make()->dataContent($trail));

        Yii::$app->language = 'de';
        self::assertStringContainsString('wurde gelöscht', TestTrailGridView::make()->dataContent($trail));
    }

    public function testAChildDeleteIsRenderedInTheReadersLanguage(): void
    {
        Yii::$app->language = 'de';

        $trail = Trail::create();
        $trail->type = Trail::TYPE_CHILD_DELETE;

        self::assertStringContainsString('Gelöscht</div> gelöscht', TestTrailGridView::make()->dataContent($trail));
    }

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

    /**
     * @return array<int|string, mixed>
     */
    public function userTrailRoute(Trail $trail): array
    {
        return $this->getUserTrailRoute($trail);
    }

    public function dataContent(Trail $trail): string
    {
        return (string)$this->getDataColumnContent($trail);
    }
}
