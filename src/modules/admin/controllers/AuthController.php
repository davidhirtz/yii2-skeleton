<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\traits\UserTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\rbac\Permission;
use yii\rbac\Role;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AuthController extends Controller
{
    use UserTrait;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['assign', 'index', 'revoke', 'view'],
                        'roles' => [User::AUTH_USER_ASSIGN],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'assign' => ['post'],
                    'revoke' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): Response|string
    {
        $items = AuthItem::find()
            ->orderByType()
            ->withUsers()
            ->allWithChildren();

        $provider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => false,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionView(int $user): Response|string
    {
        $user = $this->findUser($user, User::AUTH_USER_ASSIGN);

        $items = AuthItem::find()
            ->select(['name', 'type', 'description'])
            ->orderByType()
            ->withAssignment($user->id)
            ->allWithChildren();

        $provider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => false,
        ]);

        return $this->render('view', [
            'provider' => $provider,
            'user' => $user,
        ]);
    }

    public function actionAssign(int $id, string $name, int $type): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_ASSIGN);
        $role = $this->getAuthItem($name, $type);

        if (!Yii::$app->getAuthManager()->assign($role, $user->id)) {
            $this->error(Yii::t('skeleton', 'This permission was already assigned to user {name}.', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirect(['view', 'user' => $user->id]);
    }

    public function actionRevoke(int $id, string $name, int $type): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_ASSIGN);
        $role = $this->getAuthItem($name, $type);

        if (!Yii::$app->getAuthManager()->revoke($role, $user->id)) {
            $this->error(Yii::t('skeleton', 'This permission was not assigned to user {name}.', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirect(['view', 'user' => $user->id]);
    }

    protected function getAuthItem(string $name, int $type): Permission|Role
    {
        $rbac = Yii::$app->getAuthManager();

        $role = match ($type) {
            Role::TYPE_ROLE => $rbac->getRole($name),
            Role::TYPE_PERMISSION => $rbac->getPermission($name),
            default => null,
        };

        if (!$role) {
            throw new NotFoundHttpException();
        }

        return $role;
    }
}
