<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\TimezoneDropdown;
use davidhirtz\yii2\timeago\Timeago;
use Yii;
use yii\widgets\ActiveField;

trait UserActiveFormTrait
{
    public function countryField(array $options = []): ActiveField|string
    {
        if (!$this->model->isAttributeRequired('country')) {
            $options['inputOptions']['prompt'] ??= '';
        }

        $items = $this->model->user::getCountries();

        return $this->field($this->model, 'country', $options)->dropDownList($items);
    }

    public function emailField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'email', $options)->input('email');
    }

    public function languageField(array $options = []): ActiveField|string
    {
        $items = ArrayHelper::getColumn($this->model->user::getLanguages(), 'name');
        return $this->field($this->model, 'language', $options)->dropDownList($items);
    }

    public function newPasswordField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'newPassword', $options)->passwordInput();
    }

    /**
     * @uses UserForm::$repeatPassword
     */
    public function repeatPasswordField(array $options = []): ActiveField|string
    {
        $options['enableClientValidation'] ??= false;
        return $this->field($this->model, 'repeatPassword', $options)
            ->passwordInput();
    }

    /**
     * @uses User::getTimezones()
     */
    public function timezoneField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'timezone', $options)->widget(TimezoneDropdown::class);
    }

    public function uploadField(array $options = []): ActiveField|string
    {
        return $this->getPicturePreview()
            . $this->field($this->model, 'upload')->fileInput($options);
    }

    protected function getPicturePreview(): string
    {
        if (!$this->model->user->picture) {
            return '';
        }

        return $this->row($this->offset(Html::img($this->model->user->getPictureUrl(), [
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
        $user = $this->model->user;
        $items = [];

        if (!$user->getIsNewRecord()) {
            if ($user->updated_at) {
                $hasTrailAuth = Yii::$app->getUser()->can('trailIndex');

                $text = Yii::t('skeleton', 'Last updated {timestamp}', [
                    'timestamp' => Timeago::tag($user->updated_at),
                ]);

                $items[] = $hasTrailAuth
                    ? Html::a($text, Trail::getAdminRouteByModel(User::instance(), $user->id))
                    : $text;
            }

            if ($user->created_by_user_id) {
                $route = Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $user->admin])
                    ? ['/admin/user/update', 'id' => $user->id]
                    : null;

                $items[] = Yii::t('skeleton', 'Created by {user} {timestamp}', [
                    'timestamp' => Timeago::tag($user->created_at),
                    'user' => Html::username($user->admin, $route),
                ]);
            } else {
                $items[] = Yii::t('skeleton', 'Signed up {timestamp}', [
                    'timestamp' => Timeago::tag($user->created_at),
                ]);
            }
        }

        return $items;
    }
}
