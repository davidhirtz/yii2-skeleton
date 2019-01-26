<?php
namespace app\models\forms\user;
use davidhirtz\yii2\skeleton\db\Identity;
use app\models\Login;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\db\AfterSaveEvent;

/**
 * Class SignupForm.
 * @package app\models\forms\user
 */
class SignupForm extends Identity
{
	/**
	 * Traits.
	 */
	use \app\models\traits\user\SignupEmailTrait;

	/**
	 * Cookie name.
	 */
	const SESSION_TOKEN_NAME='signup_token';
	const SESSION_TIMESTAMP_NAME='signup_timestamp';
	const SESSION_MIN_TIME=5;
	const SESSION_MAX_TIME=1800;

	/**
	 * @var string honeypot text field to mess with bots, the text field will have a random value
	 * which will be removed by javascript on form submit.
	 */
	public $honeypot;

	/**
	 * @var boolean whether user has accepted the terms of service.
	 */
	public $terms;

	/**
	 * @var string token text field is set by ajax and checked against cookie.
	 */
	public $token;

	/**
	 * @var integer the web user ip.
	 */
	public $ip;

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

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return array_merge(parent::rules(), [
			[
				['password', 'token'],
				'required',
			],
			[
				['terms'],
				'compare',
				'compareValue'=>1,
				'message'=>Yii::t('app', 'Please accept the terms of service and privacy policy.'),
			],
			[
				['token'],
				'compare',
				'compareValue'=>static::getSessionToken(),
				'message'=>Yii::t('app', 'Sign up could not be completed, please try again.'),
				'skipOnError'=>true,
			],
			[
				['honeypot'],
				'compare',
				'compareValue'=>'',
				'message'=>Yii::t('app', 'Sign up could not be completed, please try again.'),
			],
		]);
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * @return bool
	 */
	public function beforeValidate()
	{
		$this->status=static::STATUS_ENABLED;
		$this->token=trim($this->token);

		return parent::beforeValidate();
	}

	/**
	 * Validates signup creation time and user credentials.
	 */
	public function afterValidate()
	{
		if(!$this->hasErrors())
		{
			/**
			 * Throw error if session timestamp is expired
			 */
			$timestamp=time()-Yii::$app->getSession()->get(static::SESSION_TIMESTAMP_NAME, 0);

			if($timestamp<static::SESSION_MIN_TIME && $timestamp>static::SESSION_MAX_TIME)
			{
				$this->addError('token', Yii::t('app', 'Sign up could not be completed, please try again.'));
			}

			if($this->ip)
			{
				/**
				 * TODO IP Ban check
				 */

				/**
				 * Signup spam protection.
				 * @var Login $signup
				 */
				$signup=Login::find()
					->where(['type'=>Login::TYPE_SIGNUP, 'ip'=>$this->ip])
					->orderBy(['created_at'=>SORT_DESC])
					->one();

				if($signup && $signup->created_at>new DateTime('-2 mins'))
				{
					$this->addError(false, Yii::t('app', 'You have just created a new user account. Please wait a few minutes!'));
				}
			}
		}

		parent::afterValidate();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if($insert)
		{
			$this->generatePasswordHash();
			$this->generateEmailConfirmationCode();
		}

		return parent::beforeSave($insert);
	}

	/**
	 * @var AfterSaveEvent $event
	 */
	public function onAfterInsert($event)
	{
		/**
		 * Reset signup token.
		 */
		Yii::$app->getSession()->set(static::SESSION_TOKEN_NAME, '');

		/**
		 * Login.
		 */
		if(Yii::$app->params['user.unconfirmedLogin'])
		{
			$this->loginType=Login::TYPE_SIGNUP;
			Yii::$app->getUser()->login($this);
		}
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * @return string
	 */
	public static function getSessionToken()
	{
		$time=time();
		$session=Yii::$app->getSession();

		if($session->get(static::SESSION_TIMESTAMP_NAME, 0)<$time-300 || !$session->get(static::SESSION_TOKEN_NAME))
		{
			$session->set(static::SESSION_TOKEN_NAME, Yii::$app->getSecurity()->generateRandomString(20));
			$session->set(static::SESSION_TIMESTAMP_NAME, $time);
		}

		return $session->get(static::SESSION_TOKEN_NAME, false);
	}

	/***********************************************************************
	 * Active Record.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
			'terms'=>Yii::t('app', 'I accept the terms of service and privacy policy'),
		]);
	}
}