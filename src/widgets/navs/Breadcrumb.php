<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\navs;

use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Li;
use Hirtz\Skeleton\html\Ol;
use Hirtz\Skeleton\modules\admin\Module;
use Hirtz\Skeleton\widgets\traits\ContainerWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class Breadcrumb extends Widget
{
    use ContainerWidgetTrait;

    public bool $alwaysShowHomeLink = false;
    public bool $encodeLabels = true;
    public array|null|false $homeLink = null;

    public array $listAttributes = ['class' => 'breadcrumb'];
    public array $itemAttributes = ['class' => 'breadcrumb-item'];
    public array $linkAttributes = ['class' => 'breadcrumb-link'];

    protected array $links;

    protected function renderContent(): string|Stringable
    {
        $this->links ??= $this->view->getBreadcrumbs();
        $this->addLinksFromModules();

        if ($this->homeLink !== false && ($this->links || $this->alwaysShowHomeLink)) {
            $this->setDefaultHomeLink();
        }

        if (!$this->links) {
            return '';
        }

        $list = Ol::make()
            ->attributes($this->listAttributes);

        foreach ($this->links as $link) {
            if (!is_array($link)) {
                $link = ['label' => $link];
            }

            $list->addContent(Li::make()
                ->attributes($this->itemAttributes)
                ->content($this->getLink($link)));
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

        if (Yii::$app->controller->module instanceof Module || in_array(Yii::$app->controller->module, $module->getModules(), true)) {
            if ($module->showInBreadcrumbs) {
                $this->links = [
                    [
                        'label' => $module->getName(),
                        'url' => [$module->defaultRoute],
                    ],
                    ...$this->links
                ];
            }
        }
    }

    protected function getLink(array $attributes): Stringable
    {
        if (!array_key_exists('label', $attributes)) {
            throw new InvalidConfigException('The "label" element is required for each link.');
        }

        $content = ArrayHelper::remove($attributes, 'label');
        $url = ArrayHelper::remove($attributes, 'url');
        $encode = ArrayHelper::remove($attributes, 'encode', $this->encodeLabels);

        if ($encode) {
            $content = Html::encode($content);
        }

        return A::make()
            ->addContent($content)
            ->attributes($this->linkAttributes)
            ->href($url);
    }
}
