<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserActiveFormTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;
use yii\widgets\ActiveField;

/**
 * @property AccountUpdateForm $model
 */
class AccountActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

    public bool $hasStickyButtons = true;

    /**
     * @uses static::emailField()
     * @uses static::newPasswordField()
     * @uses static::repeatPasswordField()
     * @uses static::oldPasswordField()
     * @uses static::languageField()
     * @uses static::timezoneField()
     * @uses static::countryField()
     */
    #[\Override]
    public function init(): void
    {
        $this->fields ??= [
            'name',
            'email',
            'newPassword',
            'repeatPassword',
            '-',
            'oldPassword',
            '-',
            'language',
            'timezone',
            '-',
            'first_name',
            'last_name',
            'city',
            'country',
        ];

        parent::init();
    }

    public function oldPasswordField(array $options = []): ActiveField|string
    {
        if (!$this->model->user->password_hash) {
            return '';
        }

        $options['enableClientValidation'] ??= false;

        $field = $this->field($this->model, 'oldPassword', $options)
            ->passwordInput();

        if ($icon = $this->getOldPasswordFieldIcon()) {
            $field->appendInput($icon);
        }

        return $field;
    }

    protected function getOldPasswordFieldIcon(array $options = []): string
    {
        return Icon::tag('info-circle')
            ->tooltip(Yii::t('skeleton', 'Only needed, if you want to change your password'))
            ->addAttributes($options)
            ->render();
    }
}
