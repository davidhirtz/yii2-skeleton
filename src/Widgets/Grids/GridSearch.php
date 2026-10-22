<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use yii\base\Configurable;

class GridSearch implements Configurable
{
    use ContainerConfigurationTrait;
    use UrlTrait;

    protected string $paramName = 'q';
    protected ?string $value = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            $this->configureProperties($config);
        }
    }

    public function paramName(string $paramName): static
    {
        $this->paramName = $paramName;
        $this->value = null;

        return $this;
    }

    public function getParamName(): string
    {
        return $this->paramName;
    }

    public function getUrl(): ?string
    {
        if ($this->url !== null) {
            return is_array($this->url) ? Url::to($this->url) : $this->url;
        }

        return Url::current([
            $this->paramName => null,
            'page' => null,
        ]);
    }

    public function value(?string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): string
    {
        if ($this->value === null) {
            $value = Application::current()->getRequest()->get($this->paramName);
            $this->value = is_string($value) && mb_check_encoding($value, 'UTF-8') ? trim($value) : '';
        }

        return $this->value;
    }

    public function markKeywords(string $text, bool $wordBoundary = false): string
    {
        return Html::markKeywords($text, array_values(array_filter(explode(' ', $this->getValue()))), $wordBoundary);
    }

    public function getToolbarItem(): void
    {
    }
}
