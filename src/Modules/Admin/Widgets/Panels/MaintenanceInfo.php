<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Db\DatabaseComponents;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Models\Session;
use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Panels\InfoList;
use Override;
use Yii;

class MaintenanceInfo extends InfoList
{
    #[Override]
    protected function configure(): void
    {
        if (!$this->rows) {
            $this->addAssetRow();
            $this->addCacheRows();
            $this->addSchemaRows();
            $this->addSessionRow();
        }

        parent::configure();
    }

    protected function addAssetRow(): void
    {
        $assets = $this->findPublishedAssets();

        /** @see SystemController::actionPublish() */
        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_ASSETS_LABEL'),
            $this->getValue(
                Yii::t('skeleton', 'SYSTEM_ASSETS_TEXT', [
                    'count' => $assets['count'],
                    'size' => Yii::$app->getFormatter()->asShortSize($assets['size']),
                ]),
                $assets['modified'] ? RelativeTime::make()->value($assets['modified']) : null,
            ),
            Button::make()
                ->primary()
                ->icon('sync-alt')
                ->post(['/admin/system/publish'])
                ->tooltip(Yii::t('skeleton', 'SYSTEM_ASSETS_REFRESH_TOOLTIP')),
        );
    }

    protected function addCacheRows(): void
    {
        foreach (CacheComponents::getAll() as $name => $class) {
            /** @see SystemController::actionFlush() */
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_CACHE_LABEL'),
                $this->getValue($name, $class),
                Button::make()
                    ->primary()
                    ->icon('sync-alt')
                    ->post(['/admin/system/flush', 'cache' => $name])
                    ->tooltip(Yii::t('skeleton', 'SYSTEM_CACHE_FLUSH_TOOLTIP')),
            );
        }
    }

    protected function addSchemaRows(): void
    {
        foreach (DatabaseComponents::getAll() as $name => $class) {
            /** @see SystemController::actionSchema() */
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_SCHEMA_LABEL'),
                $this->getValue($name, $class),
                Button::make()
                    ->primary()
                    ->icon('sync-alt')
                    ->post(['/admin/system/schema', 'db' => $name])
                    ->tooltip(Yii::t('skeleton', 'SYSTEM_SCHEMA_REFRESH_TOOLTIP')),
            );
        }
    }

    protected function addSessionRow(): void
    {
        $session = Application::current()->getSession();

        /** @see SystemController::actionSessionGc() */
        $this->addRow(
            Yii::t('skeleton', 'SYSTEM_SESSIONS_LABEL'),
            $this->getValue(
                Yii::t('skeleton', 'SYSTEM_SESSIONS_EXPIRED_TEXT', [
                    'count' => Session::find()->where(['<', 'expire', time()])->count(),
                ]),
                Yii::t('skeleton', 'SYSTEM_SESSIONS_TEXT', [
                    'sessionCount' => Session::find()->count(),
                    'probability' => $session->getGCProbability(),
                ]),
            ),
            Button::make()
                ->primary()
                ->icon('trash')
                ->post(['/admin/system/session-gc'])
                ->tooltip(Yii::t('skeleton', 'SYSTEM_SESSIONS_DELETE_TOOLTIP')),
        );
    }

    /**
     * @return array{count: int, size: int, modified: int|null}
     */
    protected function findPublishedAssets(): array
    {
        $basePath = Yii::$app->getAssetManager()->basePath;
        $directories = is_dir($basePath) ? FileHelper::findDirectories($basePath, ['recursive' => false]) : [];

        $size = 0;
        $modified = null;

        foreach ($directories as $directory) {
            foreach ((array)FileHelper::findFiles($directory) as $file) {
                $size += (int)@filesize((string)$file);
            }

            $time = @filemtime((string)$directory);

            if (is_int($time)) {
                $modified = max($modified ?? 0, $time);
            }
        }

        return [
            'count' => count($directories),
            'size' => $size,
            'modified' => $modified,
        ];
    }
}
