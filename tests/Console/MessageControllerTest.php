<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\MessageController;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Override;
use Yii;
use yii\console\Exception;

class MessageControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    private string $path;
    private string $sourcePath;
    private string $messagePath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->path = Yii::getAlias('@runtime/test-messages') . '/';
        $this->sourcePath = $this->path . 'src/';
        $this->messagePath = $this->path . 'messages/';

        FileHelper::createDirectory($this->sourcePath);
        FileHelper::createDirectory($this->messagePath . 'en-US');

        file_put_contents($this->sourcePath . 'source.php', <<<'PHP'
            <?php
            Yii::t('owned', 'OWNED_KEY');
            Yii::t('other', 'OTHER_KEY');
            PHP);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->path);
        parent::tearDown();
    }

    public function testOnlyTheOwnedCategoriesAreWritten(): void
    {
        $this->extract(['owned']);

        self::assertFileExists($this->messagePath . 'en-US/owned.php');
        self::assertFileDoesNotExist($this->messagePath . 'en-US/other.php');

        $messages = require $this->messagePath . 'en-US/owned.php';

        self::assertArrayHasKey('OWNED_KEY', $messages);
        self::assertArrayNotHasKey('OTHER_KEY', $messages);
    }

    /**
     * Yii removes every message file whose category a run did not produce, which is silent data loss the moment
     * the scan misses a call site.
     */
    public function testAMessageFileOfAnotherCategoryIsNeverDeleted(): void
    {
        $foreign = $this->messagePath . 'en-US/other.php';
        file_put_contents($foreign, "<?php\n\nreturn ['OTHER_KEY' => 'Other'];\n");

        $this->extract(['owned']);

        self::assertFileExists($foreign);
        self::assertSame(['OTHER_KEY' => 'Other'], require $foreign);
    }

    public function testAnOwnedCategoryWithoutAnyKeyIsSkippedRatherThanEmptied(): void
    {
        $existing = $this->messagePath . 'en-US/empty.php';
        file_put_contents($existing, "<?php\n\nreturn ['LEFTOVER' => 'Leftover'];\n");

        $output = $this->extract(['owned', 'empty']);

        self::assertStringContainsString('No message found in "empty" category', $output);
        self::assertSame(['LEFTOVER' => 'Leftover'], require $existing);
    }

    public function testAKeyWithoutATranslationIsWrittenAsAnEmptyString(): void
    {
        $this->extract(['owned']);

        self::assertSame(['OWNED_KEY' => ''], require $this->messagePath . 'en-US/owned.php');
    }

    /**
     * A key the tokenizer cannot reach — a permission description lives in the `auth_item.description` a
     * migration seeds, as a `Message` pointer inside an SQL string — was deleted by `removeUnused` on every run
     * (monorepo issue #211). Naming it in `keepMessages` keeps it, with the translation it already has.
     */
    public function testAKeptKeyAndItsTranslationSurviveARunThatCannotSeeIt(): void
    {
        $file = $this->messagePath . 'en-US/owned.php';
        file_put_contents($file, "<?php\n\nreturn ['KEPT_KEY' => 'Kept', 'STALE_KEY' => 'Stale'];\n");

        $this->extract(['owned'], ['owned' => ['KEPT_KEY']]);

        $messages = require $file;

        self::assertSame('Kept', $messages['KEPT_KEY'] ?? null);
        self::assertArrayHasKey('OWNED_KEY', $messages);
        self::assertArrayNotHasKey('STALE_KEY', $messages);
    }

    /**
     * Kept for a category the configuration does not own, so nothing of another bundle's can be written here.
     */
    public function testAKeptKeyOfAnotherCategoryIsIgnored(): void
    {
        $this->extract(['owned'], ['other' => ['KEPT_KEY']]);

        self::assertFileDoesNotExist($this->messagePath . 'en-US/other.php');
        self::assertArrayNotHasKey('KEPT_KEY', require $this->messagePath . 'en-US/owned.php');
    }

    /**
     * The skipped-category branch counts the kept keys too, or a category reached from data alone would be
     * reported as empty and left behind.
     */
    public function testACategoryHoldingOnlyKeptKeysIsWritten(): void
    {
        $this->extract(['empty'], ['empty' => ['KEPT_KEY']]);

        self::assertSame(['KEPT_KEY' => ''], require $this->messagePath . 'en-US/empty.php');
    }

    public function testAConfigurationWithoutCategoriesIsRefused(): void
    {
        $this->expectException(Exception::class);
        $this->extract(null);
    }

    public function testTheDefaultConfigPathIsTheMessagesAlias(): void
    {
        $controller = $this->createController();

        Yii::setAlias('@messages', $this->path . 'nowhere');
        self::assertNull($controller->getDefaultConfigPath());

        Yii::setAlias('@messages', rtrim($this->messagePath, '/'));
        file_put_contents($this->messagePath . 'config.php', "<?php\n\nreturn [];\n");

        self::assertSame($this->messagePath . 'config.php', $controller->getDefaultConfigPath());
    }

    /**
     * @param list<string>|null $categories
     * @param array<string, list<string>> $keepMessages
     */
    private function extract(?array $categories, array $keepMessages = []): string
    {
        $config = [
            'sourcePath' => rtrim($this->sourcePath, '/'),
            'messagePath' => rtrim($this->messagePath, '/'),
            'languages' => ['en-US'],
            'translator' => ['Yii::t'],
            'overwrite' => true,
            'removeUnused' => true,
            'only' => ['*.php'],
            'format' => 'php',
            'sort' => true,
        ];

        if ($categories !== null) {
            $config['categories'] = $categories;
        }

        if ($keepMessages) {
            $config['keepMessages'] = $keepMessages;
        }

        $configFile = $this->path . 'config.php';
        file_put_contents($configFile, '<?php return ' . var_export($config, true) . ';');

        $controller = $this->createController();

        // through `runAction()`, or Yii's own `actionExtract()` reads `$this->action->id` off a null action
        $controller->runAction('extract', [$configFile]);

        return $controller->flushStdOutBuffer();
    }

    private function createController(): TestMessageController
    {
        return new TestMessageController('message', Application::current());
    }
}

class TestMessageController extends MessageController
{
    use StdOutBufferControllerTrait;

    #[Override]
    public function getDefaultConfigPath(): ?string
    {
        return parent::getDefaultConfigPath();
    }
}
