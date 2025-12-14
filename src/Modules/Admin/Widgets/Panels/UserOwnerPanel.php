<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagTitleTrait;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Hirtz\Skeleton\Widgets\Widget;
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
