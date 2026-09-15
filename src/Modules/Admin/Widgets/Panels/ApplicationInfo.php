<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\Dsn;
use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Panels\InfoList;
use Override;
use PDO;
use Stringable;
use Throwable;
use Yii;
use yii\db\Connection;

class ApplicationInfo extends InfoList
{
    #[Override]
    protected function configure(): void
    {
        if (!$this->rows) {
            $this->addApplicationRow();
            $this->addUpdatedRow();
            $this->addEnvironmentRow();
            $this->addDatabaseRow();
            $this->addMigrationRow();
            $this->addPhpRow();

            if ($this->canReadSystem()) {
                $this->addYiiRow();
                $this->addExtensionsRow();
            }
        }

        parent::configure();
    }

    /**
     * The repository the installation was built from, the schema it runs against and everything reachable through
     * `phpinfo()` are infrastructure rather than content, so a manager is shown the platform without them.
     */
    protected function canReadSystem(): bool
    {
        return $this->webuser->can(Module::AUTH_SYSTEM);
    }

    protected function addApplicationRow(): void
    {
        $reference = VersionHelper::getApplicationReference();

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_APPLICATION'),
            $this->getValue(Yii::$app->name, trim(($this->canReadSystem() ? VersionHelper::getApplicationName() : '')
                . ' ' . VersionHelper::getApplicationVersion()
                . ($reference !== null ? " ($reference)" : ''))),
        );
    }

    protected function addUpdatedRow(): void
    {
        $timestamp = VersionHelper::getApplicationUpdatedAt();

        if ($timestamp !== null) {
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_LAST_UPDATED'),
                $this->getValue(
                    RelativeTime::make()->value($timestamp),
                    Yii::$app->getFormatter()->asDatetime($timestamp),
                ),
            );
        }
    }

    protected function addEnvironmentRow(): void
    {
        $request = Application::current()->getRequest();

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_ENVIRONMENT'),
            $this->getValue(
                $request->getEnvironmentName() ?? Yii::t('skeleton', 'REQUEST_ENVIRONMENT_PRODUCTION'),
                $request->getHostName(),
            ),
        );
    }

    protected function addDatabaseRow(): void
    {
        $db = Yii::$app->getDb();

        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_DATABASE'),
            $this->getValue(
                $this->getDatabaseVersion($db) ?? ucfirst($db->getDriverName()),
                $this->canReadSystem()
                    ? Dsn::fromString($db->dsn)->database . ' · ' . $db->getDriverName()
                    : $db->getDriverName(),
            ),
        );
    }

    protected function addMigrationRow(): void
    {
        /** @var MigrationHistory $history */
        $history = Yii::createObject(MigrationHistory::class, [Yii::$app->getDb()]);
        $migration = $history->getLastApplied();

        if ($migration !== null) {
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_LAST_MIGRATION'),
                $this->getValue(
                    RelativeTime::make()->value($migration['applyTime']),
                    $migration['version'],
                ),
            );
        }
    }

    /**
     * @see SystemController::actionPhpInfo()
     */
    protected function addPhpRow(): void
    {
        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_PHP'),
            $this->getValue(
                $this->getPhpVersion(),
                implode(' · ', [
                    'memory_limit ' . ini_get('memory_limit'),
                    'upload_max_filesize ' . ini_get('upload_max_filesize'),
                    'post_max_size ' . ini_get('post_max_size'),
                ]),
            ),
        );
    }

    protected function getPhpVersion(): string|Stringable
    {
        if (!$this->canReadSystem()) {
            return PHP_VERSION;
        }

        return A::make()
            ->href(['/admin/system/php-info'])
            ->target('_blank')
            ->attribute('hx-boost', 'false')
            ->addAttributes(['data-tooltip' => '', 'title' => Yii::t('skeleton', 'SYSTEM_PHP_INFO')])
            ->text(PHP_VERSION);
    }

    protected function addYiiRow(): void
    {
        $this->addRow(Yii::t('skeleton', 'SYSTEM_YII'), Yii::getVersion());
    }

    protected function addExtensionsRow(): void
    {
        $extensions = ExtensionVersions::make();

        if ($extensions->render() !== '') {
            $this->addRow(Yii::t('skeleton', 'SYSTEM_EXTENSIONS'), $extensions);
        }
    }

    protected function getDatabaseVersion(Connection $db): ?string
    {
        try {
            $version = (string)$db->getSlavePdo(true)->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            return null;
        }

        return $version === '' ? null : $version;
    }
}
