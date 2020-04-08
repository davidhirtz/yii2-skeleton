<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Class UserGridView
 * @package app\modules\admin\components\widgets\grid
 * @see \davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserGridView
 */
class UserGridView extends GridView
{
    use StatusGridViewTrait;

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
                        'content' => $this->getCreateUserButton(),
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
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => 'name',
            'content' => function (User $user) {
                $name = ($name = $user->getUsername()) ? Html::markKeywords($name, $this->getSearchKeywords()) : Html::tag('span', Yii::t('skeleton', 'User'), ['class' => 'text-muted']);
                return ($route = $this->getRoute($user)) ? Html::a($name, $route) : $name;
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
                return Html::markKeywords($user->email, $this->getSearchKeywords());
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
                return Html::buttons($this->getRowButtons($user));
            }
        ];
    }

    /**
     * @return string
     */
    protected function getCreateUserButton()
    {
        return Html::a(Html::iconText('user-plus', Yii::t('skeleton', 'New User')), ['create'], ['class' => 'btn btn-primary']);
    }

    /**
     * @param $user
     * @return array|string
     */
    protected function getRowButtons($user)
    {
        return ($route = $this->getRoute($user)) ? Html::a(Icon::tag('wrench'), $route, ['class' => 'btn btn-secondary']) : [];
    }

    /**
     * @param \davidhirtz\yii2\skeleton\db\ActiveRecord $model
     * @param array $params
     * @return array
     */
    protected function getRoute($model, $params = [])
    {
        return Yii::$app->getUser()->can('userUpdate', ['user' => $model]) ? parent::getRoute($model, $params) : false;
    }

    /**
     * @return \davidhirtz\yii2\skeleton\db\ActiveRecord|User
     */
    public function getModel()
    {
        return User::instance();
    }
}