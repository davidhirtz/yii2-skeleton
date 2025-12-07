<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTextareaTrait;
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
