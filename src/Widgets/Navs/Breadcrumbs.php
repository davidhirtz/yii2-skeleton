<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Ol;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Traits\BreadcrumbTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class Breadcrumbs extends Widget
{
    use BreadcrumbTrait;

    protected bool $alwaysShowHomeLink = false;
    protected bool $showAdminBreadcrumb = true;
    protected Breadcrumb|null|false $homeBreadcrumb = null;

    #[Override]
    protected function configure(): void
    {
        $this->breadcrumbs ??= $this->view->getBreadcrumbs();

        if ($this->showAdminBreadcrumb) {
            /** @var Module $module */
            $module = Yii::$app->getModule('admin');
            $current = Yii::$app->controller->module;

            if ($current instanceof Module || in_array($current, $module->getModules(), true)) {
                $this->addAdminBreadcrumb();
            }
        }

        if ($this->homeBreadcrumb !== false && ($this->breadcrumbs || $this->alwaysShowHomeLink)) {
            $this->addHomeBreadcrumb();
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->breadcrumbs
            ? Container::make()
                ->class('breadcrumbs')
                ->content($this->getList())
            : '';
    }

    protected function getList(): string|Stringable
    {
        $list = Ol::make()
            ->class('breadcrumbs-list');

        foreach ($this->breadcrumbs as $breadcrumb) {
            $tag = ($breadcrumb->url ? A::make() : Span::make())
                ->class('breadcrumbs-link')
                ->text($breadcrumb->label);

            if ($breadcrumb->url) {
                $tag->href($breadcrumb->url);
            }

            $list->addContent(Li::make()
                ->class('breadcrumbs-item')
                ->content($tag));
        }

        return $list;
    }

    protected function addHomeBreadcrumb(): void
    {
        $this->breadcrumbs = [
            $this->homeBreadcrumb ?? new Breadcrumb(Yii::$app->name, Yii::$app->getHomeUrl()),
            ...$this->breadcrumbs,
        ];
    }

    protected function addAdminBreadcrumb(): void
    {
        $this->breadcrumbs = [
            new Breadcrumb(Lang::t('skeleton', 'BREADCRUMBS_ADMIN'), ['/admin']),
            ...$this->breadcrumbs,
        ];
    }
}
