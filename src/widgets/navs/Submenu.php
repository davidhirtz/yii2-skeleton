<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Override;
use Stringable;
use yii\helpers\Html;

class Submenu extends Widget
{
    use TagAttributesTrait;
    use ContainerWidgetTrait;
    use TagContentTrait;
    use TagTitleTrait;
    use TagUrlTrait;

//    public array $attributes = ['class' => 'submenu nav nav-pills'];
    public array $badgeAttributes = ['class' => 'badge d-none d-md-inline-block'];
    public array $labelAttributes = ['class' => 'd-none d-md-inline'];


    protected function getTitle(): Stringable
    {
        return Header::make()
            ->title($this->title)
            ->url($this->url);
    }

    protected function renderContent(): string|Stringable
    {
        return $this->getTitle();
    }
}
