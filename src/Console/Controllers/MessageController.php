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

            $extracted = array_values(array_unique([
                ...$messages[$category] ?? [],
                ...$this->getKeptMessages($category),
            ]));

            if (!$extracted) {
                $this->stdout("No message found in \"$category\" category... Skipping.\n\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout('Saving messages to ' . Console::ansiFormat($file, [Console::FG_CYAN]) . "...\n");
            $this->saveMessagesCategoryToPHP($extracted, $file, $overwrite, $removeUnused, $sort, $category, $markUnused);
        }
    }

    /**
     * The keys of a category that no call site can name, because what holds them is data: the
     * `auth_item.description` a migration seeds is a {@see \Hirtz\Skeleton\I18n\Message} pointer inside an SQL
     * string, and the tokenizer reads literal arguments of `Yii::t()` and `Message::make()` alone — so
     * `removeUnused` dropped every permission description on each run (monorepo issue #211).
     *
     * @return list<string>
     */
    protected function getKeptMessages(string $category): array
    {
        $kept = $this->config['keepMessages'][$category] ?? [];
        return is_array($kept) ? array_values(array_filter($kept, is_string(...))) : [];
    }
}
