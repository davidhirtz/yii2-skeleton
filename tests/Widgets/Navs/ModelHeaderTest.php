<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Navs;

use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Navs\ModelHeader;
use Yii;
use yii\base\Model;

class ModelHeaderTest extends TestCase
{
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

    public function testTheTitleStaysOnTheBaseRecord(): void
    {
        $html = ModelHeader::make()
            ->model($this->createChain('About', 'Section #3', 'Asset #1'))
            ->render();

        self::assertStringContainsString('<h1><a href="/admin/test/update?name=About">About</a></h1>', $html);
    }

    public function testTheSubtitleIsTheChainBelowTheBase(): void
    {
        $html = ModelHeader::make()
            ->model($this->createChain('About', 'Section #3', 'Asset #1', 'Hotspot #2', 'Asset #1'))
            ->render();

        self::assertStringContainsString(
            '<h2 class="header-subtitle">Section #3 · Asset #1 · Hotspot #2 · Asset #1</h2>',
            $html,
        );
    }

    public function testABaseRecordGetsNoSubtitle(): void
    {
        $html = ModelHeader::make()
            ->model($this->createNode('About'))
            ->render();

        self::assertStringNotContainsString('header-subtitle', $html);
    }

    /**
     * A record filed under one of its own kind answers no subtitle, so the title stays on it rather than
     * climbing to its tree ancestor.
     */
    public function testATreeParentDoesNotMoveTheTitle(): void
    {
        $child = $this->createNode('Child', $this->createNode('Parent'));

        $html = ModelHeader::make()
            ->model($child)
            ->render();

        self::assertStringContainsString('<h1><a href="/admin/test/update?name=Child">Child</a></h1>', $html);
        self::assertStringNotContainsString('header-subtitle', $html);
    }

    public function testNoAncestorPathIsRendered(): void
    {
        $html = ModelHeader::make()
            ->model($this->createChain('About', 'Section #3', 'Asset #1'))
            ->render();

        self::assertStringNotContainsString('header-path', $html);
    }

    public function testTheBreadcrumbsAlternateIndexCrumbAndRecord(): void
    {
        $model = $this->createChain('About', 'Section #3', 'Asset #1');
        $model->indexBreadcrumb = new Breadcrumb('Assets', ['/admin/test/asset']);

        ModelHeader::make()
            ->model($model)
            ->render();

        self::assertSame(
            [
                ['Index of About', '/admin/test/index?name=About'],
                ['About', ['/admin/test/update', 'name' => 'About']],
                ['Index of Section #3', '/admin/test/index?name=Section #3'],
                ['Section #3', ['/admin/test/update', 'name' => 'Section #3']],
                ['Assets', ['/admin/test/asset']],
            ],
            $this->getViewBreadcrumbs(),
        );
    }

    public function testTheBreadcrumbsSkipANullIndexCrumb(): void
    {
        $model = $this->createChain('About', 'Section #3', 'Asset #1');
        $model->getAdminParent()->indexBreadcrumb = null;

        ModelHeader::make()
            ->model($model)
            ->render();

        self::assertSame(
            [
                ['Index of About', '/admin/test/index?name=About'],
                ['About', ['/admin/test/update', 'name' => 'About']],
                ['Section #3', ['/admin/test/update', 'name' => 'Section #3']],
                ['Index of Asset #1', '/admin/test/index?name=Asset #1'],
            ],
            $this->getViewBreadcrumbs(),
        );
    }

    public function testTheBreadcrumbsNeverContainTheRecordItself(): void
    {
        ModelHeader::make()
            ->model($this->createChain('About', 'Section #3'))
            ->render();

        foreach ($this->getViewBreadcrumbs() as $breadcrumb) {
            self::assertNotSame('Section #3', $breadcrumb[0]);
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

    /**
     * The first name is the base record; every one after it is subordinate and names itself in the subtitle.
     */
    private function createChain(string ...$names): ModelHeaderNode
    {
        $node = null;

        foreach ($names as $name) {
            $node = $this->createNode($name, $node);
            $node->adminSubtitle = $node->adminParent ? $name : null;
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
    public ?ModelHeaderNode $adminParent = null;
    public ?Breadcrumb $indexBreadcrumb = null;
    public ?string $adminSubtitle = null;

    public function getAdminName(): string
    {
        return $this->name;
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

    public function getAdminSubtitle(): ?string
    {
        return $this->adminSubtitle;
    }

    public function getPermissionName(): string
    {
        return 'test';
    }
}
