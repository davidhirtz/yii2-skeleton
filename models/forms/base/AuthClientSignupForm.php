<?php

namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * Class AuthClientSignupForm.
 * @package davidhirtz\yii2\skeleton\models\forms\base
 * @see \davidhirtz\yii2\skeleton\models\forms\AuthClientSignupForm
 */
class AuthClientSignupForm extends Identity
{
    use SignupEmailTrait;

    /**
     * @var ClientInterface
     */
    public $_client;

    /**
     * @var string
     */
    public $externalPictureUrl;

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => SluggableBehavior::class,
                'attribute' => 'name',
                'slugAttribute' => 'name',
            ],
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['externalPictureUrl'],
                /** {@link AuthClientSignupForm::validateExternalPictureUrl()} */
                'validateExternalPictureUrl',
            ],
        ]);
    }

    /**
     * Override to implement validation if needed.
     */
    public function validateExternalPictureUrl()
    {
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if (!Yii::$app->getUser()->isSignupEnabled()) {
            $this->addError('id', Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
            return false;
        }

        if ($this->name === null) {
            $this->name = mb_strtolower($this->first_name . $this->last_name, Yii::$app->charset) ?: explode('@', $this->email)[0];
        }

        if (!in_array($this->language, Yii::$app->getI18n()->languages)) {
            $this->language = Yii::$app->language;
        }

        return parent::beforeValidate();
    }

    /**
     * Overrides default email error to give user more context why the signup cannot completed
     * with this email address.
     */
    public function afterValidate()
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

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            if ($this->externalPictureUrl) {
                $this->upload = new StreamUploadedFile(['url' => $this->externalPictureUrl]);
                $this->generatePictureFilename();
            }
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            if ($this->upload) {
                $this->savePictureUpload();
            }

            if (!$this->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
                Yii::$app->getUser()->login($this);
            }

            $this->sendSignupEmail();
        }
    }

    /**
     * @param ClientInterface $client
     */
    public function setClient($client)
    {
        $this->setAttributes($client->getSafeUserAttributes());
        $this->loginType = $client->getName();

        $this->_client = $client;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->_client;
    }
}