<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class Breadcrumbs extends Widget
{
    public bool $alwaysShowHomeLink = false;
    public bool $encodeLabels = true;
    public array|null|false $homeLink = null;
    public array $links;

    public array $attributes = [
        'class' => 'breadcrumb',
    ];

    public array $itemAttributes = [
        'class' => 'breadcrumb-item',
    ];

    public array $linkAttributes = [
        'class' => 'breadcrumb-link',
    ];

    public function init(): void
    {
        $this->links ??= $this->getView()->getBreadcrumbs();
        $this->addLinksFromModules();

        if ($this->homeLink !== false && ($this->links || $this->alwaysShowHomeLink)) {
            $this->setDefaultHomeLink();
        }

        parent::init();
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

    public function render(): string
    {
        $items = $this->renderItems();

        if (!$items) {
            return '';
        }

        return Container::make()
            ->addHtml($items)
            ->render();
    }

    protected function renderItems(): string
    {
        if (!$this->links) {
            return '';
        }

        $list = Ul::make()->attributes($this->attributes);

        foreach ($this->links as $link) {
            if (!is_array($link)) {
                $link = ['label' => $link];
            }

            $list->addItem($this->renderItem($link), $this->itemAttributes);
        }

        return $list->render();
    }

    protected function renderItem(array $attributes): string
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
            ->addHtml($content)
            ->attributes($this->linkAttributes)
            ->href($url)
            ->render();
    }
}
