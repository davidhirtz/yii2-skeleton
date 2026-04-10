<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\base\Event;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * @property Module $module
 */
class DashboardController extends Controller
{
    public array $roles = [
        User::AUTH_USER_CREATE,
        user::AUTH_USER_ASSIGN,
    ];

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'error'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => $this->roles,
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): Response|string
    {
        return $this->render('index');
    }

    public static function addRoles(array $roles): void
    {
        Event::on(static::class, static::EVENT_BEFORE_ACTION, function (Event $event) use ($roles): void {
            /** @var static $controller */
            $controller = $event->sender;
            $controller->roles = [...$controller->roles, ...$roles];
        });
    }
}
