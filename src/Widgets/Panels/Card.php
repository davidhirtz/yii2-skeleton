<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Traits\CardTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class Card extends Widget
{
    use TagAttributesTrait;
    use TagIdTrait;
    use CardTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (null !== $this->collapsed) {
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
                // The state is on the buttons, which is what a screen reader announces; the script keeps it in sync.
                $toggle = [
                    'aria-controls' => $this->getId() . '-body',
                    'aria-expanded' => $this->collapsed ? 'false' : 'true',
                    'data-collapse' => '#' . $this->getId(),
                ];

                $title->addContent(Button::make()
                    ->link()
                    ->addAttributes($toggle)
                    ->text($this->title));

                $title->addContent(Button::make()
                    ->attribute('aria-label', Yii::t('skeleton', 'CARD_TOGGLE'))
                    ->addAttributes($toggle)
                    ->class('btn-collapse btn-icon icon')
                    ->icon('chevron-down'));
            } else {
                $title->text($this->title);
            }

            $card->addContent(Div::make()
                ->class('card-header')
                ->content($title));
        }

        $body = Div::make()
            ->class('card-body')
            ->content(...$this->content);

        if (null !== $this->collapsed) {
            $body->attribute('id', $this->getId() . '-body');
        }

        return $card->addContent($body);
    }
}
