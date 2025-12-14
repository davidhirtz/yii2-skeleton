<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagAjaxAttributeTrait;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Hirtz\Skeleton\Html\Traits\TagModalTrait;
use Hirtz\Skeleton\Html\Traits\TagTooltipAttributeTrait;
use Override;

class Button extends Tag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
    use TagInputTrait;
    use TagLinkTrait;
    use TagModalTrait;
    use TagTooltipAttributeTrait;

    public array $attributes = ['type' => 'button'];

    public function danger(): static
    {
        return $this->addClass('btn btn-danger');
    }

    public function primary(): static
    {
        return $this->addClass('btn btn-primary');
    }

    public function success(): static
    {
        return $this->addClass('btn btn-success');
    }

    public function secondary(): static
    {
        return $this->addClass('btn btn-secondary');
    }

    public function link(): static
    {
        return $this->addClass('btn btn-link');
    }

    #[Override]
    protected function getTagName(): string
    {
        return isset($this->attributes['href']) ? 'a' : 'button';
    }
}
