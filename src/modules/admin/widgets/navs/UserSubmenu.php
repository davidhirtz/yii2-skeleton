<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\navs;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\navs\Submenu;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use Override;
use Yii;

class UserSubmenu extends Submenu
{
    use UserWidgetTrait;

    #[Override]
    public function init(): void
    {
        if ($this->user && !$this->user->getIsNewRecord()) {
            $this->title ??= $this->user->getUsername();
            $this->url ??= $this->user->getAdminRoute();

            $this->items = [
                ...$this->items,
                ...$this->getUserItems(),
            ];
        } else {
            $this->title ??= Yii::t('skeleton', 'Users');
            $this->url ??= ['/admin/user/index'];

            $this->items = [
                ...$this->items,
                ...$this->getDefaultItems(),
            ];
        }

        parent::init();
    }

    protected function getDefaultItems(): array
    {
        return [
            ...$this->getUserGridViewItems(),
            ...$this->getPermissionGridViewItems(),
            ...$this->getLoginGridViewItems(),
        ];
    }

    protected function getUserItems(): array
    {
        return [
            ...$this->getUserFormItems(),
            ...$this->getUserPermissionGridViewItems(),
            ...$this->getUserLoginGridViewItems(),
            ...$this->getUserTrailGridViewItems(),
        ];
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
