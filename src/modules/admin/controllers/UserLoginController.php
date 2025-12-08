<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class UserLoginController extends Controller
{
    use UserTrait;

    #[\Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => [User::AUTH_USER_UPDATE],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(string $q = null): Response|string
    {
        $provider = new ActiveDataProvider([
            'sort' => false,
            'query' => UserLogin::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->filterWhere(['ip_address' => $q ? inet_pton($q) : null])
                ->with([
                    'user' => function (UserQuery $query): void {
                        $query->nameAttributesOnly();
                    }
                ])
        ]);

        $provider->getPagination()->defaultPageSize = 50;
        $provider->setSort(false);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionView(int $user): Response|string
    {
        $user = $this->findUser($user, User::AUTH_USER_UPDATE);

        $provider = new ActiveDataProvider([
            'sort' => false,
            'query' => UserLogin::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->where(['user_id' => $user->id]),
        ]);

        return $this->render('view', [
            'provider' => $provider,
            'user' => $user,
        ]);
    }
}
