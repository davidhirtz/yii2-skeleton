<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\modules\admin\controllers\traits\UserTrait;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;
use yii\rbac\Role;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class AuthController
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class AuthController extends Controller
{
    use UserTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['assign', 'index', 'revoke', 'view'],
                        'roles' => ['authUpdate'],
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

    /**
     * @return string
     */
    public function actionIndex()
    {
        $items = AuthItem::find()
            ->orderByType()
            ->withUsers()
            ->allWithChildren();

        $provider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => false,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int $user
     * @return string
     */
    public function actionView($user)
    {
        $user = $this->findUserForm($user, 'authUpdate');

        $items = AuthItem::find()
            ->select(['name', 'type', 'description'])
            ->orderByType()
            ->withAssignment($user->id)
            ->allWithChildren();

        $provider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => false,
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('view', [
            'provider' => $provider,
            'user' => $user,
        ]);
    }

    /**
     * @param int $id
     * @param string $name
     * @param int $type
     * @return Response|string
     */
    public function actionAssign($id, $name, $type)
    {
        $user = $this->findUserForm($id, 'authUpdate');
        $role = $this->getAuthItem($name, $type);

        if (!Yii::$app->getAuthManager()->assign($role, $user->id)) {
            $this->error(Yii::t('skeleton', 'This permission was already assigned to user {name}.', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirect(['view', 'user' => $user->id]);
    }

    /**
     * @param int $id
     * @param string $name
     * @param int $type
     * @return Response|string
     */
    public function actionRevoke($id, $name, $type)
    {
        $user = $this->findUserForm($id, 'authUpdate');
        $role = $this->getAuthItem($name, $type);

        if (!Yii::$app->getAuthManager()->revoke($role, $user->id)) {
            $this->error(Yii::t('skeleton', 'This permission was not assigned to user {name}.', [
                'name' => $user->getUsername(),
            ]));
        }

        return $this->redirect(['view', 'user' => $user->id]);
    }

    /**
     * @param string $name
     * @param string $type
     * @return Role
     */
    private function getAuthItem($name, $type)
    {
        $rbac = Yii::$app->getAuthManager();
        $role = null;

        switch ($type) {
            case Role::TYPE_ROLE:
                $role = $rbac->getRole($name);
                break;

            case Role::TYPE_PERMISSION:
                $role = $rbac->getPermission($name);
                break;
        }

        if (!$role) {
            throw new NotFoundHttpException();
        }

        return $role;
    }
}
