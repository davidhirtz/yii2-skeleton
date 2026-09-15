<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Response;
use Hirtz\Skeleton\Modules\Admin\Module;

/**
 * @extends Controller<Module>
 */
class UserLoginController extends Controller
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
                        'actions' => ['index', 'view'],
                        'roles' => [User::AUTH_USER],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?string $q = null): Response|string
    {
        $query = UserLogin::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->with([
                'user' => function (UserQuery $query): void {
                    $query->nameAttributesOnly();
                }
            ]);

        if ($q) {
            // an address that does not parse matches nothing: comparing the varbinary column to `false` would
            // cast both sides to a number and return every row
            $query->andWhere(['ip_address' => inet_pton($q) ?: []]);
        }

        $provider = new ActiveDataProvider([
            'sort' => false,
            'query' => $query,
            'pagination' => ['defaultPageSize' => 50],
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionView(int $user): Response|string
    {
        $user = $this->findUser($user, User::AUTH_USER);

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
