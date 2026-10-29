<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\db\Query;

/**
 * A {@see Message} pointer seeded in SQL has no call site, so `yii message` deleted its key unnoticed (#211).
 */
class AuthItemDescriptionTest extends TestCase
{
    public function testEveryRegisteredAuthItemHasADescriptionThatRenders(): void
    {
        $language = Yii::$app->language;
        $messages = $this->getAuthItemMessages();

        self::assertNotEmpty($messages, 'The test database holds no described auth item.');

        try {
            foreach ($this->getShippedLanguages() as $shippedLanguage) {
                Yii::$app->language = $shippedLanguage;

                foreach ($messages as $name => $message) {
                    self::assertNotSame(
                        $message->key,
                        (string)$message,
                        "Permission \"$name\" renders its key in \"$shippedLanguage\": the message is missing.",
                    );
                }
            }
        } finally {
            Yii::$app->language = $language;
        }
    }

    /**
     * A permission added without its `keepMessages` entry is deleted again by the next regeneration.
     */
    public function testEveryDescriptionKeyOfThisBundleIsKeptByTheMessageConfiguration(): void
    {
        $config = require __DIR__ . '/../../messages/config.php';
        $kept = $config['keepMessages']['skeleton'] ?? [];

        foreach ($this->getAuthItemMessages() as $name => $message) {
            if ($message->category === 'skeleton') {
                self::assertContains($message->key, $kept, "Permission \"$name\" is missing from `keepMessages`.");
            }
        }
    }

    /**
     * @return array<string, Message>
     */
    private function getAuthItemMessages(): array
    {
        $auth = Yii::$app->getAuthManager();

        $rows = (new Query())
            ->select(['name', 'description'])
            ->from($auth->itemTable)
            ->where(['not', ['description' => null]])
            ->all();

        $messages = [];

        foreach ($rows as $row) {
            $message = Message::fromJson($row['description']);

            if ($message && !$message->isLiteral()) {
                $messages[$row['name']] = $message;
            }
        }

        return $messages;
    }

    /**
     * @return list<string>
     */
    private function getShippedLanguages(): array
    {
        $languages = glob(__DIR__ . '/../../messages/*', GLOB_ONLYDIR) ?: [];
        return array_map(basename(...), $languages);
    }
}
