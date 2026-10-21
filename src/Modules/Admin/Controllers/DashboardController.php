<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Closure;
use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class DashboardController extends Controller
{
    /**
     * @var list<string>
     */
    public array $roles = [
        Module::AUTH_SYSTEM,
        Redirect::AUTH_REDIRECT,
        Trail::AUTH_TRAIL_INDEX,
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
                // @phpstan-ignore mspirkovYii2.controllerBehaviorActionsValidation (The "actionError" method is not present in this class, but we can't remove 'error' because it would break backward compatibility.)
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
        EventHelper::on(static::class, self::EVENT_CONFIGURE, function (self $controller) use ($roles): void {
            $controller->roles = array_values([...$controller->roles, ...($roles instanceof Closure ? $roles() : $roles)]);
        });
    }
}
