<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Web\Controller;
use Override;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * @property Module $module
 */
class DashboardController extends Controller
{
    /**
     * @var DashboardPanel[]
     */
    private array $panels;
    private array $roles;

    #[Override]
    public function init(): void
    {
        $this->panels = $this->module->getDashboardPanels();
        $this->roles = [];

        foreach ($this->panels as $panel) {
            foreach ($panel->items ?? [] as $item) {
                $this->roles = [...$this->roles, ...$item->roles];
            }

            $this->roles = [...$this->roles, ...$panel->roles];
        }

        $this->roles = array_unique($this->roles);

        if (!$this->roles) {
            $this->roles = ['@'];
        }

        parent::init();
    }

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
        return $this->render('index', [
            'panels' => $this->panels,
        ]);
    }
}
