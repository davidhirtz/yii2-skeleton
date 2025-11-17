<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use Yii;

/**
 * @see UserController::actionDelete()
 */
class UserDeleteActiveForm extends DeleteActiveForm
{
    use TagTitleTrait;
    use UserWidgetTrait;

    #[\Override]
    public function init(): void
    {
        $this->message ??= Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!');
        $this->confirm ??= Yii::t('skeleton', 'Are you sure you want to delete this user?');
        $this->attribute = 'email';
        parent::init();
    }

    //    public function renderContent(): string|Stringable
    //    {
    //        return $this->user->isOwner()
    //            ? $this->getOwnerWarning()
    //            : $this->getPanel();
    //    }
    //
    //    protected function getPanel(): Stringable
    //    {
    //        return Panel::make()
    //            ->danger()
    //            ->title($this->title)
    //            ->content($this->getContent())
    //            ->buttons($this->getButton());
    //    }
    //
    //    protected function getContent(): string
    //    {
    //        return ;
    //    }
    //
    //    public function getButton(): string
    //    {
    //        return DeleteActiveForm::widget([
    //            'model' => $this->user,
    //            'attribute' => 'email',
    //            'message' => Yii::t('skeleton', 'Are you sure you want to delete this user?'),
    //            'confirm' => $this->title,
    //        ]);
    //    }
    //
    //    protected function getOwnerWarning(): Stringable
    //    {
    //        return Alert::make()
    //            ->content(Yii::t('skeleton', 'You cannot delete this user, because it is the owner of this website.'))
    //            ->icon('warning-triangle')
    //            ->warning();
    //    }
}
