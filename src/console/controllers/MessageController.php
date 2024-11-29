<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\console\controllers;

use Yii;

class MessageController extends \yii\console\controllers\MessageController
{
    public function actionExtract($configFile = null): void
    {
        $configFile ??= $this->getDefaultConfigPath();
        parent::actionExtract($configFile);
    }

    protected function getDefaultConfigPath(): ?string
    {
        $file = Yii::getAlias('@messages/config.php');
        return is_file($file) ? $file : null;
    }
}
