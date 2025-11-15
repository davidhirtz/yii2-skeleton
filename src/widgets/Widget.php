<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\web\View;
use Deprecated;
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

    public function init(): void
    {
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '"@views/' . Yii::$app->controller->id . '/';
    }

    #[Deprecated]
    public static function widget(array $config = []): string
    {
        return Yii::$container->get(static::class, [], $config)->render();
    }

    public function run(): string
    {
        $this->init();
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->run();
    }

    abstract protected function render(): string;
}
