<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
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
     * @var User
     */
    public $user;

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
                        return Icon::tag($login->getTypeIcon(), [
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
                    'visible' => !$this->user,
                    'content' => function (UserLogin $login) {
                        return $login->user ? Html::a(($name = $login->user->getUsername()) ?: Html::tag('span', Yii::t('skeleton', 'User'), ['class' => !$name ? 'text-muted' : null]), ['view', 'id' => $login->user_id]) : '';
                    }
                ],
                [
                    'attribute' => 'browser',
                    'headerOptions' => ['class' => 'd-none d-md-table-cell', 'style' => 'width:45%;'],
                    'contentOptions' => ['class' => 'd-none d-md-table-cell'],
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