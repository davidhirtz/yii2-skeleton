<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Override;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<User>
 * @property UserActiveDataProvider $dataProvider
 */
class UserGridView extends GridView
{
    use StatusGridViewTrait;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'users');

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
            $this->rowOptions = fn (User $user) => ['class' => $user->isDisabled() ? 'disabled' : null];
        }

        parent::init();
    }

    protected function initHeader(): void
    {
        $this->header ??= [
            [
                $this->search->getColumn(),
            ],
        ];
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->getCreateUserButton(),
                    'visible' => Yii::$app->getUser()->can(User::AUTH_USER_CREATE),
                ],
            ],
        ];
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => 'name',
            'content' => function (User $user) {
                $name = ($name = $user->getUsername())
                    ? Html::tag('strong', Html::markKeywords($name, $this->search->getKeywords()))
                    : Html::tag('span', Yii::t('skeleton', 'User'), ['class' => 'text-muted']);

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
                    ? Html::markKeywords(Html::encode($user->email), $this->search->getKeywords())
                    : '';

                if (!$user->isUnconfirmed()) {
                    return $email;
                }

                return Html::tag('span', $email, [
                    'class' => 'text-muted',
                    'data-tooltip' => '',
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
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            'content' => function (User $user) {
                $timeago = Timeago::tag($user->last_login);

                return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $user])
                    ? Html::a($timeago, ['/admin/user-login/view', 'user' => $user->id])
                    : $timeago;
            }
        ];
    }

    public function createdAtColumn(): array
    {
        return [
            'class' => TimeagoColumn::class,
            'attribute' => 'created_at',
            'displayAtBreakpoint' => 'lg',
            'contentOptions' => [
                'class' => 'text-nowrap',
            ],
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => fn (User $user) => $this->getRowButtons($user),
        ];
    }

    protected function getCreateUserButton(): string
    {
        return Html::a(Html::iconText('user-plus', Yii::t('skeleton', 'New User')), ['/admin/user/create'], ['class' => 'btn btn-primary']);
    }

    protected function getRowButtons(User $user): array|string
    {
        if ($route = $this->getRoute($user)) {
            return Button::make()
->primary()
                ->href($route)
                ->icon('wrench')
                ->render();
        }

        if (Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $user])) {
            return Button::make()
->primary()
                ->href(['/admin/auth/assign', 'user' => $user->id])
                ->icon('unlock-alt')
                ->tooltip(Yii::t('skeleton', 'Permissions'))
                ->render();
        }

        return [];
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $model])
            ? ['/admin/user/update', 'id' => $model->id, ...$params]
            : false;
    }

    #[Override]
    public function getModel(): User
    {
        return User::instance();
    }
}
