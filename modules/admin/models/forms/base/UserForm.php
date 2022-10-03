<?php

namespace davidhirtz\yii2\skeleton\modules\admin\models\forms\base;

use davidhirtz\yii2\skeleton\models\User;
use yii\behaviors\BlameableBehavior;
use yii\db\BaseActiveRecord;
use Yii;

/**
 * UserForm extends {@link User}. It is used to update user information by an authorized administrator.
 *
 * @method static \davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm findOne($condition)
 */
class UserForm extends User
{
    /**
     * @var string
     */
    public $newPassword;

    /**
     * @var string
     */
    public $repeatPassword;

    /**
     * @var bool whether the credentials should be sent to the user's email address
     */
    public $sendEmail;

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_by_user_id'],
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
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
                'when' => function (self $model) {
                    return (bool)$model->newPassword;
                },
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
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setScenario(static::SCENARIO_INSERT);
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->setScenario(static::SCENARIO_UPDATE);
        parent::afterFind();
    }

    /**
     * @param bool $insert
     * @return bool
     */
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

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
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

    /**
     * Sends user credentials via email.
     */
    public function sendCredentialsEmail()
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

    /**
     * @return array
     */
    public function scenarios()
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'newPassword' => $this->getIsNewRecord() ? Yii::t('skeleton', 'Password') : Yii::t('skeleton', 'New password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
            'sendEmail' => Yii::t('skeleton', 'Send user account details via email'),
        ]);
    }
}