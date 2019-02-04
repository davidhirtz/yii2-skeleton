<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class UserOwnerPanel.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets
 */
class UserOwnerPanel extends HelpPanel
{
    /**
     * @var string
     */
    public $type = 'danger';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->title === null) {
            $this->title = Yii::t('skeleton', 'Transfer Ownership');
        }

        if ($this->content === null) {
            $this->content = $this->renderHelpBlock(Yii::t('skeleton', 'You are currently the owner of this website, do you want to transfer the website ownership?')) .
                $this->renderButtonToolbar(Html::a(Yii::t('skeleton', 'Transfer Ownership'), ['ownership'], ['class' => 'btn btn-danger']));
        }

        parent::init();
    }
}

