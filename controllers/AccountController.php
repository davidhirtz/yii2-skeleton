<?php

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
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
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\base\InvalidCallException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class AccountController
 * @package davidhirtz\yii2\skeleton\controllers
 */
class AccountController extends Controller
{
    /**
     * @var string
     */
    public $defaultAction = 'update';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['deauthorize', 'delete', 'logout', 'picture', 'update'],
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
                    'picture' => ['post'],
                    'delete' => ['post'],
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionCreate()
    {
        if (!Yii::$app->getUser()->getIsGuest()) {
            Yii::t('skeleton', 'Please logout before creating another account');
            return $this->goHome();
        }

        $user = new SignupForm();
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

        $form = new LoginForm();
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
    public function actionConfirm(string $email, string $code)
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
        $form = new AccountResendConfirmForm();
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
     * @return string
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws ForbiddenHttpException
     */
    public function actionRecover()
    {
        if (!Yii::$app->getUser()->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException();
        }

        $form = new PasswordRecoverForm();

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
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionReset(string $email, string $code)
    {
        if (!Yii::$app->getUser()->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException();
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
     * @return string
     * @throws \yii\db\StaleObjectException
     * @throws \Throwable
     */
    public function actionUpdate()
    {
        $user = UserForm::findOne(Yii::$app->getUser()->getId());

        if ($user->load(Yii::$app->getRequest()->post())) {
            if ($user->update()) {
                $this->success(Yii::t('skeleton', 'Your account was updated.'));
            }

            if (!$user->hasErrors()) {
                $user->newPassword = $user->oldPassword = null;
                return $this->refresh();
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'user' => $user,
        ]);
    }

    /**
     * Deletes profile picture.
     * @return \yii\web\Response
     */
    public function actionPicture()
    {
        $user = UserForm::findOne(Yii::$app->getUser()->getId());
        $user->picture = null;

        if ($user->update()) {
            $this->success(Yii::t('skeleton', 'Your account was updated.'));
        }

        return $this->redirect(['update']);
    }

    /**
     * Deletes user account.
     * @return \yii\web\Response
     */
    public function actionDelete()
    {
        /** @var DeleteForm $form */
        $form = Yii::createObject([
            'class' => 'davidhirtz\yii2\skeleton\models\forms\DeleteForm',
            'model' => UserForm::findOne(Yii::$app->getUser()->getId()),
            'attribute' => 'password',
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
     * @return \yii\web\Response
     */
    public function actionDeauthorize(string $id, string $name)
    {
        /**
         * @var $auth AuthClient
         */
        $auth = AuthClient::find()
            ->where(['id' => $id, 'name' => $name, 'user_id' => Yii::$app->getUser()->getId()])
            ->limit(1)
            ->one();

        if (!$auth) {
            throw new NotFoundHttpException();
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

        throw new ServerErrorHttpException();
    }

    /**
     * @param ClientInterface $client
     * @return string
     * @see \yii\authclient\AuthAction::$successCallback
     */
    public function onAuthSuccess(ClientInterface $client)
    {
        $auth = AuthClient::findOrCreateFromClient($client);

        if (Yii::$app->getUser()->getIsGuest()) {
            if (($auth->getIsNewRecord() ? $this->signupWithAuthClient($auth) : $this->loginWithAuthClient($auth)) === false) {
                return $this->redirect(['login']);
            }

            return $this->goBack();
        }

        $auth->user_id = Yii::$app->getUser()->getId();

        if ($auth->save()) {
            $this->success(Yii::t('skeleton', 'Your {client} account is now connected with your profile.', [
                'client' => $client->getTitle(),
            ]));
        }

        $this->error($auth->getFirstErrors());
        return $this->redirect(['update']);
    }

    /**
     * @param AuthClient $auth
     * @return bool
     */
    private function loginWithAuthClient(AuthClient $auth): bool
    {
        if ($auth->getIsNewRecord()) {
            throw new InvalidCallException();
        }

        $user = $auth->identity;

        if (!$user || $user->isDisabled()) {
            $this->error(Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
            return false;
        }

        $this->success(Yii::t('skeleton', 'Welcome back, {name}!', [
            'name' => $user->getUsername(),
        ]));

        $user->loginType = $auth->getClientClass()->getName();
        Yii::$app->getUser()->login($user, $user->cookieLifetime);

        return $auth->update();
    }

    /**
     * @param AuthClient $auth
     * @return bool
     */
    private function signupWithAuthClient(AuthClient $auth): bool
    {
        if (!$auth->getIsNewRecord()) {
            throw new InvalidCallException();
        }

        $user = new AuthClientSignupForm();
        $user->setClient($auth->getClientClass());

        if (!$user->save()) {
            $this->error($user->getFirstErrors());
            return false;
        }

        $this->success(Yii::t('skeleton', 'Sign up with {client} completed.', [
            'client' => $auth->getClientClass()->getTitle(),
        ]));

        $auth->user_id = $user->id;
        return $auth->insert();
    }
}