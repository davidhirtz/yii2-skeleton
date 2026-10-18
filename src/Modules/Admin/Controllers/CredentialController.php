<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Models\Forms\UserCredentialForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserCredential;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The user's own keys for third-party services. Only the owner adds one; a manager of the user lists and removes
 * them, and nobody reads one back.
 *
 * @extends Controller<Module>
 */
class CredentialController extends Controller
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
                        'actions' => ['create', 'delete', 'index'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'roles' => [User::AUTH_USER],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    #[Override]
    public function beforeAction($action): bool
    {
        if (!Credentials::getComponent()->getProviders()) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(): string
    {
        $user = $this->webuser->getIdentity();

        return $this->render('index', [
            'credentials' => $this->getCredentialProvider($user),
            'user' => $user,
        ]);
    }

    public function actionView(int $user): string
    {
        $user = $this->findUser($user, User::AUTH_USER);

        return $this->render('view', [
            'credentials' => $this->getCredentialProvider($user),
            'user' => $user,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new UserCredentialForm($this->webuser->getIdentity());

        if ($form->load($this->request->post()) && !$this->request->isFormReload() && $form->save()) {
            $this->success(Yii::t('skeleton', 'CREDENTIAL_SUCCESS_SAVED'));
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'form' => $form,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $credential = UserCredential::find()
            ->where(['id' => $id])
            ->with('user')
            ->one();

        $isOwn = $credential?->user_id === $this->webuser->getId();

        // Someone else's key is not found rather than forbidden, so its id reveals nothing
        if ($credential === null || (!$isOwn && !$this->webuser->can(User::AUTH_USER, ['user' => $credential->user]))) {
            throw new NotFoundHttpException();
        }

        if ($credential->delete()) {
            $this->success(Yii::t('skeleton', 'CREDENTIAL_SUCCESS_DELETED'));
        }

        return $this->redirect($isOwn ? ['index'] : ['view', 'user' => $credential->user_id]);
    }

    protected function getCredentialProvider(User $user): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => UserCredential::find()
                ->where(['user_id' => $user->id])
                ->orderBy(['provider' => SORT_ASC]),
            'sort' => false,
            'pagination' => false,
        ]);
    }
}
