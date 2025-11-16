<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\web\Controller;
use Override;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * @property Module $module
 */
class DashboardController extends Controller
{
    public ?array $_panels = null;
    public ?array $roles = null;

    #[Override]
    public function init(): void
    {
        if ($this->roles === null) {
            $this->roles = [];

            foreach ($this->getPanels() as $panel) {
                foreach ($panel['items'] ?? [] as $item) {
                    $roles = $item['roles'] ?? null;

                    if ($roles) {
                        $this->roles = [...$this->roles, ...$roles];
                    }
                }
            }

            if (!$this->roles) {
                $this->roles = ['@'];
            }
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
            'panels' => $this->getPanels(),
        ]);
    }

    protected function getPanels(): array
    {
        $this->_panels ??= $this->module->getDashboardPanels();
        return $this->_panels;
    }
}
