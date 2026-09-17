<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Navs;

use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Override;
use Yii;
use yii\base\Model;

class ModelHeaderTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(ModelHeaderRecord::tableName(), [
                'id' => 'pk',
                'type' => 'tinyint null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(ModelHeaderRecord::tableName())
            ->execute();
    }

    public function testTheTitleIsTheRecordAndLinksToItsRoute(): void
    {
        $html = ModelHeader::make()
            ->model($this->createNode('About'))
            ->render();

        self::assertStringContainsString('<h1><a href="/admin/test/update?name=About">About</a></h1>', $html);
    }

    public function testTheRecordWithoutARouteIsNotLinked(): void
    {
        $node = $this->createNode('About');
        $node->hasRoute = false;

        $html = ModelHeader::make()
            ->model($node)
            ->render();

        self::assertStringContainsString('<h1>About</h1>', $html);
    }

    public function testThePathListsTheAncestorsNearestLast(): void
    {
        $html = ModelHeader::make()
            ->model($this->createChain('About', 'Section 3', 'hero.jpg'))
            ->render();

        self::assertStringContainsString('<ol class="header-path small">', $html);
        self::assertStringContainsString('<a class="header-path-link" href="/admin/test/update?name=About">About</a>', $html);

        self::assertLessThan(
            strpos($html, 'Section 3'),
            strpos($html, 'About'),
            'The nearest ancestor is rendered last.',
        );

        self::assertStringNotContainsString('header-path-link" href="/admin/test/update?name=hero.jpg"', $html);
    }

    public function testThePathRendersTheAncestorIcon(): void
    {
        $parent = $this->createNode('About');
        $parent->adminIcon = 'star';

        $html = ModelHeader::make()
            ->model($this->createNode('Section 3', $parent))
            ->render();

        self::assertStringContainsString('<span class="header-path-icon fas fa-star"></span>', $html);
    }

    public function testAFourthAncestorCollapsesIntoANonLinkedEllipsis(): void
    {
        $model = $this->createChain('Root', 'About', 'Section 3', 'hero.jpg', 'Hotspot 2');

        $html = ModelHeader::make()
            ->model($model)
            ->render();

        self::assertStringContainsString('<li class="header-path-item">…</li>', $html);
        self::assertStringNotContainsString('name=Root', $html);
        self::assertStringContainsString('name=About', $html);
        self::assertStringContainsString('name=Section+3', $html);
        self::assertStringContainsString('name=hero.jpg', $html);
    }

    public function testAModelWithoutTypesGetsNoSubtitle(): void
    {
        $html = ModelHeader::make()
            ->model($this->createNode('About'))
            ->render();

        self::assertStringNotContainsString('header-subtitle', $html);
    }

    public function testATypedModelGetsItsTypeNameAsSubtitle(): void
    {
        $html = ModelHeader::make()
            ->model(ModelHeaderRecord::instantiateByType(ModelHeaderRecord::TYPE_DEFAULT))
            ->render();

        self::assertStringContainsString('<h2 class="header-subtitle">Test type</h2>', $html);
    }

    public function testTheBreadcrumbsAlternateIndexCrumbAndRecord(): void
    {
        $model = $this->createChain('About', 'Section 3', 'hero.jpg');
        $model->indexBreadcrumb = new Breadcrumb('Assets', ['/admin/test/asset']);

        ModelHeader::make()
            ->model($model)
            ->render();

        self::assertSame(
            [
                ['Index of About', '/admin/test/index?name=About'],
                ['About', ['/admin/test/update', 'name' => 'About']],
                ['Index of Section 3', '/admin/test/index?name=Section 3'],
                ['Section 3', ['/admin/test/update', 'name' => 'Section 3']],
                ['Assets', ['/admin/test/asset']],
            ],
            $this->getViewBreadcrumbs(),
        );
    }

    public function testTheBreadcrumbsSkipANullIndexCrumb(): void
    {
        $model = $this->createChain('About', 'Section 3', 'hero.jpg');
        $model->getAdminParent()->indexBreadcrumb = null;

        ModelHeader::make()
            ->model($model)
            ->render();

        self::assertSame(
            [
                ['Index of About', '/admin/test/index?name=About'],
                ['About', ['/admin/test/update', 'name' => 'About']],
                ['Section 3', ['/admin/test/update', 'name' => 'Section 3']],
                ['Index of hero.jpg', '/admin/test/index?name=hero.jpg'],
            ],
            $this->getViewBreadcrumbs(),
        );
    }

    public function testTheBreadcrumbsNeverContainTheRecordItself(): void
    {
        ModelHeader::make()
            ->model($this->createChain('About', 'Section 3'))
            ->render();

        foreach ($this->getViewBreadcrumbs() as $breadcrumb) {
            self::assertNotSame('Section 3', $breadcrumb[0]);
        }
    }

    public function testTheBreadcrumbsAreNotCapped(): void
    {
        ModelHeader::make()
            ->model($this->createChain('1', '2', '3', '4', '5', '6'))
            ->render();

        self::assertCount(11, $this->getViewBreadcrumbs());
    }

    public function testAHeaderWithoutAModelRendersAsAPlainHeader(): void
    {
        $header = fn (string $class): string => $class::make()
            ->title('Redirects')
            ->url(['/admin/test/index'])
            ->render();

        self::assertSame($header(Header::class), $header(ModelHeader::class));
        self::assertSame([], $this->getViewBreadcrumbs());
    }

    /**
     * @return list<array{string, array<int|string, mixed>|string|null}>
     */
    private function getViewBreadcrumbs(): array
    {
        return array_values(array_map(
            static fn (Breadcrumb $breadcrumb): array => [$breadcrumb->label, $breadcrumb->url],
            $this->getView()->getBreadcrumbs(),
        ));
    }

    private function getView(): View
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        return $view;
    }

    private function createChain(string ...$names): ModelHeaderNode
    {
        $node = null;

        foreach ($names as $name) {
            $node = $this->createNode($name, $node);
        }

        return $node;
    }

    private function createNode(string $name, ?ModelHeaderNode $parent = null): ModelHeaderNode
    {
        $node = new ModelHeaderNode();
        $node->name = $name;
        $node->adminParent = $parent;
        $node->indexBreadcrumb = new Breadcrumb("Index of $name", "/admin/test/index?name=$name");

        return $node;
    }
}

class ModelHeaderNode extends Model implements AdminModelInterface
{
    use AdminModelTrait;

    public string $name = '';
    public bool $hasRoute = true;
    public ?string $adminIcon = null;
    public ?ModelHeaderNode $adminParent = null;
    public ?Breadcrumb $indexBreadcrumb = null;

    public function getAdminName(): string
    {
        return $this->name;
    }

    public function getAdminIcon(): ?string
    {
        return $this->adminIcon;
    }

    public function getAdminRoute(): array|false
    {
        return $this->hasRoute ? ['/admin/test/update', 'name' => $this->name] : false;
    }

    public function getAdminParent(): ?ModelHeaderNode
    {
        return $this->adminParent;
    }

    public function getAdminIndexBreadcrumb(): ?Breadcrumb
    {
        return $this->indexBreadcrumb;
    }

    public function getPermissionName(): string
    {
        return 'test';
    }
}

/**
 * @property int $id
 * @property int|null $type
 */
class ModelHeaderRecord extends ActiveRecord implements AdminModelInterface, TypeAttributeInterface
{
    use AdminModelTrait;
    use TypeAttributeTrait;

    #[Override]
    public function getTypes(): array
    {
        return [Type::make(self::TYPE_DEFAULT)->name('Test type')];
    }

    public function getAdminRoute(): array|false
    {
        return false;
    }

    public function getPermissionName(): string
    {
        return 'test';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%test_model_header}}';
    }
}
