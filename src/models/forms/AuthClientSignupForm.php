<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;
use yii\behaviors\SluggableBehavior;

class AuthClientSignupForm extends Identity
{
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
        return [...parent::rules(), [
            ['externalPictureUrl'],
            $this->validateExternalPictureUrl(...),
        ]];
    }

    /**
     * Override to implement validation if needed.
     */
    public function validateExternalPictureUrl(): void
    {
    }

    public function beforeValidate(): bool
    {
        if (!Yii::$app->getUser()->isSignupEnabled()) {
            $this->addError('id', Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
            return false;
        }

        $this->name ??= mb_strtolower($this->first_name . $this->last_name, Yii::$app->charset)
            ?: explode('@', $this->email)[0];

        if (!in_array($this->language, Yii::$app->getI18n()->languages)) {
            $this->language = Yii::$app->language;
        }

        return parent::beforeValidate();
    }

    /**
     * Overrides default email error to give user more context why the signup cannot be completed with this email.
     */
    public function afterValidate(): void
    {
        if ($this->hasErrors('email')) {
            $this->clearErrors('email');

            $this->addError('email', Yii::t('skeleton', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
                'client' => $this->getClient()->getTitle(),
                'email' => $this->email,
            ]));
        }

        parent::afterValidate();
    }

    public function beforeSave($insert): bool
    {
        if ($insert) {
            if ($this->externalPictureUrl) {
                $this->upload = new StreamUploadedFile([
                    'allowedExtensions' => $this->uploadExtensions,
                    'url' => $this->externalPictureUrl,
                ]);
            }
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            if (!$this->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
                Yii::$app->getUser()->login($this);
            }

            $this->sendSignupEmail();
        }
    }

    public function setClient(ClientInterface $client): void
    {
        $this->setAttributes($client->getSafeUserAttributes());
        $this->loginType = $client->getName();

        $this->_client = $client;
    }

    public function getClient(): ?ClientInterface
    {
        return $this->_client;
    }
}
