<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Toolbar;
use Yii;

/**
 * Class UserToolbar
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base
 * @see \davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserToolbar
 *
 * @protected User $model
 */
class UserToolbar extends Toolbar
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->actions) {
            $this->actions = [$this->model ? $this->getFormSubmitButton() : $this->getCreateUserButton()];
        }

        parent::init();
    }

    /**
     * @return string
     */
    protected function getCreateUserButton()
    {
        if (Yii::$app->getUser()->can('userCreate')) {
            return Html::a(Html::iconText('user-plus', Yii::t('skeleton', 'New User')), ['create'], [
                'class' => 'btn btn-primary',
            ]);
        }

        return '';
    }
}