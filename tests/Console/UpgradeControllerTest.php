<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\UpgradeController;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;
use yii\db\Query;

class UpgradeControllerTest extends TestCase
{
    public function testTrailDataIsRewrittenAsAnObject(): void
    {
        $empty = $this->insertTrail('Hirtz\Skeleton\Models\User', null);
        $current = $this->insertTrail('Hirtz\Skeleton\Models\User', ['name' => ['Müller 😀', 'a/b']]);
        $legacy = $this->insertTrail('davidhirtz\yii2\cms\models\Entry', ['model_class' => 'app\models\Entry']);

        $controller = $this->createController();
        $controller->actionIndex();

        self::assertStringContainsString('Updated 1 trail records', $controller->flushStdOutBuffer());

        self::assertNull(Trail::findOne($empty)?->data);
        self::assertSame(['name' => ['Müller 😀', 'a/b']], Trail::findOne($current)?->data);

        $trail = Trail::findOne($legacy);
        self::assertSame('Hirtz\Cms\Models\Entry', $trail?->model_class);
        self::assertSame(['model_class' => 'App\Models\Entry'], $trail->data);

        $controller->actionIndex();
        self::assertStringContainsString('No update needed for trail records', $controller->flushStdOutBuffer());
    }

    public function testAMigrationOutsideTheOldNamespacesIsLeftAlone(): void
    {
        $versions = $this->getMigrationVersions();

        $controller = $this->createController();
        $controller->actionIndex();

        self::assertStringContainsString('No update needed for migration namespaces', $controller->flushStdOutBuffer());
        self::assertSame($versions, $this->getMigrationVersions());
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function insertTrail(string $modelClass, ?array $data): int
    {
        $trail = Trail::create();
        $trail->model_class = $modelClass;
        $trail->model_id = '1';
        $trail->type = Trail::TYPE_UPDATE;
        $trail->data = $data;

        self::assertTrue($trail->insert(), implode(' ', $trail->getErrorSummary(true)));

        return $trail->id;
    }

    /**
     * @return list<string>
     */
    private function getMigrationVersions(): array
    {
        return array_values((new Query())->select('version')->from('{{%migration}}')->orderBy(['version' => SORT_ASC])->column());
    }

    private function createController(): UpgradeIndexControllerMock
    {
        $controller = new UpgradeIndexControllerMock('upgrade', Yii::$app);
        $controller->interactive = false;

        return $controller;
    }
}

class UpgradeIndexControllerMock extends UpgradeController
{
    use StdOutBufferControllerTrait;
}
