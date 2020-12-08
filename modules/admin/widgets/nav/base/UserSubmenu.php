<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use Yii;

/**
 * Class UserSubmenu
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base
 */
class UserSubmenu extends Submenu
{
    /**
     * @var User
     */
    public $user;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        if ($this->user && !$this->user->getIsNewRecord()) {
            if (!$this->title) {
                $name = $this->user->getUsername();
                $this->title = Yii::$app->getUser()->can('userUpdate', ['user' => $this->user]) ? Html::a($name, ['/admin/user/update', 'id' => $this->user->id]) : $name;
            }

            $this->items = array_merge($this->items, $this->getUserItems());

        } else {
            if (!$this->title) {
                $this->title = Html::a(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
            }

            $this->items = array_merge($this->items, $this->getDefaultItems());
        }

        parent::init();
    }

    /**
     * @return array
     */
    protected function getDefaultItems(): array
    {
        return array_filter(array_merge(
            $this->getUserGridViewItems(),
            $this->getPermissionGridViewItems(),
            $this->getLoginGridViewItems(),
        ));
    }

    /**
     * @return array
     */
    protected function getUserItems(): array
    {
        return array_filter(array_merge(
            $this->getUserFormItems(),
            $this->getUserPermissionGridViewItems(),
            $this->getUserLoginGridViewItems(),
            $this->getUserTrailGridViewItems(),
        ));
    }

    /**
     * @return array
     */
    protected function getUserGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Users'),
                'url' => ['/admin/user/index'],
                'icon' => 'users',
                'active' => ['user/(index|owner|create)'],
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getPermissionGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Permissions'),
                'url' => ['/admin/auth/index'],
                'icon' => 'unlock-alt',
                'visible' => Yii::$app->getUser()->can('authUpdate'),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getLoginGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Logins'),
                'url' => ['/admin/user-login/index'],
                'icon' => 'bars',
                'visible' => Yii::$app->getUser()->can('userUpdate'),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getUserFormItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'User'),
                'url' => ['/admin/user/update', 'id' => $this->user->id],
                'visible' => Yii::$app->getUser()->can('userUpdate', ['user' => $this->user]),
                'icon' => 'user',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getUserPermissionGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Permissions'),
                'url' => ['/admin/auth/view', 'user' => $this->user->id],
                'visible' => Yii::$app->getUser()->can('authUpdate', ['user' => $this->user]),
                'icon' => 'unlock-alt',
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getUserLoginGridViewItems(): array
    {
        return [
            [
                'label' => Yii::t('skeleton', 'Logins'),
                'url' => ['/admin/user-login/view', 'user' => $this->user->id],
                'icon' => 'bars',
                'visible' => Yii::$app->getUser()->can('userUpdate'),
                'labelOptions' => [
                    'class' => 'd-none d-md-inline'
                ],
            ]
        ];
    }

    /**
     * @return array
     */
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