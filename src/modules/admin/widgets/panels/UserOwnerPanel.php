<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\widgets\panels\Panel;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class UserOwnerPanel extends Widget
{

    protected function renderContent(): string|Stringable
    {
        return Panel::make()
            ->danger()
            ->title($this->getTitle())
            ->content($this->getContent())
            ->buttons(...$this->getButtons());
    }

    protected function getTitle(): string
    {
        return Yii::t('skeleton', 'Transfer Ownership');
    }

    protected function getContent(): string
    {
        return Yii::t('skeleton', 'You are currently the owner of this website, do you want to transfer the website ownership?');
    }

    protected function getButtons(): array
    {
        return [
            Button::make()
                ->danger()
                ->text(Yii::t('skeleton', 'Transfer Ownership'))
                ->href(['ownership'])
        ];
    }
}
