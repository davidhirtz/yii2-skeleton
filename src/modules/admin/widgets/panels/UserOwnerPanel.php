<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\panels;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\P;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagTitleTrait;
use Hirtz\Skeleton\widgets\panels\Panel;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;
use Yii;

class UserOwnerPanel extends Widget
{
    use TagTitleTrait;
    use TagContentTrait;

    protected function renderContent(): string|Stringable
    {
        if (!$this->content) {
            $this->content(P::make()
                ->content(Yii::t('skeleton', 'Transfer ownership of this user to another administrator.')));
        }

        $this->title ??= Yii::t('skeleton', 'Transfer Ownership');

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
