<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\db\Identity;
use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * Class AuthClientSignupForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class AuthClientSignupForm extends Identity
{
    use \davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;

    /**
     * @var ClientInterface
     */
    public $_client;

    /**
     * @var string
     */
    public $externalPictureUrl;

    /**
     * @inheritdoc
     */
    public function init()
    {


        $this->on(static::EVENT_AFTER_INSERT, [$this, 'onAfterInsert']);
        $this->on(static::EVENT_AFTER_INSERT, [$this, 'sendSignupEmail']);
        parent::init();
    }

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

    /***********************************************************************
     * Validation.
     ***********************************************************************/

    /**
     * @return array
     */
    public function rules(): array
    {
        $this->emailUniqueMessage = Yii::t('skeleton', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
            'client' => $this->getClient()->getTitle(),
            'email' => $this->email,
        ]);

        return array_merge(parent::rules(), [
            [
                ['externalPictureUrl'],
                'validateExternalPictureUrl',
            ],
        ]);
    }

    /**
     * @see \davidhirtz\yii2\skeleton\models\forms\AuthSignupForm::rules()
     * @todo
     */
    public function validateExternalPictureUrl()
    {
//		if($this->externalPictureUrl)
//		{
//		}
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
     * Login after insert.
     */
    public function onAfterInsert()
    {
        if (!$this->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            Yii::$app->getUser()->login($this);
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