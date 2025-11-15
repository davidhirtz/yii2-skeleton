<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DeleteGridButton;
use davidhirtz\yii2\skeleton\widgets\panels\Panel;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @see UserController::actionDelete()
 */
class UserDeletePanel extends Widget
{
    use UserWidgetTrait;

    public function renderContent(): string|Stringable
    {
        return $this->user->isOwner()
            ? $this->getOwnerWarning()
            : $this->getPanel();
    }

    protected function getPanel(): Stringable
    {
        return Panel::make()
            ->danger()
            ->title($this->getTitle())
            ->content($this->getContent())
            ->buttons($this->getButton());
    }

    protected function getTitle(): string
    {
        return Yii::t('skeleton', 'Delete User');
    }

    protected function getContent(): string
    {
        return Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!');
    }

    public function getButton(): Stringable
    {
        DeleteGridButton::
        $this->confirm ??= Yii::t('skeleton', 'Are you sure you want to delete this user?');

        $this->content ??= DeleteActiveForm::widget([
            'model' => $this->user,
            'attribute' => 'email',
            'message' => $this->message,
            'confirm' => $this->confirm,
        ]);

        parent::init();
    }

    protected function getOwnerWarning(): string
    {
        return Html::tag('div', Yii::t('skeleton', 'You cannot delete this user, because it is the owner of this website.'), [
            'class' => 'alert alert-warning',
        ]);
    }
}
