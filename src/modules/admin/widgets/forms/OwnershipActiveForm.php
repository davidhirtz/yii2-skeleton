<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;

/**
 * @property OwnershipForm $model
 */
class OwnershipActiveForm extends ActiveForm
{
    #[\Override]
    public function init(): void
    {
        $this->buttons ??= [
            Button::danger(Yii::t('skeleton', 'Transfer'))
            ->type('submit')
        ];

        parent::init();
    }

    public function renderFields(): void
    {
        echo $this->getHelpText();
        echo $this->field($this->model, 'name');
    }

    protected function getHelpText(): string
    {
        return $this->textRow(Yii::t('skeleton', 'Enter the username of the user you want to make owner of this site. This will remove all your admin privileges and there is no going back. Please be certain!'));
    }
}
