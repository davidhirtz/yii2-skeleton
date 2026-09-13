<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Override;
use Yii;
use yii\console\Exception;
use yii\helpers\Console;

class MessageController extends \yii\console\controllers\MessageController
{
    #[Override]
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

    #[Override]
    protected function initConfig($configFile): void
    {
        parent::initConfig($configFile);

        if (empty($this->config['categories'])) {
            throw new Exception('The configuration file must specify the "categories" it owns.');
        }
    }

    /**
     * Yii deletes every message file whose category the run did not produce, so a source path that
     * misses a call site is silent data loss. Only the owned categories are written, none is deleted.
     *
     * @param array<string, string[]> $messages
     * @param string $dirName
     * @param bool $overwrite
     * @param bool $removeUnused
     * @param bool $sort
     * @param bool $markUnused
     */
    #[Override]
    protected function saveMessagesToPHP($messages, $dirName, $overwrite, $removeUnused, $sort, $markUnused): void
    {
        foreach ($this->config['categories'] as $category) {
            $file = str_replace('\\', '/', "$dirName/$category.php");
            $extracted = array_values(array_unique($messages[$category] ?? []));

            if (!$extracted) {
                $this->stdout("No message found in \"$category\" category... Skipping.\n\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout('Saving messages to ' . Console::ansiFormat($file, [Console::FG_CYAN]) . "...\n");
            $this->saveMessagesCategoryToPHP($extracted, $file, $overwrite, $removeUnused, $sort, $category, $markUnused);
        }
    }
}
