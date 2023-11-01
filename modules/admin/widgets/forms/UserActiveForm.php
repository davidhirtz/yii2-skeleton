<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

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
 * UserActiveForm is a widget that builds an interactive HTML form for {@link \davidhirtz\yii2\skeleton\models\forms\UserForm}.
 * @property UserForm|\davidhirtz\yii2\skeleton\models\forms\UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    /**
     * @var bool
     */
    public bool $hasStickyButtons = true;

    public function init(): void
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

    /** @noinspection PhpUnused {@see static::$fields} */
    public function oldPasswordField(array $options = []): ActiveField|string
    {
        return $this->model->password_hash ? $this->field($this->model, 'oldPassword', ['enableClientValidation' => false])->passwordInput($options) : '';
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function repeatPasswordField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'repeatPassword', ['enableClientValidation' => false])->passwordInput($options);
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function statusField(array $options = []): ActiveField|string
    {
        if ($this->model->isOwner()) {
            return '';
        }

        return $this->field($this->model, 'status')->widget(DynamicRangeDropdown::class, $options);
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function uploadField(array $options = []): ActiveField|string
    {
        return $this->getPicturePreview() . $this->field($this->model, 'upload')->fileInput($options);
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function countryField(array $options = ['options' => ['prompt' => '']]): ActiveField|string
    {
        return $this->field($this->model, 'country')->widget(DynamicRangeDropdown::class, $options);
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function languageField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'language')->widget(DynamicRangeDropdown::class, $options);
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function timezoneField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'timezone')->widget(DynamicRangeDropdown::class, $options);
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function sendEmailField(array $options = []): ActiveField|string
    {
        return $this->model->getIsNewRecord() ? $this->field($this->model, 'sendEmail')->checkbox($options) : '';
    }

    protected function getPicturePreview(): string
    {
        if (!$this->model->picture) {
            return '';
        }

        return $this->row($this->offset(Html::img($this->model->getBaseUrl() . $this->model->picture, ['style' => 'max-width:150px'])));
    }

    public function renderFooter(): void
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