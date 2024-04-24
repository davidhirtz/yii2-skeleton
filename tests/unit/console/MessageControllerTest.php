<?php

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\ConsoleApplicationTrait;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\MessageController;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

class MessageControllerTest extends Unit
{
    use ConsoleApplicationTrait;

    protected ?MessageControllerMock $controller = null;

    protected function _before(): void
    {
        $this->createConsoleApplicationMock();
        $this->controller = new MessageControllerMock('migration', Yii::$app);

        parent::_before();
    }

    public function _after(): void
    {
        FileHelper::removeDirectory(Yii::getAlias('@runtime/messages'));
    }

    /**
     * @see MessageController::actionExtract()
     */
    public function testActionExtractWithoutConfig(): void
    {
        self::expectExceptionMessage('Languages cannot be empty.');
        $this->controller->runAction('extract');
    }

    /**
     * @see MessageController::actionExtract()
     */
    public function testActionExtractDefaultConfig(): void
    {
        Yii::setAlias('@messages', '@skeleton/messages');
        $this->runActionWithTestConfig('extract');

        self::assertFileExists(Yii::getAlias('@runtime/messages/en-US/skeleton.php'));
        self::assertFileExists(Yii::getAlias('@runtime/messages/de/skeleton.php'));
    }

    /**
     * @see MessageController::actionExportCsv()
     */
    public function testActionExportWithWrongFormat(): void
    {
        self::expectExceptionMessage('Only PHP format is supported.');

        $this->controller->format = 'db';
        $this->controller->runAction('export-csv');
    }

    /**
     * @see MessageController::actionExportCsv()
     */
    public function testActionExport(): void
    {
        $this->runActionWithTestConfig('export-csv', ['@runtime/messages']);

        $filename = Yii::getAlias('@runtime/messages/skeleton.csv');
        self::assertFileExists($filename);

        $file = fopen($filename, 'r');
        $languages = fgetcsv($file);

        self::assertEquals($languages[0], Yii::$app->sourceLanguage);
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithWrongFormat()
    {
        self::expectExceptionMessage('Only PHP format is supported.');

        $this->controller->format = 'db';
        $this->controller->runAction('import-csv');
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithMissingSourceFile()
    {
        self::expectExceptionMessage('Source file in CSV format must be provided');
        $this->controller->runAction('import-csv');
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithInvalidSourceFile()
    {
        self::expectExceptionMessageMatches('/^Failed to read source file/');
        $this->controller->runAction('import-csv', ['@runtime/messages/invalid.csv']);
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithInvalidSourceLanguage()
    {
        self::expectExceptionMessage('Source file must contain source language as first column.');

        $filename = $this->getTestCsvFilename([
            ['de', 'en-US'],
        ]);

        $this->controller->runAction('import-csv', [$filename]);
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithInvalidLanguages(): void
    {
        self::expectExceptionMessage('Language "invalid" is not supported.');

        $filename = $this->getTestCsvFilename([
            ['en-US', 'de', 'invalid'],
        ]);

        $this->runActionWithTestConfig('import-csv', [$filename]);
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithoutPreviousTranslations()
    {
        $this->runImportActionWithTestCsvFile([
            ['en-US', 'de'],
            ['This is a test string', 'Das ist ein Teststring'],
        ]);

        self::assertFileExists(Yii::getAlias('@runtime/messages/en-US/skeleton.php'));
        self::assertFileExists(Yii::getAlias('@runtime/messages/de/skeleton.php'));

        $data = require Yii::getAlias('@runtime/messages/de/skeleton.php');
        self::assertEquals('Das ist ein Teststring', $data['This is a test string']);
    }

    /**
     * @see MessageController::actionImportCsv()
     */
    public function testActionImportWithPreviousTranslations()
    {
        $this->runActionWithTestConfig('extract');

        $this->runImportActionWithTestCsvFile([
            ['en-US', 'de'],
            ['This is a "test"', 'Das ist ein "Test" '], // Note the extra space at the end
        ]);

        self::assertFileExists(Yii::getAlias('@runtime/messages/en-US/skeleton.php'));
        self::assertFileExists(Yii::getAlias('@runtime/messages/de/skeleton.php'));

        $data = require Yii::getAlias('@runtime/messages/de/skeleton.php');

        self::assertEquals('Das ist ein "Test"', $data['This is a "test"']);
    }

    protected function runImportActionWithTestCsvFile($data): ?int
    {
        $filename = $this->getTestCsvFilename($data);
        return $this->runActionWithTestConfig('import-csv', [$filename]);
    }

    protected function runActionWithTestConfig(string $action, array $config = []): ?int
    {
        $messagesPath = Yii::getAlias('@runtime/messages');
        FileHelper::createDirectory($messagesPath);

        return $this->controller->runAction($action, [
            'sourcePath' => Yii::getAlias('@skeleton'),
            'messagePath' => $messagesPath,
            'languages' => ['en-US', 'de'],
            'ignoreCategories' => ['yii'],
            'sort' => true,
            ...$config,
        ]);
    }

    protected function getTestCsvFilename(array $data): string
    {
        $directory = Yii::getAlias('@runtime/messages');
        FileHelper::createDirectory($directory);

        $filename = "$directory/skeleton.csv";
        $fp = fopen($filename, 'w');

        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $filename;
    }
}

class MessageControllerMock extends MessageController
{
    use StdOutBufferControllerTrait;
}
