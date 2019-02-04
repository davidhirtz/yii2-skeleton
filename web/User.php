<?php
namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\helpers\StringHelper;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\datetime\DateTime;
use Yii;

/**
 * Class User
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property \davidhirtz\yii2\skeleton\db\Identity $identity
 * @method \davidhirtz\yii2\skeleton\db\Identity getIdentity($autoRenew=true)
 */
class User extends \yii\web\User
{
	/**
	 * @var bool
	 */
	public $enableLogin=true;

	/**
	 * @var bool
	 */
	public $enableUnconfirmedEmailLogin=true;

	/**
	 * @var bool
	 */
	public $enablePasswordReset=true;

	/**
	 * @var bool
	 */
	public $enableSignup=false;

	/**
	 * @var bool
	 */
	public $enableAutoLogin=true;

	/**
	 * @var string
	 */
	public $identityClass='davidhirtz\yii2\skeleton\db\Identity';

	/**
	 * @var string
	 */
	public $loginUrl=['/account/login'];

	/**
	 * @var
	 */
	private $_userCount;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if(!$this->enableLogin)
		{
			$this->enableUnconfirmedEmailLogin=false;
			$this->enableAutoLogin=false;
		}

		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function loginRequired($checkAjax=true, $checkAcceptHeader=true)
	{
		/**
		 * Set flash message for required logins.
		 */
		if (!$checkAjax || !Yii::$app->getRequest()->getIsAjax())
		{
			Yii::$app->getSession()->addFlash('warning', Yii::t('app', 'You must login to view this page!'));
		}

		return parent::loginRequired($checkAjax, $checkAcceptHeader);
	}

	/**
	 * @param Identity $identity
	 * @param bool $cookieBased
	 * @param int $duration
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
	public function afterLogin($identity, $cookieBased, $duration)
	{
		/**
		 * Update login count, cache previous login date in session and
		 * insert new record to login log.
		 */
		$session=Yii::$app->getSession();
		$session->set('last_login_timestamp', $identity->last_login ? $identity->last_login->getTimestamp() : null);

		/**
		 * Updates session's user id.
		 */
		$session->writeCallback=function() use($identity)
		{
			return [
				'user_id'=>$identity->id,
			];
		};

		/**
		 * Update user record and insert login log.
		 */
		$identity->login_count++;
		$identity->last_login=new DateTime;

		if($cookieBased)
		{
			$identity->loginType=UserLogin::TYPE_COOKIE;
		}

		$this->insertLogin($identity);
		$identity->update(false);

		parent::afterLogin($identity, $cookieBased, $duration);
	}

	/**
	 * @param Identity $identity
	 */
	public function afterLogout($identity)
	{
		/**
		 * Removes user id from session.
		 */
		Yii::$app->getSession()->writeCallback=function()
		{
			return [
				'user_id'=>null,
			];
		};

		parent::afterLogout($identity);
	}

	/**
	 * @param Identity $identity
	 * @throws \yii\db\Exception
	 */
	private function insertLogin($identity)
	{
		$columns=[
			'user_id'=>$identity->id,
			'type'=>$identity->loginType,
			'browser'=>Yii::$app->getRequest()->getUserAgent(),
			'ip'=>StringHelper::ip2Long($identity->ip ?: Yii::$app->getRequest()->getUserIP()),
			'created_at'=>$identity->last_login,
		];

		Yii::$app->getDb()->createCommand()->insert(UserLogin::tableName(), $columns)->execute();
	}

	/**
	 * @inheritdoc
	 */
	public function can($permissionName, $params=[], $allowCaching=true)
	{
		// Disable RBAC for guests.
		if($this->getIsGuest())
		{
			return false;
		}

		// Don't run RBAC for site owner.
		return !$this->identity->isOwner() ? parent::can($permissionName, $params, $allowCaching) : true;
	}

	/**
	 * @return bool
	 */
	public function isLoginEnabled()
	{
		return (bool)$this->enableLogin;
	}

	/**
	 * @return bool
	 */
	public function isUnconfirmedEmailLoginEnabled()
	{
		return (bool)$this->enableUnconfirmedEmailLogin;
	}

	/**
	 * @return bool
	 */
	public function isPasswordResetEnabled()
	{
		return (bool)$this->enablePasswordReset;
	}

	/**
	 * @return bool
	 */
	public function isSignupEnabled()
	{
		return $this->enableSignup || $this->getUserCount()==0;
	}

	/**
	 * @return int|string
	 */
	public function getUserCount()
	{
		if($this->_userCount===null)
		{
			$this->_userCount=\davidhirtz\yii2\skeleton\models\User::find()->count();
		}

		return $this->_userCount;
	}
}