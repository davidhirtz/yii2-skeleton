<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Db\DatabaseComponents;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Models\Session;
use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Panels\InfoList;
use Override;
use Stringable;
use Yii;

class MaintenanceInfo extends InfoList
{
    public function __construct(array $config = [])
    {
        $this->title ??= Yii::t('skeleton', 'SYSTEM_MAINTENANCE');
        parent::__construct($config);
    }

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
            Yii::t('skeleton', 'SYSTEM_ASSETS'),
            $this->getValue(
                Yii::t('skeleton', 'SYSTEM_ASSETS_PUBLISHED', [
                    'count' => $assets['count'],
                    'size' => Yii::$app->getFormatter()->asShortSize($assets['size'], 1),
                ]),
                $assets['modified'] ? RelativeTime::make()->value($assets['modified']) : null,
            ),
            Button::make()
                ->primary()
                ->icon('sync-alt')
                ->post(['/admin/system/publish'])
                ->tooltip(Yii::t('skeleton', 'ASSET_REFRESH')),
        );
    }

    protected function addCacheRows(): void
    {
        foreach (CacheComponents::getAll() as $name => $class) {
            /** @see SystemController::actionFlush() */
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_CACHE'),
                $this->getValue($name, $class),
                Button::make()
                    ->primary()
                    ->icon('sync-alt')
                    ->post(['/admin/system/flush', 'cache' => $name])
                    ->tooltip(Yii::t('skeleton', 'SYSTEM_FLUSH_CACHE')),
            );
        }
    }

    protected function addSchemaRows(): void
    {
        foreach (DatabaseComponents::getAll() as $name => $class) {
            /** @see SystemController::actionSchema() */
            $this->addRow(
                Yii::t('skeleton', 'SYSTEM_SCHEMA'),
                $this->getValue($name, $class),
                Button::make()
                    ->primary()
                    ->icon('sync-alt')
                    ->post(['/admin/system/schema', 'db' => $name])
                    ->tooltip(Yii::t('skeleton', 'SYSTEM_REFRESH_SCHEMA')),
            );
        }
    }

    protected function addSessionRow(): void
    {
        $session = Yii::$app->getSession();

        /** @see SystemController::actionSessionGc() */
        $this->addRow(
            Yii::t('skeleton', 'SESSION_SESSIONS'),
            $this->getValue(
                Yii::t('skeleton', 'SESSION_EXPIRED_SESSIONS', [
                    'count' => Session::find()->where(['<', 'expire', time()])->count(),
                ]),
                Yii::t('skeleton', 'SESSION_TOTAL_SESSIONS_GARBAGE_COLLECTION_PROBABILITY', [
                    'sessionCount' => Session::find()->count(),
                    'probability' => $session->getGCProbability(),
                ]),
            ),
            Button::make()
                ->primary()
                ->icon('trash')
                ->post(['/admin/system/session-gc'])
                ->tooltip(Yii::t('skeleton', 'SESSION_DELETE_EXPIRED_SESSIONS')),
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
