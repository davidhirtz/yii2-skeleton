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

    protected bool $primary = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->icon ??= 'plus';
        $this->label ??= Yii::t('skeleton', 'FORM_CREATE_BUTTON');
        $this->url ??= ['create'];

        parent::__construct($config);
    }

    public function primary(bool $primary = true): static
    {
        $this->primary = $primary;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $button = Button::make()->attributes($this->attributes);

        return ($this->primary ? $button->primary() : $button->accent())
            ->url($this->url)
            ->text($this->label)
            ->icon($this->icon);
    }
}
