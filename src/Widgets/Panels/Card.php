<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagCardTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class Card extends Widget
{
    use TagAttributesTrait;
    use TagIdTrait;
    use TagCardTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (null !== $this->collapsed) {
            $this->attribute('aria-expanded', $this->collapsed);

            if ($this->collapsed) {
                $this->addClass('collapsed');
            }

            $this->getId();
        }

        $card = Div::make()
            ->attributes($this->attributes)
            ->addClass('card');

        if ($this->title) {
            $title = Div::make()
                ->class('card-title');

            if (null !== $this->collapsed) {
                $title->addContent(Button::make()
                    ->link()
                    ->attribute('data-collapse', '#' . $this->getId())
                    ->content($this->title));

                $title->addContent(Button::make()
                    ->attribute('aria-label', Yii::t('skeleton', 'Toggle'))
                    ->attribute('data-collapse', '#' . $this->getId())
                    ->class('btn-collapse', 'btn-icon')
                    ->icon('chevron-down'));
            } else {
                $title->text($this->title);
            }

            $card->addContent(Div::make()
                ->class('card-header')
                ->content($title));
        }

        return $card->addContent(Div::make()
            ->class('card-body')
            ->content(...$this->content));
    }
}
