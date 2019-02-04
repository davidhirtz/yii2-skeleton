<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use Yii;
use yii\rbac\Role;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class AuthController.
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class AuthController extends Controller
{
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
                        'actions' => ['assign', 'index', 'revoke', 'user'],
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
     * Auth list.
     * @return string
     */
    public function actionIndex()
    {
        $items = AuthItem::find()
            ->select(['name', 'type', 'description'])
            ->orderByType()
            ->withUsers()
            ->allWithChildren();

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => new ArrayDataProvider(['allModels' => $items]),
        ]);
    }

    /**
     * Auth user list.
     * @param int $id
     * @return string
     */
    public function actionUser($id)
    {
        $user = $this->getUser($id);
        return $this->renderUserAuthItems($user);
    }

    /**
     * @param int $id
     * @param string $name
     * @param int $type
     * @return \yii\web\Response|string
     */
    public function actionAssign($id, $name, $type)
    {
        $user = $this->getUser($id);
        $role = $this->getAuthItem($name, $type);

        $rbac = Yii::$app->getAuthManager();
        $rbac->invalidateCache();

        if ($rbac->getAssignment($role->name, $user->id)) {
            $this->error( Yii::t('skeleton', 'This permission was already assigned to user {name}.', [
                'name' => $user->getUsername(),
            ]));
        } else {
            $rbac->assign($role, $user->id);

            if (Yii::$app->getRequest()->getIsAjax()) {
                return $this->renderUserAuthItems($user);
            }
        }

        return $this->redirect(['user', 'id' => $user->id]);
    }

    /**
     * @param int $id
     * @param string $name
     * @param int $type
     * @return \yii\web\Response|string
     */
    public function actionRevoke($id, $name, $type)
    {
        $user = $this->getUser($id);
        $role = $this->getAuthItem($name, $type);

        $rbac = Yii::$app->getAuthManager();
        $rbac->invalidateCache();

        if (!$rbac->getAssignment($role->name, $user->id)) {
            Yii::$app->getSession()->addFlash('error', Yii::t('skeleton', 'This permission was not assigned to user {name}.', [
                'name' => $user->getUsername(),
            ]));
        } else {
            $rbac->revoke($role, $user->id);

            if (Yii::$app->getRequest()->getIsAjax()) {
                return $this->renderUserAuthItems($user);
            }
        }

        return $this->redirect(['user', 'id' => $user->id]);
    }

    /***********************************************************************
     * Methods.
     ***********************************************************************/

    /**
     * @param User $user
     * @return string
     */
    private function renderUserAuthItems($user)
    {
        $items = AuthItem::find()
            ->select(['name', 'type', 'description'])
            ->orderByType()
            ->withAssignment($user->id)
            ->allWithChildren();

        /** @noinspection MissedViewInspection */
        return $this->render('user', [
            'provider' => new ArrayDataProvider(['allModels' => $items]),
            'user' => $user,
        ]);
    }

    /**
     * @param int $id
     * @return User
     */
    private function getUser($id)
    {
        /**
         * @var \davidhirtz\yii2\skeleton\models\User $user
         */
        if (!$user = User::findOne($id)) {
            throw new NotFoundHttpException;
        }

        if (!Yii::$app->user->can('authUpdate', ['user' => $user])) {
            throw new ForbiddenHttpException;
        }

        return $user;
    }

    /**
     * @param string $name
     * @param string $type
     * @return \yii\rbac\Role
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
            throw new NotFoundHttpException;
        }

        return $role;
    }
}
