<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class Breadcrumbs extends Widget
{
    use ContainerWidgetTrait;

    public bool $alwaysShowHomeLink = false;
    public bool $encodeLabels = true;
    public array|null|false $homeLink = null;

    public array $listAttributes = ['class' => 'breadcrumb'];
    public array $itemAttributes = ['class' => 'breadcrumb-item'];
    public array $linkAttributes = ['class' => 'breadcrumb-link'];

    protected array $links;

    public function init(): void
    {
        $this->links ??= $this->view->getBreadcrumbs();
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

    protected function renderContent(): string|Stringable
    {
        if (!$this->links) {
            return '';
        }

        $list = Ul::make()
            ->attributes($this->listAttributes);

        foreach ($this->links as $link) {
            if (!is_array($link)) {
                $link = ['label' => $link];
            }

            $list->addItem(Li::make()
                ->attributes($this->itemAttributes)
                ->content($this->getLink($link)));
        }

        return $list;
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
