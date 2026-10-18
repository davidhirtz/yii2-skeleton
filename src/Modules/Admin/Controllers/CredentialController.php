<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Models\UserCredential;
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
 * The user's own keys for third-party services. Nobody but their owner reaches them, a manager included.
 *
 * @extends Controller<Module>
 */
class CredentialController extends Controller
{
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
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
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
            'credentials' => new ActiveDataProvider([
                'query' => UserCredential::find()
                    ->where(['user_id' => $user->id])
                    ->orderBy(['provider' => SORT_ASC]),
                'sort' => false,
                'pagination' => false,
            ]),
            'providers' => Credentials::getComponent()->getProviders(),
            'user' => $user,
        ]);
    }

    public function actionCreate(): Response
    {
        $provider = Credentials::getComponent()->getProvider((string)$this->request->post('provider'));
        $secret = trim((string)$this->request->post('credentialSecret'));

        if ($provider === null || $secret === '') {
            throw new NotFoundHttpException();
        }

        if (!$provider->isValid($secret)) {
            $this->error(Yii::t('skeleton', 'CREDENTIAL_ERROR_INVALID'));
            return $this->redirect(['index']);
        }

        $credential = UserCredential::store((int)$this->webuser->getId(), $provider->getName(), $secret);
        $this->errorOrSuccess($credential, Yii::t('skeleton', 'CREDENTIAL_SUCCESS_SAVED'));

        return $this->redirect(['index']);
    }

    public function actionDelete(int $id): Response
    {
        $credential = UserCredential::findOne(['id' => $id, 'user_id' => $this->webuser->getId()]);

        if ($credential === null) {
            throw new NotFoundHttpException();
        }

        if ($credential->delete()) {
            $this->success(Yii::t('skeleton', 'CREDENTIAL_SUCCESS_DELETED'));
        }

        return $this->redirect(['index']);
    }
}
