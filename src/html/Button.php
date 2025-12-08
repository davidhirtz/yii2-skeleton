<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagAjaxAttributeTrait;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;
use Hirtz\Skeleton\html\traits\TagInputTrait;
use Hirtz\Skeleton\html\traits\TagLinkTrait;
use Hirtz\Skeleton\html\traits\TagModalTrait;
use Hirtz\Skeleton\html\traits\TagTooltipAttributeTrait;
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
