<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Ol;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class Breadcrumbs extends Widget
{
    public bool $alwaysShowHomeLink = false;
    public array|null|false $homeLink = null;

    /**
     * @var (string|Stringable)[]|list<array{label: string, url: array|string|null}>
     */
    protected array $links;

    #[Override]
    protected function configure(): void
    {
        $this->links ??= $this->view->getBreadcrumbs();
        $this->addLinksFromModules();

        if ($this->homeLink !== false && ($this->links || $this->alwaysShowHomeLink)) {
            $this->setDefaultHomeLink();
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->links
            ? Container::make()
                ->class('breadcrumbs')
                ->content($this->renderList())
            : '';
    }

    protected function renderList(): string|Stringable
    {
        $list = Ol::make()
            ->class('breadcrumbs-list');

        foreach ($this->links as $link) {
            if (is_string($link)) {
                $link = ['label' => $link];
            }

            if (is_array($link)) {
                $url = $link['url'] ?? null;

                $link = ($url ? A::make() : Span::make())
                    ->class('breadcrumbs-link')
                    ->text($link['label']);

                if ($url) {
                    $link->href($url);
                }
            }

            $list->addContent(Li::make()
                ->class('breadcrumbs-item')
                ->content($link));
        }

        return $list;
    }

    protected function setDefaultHomeLink(): void
    {
        array_unshift($this->links, $this->homeLink ?? [
            'label' => Yii::$app->name,
            'url' => Yii::$app->getHomeUrl(),
        ]);
    }

    protected function addLinksFromModules(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');

        if (
            $module->showInBreadcrumbs
            && (Yii::$app->controller->module instanceof Module
                || in_array(Yii::$app->controller->module, $module->getModules(), true))
        ) {
            $this->links = [
                [
                    'label' => Yii::t('skeleton', 'Admin'),
                    'url' => [$module->defaultRoute],
                ],
                ...$this->links
            ];
        }
    }
}
