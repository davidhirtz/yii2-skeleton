<?php

namespace davidhirtz\yii2\skeleton\widgets;

use Yii;
use yii\base\BaseObject;
use yii\base\View;
use yii\base\ViewContextInterface;

/**
 * @property View $view
 */
abstract class Widget extends BaseObject implements ViewContextInterface
{
    private ?View $_view = null;
    private ?string $_viewPath = null;

    public function run(): string
    {
        return '';
    }

    public function getView(): View
    {
        if ($this->_viewPath === null) {
            $this->setView(Yii::$app->controller->getView());
        }

        return $this->_view;
    }

    public function setView(?View $view): void
    {
        $this->_view = $view;
    }

    public function getViewPath(): ?string
    {
        if ($this->_viewPath === null) {
            $controllerId = Yii::$app->controller->id;
            $this->setViewPath("@views/$controllerId/");
        }

        return $this->_viewPath;
    }

    public function setViewPath(?string $viewPath): void
    {
        $this->_viewPath = $viewPath;
    }

    public static function widget(array $config = []): string
    {
        return Yii::$container->get(static::class, [], $config)->run();
    }
}
