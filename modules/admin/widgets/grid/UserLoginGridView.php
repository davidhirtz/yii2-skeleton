<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\Timeago;
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
    public ?User $user = null;

    public function init(): void
    {
        if (!$this->columns) {
            $this->columns = [
                [
                    'contentOptions' => ['class' => 'text-center'],
                    'content' => function (UserLogin $login) {
                        $options = [
                            'data-toggle' => 'tooltip',
                            'title' => $login->getTypeName()
                        ];

                        return ($icon = $login->getTypeIcon()) ? Icon::tag($icon, $options) : Icon::brand($login->type, $options);
                    }
                ],
                [
                    'attribute' => 'ip_address',
                    'content' => function (UserLogin $login) {
                        $ipAddress = $login->getDisplayIp();
                        return $ipAddress ? Html::a($ipAddress, ['index', 'q' => $ipAddress]) : '';
                    }
                ],
                [
                    'attribute' => 'user',
                    'visible' => !$this->user,
                    'content' => function (UserLogin $login) {
                        $name = $login->user->getUsername();
                        return $login->user ? Html::a($name ?: Html::tag('span', Yii::t('skeleton', 'User'), ['class' => !$name ? 'text-muted' : null]), ['view', 'user' => $login->user_id]) : '';
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
                        return Timeago::tag($login->created_at);
                    }
                ],
            ];
        }

        parent::init();
    }
}