<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Web\View;
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
    protected ?Closure $config = null;

    public function __construct($config = [])
    {
        Yii::configure($this, $config);
        $this->view = Yii::$app->getView();
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    public function prepare(Closure $config): static
    {
        $this->config = $config;
        return $this;
    }

    public function render(bool $refresh = false): string
    {
        if (null === $this->content || $refresh) {
            $this->configure();
            $this->content = (string)$this->renderContent();
        }

        return $this->content;
    }

    protected function configure(): void
    {
        if ($this->config instanceof Closure) {
            call_user_func($this->config, $this);
        }
    }

    abstract protected function renderContent(): string|Stringable;

    final public function __toString(): string
    {
        return $this->render();
    }
}
