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

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    /**
     * @deprecated Use `::make()` instead.
     */
    #[Deprecated]
    public static function widget(array $config = []): string
    {
        return Yii::$container->get(static::class, [], $config)->renderContent();
    }

    public function render(): string|Stringable
    {
        return $this->renderContent();
    }

    final public function __toString(): string
    {
        return (string)$this->render();
    }

    abstract protected function renderContent(): string|Stringable;
}
