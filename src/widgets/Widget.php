<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\web\View;
use Stringable;
use Yii;
use yii\base\ViewContextInterface;

/**
 * @property View $view
 */
abstract class Widget implements Stringable, ViewContextInterface
{
    use ContainerConfigurationTrait;

    protected View $view;
    protected ?string $viewPath = null;

    public function __construct()
    {
        $this->view = Yii::$app->getView();
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    public function render(): string
    {
        return (string)$this->renderContent();
    }

    final public function __toString(): string
    {
        return $this->render();
    }

    abstract protected function renderContent(): string|Stringable;
}
