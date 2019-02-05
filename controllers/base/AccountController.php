<?php

namespace davidhirtz\yii2\skeleton\controllers\base;

use davidhirtz\yii2\skeleton\auth\clients\Facebook;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\AccountConfirmForm;
use davidhirtz\yii2\skeleton\models\forms\AuthClientSignupForm;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\UserForm;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\models\forms\PasswordRecoverForm;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
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
 * @package davidhirtz\yii2\skeleton\controllers\base
 */
class AccountController extends Controller
{
    /**
     * @var string
     */
    public $defaultAction = 'update';

    /***********************************************************************
     * Behaviors.
     ***********************************************************************/

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['deauthorize', 'delete', 'logout', 'update'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'deauthorize' => ['post'],
                    'delete' => ['post'],
                    'logout' => ['post'],
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
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'clientIdGetParamName' => 'client',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    /**
     * @throws ForbiddenHttpException
     * @throws \yii\base\Exception
     * @return string
     */
    public function actionCreate()
    {
        if (!Yii::$app->getUser()->isSignupEnabled()) {
            throw new ForbiddenHttpException(Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
        }

        if (!Yii::$app->getUser()->getIsGuest()) {
            Yii::t('skeleton', 'Please logout before creating another account');
            return $this->goHome();
        }

        $user = new SignupForm;
        $request = Yii::$app->getRequest();

        if ($user->load($request->post())) {
            if ($user->save()) {
                $this->success(Yii::t('skeleton', 'Sign up completed. Please check your inbox to confirm your email address.'));
                return $this->goBack();
            }

            $user->password = null;
        } else {
            $user->email = $request->get('email', Yii::$app->session->get('email'));
            $user->honeypot = Yii::$app->getSecurity()->generateRandomString(10);
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'user' => $user,
        ]);
    }

    /**
     * Returns JSON encoded string containing a signup token.
     * The token will only be every five minutes, to prevent multiple signups within one session.
     *
     * @throws \yii\base\Exception
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
        if (!Yii::$app->getUser()->getIsGuest()) {
            $this->error(Yii::t('skeleton', 'Please logout before logging in with another account'));
            return $this->goHome();
        }

        $form = new LoginForm;
        $request = Yii::$app->getRequest();

        if ($form->load($request->post())) {
            if ($form->login()) {
                $this->success($form->getUser()->login_count > 1 ? Yii::t('skeleton', 'Welcome back, {name}!', ['name' => $form->getUser()->getUsername()]) : Yii::t('skeleton', 'Login successful!'));
                return $this->goBack();
            }

            Yii::$app->getSession()->set('email', $form->email);
        } else {
            $form->email = $request->get('email', Yii::$app->getSession()->get('email'));
        }

        /** @noinspection MissedViewInspection */
        return $this->render('login', [
            'form' => $form,
        ]);
    }

    /**
     * Logout.
     * @return string
     */
    public function actionLogout()
    {
        if (Yii::$app->getUser()->logout()) {
            $this->success(Yii::t('skeleton', 'You are now logged out! See you soon!'));
        }

        return $this->goHome();
    }

    /**
     * Confirm email.
     *
     * @param string $email
     * @param string $code
     * @return string
     * @throws BadRequestHttpException
     */
    public function actionConfirm($email, $code)
    {
        $form = new AccountConfirmForm([
            'email' => $email,
            'code' => $code,
        ]);

        if (!$form->confirm()) {
            throw new BadRequestHttpException($form->getFirstError('code'));
        }

        if (Yii::$app->getUser()->getIsGuest()) {
            $user = $form->getUser();

            if (!$user->isDisabled()) {
                $user->loginType = UserLogin::TYPE_CONFIRM_EMAIL;
                Yii::$app->getUser()->login($user);
            }
        }

        $this->success(Yii::t('skeleton', 'Your email address was successfully confirmed!'));
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
        $form = new AccountResendConfirmForm;
        $request = Yii::$app->getRequest();

        if (!Yii::$app->getUser()->getIsGuest() || $form->load($request->post())) {
            if (!Yii::$app->getUser()->getIsGuest()) {
                $form->setUser(Yii::$app->getUser()->getIdentity());
            }

            if ($form->resend()) {
                $this->success(Yii::t('skeleton', 'We have sent another email to confirm your account to {email}.', [
                    'email' => $form->getUser()->email,
                ]));

                return $this->goHome();
            }
        } else {
            $form->email = $request->get('email', Yii::$app->getSession()->get('email'));
        }

        /** @noinspection MissedViewInspection */
        return $this->render('resend', [
            'form' => $form,
        ]);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @return string
     */
    public function actionRecover()
    {
        if (!Yii::$app->getUser()->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException;
        }

        $form = new PasswordRecoverForm;

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->recover()) {
                $this->success(Yii::t('skeleton', 'We have sent an email with instructions to reset your password to {email}.', ['email' => $form->user->email]));
                return $this->goHome();
            }

            Yii::$app->getSession()->set('email', $form->email);
        } else {
            $form->email = Yii::$app->getRequest()->get('email', Yii::$app->getSession()->get('email'));
        }

        /** @noinspection MissedViewInspection */
        return $this->render('recover', [
            'form' => $form,
        ]);
    }

    /**
     * @param string $email
     * @param string $code
     * @throws ForbiddenHttpException
     * @return string
     */
    public function actionReset($email, $code)
    {
        if (!Yii::$app->getUser()->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException;
        }

        $form = new PasswordResetForm([
            'email' => $email,
            'code' => $code,
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->reset()) {
                $this->success(Yii::t('skeleton', 'Your password was updated.'));
                return $this->goHome();
            }
        } elseif (!$form->validateUser()) {
            $this->error($form->getFirstErrors());
            return $this->goHome();
        }

        /** @noinspection MissedViewInspection */
        return $this->render('reset', [
            'form' => $form,
        ]);
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @return string
     */
    public function actionUpdate()
    {
        $user = UserForm::findOne(Yii::$app->getUser()->getId());

        if ($user->load(Yii::$app->getRequest()->post())) {
            if ($user->update()) {
                $this->success(Yii::t('skeleton', 'Your account was updated.'));
                return $this->refresh();
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'user' => $user,
        ]);
    }

    /**
     * Delete.
     * @return string
     */
    public function actionDelete()
    {
        $form = new DeleteForm([
            'model' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post()) && $form->delete()) {
            $this->success(Yii::t('skeleton', 'Your account was successfully deleted and you have been logged out. Bye!'));
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
        $auth = AuthClient::find()
            ->where(['id' => $id, 'name' => $name, 'user_id' => Yii::$app->getUser()->getId()])
            ->limit(1)
            ->one();

        if (!$auth) {
            throw new NotFoundHttpException;
        }

        if ($auth->delete()) {
            $client = $auth->getClientClass();

            $this->success(Yii::t('skeleton', '{client} account "{name}" was removed from your profile.', [
                'client' => $client->getTitle(),
                'name' => $client::getDisplayName($auth),
                'isOwner' => 1,
            ]));

            return $this->redirect(['update']);
        }

        throw new ServerErrorHttpException;
    }

    /**
     * @see \yii\authclient\AuthAction::$successCallback
     * @param Facebook $client
     * @return string
     */
    public function onAuthSuccess($client)
    {
        $attributes = $client->getUserAttributes();

        /** @var $auth AuthClient */
        $auth = AuthClient::find()
            ->where(['id' => $attributes['id'], 'name' => $client->getName()])
            ->limit(1)
            ->one();

        if (Yii::$app->getUser()->getIsGuest()) {

            if ($auth) {

                // Login
                $user = Identity::findIdentity($auth->user_id);

                if (!$user) {
                    $this->error(Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
                    return $this->redirect(['login']);
                }

                $this->success(Yii::t('skeleton', 'Welcome back, {name}!', [
                    'name' => $user->getUsername(),
                ]));

                $user->loginType = $client->getName();
                Yii::$app->getUser()->login($user, $user->cookieLifetime);

            } else {

                // Signup
                if (!Yii::$app->getUser()->isSignupEnabled()) {
                    $this->error(Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
                    return $this->redirect(['login']);
                }

                if (User::findByEmail($attributes['email'])->exists()) {
                    $this->error(Yii::t('skeleton', 'A user with email {email} already exists but is not linked to this {client} account. Login using email first to link it.', [
                        'client' => $client->getTitle(),
                        'email' => $attributes['email'],
                    ]));

                    return $this->redirect(['login']);
                }

                $user = new AuthClientSignupForm;

                $user->setAttributes($client->getSafeUserAttributes());
                $user->loginType = $client->getName();

                if ($user->save()) {
                    $this->success(Yii::t('skeleton', 'Sign up with {client} completed.', [
                        'client' => $client->getTitle(),
                    ]));
                }
            }
        } else {
            if ($auth && $auth->user_id != Yii::$app->getUser()->getId()) {
                $this->error(Yii::t('skeleton', 'A different user is already linked with this {client} account!', [
                    'client' => $client->getTitle(),
                ]));

                return $this->goBack();
            }


            $this->success(Yii::t('skeleton', 'Your {client} account is now connected with your profile.', [
                'client' => $client->getTitle(),
            ]));

            $user = Yii::$app->getUser()->getIdentity();
            Url::remember(['update']);
        }

        if (!$auth) {
            $auth = new AuthClient;
            $auth->id = $attributes['id'];
            $auth->name = $client->getName();
            $auth->user_id = $user->id;
        }

        $auth->data = $client->getAuthData();
        $auth->save();

        return $this->goBack();
    }
}