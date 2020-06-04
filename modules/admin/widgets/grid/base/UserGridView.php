<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;

/**
 * Class UserGridView
 * @package app\modules\admin\components\widgets\grid
 * @see \davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserGridView
 *
 * @property UserActiveDataProvider $dataProvider
 */
class UserGridView extends GridView
{
    use StatusGridViewTrait;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->nameColumn(),
                $this->emailColumn(),
                $this->lastLoginColumn(),
                $this->createdAtColumn(),
                $this->buttonsColumn(),
            ];
        }

        if (!$this->rowOptions) {
            $this->rowOptions = function (User $user) {
                return ['class' => $user->isDisabled() ? 'disabled' : null];
            };
        }

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
                $timeago = Timeago::tag($user->last_login);
                return Yii::$app->getUser()->can('userUpdate', ['user' => $user]) ? Html::a($timeago, ['/admin/user-login/view', 'user' => $user->id]) : $timeago;
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
        if ($route = $this->getRoute($user)) {
            return Html::a(Icon::tag('wrench'), $route, ['class' => 'btn btn-secondary']);
        }

        if (Yii::$app->getUser()->can('authUpdate', ['user' => $user])) {
            return Html::a(Icon::tag('unlock-alt'), ['/admin/auth/view', 'user' => $user->id],
                [
                    'class' => 'btn btn-secondary',
                    'data-toggle' => 'tooltip',
                    'title' => Yii::t('skeleton', 'Permissions'),
                ]);
        }

        return [];
    }

    /**
     * @param \davidhirtz\yii2\skeleton\db\ActiveRecord $model
     * @param array $params
     * @return array|false
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