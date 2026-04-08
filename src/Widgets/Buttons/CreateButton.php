<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class CreateButton extends Widget
{
    use TagAttributesTrait;
    use IconTrait;
    use LabelTrait;
    use VisibilityTrait;
    use UrlTrait;

    public function __construct(array $config = [])
    {
        $this->icon ??= 'plus';
        $this->label ??= Yii::t('skeleton', 'Create');
        $this->url ??= Url::toRoute(['create']);

        parent::__construct($config);
    }

    #[Override]
    public function renderContent(): string|Stringable
    {
        return $this->isVisible()
            ? Button::make()
                ->attributes($this->attributes)
                ->primary()
                ->href($this->url)
                ->text($this->label)
                ->icon($this->icon)
            : '';
    }
}
