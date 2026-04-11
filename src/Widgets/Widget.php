<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
use ReflectionClass;
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

    /**
     * @var Closure[]
     */
    private array $callbacks = [];
    private ?string $html = null;

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }

        $this->view ??= Yii::$app->getView();

        $this->init();
    }

    protected function init(): void
    {
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    /**
     * @param Closure(static):void $callback
     * @return $this
     */
    public function prepare(Closure $callback): static
    {
        $this->callbacks[] = $callback;
        return $this;
    }

    public function render(bool $refresh = false): string
    {
        if ($this->html === null || $refresh) {
            $this->configure();
            $this->html = (string)$this->renderContent();
        }

        return $this->html;
    }

    protected function configure(): void
    {
        foreach ($this->callbacks as $callback) {
            ($callback)($this);
        }

        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            foreach ($property->getAttributes(Configure::class) as $attribute) {
                $this->{$attribute->newInstance()->method}();
            }
        }
    }

    abstract protected function renderContent(): string|Stringable;

    final public function __toString(): string
    {
        return $this->render();
    }
}
