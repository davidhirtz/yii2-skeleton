<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Registry;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Registry\Report;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

/**
 * Under the console application, which is what a deploy runs the push from.
 */
class ReportTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testTheReportCarriesTheSchemaAndTheApplicationIdentity(): void
    {
        $report = Report::create()->toArray();

        self::assertSame(Report::SCHEMA, $report['schema']);
        self::assertSame(VersionHelper::getApplicationName(), $report['name']);
        self::assertSame(VersionHelper::getApplicationVersion(), $report['version']);
        self::assertSame(VersionHelper::getApplicationReference(), $report['reference']);
        self::assertSame(VersionHelper::getApplicationUpdatedAt(), $report['deployed_at']);
        self::assertSame(Yii::$app->getBasePath(), $report['base_path']);
        self::assertSame(PHP_VERSION, $report['php']);
        self::assertSame(Yii::getVersion(), $report['yii']);
        self::assertNotEmpty($report['hostname']);
        self::assertNotEmpty($report['os']);
        self::assertLessThanOrEqual(time(), $report['reported_at']);
    }

    public function testTheExtensionsCarryTheirVersionAndReference(): void
    {
        $extensions = Report::create()->toArray()['extensions'];

        self::assertArrayHasKey('davidhirtz/yii2-skeleton', $extensions);
        self::assertSame(['version', 'reference'], array_keys($extensions['davidhirtz/yii2-skeleton']));
        self::assertNotSame('', $extensions['davidhirtz/yii2-skeleton']['version']);
    }

    public function testTheDatabaseAndTheMigrationStateAreReported(): void
    {
        $report = Report::create()->toArray();
        $history = new MigrationHistory(Yii::$app->getDb());

        self::assertSame(Yii::$app->getDb()->getDriverName(), $report['database']['driver']);
        self::assertMatchesRegularExpression('/^\d+\.\d+/', (string)$report['database']['version']);

        self::assertSame($history->getLastApplied()['version'] ?? null, $report['migration']['version']);
        self::assertSame($history->getLastApplied()['applyTime'] ?? null, $report['migration']['applied_at']);
        self::assertSame(count($history->getPending()), $report['migration']['pending']);
    }

    /**
     * A console URL manager without a configured `hostInfo` throws rather than answering `null`.
     */
    public function testTheUrlIsNullUnderAConsoleApplicationWithoutAHost(): void
    {
        self::assertNull(Report::create()->toArray()['url']);
    }

    public function testTheUrlOptionAndTheConfiguredHostAreUsed(): void
    {
        self::assertSame('https://www.example.com', Report::create(['url' => 'https://www.example.com'])->toArray()['url']);

        Yii::$app->getUrlManager()->setHostInfo('https://www.configured.com');
        self::assertSame('https://www.configured.com', Report::create()->toArray()['url']);
    }

    public function testExtraKeysAreOnlyWrittenWhenSet(): void
    {
        self::assertArrayNotHasKey('extra', Report::create()->toArray());
        self::assertSame(['tenants' => 3], Report::create(['extra' => ['tenants' => 3]])->toArray()['extra']);
    }

    public function testTheReportSerializesToItsArray(): void
    {
        $report = Report::create();
        $decoded = json_decode((string)json_encode($report), true);

        self::assertIsArray($decoded);
        self::assertSame(array_keys($report->toArray()), array_keys($decoded));
    }
}
