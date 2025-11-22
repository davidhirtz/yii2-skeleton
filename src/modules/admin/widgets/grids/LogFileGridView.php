<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\models\LogFile;
use davidhirtz\yii2\skeleton\modules\admin\data\LogFileArrayDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DeleteGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\ViewGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @property LogFileArrayDataProvider|null $provider
 */
class LogFileGridView extends GridView
{
    public string $layout = '{items}';
    public bool $showOnEmpty = false;

    public array $tableAttributes = [
        'class' => 'table table-striped',
        'style' => 'table-layout: fixed;',
    ];

    #[Override]
    public function configure(): void
    {
        $this->attributes['id'] ??= 'logs';
        $this->provider ??= Yii::createObject(LogFileArrayDataProvider::class);

        $this->columns ??= [
            $this->getNameColumn(),
            $this->getSizeColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        $this->view->registerCss('pre{margin-top: 20px; max-height:200px;}');

        parent::configure();
    }

    protected function getNameColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('name')
            ->header(Yii::t('skeleton', 'Name'))
            ->href(fn (LogFile $file): array => $this->getLogFileUrl($file))
            ->contentAttributes(['class' => 'strong']);
    }

    protected function getSizeColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('size')
            ->header(Yii::t('skeleton', 'File Size'))
            ->format('shortSize');
    }

    protected function getUpdatedAtColumn(): TimeagoColumn
    {
        return TimeagoColumn::make()
            ->property('updated_at')
            ->header(Yii::t('skeleton', 'Last Update'));
    }

    protected function getButtonColumn(): ButtonColumn
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
