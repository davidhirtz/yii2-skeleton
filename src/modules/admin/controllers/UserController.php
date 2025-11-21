<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\traits\UserTrait;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class UserController extends Controller
{
    use UserTrait;

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
                        'actions' => ['create'],
                        'roles' => [User::AUTH_USER_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [User::AUTH_USER_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'deauthorize',
                            'delete-picture',
                            'disable-authenticator',
                            'index',
                            'ownership',
                            'reset',
                            'update',
                        ],
                        'roles' => [User::AUTH_USER_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => [User::AUTH_USER_ASSIGN],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'deauthorize' => ['post'],
                    'delete-picture' => ['post'],
                    'delete' => ['post'],
                    'reset' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(string $q = null, ?int $status = null): Response|string
    {
        $provider = Yii::$container->get(UserActiveDataProvider::class, config: [
            'searchString' => $q,
            'status' => $status,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(): Response|string
    {
        if (!Yii::$app->getUser()->can(User::AUTH_USER_CREATE)) {
            throw new ForbiddenHttpException();
        }

        $identity = Yii::$app->getUser()->getIdentity();
        $form = UserForm::create();

        $form->user->language = $identity->language;
        $form->user->timezone = $identity->timezone;
        $form->user->country = $identity->country;

        if ($form->load(Yii::$app->getRequest()->post()) && $form->save()) {
            $this->success(Yii::t('skeleton', 'The user was created.'));
            return $this->redirect(['update', 'id' => $form->user->id]);
        }

        return $this->render('create', [
            'form' => $form,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_UPDATE);
        $form = UserForm::create(['user' => $user]);

        if ($form->load(Yii::$app->getRequest()->post()) && $form->save()) {
            $this->success(Yii::t('skeleton', 'The user was updated.'));
            return $this->refresh();
        }

        return $this->render('update', [
            'form' => $form,
        ]);
    }

    public function actionDeletePicture(int $id): Response|string
    {
        return $this->updateUserAttributes($id, ['picture' => null]);
    }

    public function actionDisableGoogleAuthenticator(int $id): Response|string
    {
        return $this->updateUserAttributes($id, ['google_2fa_secret' => null]);
    }

    public function actionReset(int $id): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_UPDATE);
        $user->generatePasswordResetToken();

        if ($user->save()) {
            $this->success(Yii::t('skeleton', 'The password reset link was updated.'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }

    public function actionDelete(int $id): Response|string
    {
        if (!$user = User::findOne(['id' => $id])) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $user])) {
            throw new ForbiddenHttpException();
        }

        $form = Yii::$container->get(DeleteForm::class, [], [
            'model' => $user,
            'attribute' => 'email',
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->delete()) {
                $this->success(Yii::t('skeleton', 'The user was deleted.'));

                if ($user->id === Yii::$app->getUser()->id) {
                    Yii::$app->getUser()->logout(false);
                    return $this->goHome();
                }

                return $this->redirect(['index']);
            }
        }

        $this->error($form);

        return $this->redirect(['update', 'id' => $user->id]);
    }

    public function actionDeauthorize(string $id, string $name): Response|string
    {
        $auth = AuthClient::find()
            ->where([
                AuthClient::tableName() . '.[[id]]' => $id,
                AuthClient::tableName() . '.[[name]]' => $name,
            ])
            ->joinWith('user', true, 'JOIN')
            ->limit(1)
            ->one();

        if (!$auth) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $auth->identity])) {
            throw new ForbiddenHttpException();
        }

        if ($auth->delete()) {
            $client = $auth->getClientClass();

            $this->success(Yii::t('skeleton', '{client} account "{name}" was removed from user account.', [
                'client' => $client->getTitle(),
                'name' => $client::getDisplayName($auth),
            ]));

            return $this->redirect(['update', 'id' => $auth->user_id]);
        }

        throw new ServerErrorHttpException();
    }

    public function actionOwnership(): Response|string
    {
        if (!Yii::$app->getUser()->getIdentity()->isOwner()) {
            throw new ForbiddenHttpException();
        }

        $form = OwnershipForm::create();

        if ($form->load(Yii::$app->request->post())) {
            if ($form->update()) {
                $this->success(Yii::t('skeleton', 'The website ownership was successful transferred!'));
                return $this->goHome();
            }
        }

        return $this->render('ownership', [
            'form' => $form,
        ]);
    }

    protected function updateUserAttributes(int $id, array $attributes): Response
    {
        $user = $this->findUser($id, User::AUTH_USER_UPDATE);
        $user->setAttributes($attributes, false);

        if ($user->save()) {
            $this->success(Yii::t('skeleton', 'The user was updated.'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }
}
