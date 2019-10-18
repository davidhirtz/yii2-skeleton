<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Class UserGridView.
 * @package app\modules\admin\components\widgets\grid
 */
class UserGridView extends GridView
{
    /**
     * @var array
     */
    public $columns = [
        'status',
        'name',
        'email',
        'last_login',
        'created_at',
        'buttons'
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->header === null) {
            $this->header = [
                [
                    [
                        'content' => $this->getSearchInput(),
                        'options' => [
                            'class' => 'col-12 col-md-6',
                        ],
                    ],
                    'options' => [
                        'class' => 'justify-content-end',
                    ],
                ],
            ];
        }

        if ($this->footer === null) {
            $this->footer = [
                [
                    [
                        'content' => $this->createUserButton(),
                        'visible' => Yii::$app->getUser()->can('userCreate'),
                        'options' => [
                            'class' => 'col-12',
                        ],
                    ],
                ],
            ];
        }

        $this->rowOptions = function (User $user) {
            return ['class' => $user->isDisabled() ? 'disabled' : null];
        };

        parent::init();
    }

    /**
     * @return string
     */
    public function createUserButton()
    {
        return Html::a(Html::iconText('user-plus', Yii::t('skeleton', 'New User')), ['create'], ['class' => 'btn btn-primary']);
    }

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (User $user) {
                return Icon::tag($user->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $user->getStatusName()
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => 'name',
            'content' => function (User $user) {
                $name = $user->getFullName();
                $name = Html::markKeywords($name ? "{$user->name} ($name)" : $user->name, $this->getSearchKeywords());
                return Html::a($name, ['update', 'id' => $user->id]);
            }
        ];
    }

    /**
     * @return array
     */
    public function emailColumn()
    {
        return [
            'attribute' => 'email',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (User $user) {
                return Html::a(Html::markKeywords($user->email, $this->getSearchKeywords()), [
                    'update',
                    'id' => $user->id
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function lastLoginColumn()
    {
        return [
            'attribute' => 'last_login',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (User $user) {
                return Html::a(Timeago::tag($user->last_login), ['/admin/user-login/view', 'user' => $user->id]);
            }
        ];
    }

    /**
     * @return array
     */
    public function createdAtColumn()
    {
        return [
            'attribute' => 'created_at',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (User $user) {
                return Timeago::tag($user->created_at);
            }
        ];
    }

    /**
     * @return array
     */
    public function buttonsColumn()
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-right'],
            'content' => function (User $user) {
                $buttons = [];

                if (Yii::$app->getUser()->can('userUpdate', ['user' => $user])) {
                    $buttons[] = Html::a(Icon::tag('wrench'), [
                        'update',
                        'id' => $user->id
                    ], ['class' => 'btn btn-secondary']);
                }

                return Html::buttons($buttons);
            }
        ];
    }
}