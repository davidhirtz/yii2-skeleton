<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use Yii;
use yii\helpers\Url;

/**
 * Class BaseHomePanel.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\panels
 * @see HomePanel
 */
class BaseHomePanel implements HomePanelInterface
{
    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return Yii::t('app', 'Admin');
    }

    /**
     * @inheritdoc
     */
    public static function getListItems()
    {
        $user = Yii::$app->getUser();

        return [
            [
                'label' => Yii::t('app', 'Create New User'),
                'url' => ['/admin/user/create'],
                'icon' => 'user-plus',
                'visible' => $user->can('userCreate'),
            ],
            [
                'label' => Yii::t('app', 'Your Account'),
                'url' => ['/admin/account/update'],
                'icon' => 'user',
            ],
            [
                'label' => Yii::t('app', 'System Settings'),
                'url' => ['/admin/system/index'],
                'icon' => 'cog',
                'visible' => $user->can('admin'),
            ],
            [
                'label' => Yii::t('app', 'Homepage'),
                'url' => Url::home(),
                'icon' => 'globe',
                'options' => ['target' => '_blank'],
            ],
        ];
    }
}