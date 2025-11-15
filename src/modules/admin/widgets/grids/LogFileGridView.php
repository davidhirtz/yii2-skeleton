<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\data\LogFileArrayDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\timeago\Timeago;
use Override;
use Yii;

/**
 * @property LogFileArrayDataProvider|null $provider
 */
class LogFileGridView extends GridView
{
    public string $layout = '{items}';

    public array $tableAttributes = [
        'class' => 'table table-striped',
        'style' => 'table-layout: fixed;',
    ];

    #[Override]
    public function init(): void
    {
        $this->provider ??= Yii::createObject(LogFileArrayDataProvider::class);

        $this->columns ??= [
            $this->getNameColumn(),
            $this->getSizeColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        $this->view->registerCss('pre{margin-top: 20px; max-height:200px;}');

        parent::init();
    }

    protected function getNameColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('name')
            ->header(Yii::t('skeleton', 'Name'))
            ->href(fn (array $model): array => ['view', 'log' => $model['name']])
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
            ->content(fn (array $model): array => [
                Button::make()
                    ->primary()
                    ->href(['view', 'log' => $model['name'], 'raw' => 1])
                    ->icon('file'),
                Button::make()
                    ->danger()
                    ->icon('trash')
                    ->post(['delete', 'log' => $model['name']])
            ]);
    }
}
