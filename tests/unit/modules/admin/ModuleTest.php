<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\ModuleInterface;
use Yii;

class ModuleTest extends Unit
{
    public function testNavBarItems(): void
    {
        $module = $this->getAdminModule();

        $module->setNavBarItems([
            'test' => [
                'name' => 'Test',
            ],
        ]);

        self::assertEquals(['users', 'test'], array_keys($module->getNavBarItems()));

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        self::assertEquals(['users', 'module', 'test'], array_keys($module->getNavBarItems()));
    }

    public function testDashboardPanels(): void
    {
        $module = $this->getAdminModule();

        $module->setDashboardPanels([
            'test' => [
                'name' => 'Test',
            ],
        ]);

        self::assertEquals(['skeleton', 'test'], array_keys($module->getDashboardPanels()));

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        $panels = $module->getDashboardPanels();

        self::assertEquals(['skeleton', 'module', 'test'], array_keys($panels));
        self::assertEquals('Overridden label', $panels['skeleton']['items']['user']['label']);
    }

    private function getAdminModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }
}

class TestModule extends \davidhirtz\yii2\skeleton\base\Module implements ModuleInterface
{
    public function getDashboardPanels(): array
    {
        return [
            'module' => [
                'name' => 'Test Module',
            ],
            'skeleton' => [
                'items' => [
                    'user' => [
                        'label' => 'Overridden label',
                    ],
                ],
            ],
        ];
    }

    public function getNavBarItems(): array
    {
        return [
            'module' => [
                'name' => 'Test Module',
            ],
        ];
    }
}
