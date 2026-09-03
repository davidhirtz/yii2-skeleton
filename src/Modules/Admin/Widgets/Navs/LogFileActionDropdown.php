<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Stringable;
use Yii;

class LogFileActionDropdown extends ActionDropdown
{
    protected string $file;

    public function file(string $file): self
    {
        $this->file = $file;
        return $this->addItem($this->getOpenRawFileButton(), $this->getDeleteFileButton());
    }

    protected function getOpenRawFileButton(): ?Stringable
    {
        return Button::make()
            ->primary()
            ->icon('file-alt')
            ->text(Lang::t('skeleton', 'LOG_FILE_ACTION_DROPDOWN_OPEN_FILE'))
            ->url(["/admin/log/view", 'log' => $this->file, 'raw' => 1])
            ->target('_blank');
    }

    protected function getDeleteFileButton(): ?Stringable
    {
        return DeleteButton::make()
            ->label(Lang::t('skeleton', 'LOG_FILE_ACTION_DROPDOWN_DELETE_FILE'))
            ->url(["/admin/log/delete", 'log' => $this->file]);
    }
}
