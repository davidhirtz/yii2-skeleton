<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\GridView;
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
                ->content(fn (array $item): array => [
                    Div::make()
                        ->content(ucwords((string)$item['name']))
                        ->class('strong'),
                    Div::make()
                        ->content($item['class'])
                        ->class('small'),
                ]),
            ButtonColumn::make()
                /** @see SystemController::actionFlush() */
                ->content(fn (array $item): Stringable => Button::make()
                    ->primary()
                    ->icon('sync-alt')
                    ->post(['flush', 'cache' => $item['name']]))
        ];

        parent::configure();
    }
}
