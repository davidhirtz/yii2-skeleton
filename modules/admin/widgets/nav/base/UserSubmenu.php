<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use Yii;

/**
 * Class UserSubmenu.
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
            $this->items = [
                [
                    'label' => Yii::t('skeleton', 'User'),
                    'url' => ['/admin/user/update', 'id' => $this->user->id],
                    'icon' => 'user hidden-sm hidden-xs',
                ],
                [
                    'label' => Yii::t('skeleton', 'Permissions'),
                    'url' => ['/admin/auth/view', 'user' => $this->user->id],
                    'icon' => 'unlock-alt hidden-sm hidden-xs',
                    'visible' => Yii::$app->getUser()->can('authUpdate', ['user' => $this->user]),
                ],
                [
                    'label' => Yii::t('skeleton', 'Logins'),
                    'url' => ['/admin/user-login/view', 'user' => $this->user->id],
                    'icon' => 'bars hidden-sm hidden-xs',
                    'visible' => Yii::$app->getUser()->can('userUpdate'),
                ],
            ];

            if(!$this->title) {
                $this->title = Html::a($this->user->getOldAttribute('name'), ['/admin/user/update', 'id' => $this->user->id]);
            }

        } else {
            $this->items = [
                [
                    'label' => Yii::t('skeleton', 'Users'),
                    'url' => ['/admin/user/index'],
                    'icon' => 'users hidden-sm hidden-xs',
                    'active' => ['user/(index|owner|create)'],
                ],
                [
                    'label' => Yii::t('skeleton', 'Permissions'),
                    'url' => ['/admin/auth/index'],
                    'icon' => 'unlock-alt hidden-sm hidden-xs',
                    'visible' => Yii::$app->getUser()->can('authUpdate'),
                ],
                [
                    'label' => Yii::t('skeleton', 'Logins'),
                    'url' => ['/admin/user-login/index'],
                    'icon' => 'bars hidden-sm hidden-xs',
                    'visible' => Yii::$app->getUser()->can('userUpdate'),
                ],
            ];

            if(!$this->title) {
                $this->title = Html::a(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
            }
        }

        parent::init();
    }
}