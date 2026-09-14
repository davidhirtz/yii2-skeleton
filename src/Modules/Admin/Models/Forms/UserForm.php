<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Models\Forms;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Forms\Traits\UserFormTrait;
use Hirtz\Skeleton\Models\User;
use Override;
use Yii;
use yii\base\Model;
use Hirtz\Skeleton\Helpers\Url;

;

class UserForm extends Model
{
    use UserFormTrait;
    use ModelTrait;

    public const string SCENARIO_INSERT = 'insert';

    public string|int|null $status = null;
    public ?string $newPassword = null;
    public ?string $repeatPassword = null;
    public bool $sendEmail = false;

    private ?string $passwordResetUrl = null;
    private bool $isNewUser = false;

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
                'range' => array_keys(User::getStatusDefinitions()),
            ],
            [
                ['newPassword', 'repeatPassword'],
                'trim',
            ],
            [
                ['newPassword'],
                'string',
                'min' => $this->user->passwordMinLength,
                'max' => $this->user->passwordMaxLength,
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
                'message' => Yii::t('skeleton', 'COMMON_PASSWORD_MUST_MATCH'),
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
        $this->isNewUser = $this->user->getIsNewRecord();

        if (!$this->user->isOwner()) {
            $this->user->status = $this->status;
        }

        if ($this->newPassword) {
            $this->user->generateAuthKey();
            $this->user->generatePasswordHash($this->newPassword);
        }

        // An account an administrator creates is confirmed by them, not by an email the user has to answer
        if ($this->user->getIsNewRecord()) {
            $this->user->email_confirmed_at ??= new DateTime();
        }

        $this->user->created_by_user_id ??= Yii::$app->getUser()->getId();

        return true;
    }

    protected function afterSave(): void
    {
        if ($this->newPassword) {
            $this->user->afterPasswordChange();
            Yii::$app->getUser()->destroyOtherSessions($this->user);
        }

        // The credentials email never carries the password, so it needs a reset link whether or not one was set
        // here — and a user created without a password can only reach the account that way at all.
        if ($this->sendEmail || (!$this->newPassword && $this->isNewUser)) {
            $this->passwordResetUrl = $this->user->createPasswordResetUrl();
        }

        if ($this->sendEmail) {
            $this->sendCredentialsEmail();
        }
    }

    protected function sendCredentialsEmail(): void
    {
        Yii::$app->getI18n()->callback($this->user->language, function (): void {
            Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/credentials', ['form' => $this])
                ->setSubject(Yii::t('skeleton', 'USER_YOUR_ACCOUNT', ['name' => Yii::$app->name]))
                ->setFrom(Yii::$app->params['email'])
                ->setTo($this->user->email)
                ->send();
        });
    }

    public function getLoginUrl(): string
    {
        return Url::to(Yii::$app->getUser()->loginUrl, true);
    }

    /**
     * @return string|null the reset url the credentials email sends the user to, in place of a password. Only the
     *     token's HMAC is stored, so this is populated by {@see static::save()} and cannot be rebuilt later.
     */
    public function getPasswordResetUrl(): ?string
    {
        return $this->passwordResetUrl;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'newPassword' => $this->user->getIsNewRecord()
                ? Yii::t('skeleton', 'COMMON_PASSWORD')
                : Yii::t('skeleton', 'COMMON_NEW_PASSWORD'),
            'repeatPassword' => Yii::t('skeleton', 'USER_REPEATPASSWORD_LABEL'),
            'sendEmail' => Yii::t('skeleton', 'USER_SENDEMAIL_LABEL'),
        ];
    }
}
