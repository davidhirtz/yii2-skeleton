<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use Yii;

class UserOwnerPanel extends HelpPanel
{
    public string $type = self::TYPE_DANGER;

    #[\Override]
    public function init(): void
    {
        $this->title ??= Yii::t('skeleton', 'Transfer Ownership');

        $content = Yii::t('skeleton', 'You are currently the owner of this website, do you want to transfer the website ownership?');

        /** @see UserController::actionOwnership() */
        $button = Html::a(Yii::t('skeleton', 'Transfer Ownership'), ['ownership'], [
            'class' => 'btn btn-danger',
        ]);

        $this->content ??= $this->renderHelpBlock($content) . $this->renderButtonToolbar($button);

        parent::init();
    }
}
