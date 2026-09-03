<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Auth\Clients\ClientInterface;
use Hirtz\Skeleton\Models\AuthClient;
use Hirtz\Skeleton\Models\Forms\AccountConfirmForm;
use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Models\Forms\AuthClientSignupForm;
use Hirtz\Skeleton\Models\Forms\DeleteForm;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Models\Forms\PasswordResetForm;
use Hirtz\Skeleton\Models\Forms\SignupForm;
use Hirtz\Skeleton\Models\Forms\TwoFactorAuthenticatorForm;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\authclient\AuthAction;
use yii\base\InvalidCallException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class AccountController extends Controller
{
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
                            'timezone',
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
                    'token' => ['post'],
                    'picture' => ['post'],
                    'timezone' => ['post'],
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
            $this->error(Lang::t('skeleton', 'ACCOUNT_LOGOUT_BEFORE_CREATING'));
            return $this->goHome();
        }

        $form = SignupForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post()) && $form->insert()) {
            $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_SIGN_UP_COMPLETED_PLEASE'));
            return $this->goHome();
        }

        return $this->render('create', [
            'form' => $form,
        ]);
    }

    /**
     * Returns JSON encoded string containing a signup token.
     * The token will only be every five minutes, to prevent multiple signups within one session.
     */
    public function actionToken(): array
    {
        $this->response->format = Response::FORMAT_JSON;

        return [
            'csrf' => $this->request->getCsrfToken(),
            'token' => SignupForm::create()->getSessionToken(),
        ];
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->getUser()->getIsGuest()) {
            $this->error(Lang::t('skeleton', 'ACCOUNT_LOGOUT_BEFORE_LOGGING'));
            return $this->goHome();
        }

        $form = LoginForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post())) {
            if ($form->login()) {
                $this->success($form->user->login_count === 1
                    ? Lang::t('skeleton', 'ACCOUNT_SUCCESS_LOGIN_SUCCESSFUL')
                    : Lang::t('skeleton', 'ACCOUNT_WELCOME_BACK', [
                        'name' => $form->user->getUsername(),
                    ]));

                return $this->goBack(['/admin/dashboard/index']);
            }

            if ($form->isTwoFactorAuthenticationCodeRequired()) {
                return $this->render('authentication', [
                    'form' => $form,
                ]);
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
            $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_NOW_LOGGED_OUT'));
        }

        return $this->redirect(['login']);
    }

    public function actionConfirm(string $email, string $code): Response|string
    {
        $form = Yii::$container->get(AccountConfirmForm::class, [], [
            'email' => $email,
            'code' => $code,
        ]);

        if ($form->confirm() && Yii::$app->getUser()->getIsGuest() && !$form->user->isDisabled()) {
            $webuser = Yii::$app->getUser();
            $webuser->loginType = UserLogin::TYPE_CONFIRM_EMAIL;
            $webuser->login($form->user);
        }

        $this->errorOrSuccess($form, Lang::t('skeleton', 'ACCOUNT_SUCCESS_EMAIL_ADDRESS_SUCCESSFULLY'));
        return $this->goHome();
    }

    public function actionResend(): Response|string
    {
        $form = AccountResendConfirmForm::create();

        $form->user = Yii::$app->getUser()->getIdentity();
        $form->email ??= $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post())) {
            if ($form->resend()) {
                $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_SENT_ANOTHER', [
                    'email' => $form->user->email,
                ]));

                return $this->goBack();
            }

            $this->error($form);
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

        $form = PasswordRecoverForm::create();
        $form->email = Yii::$app->getRequest()->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->recover()) {
                $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_SENT_EMAIL', [
                    'email' => $form->user->email,
                ]));

                return $this->goHome();
            }

            Yii::$app->getSession()->set('email', $form->email);
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

        $form = PasswordResetForm::create();
        $form->email = $email;
        $form->code = $code;

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->reset()) {
                $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_UPDATED_PASSWORD'));
                return $this->goHome();
            }
        } elseif (!$form->validateEmail() || !$form->validatePasswordResetCode()) {
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
                $this->success(Lang::t('skeleton', 'ACCOUNT_FLASH_YOUR_ACCOUNT_WAS_UPDATED'));
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
            $this->success(Lang::t('skeleton', 'ACCOUNT_FLASH_YOUR_ACCOUNT_WAS_UPDATED'));
        }

        return $this->redirect(['update']);
    }

    public function actionDelete(): Response|string
    {
        $form = DeleteForm::create([
            'model' => Yii::$app->getUser()->getIdentity(),
            'attribute' => 'password',
        ]);

        if ($form->load(Yii::$app->getRequest()->post()) && $form->delete()) {
            $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_DELETED'));

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
            $this->errorOrSuccess($form, Lang::t('skeleton', 'ACCOUNT_SUCCESS_TWO_FACTOR_AUTHENTICATION_ENABLED'));
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
            $this->errorOrSuccess($form, Lang::t('skeleton', 'ACCOUNT_SUCCESS_TWO_FACTOR_AUTHENTICATION_DISABLED'));
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

            $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_REMOVED', [
                'client' => $client->getTitle(),
                'name' => $client::getDisplayName($auth),
                'isOwner' => 1,
            ]));

            return $this->redirect(['update']);
        }

        throw new ServerErrorHttpException();
    }

    public function actionTimezone(?string $redirect = null): Response|string
    {
        $user = Yii::$app->getUser()->getIdentity();
        $user->timezone = $this->request->post('timezone');
        $user->update();

        $this->errorOrSuccess($user, Lang::t('skeleton', 'ACCOUNT_SUCCESS_UPDATED_TIMEZONE'));
        return $this->redirect($redirect ?? ['/admin/dashboard/index']);
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
            $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_ACCOUNT_NOW_CONNECTED', [
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
            $this->error(Lang::t('skeleton', 'COMMON_ACCOUNT_CURRENTLY_DISABLED'));
            return false;
        }

        $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_WELCOME_BACK', [
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

        $this->success(Lang::t('skeleton', 'ACCOUNT_SUCCESS_SIGN_UP_COMPLETED_CLIENT', [
            'client' => $form->client->getTitle(),
        ]));

        $auth->user_id = $form->user->id;

        return $auth->insert();
    }
}
