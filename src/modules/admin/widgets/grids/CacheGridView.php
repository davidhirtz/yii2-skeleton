<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\caching\CacheComponents;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Override;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

class CacheGridView extends GridView
{
    public string $layout = '{items}{footer}';

    #[Override]
    public function configure(): void
    {
        $caches = [];

        foreach (CacheComponents::getAll() as $name => $class) {
            $caches[] = [
                'name' => $name,
                'class' => $class,
            ];
        }

        $this->provider ??= new ArrayDataProvider([
            'allModels' => $caches,
            'pagination' => false,
            'sort' => false,
        ]);

        $this->columns ??= [
            Column::make()
                ->header(Yii::t('skeleton', 'Name'))
                ->content(fn(array $item): array => [
                    Div::make()
                        ->content(ucwords((string)$item['name']))
                        ->class('strong'),
                    Div::make()
                        ->content($item['class'])
                        ->class('small'),
                ]),
            ButtonColumn::make()
                /** @see SystemController::actionFlush() */
                ->content(fn(array $item): Stringable => Button::make()
                    ->primary()
                    ->icon('sync-alt')
                    ->post(['flush', 'cache' => $item['name']]))
        ];

        parent::configure();
    }
}
