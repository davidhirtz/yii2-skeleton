<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Session;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

class SessionGridView extends GridView
{
    public string $layout = '{items}{footer}';

    #[Override]
    public function configure(): void
    {
        $this->provider ??= new ArrayDataProvider([
            'allModels' => [
                [
                    'sessionCount' => Session::find()->count(),
                    'expiredSessionCount' => Session::find()
                        ->where(['<', 'expire', time()])
                        ->count(),
                ],
            ],
            'pagination' => false,
            'sort' => false,
        ]);

        $this->columns ??= [
            Column::make()
                ->header(Yii::t('skeleton', 'Sessions'))
                ->content(fn (array $item): array => [
                    Div::make()
                        ->class('strong')
                        ->content(Yii::t('skeleton', 'Expired sessions: {count,number}', [
                            'count' => $item['expiredSessionCount'],
                        ])),
                    Div::make()
                        ->class('small')
                        ->content(Yii::t('skeleton', 'Total sessions: {sessionCount,number} / Garbage collection probability: {probability}', [
                            'sessionCount' => $item['sessionCount'],
                            'probability' => Yii::$app->getSession()->getGCProbability(),
                        ]))
                ]),
            ButtonColumn::make()
                /** @see SystemController::actionSessionGc() */
                ->content(fn (): Stringable => Button::make()
                    ->primary()
                    ->icon('trash')
                    ->post(['/admin/system/session-gc'])
                    ->tooltip(Yii::t('skeleton', 'Delete expired sessions')))
        ];

        parent::configure();
    }
}
