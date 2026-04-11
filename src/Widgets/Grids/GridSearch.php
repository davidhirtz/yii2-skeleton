<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Yii;

class GridSearch
{
    use ContainerConfigurationTrait;
    use UrlTrait;

    public string $paramName = 'q';
    protected ?string $value = null;

    protected array $keywords = [];

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }
    }

    public function paramName(string $paramName): static
    {
        $this->paramName = $paramName;
        $this->value = null;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url ?? Url::current([
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
            $this->value = Yii::$app->getRequest()->get($this->paramName);
            $this->value = trim($this->value ?? '');
        }

        return $this->value;
    }

    public function markKeywords(string $text, bool $encode = true, bool $wordBoundary = false): string
    {
        $keywords = array_filter(explode(' ', $this->getValue()));

        if ($encode) {
            $text = Html::encode($text);
        }

        if ($keywords) {
            foreach ($keywords as $keyword) {
                $keyword = preg_quote((string)$keyword);

                if ($wordBoundary) {
                    $keyword = "\b$keyword";
                }

                $text = preg_replace("#($keyword)#ui", '<mark>$1</mark>', (string)$text);
            }
        }

        return $text;
    }

    public function getToolbarItem()
    {
    }
}
