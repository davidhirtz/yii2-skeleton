<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

class UserOwnerPanel extends HelpPanel
{
    public string $type = 'danger';

    public function init(): void
    {
        $this->title ??= Yii::t('skeleton', 'Transfer Ownership');

        $this->content ??= $this->renderHelpBlock(Yii::t('skeleton', 'You are currently the owner of this website, do you want to transfer the website ownership?'))
            . $this->renderButtonToolbar(Html::a(Yii::t('skeleton', 'Transfer Ownership'), ['ownership'], ['class' => 'btn btn-danger']));

        parent::init();
    }
}

