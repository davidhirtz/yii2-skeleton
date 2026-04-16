<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\Td;
use Hirtz\Skeleton\Models\LogFile;
use Hirtz\Skeleton\Modules\Admin\Data\LogFileArrayDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\PropertyColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Yii;

/**
 * @property LogFileArrayDataProvider|null $provider
 */
class LogFileGridView extends GridView
{
    // todo add nice message if no log was found
    protected string $layout = '{items}';
    protected bool $showOnEmpty = false;

    protected array $tableAttributes = [
        'class' => 'table table-striped',
        'style' => 'table-layout: fixed;',
    ];

    #[Override]
    public function configure(): void
    {
        $this->attributes['id'] ??= 'logs';

        $this->columns ??= [
            $this->getNameColumn(),
            $this->getSizeColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getNameColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('name')
            ->title(Yii::t('skeleton', 'Name'))
            ->url(fn (LogFile $file): array => $this->getLogFileUrl($file))
            ->body(fn (Td $td) => $td->addClass('strong'));
    }

    protected function getSizeColumn(): PropertyColumn
    {
        return PropertyColumn::make()
            ->property('size')
            ->title(Yii::t('skeleton', 'File Size'))
            ->format('shortSize');
    }

    protected function getUpdatedAtColumn(): RelativeTimeColumn
    {
        return RelativeTimeColumn::make()
            ->property('updated_at')
            ->title(Yii::t('skeleton', 'Last Update'));
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(LogFile $file): array
    {
        return [
            ViewGridButton::make()
                ->url($this->getLogFileUrl($file, raw: true))
                ->icon('file'),
            DeleteGridButton::make()
                ->url($this->getLogFileUrl($file, 'delete'))
        ];
    }

    /**
     * @see LogController::actionView()
     * @see LogController::actionDelete()
     */
    protected function getLogFileUrl(LogFile $file, string $action = 'view', ?bool $raw = null): array
    {
        return ["/admin/log/$action", 'log' => $file->name, 'raw' => $raw];
    }
}
