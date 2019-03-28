<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class UserLoginController.
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class UserLoginController extends Controller
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
                        'actions' => ['index', 'view'],
                        'roles' => ['userUpdate'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param int $q
     * @return string
     */
    public function actionIndex($q = null)
    {
        $provider = new ActiveDataProvider([
            'sort' => false,
            'query' => UserLogin::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->filterWhere(['ip' => $q])
                ->with([
                    'user' => function (\davidhirtz\yii2\skeleton\models\queries\UserQuery $query) {
                        $query->nameAttributesOnly();
                    }
                ])
        ]);

        $provider->getPagination()->defaultPageSize = 50;
        $provider->setSort(false);

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'provider' => $provider,
            'ip' => $q ? long2ip($q) : null,
        ]);
    }

    /**
     * @param int $user
     * @return string
     */
    public function actionView($user)
    {
        $user = User::find()
            ->nameAttributesOnly()
            ->where(['id' => $user])
            ->one();

        if (!$user) {
            throw new NotFoundHttpException;
        }

        $provider = new ActiveDataProvider([
            'sort' => false,
            'query' => UserLogin::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->where(['user_id' => $user->id]),
        ]);

        /** @noinspection MissedViewInspection */
        return $this->render('view', [
            'provider' => $provider,
            'user' => $user,
        ]);
    }
}