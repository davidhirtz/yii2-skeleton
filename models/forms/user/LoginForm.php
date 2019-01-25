<?php
namespace davidhirtz\yii2\skeleton\models\forms\user;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\base\Model;

/**
 * Class LoginForm.
 * @package davidhirtz\yii2\skeleton\models\forms\user
 *
 * @property Identity $user
 * @see LoginForm::getUser()
 */
class LoginForm extends Model
{
	/**
	 * @var string
	 */
	public $email;

	/**
	 * @var string
	 */
	public $password;

	/**
	 * @var bool
	 */
	public $rememberMe=true;

	/**
	 * @var integer
	 */
	public $cookieDuration;

	/**
	 * @var integer
	 */
	public $ip;

	/**
	 * @var Identity
	 * @see LoginForm::getIdentity()
	 */
	private $_user=false;

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[
				['email', 'password'],
				'filter',
				'filter'=>'trim',
			],
			[
				['email', 'password'],
				'required',
			],
			[
				['email'],
				'email',
			],
			[
				['rememberMe'],
				'boolean',
			],
		];
	}

	/**
	 * Validates user credentials.
	 */
	public function afterValidate()
	{
		if(!$this->hasErrors())
		{
			$user=$this->getUser();

			if(!$user || !$user->validatePassword($this->password))
			{
				$this->addError('email', Yii::t('app', 'Your email or password are incorrect.'));
			}
			elseif($user->getIsDisabled() && !$user->getIsOwner())
			{
				$this->addError('status', Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
			}
			elseif($user->getIsUnconfirmed() && !Yii::$app->params['user.unconfirmedLogin'])
			{
				$this->addError('status', Yii::t('app', 'Your email address is not confirmed yet. You should find a confirmation email in your inbox.'));
			}
			else
			{
				$this->addErrors($user->getErrors());
			}
		}

		parent::afterValidate();
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Logs in a user using the provided email and password.
	 * @return boolean
	 */
	public function login()
	{
		if($this->validate())
		{
			/**
			 * Set identity attributes.
			 */
			$user=$this->getUser();

			$user->generatePasswordHash($this->password);
			$user->loginType=UserLogin::TYPE_LOGIN;
			$user->ip=$this->ip;

			/**
			 * Login.
			 */
			return Yii::$app->getUser()->login($user, $this->rememberMe ? $this->cookieDuration ?: Yii::$app->params['app.cookieDuration'] : 0);
		}

		return false;
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return Identity
	 */
	public function getUser()
	{
		if($this->_user===false)
		{
			$this->_user=Identity::findByEmail($this->email)
				->loginAttributesOnly()
				->limit(1)
				->one();
		}

		return $this->_user;
	}

	/***********************************************************************
	 * Model.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'rememberMe'=>Yii::t('app', 'Keep me logged in'),
		];
	}
}