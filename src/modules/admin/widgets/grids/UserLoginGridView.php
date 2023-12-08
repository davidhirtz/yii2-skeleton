<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\Timeago;

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
                    'content' => fn(UserLogin $login) => Html::username($login->user, ['view', 'user' => $login->user_id])
                ],
                [
                    'attribute' => 'browser',
                    'headerOptions' => ['class' => 'd-none d-md-table-cell', 'style' => 'width:45%;'],
                    'contentOptions' => ['class' => 'd-none d-md-table-cell'],
                    'content' => fn(UserLogin $login) => $login->browser
                ],
                [
                    'attribute' => 'created_at',
                    'content' => fn(UserLogin $login): string => Timeago::tag($login->created_at)
                ],
            ];
        }

        parent::init();
    }
}
