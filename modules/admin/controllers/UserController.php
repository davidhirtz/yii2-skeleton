<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\modules\admin\controllers\traits\UserTrait;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\forms\DeleteForm;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use Yii;

/**
 * Class UserController
 * @package app\controllers
 */
class UserController extends Controller
{
    use UserTrait;

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['userCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['userDelete'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['deauthorize', 'delete-picture', 'disable-google-authenticator', 'index', 'ownership', 'reset', 'update'],
                        'roles' => ['userUpdate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['authUpdate'],
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

    /**
     * @param string|null $q
     * @return string
     */
    public function actionIndex($q = null)
    {
        /** @var UserActiveDataProvider $provider */
        $provider = Yii::createObject([
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\data\UserActiveDataProvider',
            'searchString' => $q,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @return string|Response
     */
    public function actionCreate()
    {
        $user = new UserForm();
        $user->status = $user::STATUS_ENABLED;

        if (!Yii::$app->getUser()->can('userCreate', ['user' => $user])) {
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

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'user' => $user,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $user = $this->findUserForm($id, 'userUpdate');

        if ($user->load(Yii::$app->getRequest()->post())) {
            if ($user->update()) {
                $this->success(Yii::t('skeleton', 'The user was updated.'));
                return $this->refresh();
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('update', [
            'user' => $user,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionDeletePicture($id)
    {
        return $this->updateUserAttributes($id, ['picture' => null]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionDisableGoogleAuthenticator($id)
    {
        return $this->updateUserAttributes($id, ['google_2fa_secret' => null]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionReset($id)
    {
        $user = $this->findUserForm($id, 'userUpdate');
        $user->generatePasswordResetCode();

        if ($user->update()) {
            $this->success(Yii::t('skeleton', 'The password reset link was updated.'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionDelete($id)
    {
        if (!$user = User::findOne(['id' => $id])) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can('userDelete', ['user' => $user])) {
            throw new ForbiddenHttpException();
        }

        /** @var DeleteForm $form */
        $form = Yii::createObject([
            'class' => 'davidhirtz\yii2\skeleton\models\forms\DeleteForm',
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

        $this->error($form->getFirstErrors());

        return $this->redirect(['update', 'id' => $user->id]);
    }

    /**
     * @param string $id
     * @param string $name
     * @return string|Response
     */
    public function actionDeauthorize($id, $name)
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

        if (!Yii::$app->getUser()->can('userUpdate', ['user' => $auth->identity])) {
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

    /**
     * @return string|Response
     */
    public function actionOwnership()
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

        /** @noinspection MissedViewInspection */
        return $this->render('ownership', [
            'form' => $form,
        ]);
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return string|Response
     */
    private function updateUserAttributes($id, $attributes)
    {
        $user = $this->findUserForm($id, 'userUpdate');
        $user->setAttributes($attributes, false);

        if ($user->update()) {
            $this->success(Yii::t('skeleton', 'The user was updated.'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }
}