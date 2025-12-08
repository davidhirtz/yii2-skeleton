<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html;

use Hirtz\Skeleton\helpers\ArrayHelper;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\traits\TagInputTrait;
use Hirtz\Skeleton\html\traits\TagTextareaTrait;
use Stringable;

class Textarea extends base\Tag
{
    use TagInputTrait;
    use TagTextareaTrait;

    protected string $content = '';

    #[\Override]
    protected function before(): string
    {
        $this->content = Html::encode(ArrayHelper::remove($this->attributes, 'value', ''));
        return parent::before();
    }

    #[\Override]
    protected function renderContent(): string|Stringable
    {
        return $this->content;
    }

    protected function getTagName(): string
    {
        return 'textarea';
    }
}
