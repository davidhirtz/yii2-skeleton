<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\AuthItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\rbac\Permission;
use yii\rbac\Role;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Hirtz\Skeleton\Modules\Admin\Module;

/**
 * @extends Controller<Module>
 */
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
            'allModels' => $this->module->sortAuthItems($items),
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
            $this->success(Yii::t('skeleton', 'USER_AUTH_ASSIGN_SUCCESS'));
        } else {
            $this->error(Yii::t('skeleton', 'USER_AUTH_ASSIGN_ERROR', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirectToGrid($user);
    }

    public function actionDelete(int $id, string $name, int $type): Response|string
    {
        $user = $this->findUser($id, User::AUTH_USER_ASSIGN);
        $role = $this->getAuthItem($name, $type);

        if (Yii::$app->getAuthManager()->revoke($role, $user->id)) {
            $this->success(Yii::t('skeleton', 'USER_AUTH_REVOKE_SUCCESS'));
        } else {
            $this->error(Yii::t('skeleton', 'USER_AUTH_REVOKE_ERROR', [
                'name' => $user->getUsername(),
            ]));
        }

        // Revoking your own permission may leave you without this page.
        if (!$this->webuser->can(User::AUTH_USER_ASSIGN, ['user' => $user])) {
            return $this->redirect(['/admin/account/update']);
        }

        return $this->redirectToGrid($user);
    }

    protected function redirectToGrid(User $user): Response
    {
        Application::current()->getResponse()->setHtmxRedirectTarget(null);
        return $this->redirect(['/admin/user-auth/index', 'id' => $user->id]);
    }

    /**
     * Refuses an item the acting user does not hold, or `authUpdate` alone could assign the `admin` role.
     */
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

        if (!$this->webuser->can($role->name)) {
            throw new ForbiddenHttpException();
        }

        return $role;
    }
}
