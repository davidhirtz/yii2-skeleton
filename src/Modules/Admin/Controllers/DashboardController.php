<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Closure;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\base\Event;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class DashboardController extends Controller
{
    public array $roles = [
        User::AUTH_USER,
        User::AUTH_USER_ASSIGN,
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

    /**
     * A bootstrap runs on every request, the dashboard on one action — so a `Closure` keeps the models the
     * permission constants live on from being autoloaded just to name them.
     *
     * @param array<string>|Closure(): array<string> $roles
     */
    public static function addRoles(array|Closure $roles): void
    {
        Event::on(static::class, self::EVENT_CONFIGURE, function (Event $event) use ($roles): void {
            /** @var static $controller */
            $controller = $event->sender;
            $controller->roles = [...$controller->roles, ...($roles instanceof Closure ? $roles() : $roles)];
        });
    }
}
