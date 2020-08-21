<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\CountryDropdown;
use davidhirtz\yii2\skeleton\widgets\forms\LanguageDropdown;
use davidhirtz\yii2\skeleton\widgets\forms\TimezoneDropdown;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class UserActiveForm.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base
 *
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                'status',
                'name',
                ['email', 'email'],
                ['newPassword', 'password'],
                'repeatPassword',
                'oldPassword',
                '-',
                ['language', LanguageDropdown::class],
                ['timezone', TimezoneDropdown::class],
                ['upload', 'fileInput'],
                '-',
                'first_name',
                'last_name',
                'city',
                ['country', CountryDropdown::class],
                'sendEmail',
            ];
        }

        parent::init();
    }

    /**
     * Renders user information footer.
     */
    public function renderFooter()
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    /**
     * @return array
     */
    protected function getFooterItems(): array
    {
        $items = [];

        if ($this->model->updated_at) {
            $items[] = Yii::t('skeleton', 'Last updated {timestamp}', [
                'timestamp' => Timeago::tag($this->model->updated_at),
            ]);
        }

        if ($this->model->created_by_user_id) {
            $items[] = Yii::t('skeleton', 'Created by {user} {timestamp}', [
                'timestamp' => Timeago::tag($this->model->created_at),
                'user' => Html::username($this->model->admin, Yii::$app->getUser()->can('userUpdate', ['user' => $this->model]) ? ['/admin/user/update', 'id' => $this->model->id] : null),
            ]);
        } else {
            $items[] = Yii::t('skeleton', 'Signed up {timestamp}', [
                'timestamp' => Timeago::tag($this->model->created_at),
            ]);
        }

        return $items;
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function oldPasswordField($options = [])
    {
        return $this->model->password ? $this->field($this->model, 'oldPassword', ['enableClientValidation' => false])->passwordInput($options) : '';
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function repeatPasswordField($options = [])
    {
        return $this->field($this->model, 'repeatPassword', ['enableClientValidation' => false])->passwordInput($options);
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function statusField($options = [])
    {
        if ($this->model->isOwner()) {
            return null;
        }

        $statusOptions = ArrayHelper::getColumn(User::getStatuses(), 'name');
        return count($statusOptions) > 1 ? $this->field($this->model, 'status')->dropDownList($statusOptions, $options) : '';
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function sendEmailField($options = [])
    {
        return $this->model->getIsNewRecord() ? $this->field($this->model, 'sendEmail')->checkbox($options) : '';
    }
}