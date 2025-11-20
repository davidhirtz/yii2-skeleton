<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\forms\traits\UserFormTrait;
use davidhirtz\yii2\skeleton\models\User;
use Override;
use Yii;
use yii\base\Model;

class UserForm extends Model
{
    use UserFormTrait;
    use ModelTrait;

    public string|int|null $status = null;
    public ?string $newPassword = null;
    public bool $sendEmail = false;

    public function __construct(public User $user, array $config = [])
    {
        $this->setScenario($user->getIsNewRecord()
            ? ActiveRecord::SCENARIO_INSERT
            : ActiveRecord::SCENARIO_UPDATE);

        if ($user->getIsNewRecord()) {
            $user->loadDefaultValues();
            $user->status = User::STATUS_ENABLED;
        }

        $this->setAttributesFromUser();

        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['status', 'email'],
                'required',
            ],
            [
                ['newPassword', 'repeatPassword'],
                'trim',
            ],
            [
                ['newPassword'],
                'required',
                'on' => ActiveRecord::SCENARIO_INSERT,
            ],
            [
                ['newPassword'],
                'string',
                'min' => $this->user->passwordMinLength,
                'skipOnEmpty' => true,
            ],
            [
                ['repeatPassword'],
                'required',
                'when' => fn (self $model): bool => (bool)$model->newPassword,
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
        ];
    }


    #[Override]
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
            'timezone',
            'upload',
        ];

        if (!$this->user->isOwner()) {
            $attributes[] = 'status';
        }

        return [
            ActiveRecord::SCENARIO_INSERT => [
                ...$attributes,
                'sendEmail',
            ],
            ActiveRecord::SCENARIO_UPDATE => $attributes,
        ];
    }

    public function load($data, $formName = null): bool
    {
        $this->user->load($data, $formName);
        return parent::load($data, $formName);
    }

    public function save(): bool
    {
        if (!$this->validate() || !$this->beforeSave()) {
            return false;
        }

        if ($this->user->upsert(false)) {
            $this->afterSave();
            return true;
        }

        return false;
    }

    public function beforeSave(): bool
    {
        if ($this->getIsNewRecord()) {
            $this->user->created_by_user_id = Yii::$app->getUser()->getId();
        }

        if ($this->newPassword) {
            $this->user->generateAuthKey();
            $this->user->generatePasswordHash($this->newPassword);
        }

        return true;
    }

    public function afterSave(): void
    {
        $this->setAttributesFromUser();

        if (!$this->getIsNewRecord() && $this->newPassword) {
            $this->user->afterPasswordChange();
        }

        if ($this->sendEmail) {
            $this->sendCredentialsEmail();
        }
    }

    public function sendCredentialsEmail(): void
    {
        $language = Yii::$app->language;
        Yii::$app->language = $this->language ?: $language;

        Yii::$app->getMailer()->compose('@skeleton/mail/account/credentials', ['form' => $this])
            ->setSubject(Yii::t('skeleton', 'Your {name} Account', ['name' => Yii::$app->name]))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();

        Yii::$app->language = $language;
    }

    public function getIsNewRecord(): bool
    {
        return $this->scenario === ActiveRecord::SCENARIO_INSERT;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...$this->user->attributeLabels(),
            'newPassword' => $this->user->getIsNewRecord()
                ? Yii::t('skeleton', 'Password')
                : Yii::t('skeleton', 'New password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
            'sendEmail' => Yii::t('skeleton', 'Send user account details via email'),
        ];
    }
}
