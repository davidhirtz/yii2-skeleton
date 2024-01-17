<?php

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\AccountConfirmForm;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\models\forms\AuthClientSignupForm;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\models\forms\PasswordRecoverForm;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\models\forms\UserForm;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\authclient\AuthAction;
use yii\base\InvalidCallException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class AccountController extends Controller
{
    public $defaultAction = 'update';

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'deauthorize',
                            'delete',
                            'disable-google-authenticator',
                            'enable-google-authenticator',
                            'logout',
                            'picture',
                            'update',
                        ],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'auth',
                            'confirm',
                            'create',
                            'login',
                            'recover',
                            'resend',
                            'reset',
                            'token',
                        ],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'deauthorize' => ['post'],
                    'delete' => ['post'],
                    'disable-google-authenticator' => ['post'],
                    'enable-google-authenticator' => ['post'],
                    'logout' => ['post'],
                    'picture' => ['post'],
                ],
            ],
        ];
    }

    public function actions(): array
    {
        return [
            'auth' => [
                'class' => AuthAction::class,
                'successCallback' => $this->onAuthSuccess(...),
            ],
        ];
    }

    public function actionCreate(): Response|string
    {
        if (!Yii::$app->getUser()->getIsGuest()) {
            $this->error(Yii::t('skeleton', 'Please logout before creating another account'));
            return $this->goHome();
        }

        $user = SignupForm::create();
        $request = Yii::$app->getRequest();

        if ($user->load($request->post())) {
            if ($user->save()) {
                $this->success(Yii::t('skeleton', 'Sign up completed. Please check your inbox to confirm your email address.'));
                return $this->goBack();
            }

            $user->password_hash = null;
        } else {
            $user->email = $request->get('email', Yii::$app->session->get('email'));
            $user->honeypot = Yii::$app->getSecurity()->generateRandomString(10);
        }

        return $this->render('create', [
            'user' => $user,
        ]);
    }

    /**
     * Returns JSON encoded string containing a signup token.
     * The token will only be every five minutes, to prevent multiple signups within one session.
     */
    public function actionToken(): string
    {
        return SignupForm::create()::getSessionToken();
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->getUser()->getIsGuest()) {
            $this->error(Yii::t('skeleton', 'Please logout before logging in with another account'));
            return $this->goHome();
        }

        $form = Yii::createObject(LoginForm::class);
        $request = Yii::$app->getRequest();

        if ($form->load($request->post())) {
            if ($form->login()) {
                $this->success($form->getUser()->login_count > 1 ? Yii::t('skeleton', 'Welcome back, {name}!', ['name' => $form->getUser()->getUsername()]) : Yii::t('skeleton', 'Login successful!'));
                return $this->goBack();
            }

            if ($form->isGoogleAuthenticatorCodeRequired()) {
                return $this->render('google-authenticator', [
                    'form' => $form,
                ]);
            }

            Yii::$app->getSession()->set('email', $form->email);
        } else {
            $form->email = $request->get('email', Yii::$app->getSession()->get('email'));
        }

        return $this->render('login', [
            'form' => $form,
        ]);
    }

    public function actionLogout(): Response|string
    {
        if (Yii::$app->getUser()->logout()) {
            $this->success(Yii::t('skeleton', 'You are now logged out! See you soon!'));
        }

        return $this->goHome();
    }

    public function actionConfirm(string $email, string $code): Response|string
    {
        $form = Yii::$container->get(AccountConfirmForm::class, [], [
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

    public function actionResend(): Response|string
    {
        $form = Yii::createObject(AccountResendConfirmForm::class);
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

        return $this->render('resend', [
            'form' => $form,
        ]);
    }

    public function actionRecover(): Response|string
    {
        if (!Yii::$app->getUser()->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException();
        }

        $form = Yii::createObject(PasswordRecoverForm::class);

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->recover()) {
                $this->success(Yii::t('skeleton', 'We have sent an email with instructions to reset your password to {email}.', ['email' => $form->user->email]));
                return $this->goHome();
            }

            Yii::$app->getSession()->set('email', $form->email);
        } else {
            $form->email = Yii::$app->getRequest()->get('email', Yii::$app->getSession()->get('email'));
        }

        return $this->render('recover', [
            'form' => $form,
        ]);
    }

    public function actionReset(string $email, string $code): Response|string
    {
        if (!Yii::$app->getUser()->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException();
        }

        $form = Yii::$container->get(PasswordResetForm::class, [], [
            'email' => $email,
            'code' => $code,
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->reset()) {
                $this->success(Yii::t('skeleton', 'Your password was updated.'));
                return $this->goHome();
            }
        } elseif (!$form->validatePasswordResetCode()) {
            $this->error($form);
            return $this->goHome();
        }

        return $this->render('reset', [
            'form' => $form,
        ]);
    }

    public function actionUpdate(): Response|string
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

        return $this->render('update', [
            'user' => $user,
        ]);
    }

    public function actionPicture(): Response|string
    {
        $user = UserForm::findOne(Yii::$app->getUser()->getId());
        $user->picture = null;

        if ($user->update()) {
            $this->success(Yii::t('skeleton', 'Your account was updated.'));
        }

        return $this->redirect(['update']);
    }

    public function actionDelete(): Response|string
    {
        $form = Yii::$container->get(DeleteForm::class, [], [
            'model' => UserForm::findOne(Yii::$app->getUser()->getId()),
            'attribute' => 'password',
        ]);

        if ($form->load(Yii::$app->getRequest()->post()) && $form->delete()) {
            $this->success(Yii::t('skeleton', 'Your account was successfully deleted and you have been logged out. Bye!'));

            Yii::$app->getUser()->logout();
            return $this->goHome();
        }

        $this->error($form);

        return $this->redirect(['update']);
    }

    public function actionEnableGoogleAuthenticator(): Response|string
    {
        $form = Yii::$container->get(GoogleAuthenticatorForm::class, [], [
            'user' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post()) && $form->save()) {
            $this->success(Yii::t('skeleton', 'Two-factor authentication is now enabled on your account.'));
        }

        $this->error($form);

        return $this->redirect(['update']);
    }

    public function actionDisableGoogleAuthenticator(): Response|string
    {
        $form = Yii::$container->get(GoogleAuthenticatorForm::class, [], [
            'user' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post()) && $form->delete()) {
            $this->success(Yii::t('skeleton', 'Two-factor authentication is now disabled on your account.'));
        }

        $this->error($form);

        return $this->redirect(['update']);
    }

    public function actionDeauthorize(string $id, string $name): Response|string
    {
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
     * @see AuthAction::successCallback()
     */
    public function onAuthSuccess(ClientInterface $client): Response|string
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

        $this->error($auth);

        return $this->redirect(['update']);
    }

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

    private function signupWithAuthClient(AuthClient $auth): bool
    {
        if (!$auth->getIsNewRecord()) {
            throw new InvalidCallException();
        }

        $user = AuthClientSignupForm::create();
        $user->setClient($auth->getClientClass());

        if (!$user->save()) {
            $this->error($user);
            return false;
        }

        $this->success(Yii::t('skeleton', 'Sign up with {client} completed.', [
            'client' => $auth->getClientClass()->getTitle(),
        ]));

        $auth->user_id = $user->id;

        return $auth->insert();
    }
}
