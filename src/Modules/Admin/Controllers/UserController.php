<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Forms\DeleteForm;
use Hirtz\Skeleton\Models\Forms\OwnershipForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Models\forms\UserForm;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
                    'delete' => ['post'],
                    'ownership' => ['post'],
                    'reset' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(?string $q = null, ?int $status = null): Response|string
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
        if (!$this->webuser->can(User::AUTH_USER_CREATE)) {
            throw new ForbiddenHttpException();
        }

        $identity = $this->webuser->getIdentity();
        $form = UserForm::create();

        $form->user->language = $identity->language;
        $form->user->timezone = $identity->timezone;

        if ($form->load($this->request->post()) && $form->save()) {
            $this->success(Yii::t('skeleton', 'USER_SUCCESS_CREATED'));
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

        if ($form->load($this->request->post()) && $form->save()) {
            $this->success(Yii::t('skeleton', 'USER_SUCCESS_UPDATED'));
            return $this->refresh();
        }

        return $this->render('update', [
            'form' => $form,
        ]);
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
            $this->success(Yii::t('skeleton', 'USER_SUCCESS_UPDATED_PASSWORD'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }

    public function actionDelete(int $id): Response|string
    {
        if (!$user = User::findOne(['id' => $id])) {
            throw new NotFoundHttpException();
        }

        if (!$this->webuser->can(User::AUTH_USER_DELETE, ['user' => $user])) {
            throw new ForbiddenHttpException();
        }

        $form = DeleteForm::create([
            'model' => $user,
            'attribute' => 'email',
        ]);

        if ($form->load($this->request->post()) && $form->delete()) {
            $this->success(Yii::t('skeleton', 'USER_SUCCESS_DELETED'));

            if ($user->id === $this->webuser->id) {
                $this->webuser->logout(false);
                return $this->goHome();
            }

            return $this->redirect(['index']);
        }

        $this->error($form);

        return $this->redirect(['update', 'id' => $user->id]);
    }

    public function actionOwnership(int $id): Response|string
    {
        if (!$this->webuser->getIdentity()->isOwner()) {
            throw new ForbiddenHttpException();
        }

        $user = $this->findUser($id, User::AUTH_USER_UPDATE);

        $form = OwnershipForm::create([
            'user' => $user,
        ]);

        if ($form->update()) {
            $this->success(Yii::t('skeleton', 'USER_SUCCESS_WEBSITE_OWNERSHIP_SUCCESSFUL'));
            return $this->goHome();
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
            $this->success(Yii::t('skeleton', 'USER_SUCCESS_UPDATED'));
        }

        return $this->redirect(['update', 'id' => $user->id]);
    }
}
