<?php

namespace davidhirtz\yii2\skeleton\modules\admin\models\forms;

use davidhirtz\yii2\skeleton\models\User;
use yii\behaviors\BlameableBehavior;
use yii\db\BaseActiveRecord;
use Yii;

/**
 * UserForm extends {@link User}. It is used to update user information by an authorized administrator.
 */
class UserForm extends User
{
    public ?string $newPassword = null;
    public ?string $repeatPassword = null;

    /**
     * @var bool whether the credentials should be sent to the user's email address
     */
    public bool $sendEmail = false;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_by_user_id'],
                ],
            ]
        ];
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['newPassword'],
                'trim',
            ],
            [
                ['newPassword'],
                'string',
                'min' => $this->passwordMinLength,
                'skipOnEmpty' => true,
            ],
            [
                ['repeatPassword'],
                'required',
                'when' => fn(self $model): bool => (bool)$model->newPassword,
            ],
            [
                ['repeatPassword'],
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => Yii::t('skeleton', 'The password must match the new password.'),
            ],
            [
                ['sendEmail'],
                'boolean',
            ]
        ];
    }

    public function init(): void
    {
        $this->setScenario(static::SCENARIO_INSERT);
        parent::init();
    }

    public function afterFind(): void
    {
        $this->setScenario(static::SCENARIO_UPDATE);
        parent::afterFind();
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($this->newPassword) {
                $this->generateAuthKey();
                $this->generatePasswordHash($this->newPassword);
            }

            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert) {
            if (array_key_exists('password_hash', $changedAttributes)) {
                $this->afterPasswordChange();
            }
        }

        if ($this->sendEmail) {
            $this->sendCredentialsEmail();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function sendCredentialsEmail(): void
    {
        $language = Yii::$app->language;
        Yii::$app->language = $this->language ?: $language;

        Yii::$app->getMailer()->compose('@skeleton/mail/account/credentials', ['user' => $this])
            ->setSubject(Yii::t('skeleton', 'Your {name} Account', ['name' => Yii::$app->name]))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();

        Yii::$app->language = $language;
    }

    public function scenarios(): array
    {
        $attributes = [
            'city',
            'country',
            'email',
            'first_name',
            'language',
            'last_name',
            'name',
            'newPassword',
            'repeatPassword',
            'sendEmail',
            'status',
            'timezone',
            'upload',
        ];

        return [
            static::SCENARIO_INSERT => $attributes,
            static::SCENARIO_UPDATE => $attributes,
        ];
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'newPassword' => $this->getIsNewRecord()
                ? Yii::t('skeleton', 'Password')
                : Yii::t('skeleton', 'New password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
            'sendEmail' => Yii::t('skeleton', 'Send user account details via email'),
        ];
    }
}