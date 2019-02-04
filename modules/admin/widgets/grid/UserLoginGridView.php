<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use rmrevin\yii\fontawesome\FAS;
use yii\helpers\Html;

/**
 * Class LoginGridView.
 * @package app\modules\admin\components\widgets\grid
 *
 * @property User $model
 */
class UserLoginGridView extends GridView
{
    /**
     * Sets default columns.
     */
    public function init()
    {
        if (!$this->columns) {
            $this->columns = [
                [
                    'contentOptions' => ['class' => 'text-center'],
                    'content' => function (UserLogin $login) {
                        return FAS::icon($login->getTypeIcon(), [
                            'data-toggle' => 'tooltip',
                            'title' => $login->getTypeName()
                        ]);
                    }
                ],
                [
                    'attribute' => 'ip',
                    'content' => function (UserLogin $login) {
                        $ip = $login->getDisplayIp();
                        return $ip ? Html::a($login->getDisplayIp(), ['index', 'q' => $login->ip]) : '';
                    }
                ],
                [
                    'attribute' => 'user',
                    'visible' => !$this->model,
                    'content' => function (UserLogin $login) {
                        return $login->user ? Html::a($login->user->getUsername(), [
                            'view',
                            'id' => $login->user_id
                        ]) : 'N/A';
                    }
                ],
                [
                    'attribute' => 'browser',
                    'headerOptions' => ['class' => 'hidden-sm hidden-xs', 'style' => 'width:45%;'],
                    'contentOptions' => ['class' => 'hidden-sm hidden-xs'],
                    'content' => function (UserLogin $login) {
                        return $login->browser;
                    }
                ],
                [
                    'attribute' => 'created_at',
                    'content' => function (UserLogin $login) {
                        return \davidhirtz\yii2\timeago\Timeago::tag($login->created_at);
                    }
                ],
            ];
        }

        parent::init();
    }
}