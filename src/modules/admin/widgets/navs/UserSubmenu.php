<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use Override;
use Yii;

class UserSubmenu extends Submenu
{
    public ?User $user = null;

    #[Override]
    public function init(): void
    {
        if ($this->user && !$this->user->getIsNewRecord()) {
            if (!$this->title) {
                $name = $this->user->getUsername();
                $this->title = Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user])
                    ? Html::a($name, $this->user->getAdminRoute())
                    : $name;
            }

            $this->items = [
                ...$this->items,
                ...$this->getUserItems(),
            ];
        } else {
            if (!$this->title) {
                $this->title = Html::a(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
            }

            $this->items = [
                ...$this->items,
                ...$this->getDefaultItems(),
            ];
        }

        parent::init();
    }

    protected function getDefaultItems(): array
    {
        return array_filter([
            ...$this->getUserGridViewItems(),
            ...$this->getPermissionGridViewItems(),
            ...$this->getLoginGridViewItems(),
        ]);
    }

    protected function getUserItems(): array
    {
        return array_filter([
            ...$this->getUserFormItems(),
            ...$this->getUserPermissionGridViewItems(),
            ...$this->getUserLoginGridViewItems(),
            ...$this->getUserTrailGridViewItems(),
        ]);
    }

    protected function getUserGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Users'),
                'url' => ['/admin/user/index'],
                'icon' => 'users',
                'active' => ['user/(index|owner|create)'],
                'labelOptions' => [
                    'class' => 'd-none d-md-block d-active-block'
                ],
            ]
        ];
    }

    protected function getPermissionGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Permissions'),
                'url' => ['/admin/auth/index'],
                'icon' => 'unlock-alt',
                'visible' => Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    protected function getLoginGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Logins'),
                'url' => ['/admin/user-login/index'],
                'icon' => 'bars',
                'visible' => Yii::$app->getUser()->can(User::AUTH_USER_UPDATE),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    protected function getUserFormItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'User'),
                'url' => ['/admin/user/update', 'id' => $this->user->id],
                'visible' => Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user]),
                'icon' => 'user',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    protected function getUserPermissionGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Permissions'),
                'url' => ['/admin/auth/view', 'user' => $this->user->id],
                'visible' => Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $this->user]),
                'icon' => 'unlock-alt',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    protected function getUserLoginGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Logins'),
                'url' => ['/admin/user-login/view', 'user' => $this->user->id],
                'icon' => 'bars',
                'visible' => Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->user]),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    protected function getUserTrailGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'History'),
                'url' => ['/admin/trail/index', 'user' => $this->user->id],
                'icon' => 'history',
                'visible' => Yii::$app->getUser()->can('trailIndex'),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }
}
