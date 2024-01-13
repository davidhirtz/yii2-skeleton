<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use Yii;

/**
 * @see UserController::actionDelete()
 */
class UserDeletePanel extends Panel
{
    public ?User $user = null;

    /**
     * @var string|null the message to display above the "delete" button
     */
    public ?string $message = null;

    /**
     * @var string|null the confirmation message to display when the "delete" button is clicked
     */
    public ?string $confirm = null;

    public string $type = self::TYPE_DANGER;

    public function init(): void
    {
        $this->title ??= Yii::t('skeleton', 'Delete User');
        $this->message ??= Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!');
        $this->confirm ??= Yii::t('skeleton', 'Are you sure you want to delete this user?');

        $this->content ??= DeleteActiveForm::widget([
            'model' => $this->user,
            'attribute' => 'email',
            'message' => $this->message,
            'confirm' => $this->confirm,
        ]);

        parent::init();
    }

    public function render($view, $params = []): string
    {
        return $this->user->isOwner() ? $this->renderOwnerWarning() : parent::render($view, $params);
    }

    protected function renderOwnerWarning(): string
    {
        return Html::tag('div', Yii::t('skeleton', 'You cannot delete this user, because it is the owner of this website.'), [
            'class' => 'alert alert-warning',
        ]);
    }
}
