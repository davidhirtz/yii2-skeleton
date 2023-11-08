<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\traits\UserTrait;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\web\Controller;
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

    public function behaviors(): array
    {
        return [
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
                        'actions' => ['deauthorize', 'delete-picture', 'disable-google-authenticator', 'index', 'ownership', 'reset', 'update'],
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

    public function actionIndex(string $q = null): Response|string
    {
        $provider = Yii::$container->get(UserActiveDataProvider::class, [], [
            'searchString' => $q,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $user = new UserForm();
        $user->status = $user::STATUS_ENABLED;

        if (!Yii::$app->getUser()->can(User::AUTH_USER_CREATE, ['user' => $user])) {
            throw new ForbiddenHttpException();
        }

        if ($user->load(Yii::$app->getRequest()->post())) {
            if ($user->insert()) {
                $this->success(Yii::t('skeleton', 'The user was created.'));
                return $this->redirect(['update', 'id' => $user->id]);
            }
        } else {
            $identity = Yii::$app->getUser()->getIdentity();
            $user->language = $identity->language;
            $user->timezone = $identity->timezone;
        }

        return $this->render('create', [
            'user' => $user,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $user = $this->findUserForm($id, User::AUTH_USER_UPDATE);

        if ($user->load(Yii::$app->getRequest()->post())) {
            if ($user->update()) {
                $this->success(Yii::t('skeleton', 'The user was updated.'));
                return $this->refresh();
            }
        }

        return $this->render('update', [
            'user' => $user,
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
        $user = $this->findUserForm($id, User::AUTH_USER_UPDATE);
        $user->generatePasswordResetToken();

        if ($user->update()) {
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

                if ($user->id == Yii::$app->getUser()->id) {
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
        /** @var AuthClient $auth */
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

        $form = new OwnershipForm();

        if ($form->load(Yii::$app->request->post())) {
            if ($form->transfer()) {
                $this->success(Yii::t('skeleton', 'The website ownership was successful transferred!'));
                return $this->goHome();
            }
        }

        return $this->render('ownership', [
            'form' => $form,
        ]);
    }

    private function updateUserAttributes(int $id, array $attributes): Response|string
    {
        $user = $this->findUserForm($id, User::AUTH_USER_UPDATE);
        $user->setAttributes($attributes, false);

        if ($user->update()) {
            $this->success(Yii::t('skeleton', 'The user was updated.'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }
}
