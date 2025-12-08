<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\models\forms;

use Hirtz\Skeleton\base\traits\ModelTrait;
use Hirtz\Skeleton\models\forms\traits\UserFormTrait;
use Hirtz\Skeleton\models\User;
use Override;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

class UserForm extends Model
{
    use UserFormTrait;
    use ModelTrait;

    public const string SCENARIO_INSERT = 'insert';

    public string|int|null $status = null;
    public ?string $newPassword = null;
    public bool $sendEmail = false;

    public function __construct(public User $user, array $config = [])
    {
        $user->loadDefaultValues();
        $this->status = $user->status;

        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['status'],
                'in',
                'range' => array_keys(User::getStatuses()),
            ],
            [
                ['newPassword', 'repeatPassword'],
                'trim',
            ],
            [
                ['newPassword'],
                'required',
                'on' => self::SCENARIO_INSERT,
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
                'on' => self::SCENARIO_INSERT,
            ],
        ];
    }

    #[\Override]
    public function getScenario(): string
    {
        return $this->user->getIsNewRecord() ? self::SCENARIO_INSERT : Model::SCENARIO_DEFAULT;
    }

    protected function beforeSave(): bool
    {
        if (!$this->user->isOwner()) {
            $this->user->status = $this->status;
        }

        if ($this->newPassword) {
            $this->user->generateAuthKey();
            $this->user->generatePasswordHash($this->newPassword);
        }

        $this->user->created_by_user_id ??= Yii::$app->getUser()->getId();

        return true;
    }

    protected function afterSave(): void
    {
        if ($this->newPassword) {
            $this->user->afterPasswordChange();
        }

        if ($this->sendEmail) {
            $this->sendCredentialsEmail();
        }
    }

    protected function sendCredentialsEmail(): void
    {
        Yii::$app->getI18n()->callback($this->user->language, function () {
            Yii::$app->getMailer()->compose('@skeleton/mail/account/credentials', ['form' => $this])
                ->setSubject(Yii::t('skeleton', 'Your {name} Account', ['name' => Yii::$app->name]))
                ->setFrom(Yii::$app->params['email'])
                ->setTo($this->user->email)
                ->send();
        });
    }

    public function getLoginUrl(): string
    {
        return Url::to(Yii::$app->getUser()->loginUrl, true);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'newPassword' => $this->user->getIsNewRecord()
                ? Yii::t('skeleton', 'Password')
                : Yii::t('skeleton', 'New password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
            'sendEmail' => Yii::t('skeleton', 'Send user account details via email'),
        ];
    }
}
