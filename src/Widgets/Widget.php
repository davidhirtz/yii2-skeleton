<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Base\Traits\EvaluateClosureTrait;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Traits\ConfigureAttributesTrait;
use Stringable;
use Yii;
use yii\base\ViewContextInterface;

/**
 * @property View $view
 */
abstract class Widget implements Stringable, ViewContextInterface
{
    use ContainerConfigurationTrait;
    use ConfigureAttributesTrait;
    use EvaluateClosureTrait;

    protected View $view;
    protected ?string $viewPath = null;

    /**
     * @var Closure[]
     */
    private array $configureClosures = [];
    private ?string $html = null;

    /**
     * @var array<class-string, list<string>>
     */
    private static array $configureAttributes = [];

    public function __construct(array $config = [])
    {
        $this->view ??= Yii::$app->getView();

        if ($config) {
            Yii::configure($this, $config);
        }
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    /**
     * @param Closure(static): (void|static) $callback
     * @return $this
     */
    public function prepare(Closure $callback): static
    {
        $this->configureClosures[] = $callback;
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
        $this->configureAttributes();
        $this->evaluate($this->configureClosures, $this);
    }

    final public function __toString(): string
    {
        return $this->render();
    }

    abstract protected function renderContent(): string|Stringable;
}
