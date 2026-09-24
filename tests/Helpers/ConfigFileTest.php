<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\ConfigFile;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class ConfigFileTest extends TestCase
{
    private string $folder;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->folder = Yii::getAlias('@runtime/config-file');
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->folder);
        parent::tearDown();
    }

    public function testWriteCreatesTheDirectoryAndReturnsTheConfig(): void
    {
        $file = "$this->folder/nested/params.php";

        $config = [
            'string' => 'this is a string',
            'integer' => 123,
            'float' => 123.456,
            'boolean' => false,
            'null' => null,
            'list' => ['b', 'a'],
        ];

        self::assertTrue(ConfigFile::write($file, $config));
        self::assertFileExists($file);
        self::assertEquals($config, require $file);
    }

    public function testWriteSortsTheTopLevelKeysAlphabetically(): void
    {
        $file = "$this->folder/params.php";

        ConfigFile::write($file, [
            'sentryDsn' => 'dsn',
            'Zeta' => true,
            'adminAlias' => 'admin',
            'cookieValidationKey' => 'key',
            'mailer' => ['to' => 'b', 'from' => 'a'],
        ]);

        $config = require $file;

        self::assertSame(['adminAlias', 'cookieValidationKey', 'mailer', 'sentryDsn', 'Zeta'], array_keys($config));
        self::assertSame(['to' => 'b', 'from' => 'a'], $config['mailer']);
    }

    public function testAnUnchangedConfigIsNotRewritten(): void
    {
        $file = "$this->folder/params.php";
        ConfigFile::write($file, ['key' => 'value']);

        $contents = (string)preg_replace('/@version .*/', '@version 2000-01-01T00:00:00+00:00', (string)file_get_contents($file));
        file_put_contents($file, $contents);

        self::assertTrue(ConfigFile::write($file, ['key' => 'value']));
        self::assertSame($contents, file_get_contents($file));

        self::assertTrue(ConfigFile::write($file, ['key' => 'changed']));
        self::assertStringNotContainsString('2000-01-01', (string)file_get_contents($file));
        self::assertSame(['key' => 'changed'], require $file);
    }

    public function testWriteLeavesNoTemporaryFile(): void
    {
        ConfigFile::write("$this->folder/params.php", ['key' => 'value']);
        self::assertSame(['params.php'], array_values(array_diff((array)scandir($this->folder), ['.', '..'])));
    }

    public function testRenderWritesThePhpdocAndVersion(): void
    {
        $contents = ConfigFile::render(['key' => 'value'], "First line\nSecond line");

        self::assertStringStartsWith("<?php\n\ndeclare(strict_types=1);\n\n/**\n * First line\n * Second line\n *\n * @version ", $contents);
        self::assertStringEndsWith("return [\n    'key' => 'value',\n];", $contents);

        $contents = ConfigFile::render([], ['Only line']);
        self::assertStringContainsString("/**\n * Only line\n *\n * @version ", $contents);

        $contents = ConfigFile::render();
        self::assertStringContainsString("/**\n * @version ", $contents);
        self::assertStringEndsWith('return [];', $contents);
    }
}
