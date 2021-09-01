<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel;
use Yii;

/**
 * Class UserHelpPanel
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\panels\base
 */
class UserHelpPanel extends HelpPanel
{
    /**
     * @var User
     */
    public $user;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->title === null) {
            $this->title = Yii::t('skeleton', 'Operations');
        }

        if ($this->content === null) {
            $this->content = $this->renderButtonToolbar($this->getButtons());
        }

        parent::init();
    }


    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return [
            $this->getDeletePictureButton(),
            $this->getDisableGoogleAuthenticatorButton(),
            $this->getCreatePasswordResetLinkButton(),
            $this->getPasswordResetLinkButton(),
        ];
    }

    /**
     * @return string
     */
    protected function getDeletePictureButton()
    {
        if (!$this->user->picture) {
            return '';
        }

        return Html::a(Html::iconText('portrait', Yii::t('skeleton', 'Delete picture')), ['delete-picture', 'id' => $this->user->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }

    protected function getDisableGoogleAuthenticatorButton()
    {
        if (!$this->user->google_2fa_secret) {
            return '';
        }

        return Html::a(Html::iconText('google', Yii::t('skeleton', 'Disable Google Authenticator')), ['delete-picture', 'id' => $this->user->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }

    /**
     * @return string
     */
    protected function getCreatePasswordResetLinkButton()
    {
        return Html::a(Html::iconText('key', Yii::t('skeleton', 'Create password link')), ['reset', 'id' => $this->user->id], [
            'class' => 'btn btn-primary',
            'data-confirm' => $this->user->password_reset_code ? Yii::t('skeleton', 'Are you sure you want to create a new password reset link? The current link will be invalidated.') : null,
            'data-method' => 'post',
        ]);
    }

    /**
     * @return string
     */
    protected function getPasswordResetLinkButton()
    {
        if (!$this->user->password_reset_code) {
            return '';
        }

        return Html::button(Html::iconText('clipboard', Yii::t('skeleton', 'Show password link')), [
            'class' => 'btn btn-secondary',
            'data-confirm' => Html::tag('div', $this->user->getPasswordResetUrl(), ['class' => 'text-break']),
        ]);
    }
}