<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Forms\AccountConfirmForm;
use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
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
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

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
                            'delete',
                            'disable-authenticator',
                            'enable-authenticator',
                            'logout',
                            'update',
                            'timezone',
                        ],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
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
                    'delete' => ['post'],
                    'disable-authenticator' => ['post'],
                    'enable-authenticator' => ['post'],
                    'logout' => ['post'],
                    'token' => ['post'],
                    'timezone' => ['post'],
                ],
            ],
        ];
    }

    public function actionCreate(): Response|string
    {
        if (!$this->webuser->getIsGuest()) {
            $this->error(Yii::t('skeleton', 'ACCOUNT_LOGOUT_BEFORE_CREATING'));
            return $this->goHome();
        }

        $form = SignupForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post()) && $form->insert()) {
            $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_SIGN_UP_COMPLETED_PLEASE'));
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
        if (!$this->webuser->getIsGuest()) {
            $this->error(Yii::t('skeleton', 'ACCOUNT_LOGOUT_BEFORE_LOGGING'));
            return $this->goHome();
        }

        $form = LoginForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post())) {
            if ($form->login()) {
                $this->success($form->user->login_count === 1
                    ? Yii::t('skeleton', 'ACCOUNT_SUCCESS_LOGIN_SUCCESSFUL')
                    : Yii::t('skeleton', 'ACCOUNT_WELCOME_BACK', [
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
        if ($this->webuser->logout()) {
            $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_NOW_LOGGED_OUT'));
        }

        return $this->redirect(['login']);
    }

    public function actionConfirm(string $email, string $code): Response|string
    {
        $form = Yii::$container->get(AccountConfirmForm::class, [], [
            'email' => $email,
            'code' => $code,
        ]);

        if ($form->confirm() && $this->webuser->getIsGuest() && !$form->user->isDisabled()) {
            $this->webuser->loginType = UserLogin::TYPE_CONFIRM_EMAIL;
            $this->webuser->login($form->user);
        }

        $this->errorOrSuccess($form, Yii::t('skeleton', 'ACCOUNT_SUCCESS_EMAIL_ADDRESS_SUCCESSFULLY'));
        return $this->goHome();
    }

    public function actionResend(): Response|string
    {
        $form = AccountResendConfirmForm::create();

        $form->user = $this->webuser->getIdentity();
        $form->email ??= $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post())) {
            if ($form->resend()) {
                $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_SENT_ANOTHER', [
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
        if (!$this->webuser->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException();
        }

        $form = PasswordRecoverForm::create();
        $form->email = $this->request->get('email', Yii::$app->getSession()->get('email'));

        if ($form->load($this->request->post())) {
            if ($form->recover()) {
                $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_SENT_EMAIL', [
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
        if (!$this->webuser->isPasswordResetEnabled()) {
            throw new ForbiddenHttpException();
        }

        $form = PasswordResetForm::create();
        $form->email = $email;
        $form->code = $code;

        if ($form->load($this->request->post())) {
            if ($form->reset()) {
                $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_UPDATED_PASSWORD'));
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
            'user' => $this->webuser->getIdentity(),
        ]);

        if ($form->load($this->request->post())) {
            if ($form->save()) {
                $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_PROFILE_UPDATED'));
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

    public function actionDelete(): Response|string
    {
        $form = DeleteForm::create([
            'model' => $this->webuser->getIdentity(),
            'attribute' => 'password',
        ]);

        if ($form->load($this->request->post()) && $form->delete()) {
            $this->success(Yii::t('skeleton', 'ACCOUNT_SUCCESS_DELETED'));

            $this->webuser->logout();
            return $this->goHome();
        }

        $this->error($form);

        return $this->redirect(['update']);
    }

    public function actionEnableAuthenticator(): Response|string
    {
        $form = TwoFactorAuthenticatorForm::create([
            'user' => $this->webuser->getIdentity(),
        ]);

        if ($form->load($this->request->post())) {
            $form->save();
            $this->errorOrSuccess($form, Yii::t('skeleton', 'ACCOUNT_SUCCESS_TWO_FACTOR_AUTHENTICATION_ENABLED'));
        }

        return $this->redirect(['update']);
    }

    public function actionDisableAuthenticator(): Response|string
    {
        $form = TwoFactorAuthenticatorForm::create([
            'user' => $this->webuser->getIdentity(),
        ]);

        if ($form->load($this->request->post())) {
            $form->delete();
            $this->errorOrSuccess($form, Yii::t('skeleton', 'ACCOUNT_SUCCESS_TWO_FACTOR_AUTHENTICATION_DISABLED'));
        }

        return $this->redirect(['update']);
    }

    public function actionTimezone(?string $redirect = null): Response|string
    {
        $user = $this->webuser->getIdentity();
        $user->timezone = $this->request->post('timezone');
        $user->update();

        $this->errorOrSuccess($user, Yii::t('skeleton', 'ACCOUNT_SUCCESS_UPDATED_TIMEZONE'));
        return $this->redirect($redirect ?? ['/admin/dashboard/index']);
    }
}
