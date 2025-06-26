<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;
use yii\base\Model;

class AuthClientSignupForm extends Model
{
    use ModelTrait;
    use SignupEmailTrait;

    public ?string $externalPictureUrl = null;

    /**
     * @var string|false the pattern for all characters that should be removed, set false to disable filter
     */
    public string|false $namePatternFilter = '/[^a-z0-9.-]/';

    public function __construct(
        public ClientInterface $client,
        public ?User $user = null,
        $config = []
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        $this->user ??= User::create();
        $this->user->setAttributes($this->client->getSafeUserAttributes());

        parent::init();
    }

    #[\Override]
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

    #[\Override]
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
            $name = implode('-', array_filter([$this->user->first_name, $this->user->last_name]));
            $name = mb_strtolower($name, Yii::$app->charset);

            if (!$name && $this->user->email) {
                $name = explode('@', $this->user->email)[0];
            }

            $this->user->name = $name;
        }

        if ($this->user->name && $this->namePatternFilter) {
            $this->user->name = preg_replace($this->namePatternFilter, '', $this->user->name);
        }
    }

    #[\Override]
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
            'client' => $this->client->getTitle(),
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

            $form->file = new StreamUploadedFile([
                'allowedExtensions' => $form->uploadExtensions,
                'url' => $this->externalPictureUrl,
            ]);

            $form->upload();
        }

        $this->user->generateVerificationToken();

        return true;
    }

    public function afterInsert(): void
    {
        $webuser = Yii::$app->getUser();

        if (!$this->user->isUnconfirmed() || $webuser->isUnconfirmedEmailLoginEnabled()) {
            $webuser->loginType = $this->client->getName();
            $webuser->login($this->user);
        }

        $this->sendSignupEmail();
    }
}
