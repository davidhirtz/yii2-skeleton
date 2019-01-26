<?php
namespace davidhirtz\yii2\skeleton\modules\admin\controllers\base;

use app\models\forms\user\LoginForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class AccountController.
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers\base
 */
class AccountController extends Controller
{
	/**
	 * @var string
	 */
	public $defaultAction='update';

	/***********************************************************************
	 * Behaviors.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access'=>[
				'class'=>AccessControl::class,
				'only'=>['deauthorize', 'delete', 'logout', 'update'],
				'rules'=>[
					[
						'allow'=>true,
						'roles'=>['@'],
					],
				],
			],
			'verbs'=>[
				'class'=>VerbFilter::class,
				'actions'=>[
					'deauthorize'=>['post'],
					'delete'=>['post'],
					'logout'=>['post'],
				],
			],
		];
	}

	/***********************************************************************
	 * Actions.
	 ***********************************************************************/

	/**
	 * @return array
	 */
	public function actions()
	{
		return [
			'auth'=>[
				'class'=>'yii\authclient\AuthAction',
				'clientIdGetParamName'=>'client',
				'successCallback'=>[$this, 'onAuthSuccess'],
			],
		];
	}

	/**
	 * Signup.
	 * @return string
	 */
	public function actionCreate()
	{
		if(!Yii::$app->params['user.enableSignup'] && User::find()->count())
		{
			throw new ForbiddenHttpException(Yii::t('app', 'Sorry, signing up is currently disabled!'));
		}

		if(!Yii::$app->getUser()->getIsGuest())
		{
			Yii::t('app', 'Please logout before creating another account');
			return $this->goHome();
		}

		$user=new SignupForm;

		if($user->load(Yii::$app->request->post()))
		{
			try
			{
				if($user->save())
				{
					$this->success(Yii::t('app', 'Sign up completed. Please check your inbox to confirm your email address.'));
					return $this->goBack();
				}
			}
			catch(\Exception $ex)
			{
				Yii::error('User sign up failed: '.$ex->getMessage().$ex->getTraceAsString());
				$user->addError('id', Yii::t('app', 'Sign up could not be completed, please try again.'));
			}

			$user->password=null;
		}
		else
		{
			$user->email=Yii::$app->request->get('email', Yii::$app->session->get('email'));
			$user->honeypot=Yii::$app->security->generateRandomString(10);
		}

		/** @noinspection MissedViewInspection */
		return $this->render('create', [
			'user'=>$user,
		]);
	}

	/**
	 * Returns JSON encoded string containing a signup token.
	 * The token will only be every five minutes, to prevent multiple signups within one session.
	 *
	 * @return string
	 */
	public function actionToken()
	{
		return SignupForm::getSessionToken();
	}

	/**
	 * Login.
	 * @return string
	 */
	public function actionLogin()
	{
		if(!Yii::$app->getUser()->getIsGuest())
		{
			$this->error(Yii::t('app', 'Please logout before logging in with another account'));
			return $this->goHome();
		}

		$model=new LoginForm;

		if($model->load(Yii::$app->getRequest()->post()))
		{
			if($model->login())
			{
				$this->success($model->getUser()->login_count>1 ? Yii::t('app', 'Welcome back, {name}!', ['name'=>$model->getUser()->getUsername()]) : Yii::t('app', 'Login successful!'));
				return $this->goBack();
			}

			Yii::$app->getSession()->set('email', $model->email);
		}
		else
		{
			$model->email=Yii::$app->request->get('email', Yii::$app->getSession()->get('email'));
		}

		return $this->render('@skeleton/modules/admin/views/account/login', [
			'model'=>$model,
		]);
	}

	/**
	 * Logout.
	 * @return string
	 */
	public function actionLogout()
	{
		if(Yii::$app->getUser()->logout())
		{
			$this->success(Yii::t('app', 'You are now logged out! See you soon!'));
		}

		return $this->goHome();
	}

	/**
	 * Confirm email.
	 *
	 * @param string $user
	 * @param string $code
	 * @return string
	 */
	public function actionConfirm($user, $code)
	{
		$form=new ConfirmForm([
			'name'=>$user,
			'code'=>$code,
		]);

		if(!$form->update())
		{
			throw new BadRequestHttpException($form->getFirstError('code'));
		}

		if(Yii::$app->getUser()->getIsGuest())
		{
			$user=$form->getUser();

			if(!$user->getIsDisabled())
			{
				$user->loginType=Login::TYPE_CONFIRM_EMAIL;
				Yii::$app->getUser()->login($user);
			}
		}

		$this->success(Yii::t('app', 'Your email address was successfully confirmed!'));
		return $this->goHome();
	}

	/**
	 * Resend form.
	 *
	 * If user is already logged in, skip form and directly populate
	 * the model with the user identity.
	 *
	 * @return string
	 */
	public function actionResend()
	{
		$model=new ResendConfirmForm;

		if(!Yii::$app->getUser()->getIsGuest())
		{
			$model->user=Yii::$app->getUser()->identity;
		}

		if(!Yii::$app->getUser()->getIsGuest() || $model->load(Yii::$app->request->post()))
		{
			if($model->resend())
			{
				$this->success(Yii::t('app', 'We have sent another email to confirm your account to {email}.', [
					'email'=>$model->user->email,
				]));

				return $this->goHome();
			}
		}
		else
		{
			$model->email=Yii::$app->request->get('email', Yii::$app->session->get('email'));
		}

		/** @noinspection MissedViewInspection */
		return $this->render('resend', [
			'model'=>$model,
		]);
	}

	/**
	 * Recover form.
	 * @return string
	 */
	public function actionRecover()
	{
		if(!Yii::$app->params['user.resetPassword'])
		{
			throw new ForbiddenHttpException;
		}

		$model=new PasswordRecoverForm;

		if($model->load(Yii::$app->getRequest()->post()))
		{
			if($model->recover())
			{
				$this->success(Yii::t('app', 'We have sent an email with instructions to reset your password to {email}.', ['email'=>$model->user->email]));
				return $this->goHome();
			}

			Yii::$app->getSession()->set('email', $model->email);
		}
		else
		{
			$model->email=Yii::$app->getRequest()->get('email', Yii::$app->getSession()->get('email'));
		}

		/** @noinspection MissedViewInspection */
		return $this->render('recover', [
			'model'=>$model,
		]);
	}

	/**
	 * Reset password form.
	 *
	 * @param string $user
	 * @param string $code
	 * @return string
	 */
	public function actionReset($user, $code)
	{
		if(!Yii::$app->params['user.resetPassword'])
		{
			throw new ForbiddenHttpException;
		}

		$model=new PasswordResetForm([
			'name'=>$user,
			'code'=>$code,
		]);

		if($model->load(Yii::$app->getRequest()->post()))
		{
			if($model->reset())
			{
				$this->success(Yii::t('app', 'Your password was updated.'));
				return $this->goHome();
			}
		}
		elseif(!$model->validateUser())
		{
			$this->error($model->getFirstErrors());
			return $this->goHome();
		}

		/** @noinspection MissedViewInspection */
		return $this->render('reset', [
			'model'=>$model,
		]);
	}

	/**
	 * Update.
	 * @return string
	 */
	public function actionUpdate()
	{
		/**
		 * @var AccountForm $user
		 */
		$user=AccountForm::find()
			->where(['id'=>Yii::$app->getUser()->getId()])
			->limit(1)
			->one();

		if($user->load(Yii::$app->getRequest()->post()) && $user->update())
		{
			$this->success(Yii::t('app', '{isOwner, select, 1{Your} other{The}} account was updated.', [
				'isOwner'=>1,
			]));

			return $this->refresh();
		}

		/** @noinspection MissedViewInspection */
		return $this->render('update', [
			'user'=>$user,
		]);
	}

	/**
	 * Delete.
	 * @return string
	 */
	public function actionDelete()
	{
		$form=new DeleteForm([
			'model'=>Yii::$app->getUser()->getIdentity(),
		]);

		if($form->load(Yii::$app->getRequest()->post()) && $form->delete())
		{
			$this->success(Yii::t('app', 'Your account was successfully deleted and you have been logged out. Bye!'));
			Yii::$app->getUser()->logout();

			return $this->goHome();
		}

		$this->error($form->getFirstErrors());
		return $this->redirect(['update']);
	}

	/**
	 * Deletes a related auth client.
	 *
	 * @param string $id
	 * @param string $name
	 * @return string
	 */
	public function actionDeauthorize($id, $name)
	{
		/**
		 * @var $auth AuthClient
		 */
		$auth=AuthClient::find()
			->where(['id'=>$id, 'name'=>$name, 'user_id'=>Yii::$app->getUser()->getId()])
			->limit(1)
			->one();

		if(!$auth)
		{
			throw new NotFoundHttpException;
		}

		if($auth->delete())
		{
			$client=$auth->getClientClass();

			$this->success(Yii::t('app', '{client} account "{name}" was removed from {isOwner, select, 1{your} other{this}} profile.', [
				'client'=>$client->getTitle(),
				'name'=>$client::getDisplayName($auth),
				'isOwner'=>1,
			]));

			return $this->redirect(['update']);
		}

		throw new ServerErrorHttpException;
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * @see \yii\authclient\AuthAction::$successCallback
	 * @param Facebook $client
	 * @return string
	 */
	public function onAuthSuccess($client)
	{
		$attributes=$client->getUserAttributes();

		/**
		 * @var $auth AuthClient
		 */
		$auth=AuthClient::find()
			->where(['id'=>$attributes['id'], 'name'=>$client->getName()])
			->limit(1)
			->one();

		if(Yii::$app->getUser()->getIsGuest())
		{
			if($auth)
			{
				/**
				 * Login.
				 */
				$user=Identity::findIdentity($auth->user_id);

				if(!$user)
				{
					$this->error(Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
					return $this->redirect(['login']);
				}

				$this->success(Yii::t('app', 'Welcome back, {name}!', [
					'name'=>$user->getUsername(),
				]));

				$user->loginType=$client->getName();
				Yii::$app->getUser()->login($user, Yii::$app->params['app.cookieDuration']);
			}
			else
			{
				/**
				 * Signup.
				 */
				if(!Yii::$app->params['user.enableSignup'] && User::find()->count())
				{
					$this->error(Yii::t('app', 'Sorry, signing up is currently disabled!'));
					return $this->redirect(['login']);
				}

				if(User::findByEmail($attributes['email'])->exists())
				{
					$this->error(Yii::t('app', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
						'client'=>$client->getTitle(),
						'email'=>$attributes['email'],
					]));

					return $this->redirect(['login']);
				}

				$user=new AuthClientSignupForm;

				$user->setAttributes($client->getSafeUserAttributes());
				$user->loginType=$client->getName();

				if($user->save())
				{
					$this->success(Yii::t('app', 'Sign up with {client} completed.', [
						'client'=>$client->getTitle(),
					]));
				}
			}
		}
		else
		{
			if($auth && $auth->user_id!=Yii::$app->getUser()->getId())
			{
				$this->error(Yii::t('app', 'A different user is already linked with this {client} account!', [
					'client'=>$client->getTitle(),
				]));

				return $this->goBack();
			}


			$this->success(Yii::t('app', 'You have successfully {type, select, insert{added} update{updated}} your {client} account to your profile.', [
				'type'=>!$auth ? 'insert' : 'update',
				'client'=>$client->getTitle(),
			]));

			$user=Yii::$app->getUser()->getIdentity();
			Url::remember(['update']);
		}

		/**
		 * Update auth.
		 */
		if(!$auth)
		{
			$auth=new AuthClient;
			$auth->id=$attributes['id'];
			$auth->name=$client->getName();
			$auth->user_id=$user->id;
		}

		$auth->data=$client->getAuthData();
		$auth->save();

		return $this->goBack();
	}
}