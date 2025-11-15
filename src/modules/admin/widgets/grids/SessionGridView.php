<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\models\Session;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Stringable;
use Yii;
use yii\data\ArrayDataProvider;

class SessionGridView extends GridView
{
    public string $layout = '{items}{footer}';

    public function init(): void
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
                        ->html(Yii::t('skeleton', 'Expired sessions: {count,number}', [
                            'count' => $item['expiredSessionCount'],
                        ])),
                    Div::make()
                        ->class('small')
                        ->html(Yii::t('app', 'Total sessions: {sessionCount,number} / Garbage collection probability: {probability}', [
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

        parent::init();
    }
}
