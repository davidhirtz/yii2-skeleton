<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Test\Models\UploadRecord;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class UploadCustomAttributeTest extends TestCase
{
    private Upload $upload;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        $this->upload = Upload::getComponent();

        // A run that left files behind would otherwise be counted as this test's own.
        FileHelper::removeDirectory($this->upload->tempPath);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->upload->path);
        FileHelper::removeDirectory($this->upload->tempPath);

        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(UploadRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(UploadRecord::tableName())
            ->execute();
    }

    /**
     * The JSON keeps the filename alone; the directory is the record's, so nothing in it names a column.
     */
    public function testTheTokenBecomesTheFilenameAndTheFileMovesIntoPlace(): void
    {
        $record = $this->createRecord();
        $record->attachment = $this->createToken('notes.txt', 'content');

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));
        self::assertSame('notes.txt', $record->attachment);
        self::assertSame(['attachment' => 'notes.txt'], $this->findStoredValues($record));

        $path = $this->upload->getFilePath($record, 'attachment', 'notes.txt');

        self::assertFileExists($path);
        self::assertSame('content', file_get_contents($path));
        self::assertSame(
            "/attachments/upload_test/$record->id/" . substr(md5('attachment'), 0, 8) . '/notes.txt',
            $this->upload->getUrl($record, 'attachment', 'notes.txt'),
        );
    }

    public function testTheTemporaryFileIsGone(): void
    {
        $record = $this->createRecord();
        $token = $this->createToken('notes.txt');
        $record->attachment = $token;

        self::assertTrue($record->save());
        self::assertNull($this->upload->getTempFile($token));
    }

    /**
     * An attribute holds one file, so the one it held is not left behind.
     */
    public function testReplacingTheFileRemovesThePreviousOne(): void
    {
        $record = $this->createRecord();
        $record->attachment = $this->createToken('first.txt');
        $record->save();

        $previous = $this->upload->getFilePath($record, 'attachment', 'first.txt');

        $record->attachment = $this->createToken('second.txt');

        self::assertSame(1, $record->update(), implode(' ', $record->getErrorSummary(true)));
        self::assertFileDoesNotExist($previous);
        self::assertFileExists($this->upload->getFilePath($record, 'attachment', 'second.txt'));
    }

    public function testClearingTheAttributeRemovesTheFile(): void
    {
        $record = $this->createRecord();
        $record->attachment = $this->createToken('notes.txt');
        $record->save();

        $path = $this->upload->getFilePath($record, 'attachment', 'notes.txt');
        $record->attachment = '';

        self::assertSame(1, $record->update(), implode(' ', $record->getErrorSummary(true)));
        self::assertNull($record->attachment);
        self::assertFileDoesNotExist($path);
        self::assertSame([], $this->findStoredValues($record));
    }

    public function testDeletingTheRecordRemovesItsFiles(): void
    {
        $record = $this->createRecord();
        $record->attachment = $this->createToken('notes.txt');
        $record->track_de = $this->createToken('untertitel.vtt');
        $record->save();

        $paths = [
            $this->upload->getFilePath($record, 'attachment', 'notes.txt'),
            $this->upload->getFilePath($record, 'track_de', 'untertitel.vtt'),
        ];

        self::assertFileExists($paths[0]);
        self::assertFileExists($paths[1]);

        $record->delete();

        self::assertFileDoesNotExist($paths[0]);
        self::assertFileDoesNotExist($paths[1]);
        self::assertDirectoryDoesNotExist(dirname($paths[0], 2));
    }

    /**
     * A translatable definition is one file per language, in a directory of its own.
     */
    public function testEachLanguageKeepsItsOwnFile(): void
    {
        $record = $this->createRecord();
        $record->track = $this->createToken('subtitles.vtt');
        $record->track_de = $this->createToken('untertitel.vtt');

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));

        self::assertSame([
            'track' => 'subtitles.vtt',
            'track_de' => 'untertitel.vtt',
        ], $this->findStoredValues($record));

        self::assertNotSame(
            $this->upload->getAttributePath($record, 'track'),
            $this->upload->getAttributePath($record, 'track_de'),
        );

        self::assertFileExists($this->upload->getFilePath($record, 'track', 'subtitles.vtt'));
        self::assertFileExists($this->upload->getFilePath($record, 'track_de', 'untertitel.vtt'));
    }

    /**
     * The value becomes a path segment, so a value that would reach out of the record's own directory is dropped
     * rather than stored.
     */
    #[DataProvider('outOfSegmentValueDataProvider')]
    public function testAValueThatIsNotASingleFilenameIsDropped(string $value): void
    {
        $record = $this->createRecord();
        $record->attachment = $value;

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));
        self::assertNull($record->attachment);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function outOfSegmentValueDataProvider(): array
    {
        return [
            'parent' => ['../../../config/db.php'],
            'nested' => ['1/notes.txt'],
            'dot' => ['..'],
            'hidden' => ['.htaccess'],
        ];
    }

    public function testTheStoredFilenameSurvivesASaveThatDoesNotTouchIt(): void
    {
        $record = $this->createRecord();
        $record->attachment = $this->createToken('notes.txt');
        $record->save();

        $loaded = UploadRecord::findOne($record->id);
        $loaded->attachment = 'notes.txt';

        self::assertSame(0, $loaded->update());
        self::assertSame('notes.txt', $loaded->attachment);
        self::assertFileExists($this->upload->getFilePath($record, 'attachment', 'notes.txt'));
    }

    /**
     * Sixteen letters and hyphens followed by a hyphen is a plain slug, and it once read as a token whose file was
     * gone — so the record never validated again, and a save would have cut the name down to what followed.
     */
    public function testAHyphenatedFilenameIsNotAToken(): void
    {
        $filename = 'verbos-espanoles-konjugationstabelle.pdf';

        self::assertFalse($this->upload->isToken($filename));
        self::assertSame($filename, $this->upload->getFilename($filename));

        $record = $this->createRecord();
        $record->attachment = $filename;

        self::assertTrue($record->validate(), print_r($record->getErrors(), true));
    }

    public function testATokenWhoseFileIsGoneIsReported(): void
    {
        $record = $this->createRecord();
        $record->attachment = 'abcdefghijklmnop_notes.txt';

        self::assertFalse($record->save());
        self::assertArrayHasKey('attachment', $record->getErrors());
    }

    public function testTheTrailReportsTheFilename(): void
    {
        $record = $this->createRecord();
        $definition = $record->getCustomAttribute('attachment');

        self::assertInstanceOf(UploadCustomAttribute::class, $definition);
        self::assertSame('notes.txt', $definition->formatValue($record, $this->createToken('notes.txt')));
        self::assertSame('notes.txt', $definition->formatValue($record, 'notes.txt'));
        self::assertNull($definition->formatValue($record, null));
    }

    public function testTheAcceptAttributeFollowsTheExtensions(): void
    {
        $record = $this->createRecord();

        foreach (['attachment' => '.txt,.pdf', 'track' => '.vtt'] as $name => $accept) {
            $definition = $record->getCustomAttribute($name);

            self::assertInstanceOf(UploadCustomAttribute::class, $definition);
            self::assertSame($accept, $definition->getAccept());
        }
    }

    /**
     * The file arrives in chunks, so its assembled size is past php.ini's `upload_max_filesize` as often as not.
     */
    public function testAFileLargerThanASingleRequestIsAccepted(): void
    {
        $definition = $this->createRecord()->getCustomAttribute('attachment');
        self::assertInstanceOf(UploadCustomAttribute::class, $definition);

        $size = StringHelper::convertIniSizeToBytes((string)ini_get('upload_max_filesize')) + 1;
        self::assertLessThan($definition->getMaxSize(), $size);

        $upload = new UploadedFile([
            'name' => 'notes.pdf',
            'tempName' => __FILE__,
            'type' => 'application/pdf',
            'size' => $size,
            'error' => UPLOAD_ERR_OK,
        ]);

        self::assertNull($definition->validateUploadedFile($upload));

        $upload->size = $definition->getMaxSize() + 1;
        self::assertNotNull($definition->validateUploadedFile($upload));
    }

    /**
     * A duplicate is inserted with the source's filename, which without a copy of its own would point into the
     * source's directory — and go with it the moment the source is deleted.
     */
    public function testADuplicateGetsItsOwnCopyOfTheFile(): void
    {
        $record = $this->createRecord();
        $record->attachment = $this->createToken('notes.txt', 'content');
        $record->save();

        $duplicate = DuplicateActiveRecord::create(['model' => $record]);

        self::assertInstanceOf(UploadRecord::class, $duplicate);
        self::assertNotSame($record->id, $duplicate->id);
        self::assertSame('notes.txt', $duplicate->attachment);

        $path = $this->upload->getFilePath($duplicate, 'attachment', 'notes.txt');

        self::assertFileExists($path);
        self::assertSame('content', file_get_contents($path));

        $record->delete();

        self::assertFileExists($path);
    }

    /**
     * The directory is public and the web server serves it, so what may land there is an allow list — a definition
     * without one would let an editor store a `.php` file.
     */
    public function testADefinitionWithoutExtensionsIsRefused(): void
    {
        $record = $this->createRecord();
        $record->setCustomAttributes([UploadCustomAttribute::make('attachment')]);

        $this->expectException(InvalidConfigException::class);

        $record->getCustomAttribute('attachment')?->createField($record);
    }

    private function createRecord(int $type = UploadRecord::TYPE_DEFAULT): UploadRecord
    {
        $record = UploadRecord::create();
        $record->type = $type;

        return $record;
    }

    /**
     * Stands in for what the upload action parks under a token, so the model side is tested without the request.
     */
    private function createToken(string $filename, string $content = 'x'): string
    {
        $token = 'abcdefghijklmnop_' . $filename;

        FileHelper::createDirectory($this->upload->tempPath);
        file_put_contents($this->upload->tempPath . $token, $content);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function findStoredValues(UploadRecord $record): array
    {
        $values = UploadRecord::find()
            ->select(['custom_attributes'])
            ->where(['id' => $record->id])
            ->scalar();

        return $values ? (array)json_decode((string)$values, true, flags: JSON_THROW_ON_ERROR) : [];
    }
}
