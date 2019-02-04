<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\Identity;
use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * Class AuthClientSignupForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class AuthClientSignupForm extends Identity
{
    /**
     * Traits.
     */
    use \davidhirtz\yii2\skeleton\models\traits\user\SignupEmailTrait;

    /**
     * @var string
     */
    public $externalPictureUrl;

    /***********************************************************************
     * Init.
     ***********************************************************************/

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
    public function behaviors()
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
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['externalPictureUrl'],
                'validateExternalPictureUrl',
            ],
        ]);
    }

    /**
     * @see \davidhirtz\yii2\skeleton\models\forms\user\AuthSignupForm::rules()
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
        if (!$this->name) {
            $this->name = mb_strtolower($this->first_name . $this->last_name, Yii::$app->charset) ?: explode('@', $this->email)[0];
        }

        if (!in_array($this->language, Yii::$app->getI18n()->languages)) {
            $this->language = Yii::$app->language;
        }

        return parent::beforeValidate();
    }

    /***********************************************************************
     * Events.
     ***********************************************************************/

    /**
     * Login after insert.
     */
    public function onAfterInsert()
    {
        if (!$this->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            Yii::$app->getUser()->login($this);
        }
    }
}