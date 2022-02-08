<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\DynamicRangeDropdown;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\widgets\ActiveField;

/**
 * Class UserActiveForm
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base
 *
 * @property UserForm|\davidhirtz\yii2\skeleton\models\forms\UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public $hasStickyButtons = true;

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
                'language',
                'timezone',
                'upload',
                '-',
                'first_name',
                'last_name',
                'city',
                'country',
                'sendEmail',
            ];
        }

        parent::init();
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function oldPasswordField($options = [])
    {
        return $this->model->password ? $this->field($this->model, 'oldPassword', ['enableClientValidation' => false])->passwordInput($options) : '';
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function repeatPasswordField($options = [])
    {
        return $this->field($this->model, 'repeatPassword', ['enableClientValidation' => false])->passwordInput($options);
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function statusField($options = [])
    {
        if ($this->model->isOwner()) {
            return '';
        }

        return $this->field($this->model, 'status')->widget(DynamicRangeDropdown::class, $options);
    }

    /**
     * @param array $options
     * @return string
     */
    public function uploadField($options = [])
    {
        return $this->getPicturePreview() . $this->field($this->model, 'upload')->fileInput($options);
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function countryField($options = ['options' => ['prompt' => '']])
    {
        return $this->field($this->model, 'country')->widget(DynamicRangeDropdown::class, $options);
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function languageField($options = [])
    {
        return $this->field($this->model, 'language')->widget(DynamicRangeDropdown::class, $options);
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function timezoneField($options = [])
    {
        return $this->field($this->model, 'timezone')->widget(DynamicRangeDropdown::class, $options);
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function sendEmailField($options = [])
    {
        return $this->model->getIsNewRecord() ? $this->field($this->model, 'sendEmail')->checkbox($options) : '';
    }

    /**
     * @return string
     */
    protected function getPicturePreview()
    {
        if (!$this->model->picture) {
            return '';
        }

        return $this->row($this->offset(Html::img($this->model->getBaseUrl() . $this->model->picture, ['style' => 'max-width:150px'])));
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

        if (!$this->model->getIsNewRecord()) {
            if ($this->model->updated_at) {
                $hasTrailAuth = Yii::$app->getUser()->can('trailIndex');
                $text = Yii::t('skeleton', 'Last updated {timestamp}', [
                    'timestamp' => Timeago::tag($this->model->updated_at),
                ]);

                $items[] = $hasTrailAuth ? Html::a($text, Trail::getAdminRouteByModel(User::instance(), $this->model->id)) : $text;
            }

            if ($this->model->created_by_user_id) {
                $items[] = Yii::t('skeleton', 'Created by {user} {timestamp}', [
                    'timestamp' => Timeago::tag($this->model->created_at),
                    'user' => Html::username($this->model->admin, Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $this->model->admin]) ? ['/admin/user/update', 'id' => $this->model->id] : null),
                ]);
            } else {
                $items[] = Yii::t('skeleton', 'Signed up {timestamp}', [
                    'timestamp' => Timeago::tag($this->model->created_at),
                ]);
            }
        }

        return $items;
    }
}