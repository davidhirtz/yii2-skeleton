<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\forms\DynamicRangeDropdown;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\widgets\ActiveField;

trait UserFormTrait
{
    /** @uses User::getCountries() */
    public function countryField(array $options = ['options' => ['prompt' => '']]): ActiveField|string
    {
        return $this->field($this->model, 'country')->widget(DynamicRangeDropdown::class, $options);
    }

    public function emailField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'email')->input('email', $options);
    }

    /** @uses User::getLanguages() */
    public function languageField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'language')->widget(DynamicRangeDropdown::class, $options);
    }

    public function newPassword(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'email')->passwordInput($options);
    }

    public function repeatPasswordField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'repeatPassword', ['enableClientValidation' => false])->passwordInput($options);
    }

    /** @uses User::getStatuses() */
    public function statusField(array $options = []): ActiveField|string
    {
        if ($this->model->isOwner()) {
            return '';
        }

        return $this->field($this->model, 'status')->widget(DynamicRangeDropdown::class, $options);
    }

    /** @uses User::getTimezones() */
    public function timezoneField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'timezone')->widget(DynamicRangeDropdown::class, $options);
    }

    public function uploadField(array $options = []): ActiveField|string
    {
        return $this->getPicturePreview() . $this->field($this->model, 'upload')->fileInput($options);
    }

    protected function getPicturePreview(): string
    {
        if (!$this->model->picture) {
            return '';
        }

        return $this->row($this->offset(Html::img($this->model->getBaseUrl() . $this->model->picture, [
            'style' => 'max-width:150px',
        ])));
    }

    public function renderFooter(): void
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

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