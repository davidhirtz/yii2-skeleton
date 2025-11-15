<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use Override;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * @property array{'name':string, 'size':int, 'updated_at':int}[] $allModels
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
        $files = [];

        foreach (glob(Yii::getAlias($this->basePath . '*')) as $file) {
            $files[] = [
                'name' => pathinfo($file, PATHINFO_BASENAME),
                'size' => filesize($file),
                'updated_at' => filemtime($file),
            ];
        }

        return $files;
    }
}
