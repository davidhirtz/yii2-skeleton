<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\AuthItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\web\Response;

class AuthController extends Controller
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
                        'actions' => ['index'],
                        'roles' => [User::AUTH_USER_ASSIGN],
                    ],
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
}
