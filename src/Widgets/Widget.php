<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Base\Traits\EvaluateClosureTrait;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\User;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Stringable;
use Yii;
use yii\base\Configurable;
use yii\base\Event;
use yii\base\ViewContextInterface;

/**
 * @property View $view
 */
abstract class Widget implements Configurable, Stringable, ViewContextInterface
{
    use ContainerConfigurationTrait;
    use EvaluateClosureTrait;
    use VisibilityTrait;

    final public const string EVENT_CONFIGURE = 'configure';

    protected View $view;
    protected User $webuser;
    protected ?string $viewPath = null;

    /**
     * @var list<Closure(static): mixed>
     */
    private array $configureClosures = [];
    private ?string $html = null;

    /**
     * A subclass constructor runs before this one, so it must not read `$view` or `$webuser` but call the components.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->view ??= Yii::$app->getView();
        $this->webuser ??= Application::current()->getUser();

        if ($config) {
            $this->configureProperties($config);
        }
    }

    public function viewPath(?string $viewPath): static
    {
        $this->viewPath = $viewPath;
        return $this;
    }

    public function getViewPath(): ?string
    {
        return $this->viewPath ??= '@views/' . Yii::$app->controller->id . '/';
    }

    /**
     * @param Closure(static): mixed $callback
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
            $this->html = $this->isVisible() ? (string)$this->renderContent() : '';
        }

        return $this->html;
    }

    protected function configure(): void
    {
        Event::trigger($this, self::EVENT_CONFIGURE);
        foreach ($this->configureClosures as $closure) {
            $closure($this);
        }
    }

    final public function __toString(): string
    {
        return $this->render();
    }

    abstract protected function renderContent(): string|Stringable;
}
