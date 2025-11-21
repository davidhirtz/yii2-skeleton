<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\controllers;

use davidhirtz\yii2\skeleton\auth\clients\ClientInterface;
use davidhirtz\yii2\skeleton\controllers\traits\HtmxControllerTrait;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\AccountConfirmForm;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\models\forms\AuthClientSignupForm;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\TwoFactorAuthenticatorForm;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\models\forms\PasswordRecoverForm;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\web\Controller;
use Override;
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
    use HtmxControllerTrait;

    public $defaultAction = 'update';

    #[Override]
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
                            'disable-authenticator',
                            'enable-authenticator',
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
                    'disable-authenticator' => ['post'],
                    'enable-authenticator' => ['post'],
                    'logout' => ['post'],
                    'picture' => ['post'],
                ],
            ],
        ];
    }

    #[Override]
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

        $form = SignupForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post()) && $form->insert()) {
            $this->success(Yii::t('skeleton', 'Sign up completed. Please check your inbox to confirm your email address.'));
            return $this->goBack();
        }

        if ($form->hasErrors()) {
            $form->user->password_hash = null;
        }

        return $this->render('create', [
            'form' => $form,
        ]);
    }

    /**
     * Returns JSON encoded string containing a signup token.
     * The token will only be every five minutes, to prevent multiple signups within one session.
     */
    public function actionToken(): string
    {
        return SignupForm::create()->getSessionToken();
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->getUser()->getIsGuest()) {
            $this->error(Yii::t('skeleton', 'Please logout before logging in with another account'));
            return $this->goHome();
        }

        $form = LoginForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post())) {
            if ($form->login()) {
                $this->success($form->getUser()->login_count === 1
                    ? Yii::t('skeleton', 'Login successful!')
                    : Yii::t('skeleton', 'Welcome back, {name}!', [
                        'name' => $form->getUser()->getUsername(),
                    ]));

                return $this->goBack();
            }

            if ($form->isGoogleAuthenticatorCodeRequired()) {
                return $this->render('google-authenticator', [
                    'form' => $form,
                ]);
            }

            if ($form->hasErrors()) {
                $this->error($form);
            }

            Yii::$app->getSession()->set('email', $form->email);
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

        $this->requestHtmxRefresh();

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
                $webuser = Yii::$app->getUser();
                $webuser->loginType = UserLogin::TYPE_CONFIRM_EMAIL;
                $webuser->login($user);
            }
        }

        $this->success(Yii::t('skeleton', 'Your email address was successfully confirmed!'));
        return $this->goHome();
    }

    public function actionResend(): Response|string
    {
        $form = Yii::createObject(AccountResendConfirmForm::class);

        if (!Yii::$app->getUser()->getIsGuest() || $form->load($this->request->post())) {
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
            $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));
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
        $form = AccountUpdateForm::create([
            'user' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->save()) {
                $this->success(Yii::t('skeleton', 'Your account was updated.'));
            }

            if (!$form->hasErrors()) {
                return $this->refresh();
            }

            $form->oldPassword = null;
        }

        return $this->render('update', [
            'form' => $form,
        ]);
    }

    public function actionPicture(): Response|string
    {
        $user = Yii::$app->getUser()->getIdentity();
        $user->picture = null;

        if ($user->update()) {
            $this->success(Yii::t('skeleton', 'Your account was updated.'));
        }

        return $this->redirect(['update']);
    }

    public function actionDelete(): Response|string
    {
        $form = Yii::$container->get(DeleteForm::class, [], [
            'model' => Yii::$app->getUser()->getIdentity(),
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

    public function actionEnableAuthenticator(): Response|string
    {
        $form = TwoFactorAuthenticatorForm::create([
            'user' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            $form->save();
            $this->errorOrSuccess($form, Yii::t('skeleton', 'Two-factor authentication is now enabled on your account.'));
        }

        return $this->redirect(['update']);
    }

    public function actionDisableAuthenticator(): Response|string
    {
        $form = TwoFactorAuthenticatorForm::create([
            'user' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            $form->delete();
            $this->errorOrSuccess($form, Yii::t('skeleton', 'Two-factor authentication is now disabled on your account.'));
        }

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
            $success = $auth->getIsNewRecord()
                ? $this->signupWithAuthClient($auth)
                : $this->loginWithAuthClient($auth);

            if (!$success) {
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

        if (!$user?->isEnabled()) {
            $this->error(Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
            return false;
        }

        $this->success(Yii::t('skeleton', 'Welcome back, {name}!', [
            'name' => $user->getUsername(),
        ]));

        $webuser = Yii::$app->getUser();
        $webuser->loginType = $auth->getClientClass()->getName();
        $webuser->login($user, $webuser->cookieLifetime);

        return $auth->update() !== false;
    }

    private function signupWithAuthClient(AuthClient $auth): bool
    {
        if (!$auth->getIsNewRecord()) {
            throw new InvalidCallException();
        }

        $form = AuthClientSignupForm::create(['client' => $auth->getClientClass()]);

        if (!$form->insert()) {
            $this->error($form);
            return false;
        }

        $this->success(Yii::t('skeleton', 'Sign up with {client} completed.', [
            'client' => $form->client->getTitle(),
        ]));

        $auth->user_id = $form->user->id;

        return $auth->insert();
    }
}
