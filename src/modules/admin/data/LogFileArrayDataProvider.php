<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\models\LogFile;
use Override;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * @property LogFile[] $allModels
 */
class LogFileArrayDataProvider extends ArrayDataProvider
{
    public string $basePath = '@runtime/logs/';

    #[Override]
    public function init(): void
    {
        $this->allModels = $this->findFiles();
        $this->setPagination(false);

        parent::init();
    }

    protected function findFiles(): array
    {
        $files = glob(Yii::getAlias($this->basePath . '*'));
        return array_map(LogFile::createFromFilename(...), $files ?: []);
    }
}
