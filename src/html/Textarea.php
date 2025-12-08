<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagTextareaTrait;
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
