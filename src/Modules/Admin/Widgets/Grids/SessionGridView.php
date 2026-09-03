<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Session;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

class SessionGridView extends GridView
{
    protected string $layout = '{items}{footer}';

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
                ->title(Lang::t('skeleton', 'SESSION_SESSIONS'))
                ->content(fn (array $item): array => [
                    Div::make()
                        ->class('strong')
                        ->content(Lang::t('skeleton', 'SESSION_EXPIRED_SESSIONS', [
                            'count' => $item['expiredSessionCount'],
                        ])),
                    Div::make()
                        ->class('small')
                        ->content(Lang::t('skeleton', 'SESSION_TOTAL_SESSIONS_GARBAGE_COLLECTION_PROBABILITY', [
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
                    ->tooltip(Lang::t('skeleton', 'SESSION_DELETE_EXPIRED_SESSIONS')))
        ];

        parent::configure();
    }
}
