<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @property UserActiveDataProvider $dataProvider
 */
class UserGridView extends GridView
{
    use StatusGridViewTrait;

    public function init(): void
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
            $this->rowOptions = fn(User $user) => ['class' => $user->isDisabled() ? 'disabled' : null];
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
                        'visible' => Yii::$app->getUser()->can(User::AUTH_USER_CREATE),
                        'options' => [
                            'class' => 'col-12',
                        ],
                    ],
                ],
            ];
        }

        parent::init();
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => 'name',
            'content' => function (User $user) {
                $name = ($name = $user->getUsername()) ? Html::markKeywords($name, $this->getSearchKeywords()) : Html::tag('span', Yii::t('skeleton', 'User'), ['class' => 'text-muted']);
                return ($route = $this->getRoute($user)) ? Html::a($name, $route) : $name;
            }
        ];
    }

    public function emailColumn(): array
    {
        return [
            'attribute' => 'email',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (User $user) {
                $email = $user->email
                    ? Html::markKeywords(Html::encode($user->email), $this->getSearchKeywords())
                    : '';

                return !$user->isUnconfirmed() ? $email : Html::tag('span', $email, [
                    'class' => 'text-muted',
                    'data-toggle' => 'tooltip',
                    'title' => Yii::t('skeleton', 'Unconfirmed email'),
                ]);
            }
        ];
    }

    public function lastLoginColumn(): array
    {
        return [
            'attribute' => 'last_login',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (User $user) {
                $timeago = Timeago::tag($user->last_login);
                return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $user]) ? Html::a($timeago, ['/admin/user-login/view', 'user' => $user->id]) : $timeago;
            }
        ];
    }

    public function createdAtColumn(): array
    {
        return [
            'class' => TimeagoColumn::class,
            'attribute' => 'created_at',
            'displayAtBreakpoint' => 'lg',
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-right'],
            'content' => fn(User $user): string => Html::buttons($this->getRowButtons($user))
        ];
    }

    protected function getCreateUserButton(): string
    {
        return Html::a(Html::iconText('user-plus', Yii::t('skeleton', 'New User')), ['/admin/user/create'], ['class' => 'btn btn-primary']);
    }

    protected function getRowButtons(User $user): array|string
    {
        if ($route = $this->getRoute($user)) {
            return Html::a(Icon::tag('wrench'), $route, ['class' => 'btn btn-primary']);
        }

        if (Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $user])) {
            return Html::a(Icon::tag('unlock-alt'), ['/admin/auth/view', 'user' => $user->id],
                [
                    'class' => 'btn btn-primary',
                    'data-toggle' => 'tooltip',
                    'title' => Yii::t('skeleton', 'Permissions'),
                ]);
        }

        return [];
    }

    /**
     * @param User $model
     */
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $model]) ? ['/admin/user/update', 'id' => $model->id, ...$params] : false;
    }

    public function getModel(): User
    {
        return User::instance();
    }
}