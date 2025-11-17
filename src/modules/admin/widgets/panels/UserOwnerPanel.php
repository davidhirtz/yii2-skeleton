<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\P;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\widgets\panels\Panel;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class UserOwnerPanel extends Widget
{
    use TagTitleTrait;
    use TagContentTrait;

    public function init(): void
    {
        if (!$this->content) {
            $this->content(P::make()
                ->content(Yii::t('skeleton', 'Transfer ownership of this user to another administrator.')));
        }

        $this->title ??= Yii::t('skeleton', 'Transfer Ownership');

        parent::init();
    }

    protected function renderContent(): string|Stringable
    {
        return Panel::make()
            ->danger()
            ->title($this->title)
            ->content(...$this->content)
            ->buttons(...$this->getButtons());
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
