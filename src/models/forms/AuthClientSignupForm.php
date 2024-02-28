<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;
use yii\base\Model;
use yii\behaviors\SluggableBehavior;

class AuthClientSignupForm extends Model
{
    use IdentityTrait;
    use ModelTrait;
    use SignupEmailTrait;

    public ?ClientInterface $_client = null;
    public string $externalPictureUrl;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'SluggableBehavior' => [
                'class' => SluggableBehavior::class,
                'attribute' => 'name',
                'slugAttribute' => 'name',
            ],
        ];
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

        $user = $this->getUser();

        $user->name ??= mb_strtolower($user->first_name . $user->last_name, Yii::$app->charset)
            ?: explode('@', $user->email)[0];

        if (!in_array($user->language, Yii::$app->getI18n()->languages)) {
            $user->language = Yii::$app->language;
        }

        return parent::beforeValidate();
    }

    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user->validate()) {
                $this->addErrors($user->getErrors());
            }
        }

        if ($this->hasErrors('email')) {
            $this->clearErrors('email');
            $this->addContextToEmailError();
        }

        parent::afterValidate();
    }

    protected function addContextToEmailError(): void
    {
        $this->addError('email', Yii::t('skeleton', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
            'client' => $this->getClient()->getTitle(),
            'email' => $this->email,
        ]));
    }

    public function insert(): bool
    {
        if (!$this->validate() || !$this->beforeInsert()) {
            return false;
        }

        if ($this->getUser()->insert(false)) {
            $this->afterInsert();
            return true;
        }

        return false;
    }

    public function beforeInsert(): bool
    {
        $user = $this->getUser();

        if ($this->externalPictureUrl) {
            $user->upload = new StreamUploadedFile([
                'allowedExtensions' => $user->uploadExtensions,
                'url' => $this->externalPictureUrl,
            ]);
        }

        return true;
    }

    public function afterInsert(): void
    {
        $user = $this->getUser();

        if (!$user->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            Yii::$app->getUser()->login($user);
        }

        $this->sendSignupEmail();
    }

    public function setClient(ClientInterface $client): void
    {
        $user = User::create();
        $user->setAttributes($client->getSafeUserAttributes());
        $this->setUser($user);

        Yii::$app->getUser()->loginType = $client->getName();

        $this->_client = $client;
    }

    public function getClient(): ?ClientInterface
    {
        return $this->_client;
    }
}
