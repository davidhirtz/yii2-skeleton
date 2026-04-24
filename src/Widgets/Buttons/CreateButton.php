<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class CreateButton extends Widget
{
    use TagAttributesTrait;
    use IconTrait;
    use LabelTrait;
    use UrlTrait;

    public function __construct(array $config = [])
    {
        $this->icon ??= 'plus';
        $this->label ??= Yii::t('skeleton', 'Create');
        $this->url ??= ['create'];

        parent::__construct($config);
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Button::make()
            ->attributes($this->attributes)
            ->primary()
            ->url($this->url)
            ->text($this->label)
            ->icon($this->icon);
    }
}
