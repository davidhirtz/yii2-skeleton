<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagTextareaTrait;
use Override;
use Stringable;

class Textarea extends Base\Tag
{
    use TagInputTrait;

    protected string $content = '';

    public function rows(int $rows): static
    {
        return $this->attribute('rows', $rows);
    }

    public function cols(int $cols): static
    {
        return $this->attribute('cols', $cols);
    }

    #[Override]
    protected function before(): string
    {
        $this->content = Html::encode(ArrayHelper::remove($this->attributes, 'value', ''));
        return parent::before();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->content;
    }

    protected function getTagName(): string
    {
        return 'textarea';
    }
}
