<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\web\View;
use Stringable;
use Yii;
use yii\base\BaseObject;
use yii\base\ViewContextInterface;

/**
 * @property View $view
 */
abstract class Widget extends BaseObject implements Stringable, ViewContextInterface
{
    private ?View $view = null;
    private ?string $viewPath = null;

    public function getView(): View
    {
        if ($this->viewPath === null) {
            /** @var View $view */
            $view = Yii::$app->getView();
            $this->setView($view);
        }

        return $this->view;
    }

    public function setView(?View $view): void
    {
        $this->view = $view;
    }

    public function getViewPath(): ?string
    {
        if ($this->viewPath === null) {
            $controllerId = Yii::$app->controller->id;
            $this->setViewPath("@views/$controllerId/");
        }

        return $this->viewPath;
    }

    public function setViewPath(?string $viewPath): void
    {
        $this->viewPath = $viewPath;
    }

    public static function widget(array $config = []): string
    {
        return Yii::$container->get(static::class, [], $config)->render();
    }

    public static function make(): static
    {
        /** @var static $instance */
        $instance = Yii::$container->get(static::class);
        return $instance;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    abstract public function render(): string;
}
