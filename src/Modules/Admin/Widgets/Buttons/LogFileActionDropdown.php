<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Override;
use Yii;

class LogFileActionDropdown extends ActionDropdown
{
    protected string $file;

    public function file(string $file): self
    {
        $this->file = $file;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->addItem(Button::make()
            ->primary()
            ->icon('file-alt')
            ->text(Yii::t('skeleton', 'Open file'))
            ->url(["/admin/log/view", 'log' => $this->file, 'raw' => 1])
            ->target('_blank'));

        $this->addItem(DeleteButton::make()
            ->label(Yii::t('skeleton', 'Delete file'))
            ->url(["/admin/log/delete", 'log' => $this->file]));

        parent::configure();
    }
}
