<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Toolbar;
use Yii;

/**
 * Class UserToolbar
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base
 * @see \davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserToolbar
 */
class UserToolbar extends Toolbar
{
    /**
     * @var User
     */
    public $user;

    /**
     * Default actions.
     */
    public function init()
    {
        if (!$this->actions) {
            $this->actions = [$this->user ? $this->getFormSubmitButton() : $this->getCreateUserButton()] + [$this->getCreateUserButton(), $this->getCreateUserButton(), $this->getCreateUserButton()];
        }

        parent::init();
    }

    /**
     * @return string
     */
    protected function getCreateUserButton()
    {
        return Html::a(Html::iconText('user-plus', Yii::t('skeleton', 'New User')), ['create'], [
            'class' => 'btn btn-primary',
        ]);
    }

    /**
     * @return string
     */
    protected function getFormSubmitButton()
    {
        if (Yii::$app->getUser()->can('userCreate')) {
            return Html::submitButton(Yii::t('skeleton', 'Update'), [
                'class' => 'btn btn-primary btn-submit',
                'form' => 'user-form',
            ]);
        }

        return '';
    }
}