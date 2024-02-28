<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;
use yii\base\Model;

/**
 * @property ClientInterface|null $client {@see static::getClient()}
 */
class AuthClientSignupForm extends Model
{
    use ModelTrait;
    use SignupEmailTrait;

    public ?string $externalPictureUrl = null;
    public ?User $user = null;

    /**
     * @var string|false the pattern for all characters that should be removed, set false to disable filter
     */
    public string|false $namePatternFilter = '/[^a-z0-9.-]/';

    private ?ClientInterface $_client = null;

    public function init(): void
    {
        $this->user ??= User::create();
        parent::init();
    }

    public function rules(): array
    {
        return [
            [
                ['externalPictureUrl'],
                $this->validateExternalPictureUrl(...),
            ],
        ];
    }

    public function validateExternalPictureUrl(): void
    {
    }

    public function beforeValidate(): bool
    {
        if (!Yii::$app->getUser()->isSignupEnabled()) {
            $this->addError('id', Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
            return false;
        }

        $this->sanitizeName();

        if (!in_array($this->user->language, Yii::$app->getI18n()->languages)) {
            $this->user->language = Yii::$app->language;
        }

        return parent::beforeValidate();
    }

    protected function sanitizeName(): void
    {
        if (!$this->user->name && $this->user->requireName) {
            $fullName = implode('-', array_filter([$this->user->first_name, $this->user->last_name]));
            $this->user->name = mb_strtolower($fullName, Yii::$app->charset) ?: explode('@', $this->user->email)[0];
        }

        if ($this->user->name && $this->namePatternFilter) {
            $this->user->name = preg_replace($this->namePatternFilter, '', $this->user->name);
        }
    }

    public function afterValidate(): void
    {
        if (!$this->hasErrors() && !$this->user->validate()) {
            $this->addErrors($this->user->getErrors());
        }

        if ($this->user->email && $this->hasErrors('email')) {
            $this->clearErrors('email');
            $this->addContextToEmailError();
        }

        parent::afterValidate();
    }

    protected function addContextToEmailError(): void
    {
        $this->addError('email', Yii::t('skeleton', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
            'client' => $this->getClient()->getTitle(),
            'email' => $this->user->email,
        ]));
    }

    public function insert(): bool
    {
        if (!$this->validate() || !$this->beforeInsert()) {
            return false;
        }

        if ($this->user->insert(false)) {
            $this->afterInsert();
            return true;
        }

        return false;
    }

    public function beforeInsert(): bool
    {
        if ($this->externalPictureUrl) {
            $form = UserPictureForm::create(['user' => $this->user]);

            $form->upload = new StreamUploadedFile([
                'allowedExtensions' => $form->uploadExtensions,
                'url' => $this->externalPictureUrl,
            ]);

            $form->save();
        }

        $this->user->generateVerificationToken();

        return true;
    }

    public function afterInsert(): void
    {
        if (!$this->user->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            Yii::$app->getUser()->login($this->user);
        }

        $this->sendSignupEmail();
    }

    public function setClient(ClientInterface $client): void
    {
        $this->user->setAttributes($client->getSafeUserAttributes());
        Yii::$app->getUser()->loginType = $client->getName();

        $this->_client = $client;
    }

    public function getClient(): ?ClientInterface
    {
        return $this->_client;
    }
}
