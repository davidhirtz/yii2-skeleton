<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\AuthItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\rbac\Permission;
use yii\rbac\Role;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UserAuthController extends Controller
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
                        'roles' => [User::AUTH_USER_ASSIGN],
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

    public function actionIndex(int $id): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_ASSIGN);

        $items = AuthItem::find()
            ->select(['name', 'type', 'description'])
            ->orderByType()
            ->withAssignment($user->id)
            ->allWithChildren();

        $provider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => false,
        ]);

        return $this->render('index', [
            'provider' => $provider,
            'user' => $user,
        ]);
    }

    public function actionCreate(int $id, string $name, int $type): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_ASSIGN);
        $role = $this->getAuthItem($name, $type);

        if (Yii::$app->getAuthManager()->assign($role, $user->id)) {
            $this->success(Lang::t('skeleton', 'USER_AUTH_FLASH_THE_PERMISSION_WAS_ASSIGNED'));
        } else {
            $this->error(Lang::t('skeleton', 'USER_AUTH_THIS_PERMISSION_WAS_ALREADY_ASSIGNED_TO', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirect(['/admin/user-auth/index', 'id' => $user->id]);
    }

    public function actionDelete(int $id, string $name, int $type): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_ASSIGN);
        $role = $this->getAuthItem($name, $type);

        if (Yii::$app->getAuthManager()->revoke($role, $user->id)) {
            $this->success(Lang::t('skeleton', 'USER_AUTH_FLASH_THE_PERMISSION_WAS_REMOVED'));
        } else {
            $this->error(Lang::t('skeleton', 'USER_AUTH_THIS_PERMISSION_WAS_NOT_ASSIGNED_TO', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirect(['/admin/user-auth/index', 'id' => $user->id]);
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
