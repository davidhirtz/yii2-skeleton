<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\web\User;
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

    private ?string $content = null;

    public function __construct()
    {
        $this->view = Yii::$app->getView();
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    public function render(bool $refresh = false): string
    {
        if ($this->content === null || $refresh) {
            $this->configure();
            $this->content = (string)$this->renderContent();
        }

        return $this->content;
    }

    protected function configure(): void
    {
    }

    abstract protected function renderContent(): string|Stringable;

    final public function __toString(): string
    {
        return $this->render();
    }
}
