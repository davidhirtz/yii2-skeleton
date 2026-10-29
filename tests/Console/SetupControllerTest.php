<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\SetupController;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Setup\Seeder;
use Hirtz\Skeleton\Setup\Setup;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\console\ExitCode;

class SetupControllerTest extends TestCase
{
    use UserFixtureTrait;

    protected string $applicationClass = Application::class;

    private string $file;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Setup::getComponent()->seeders = ['redirects' => SetupControllerTestSeeder::class];
        $this->file = Yii::getAlias('@runtime/' . uniqid('setup-') . '.php');
    }

    #[Override]
    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function testTheInsertedAndFailedRecordsAreReported(): void
    {
        $this->writeSetupFile("return ['redirects' => ['/setup-old' => '/setup-new', '' => '/setup-none']];");

        $controller = $this->createController();
        $result = $controller->runAction('index', ['file' => $this->file]);
        $output = $controller->flushStdOutBuffer();

        self::assertSame(ExitCode::DATAERR, $result);
        self::assertStringContainsString('Inserted ' . Redirect::class, $output);
        self::assertStringContainsString('Failed to insert ' . Redirect::class, $output);
        self::assertNotNull(Redirect::findOne(['request_uri' => 'setup-old']));
    }

    public function testAKeyNoSeederReadsIsRefused(): void
    {
        $this->writeSetupFile("return ['redirects' => ['/setup-old' => '/setup-new'], 'unknown' => []];");

        $controller = $this->createController();

        self::assertSame(ExitCode::DATAERR, $controller->runAction('index', ['file' => $this->file]));
        self::assertStringContainsString('No seeder reads unknown.', $controller->flushStdOutBuffer());
        self::assertNull(Redirect::findOne(['request_uri' => 'setup-old']));
    }

    public function testTheFileInTheRootConfigIsTheDefault(): void
    {
        $root = Yii::getAlias('@runtime/' . uniqid('setup-root-'));
        $this->file = "$root/config/setup.php";
        mkdir(dirname($this->file), 0777, true);

        Yii::setAlias('@root', $root);
        $this->writeSetupFile("return ['redirects' => ['/setup-root' => '/setup-new']];");

        try {
            self::assertSame(ExitCode::OK, $this->createController()->runAction('index'));
            self::assertNotNull(Redirect::findOne(['request_uri' => 'setup-root']));
        } finally {
            @unlink($this->file);
            @rmdir(dirname($this->file));
            @rmdir($root);
        }
    }

    public function testWithoutAFileEverySeederRuns(): void
    {
        SetupControllerTestSeeder::$ran = false;
        Yii::setAlias('@root', Yii::getAlias('@runtime/' . uniqid('setup-root-')));

        self::assertSame(ExitCode::OK, $this->createController()->runAction('index'));
        self::assertTrue(SetupControllerTestSeeder::$ran);
    }

    public function testAMissingFileIsRefused(): void
    {
        self::assertSame(ExitCode::NOINPUT, $this->createController()->runAction('index', ['file' => $this->file]));
    }

    private function createController(): SetupControllerMock
    {
        return new SetupControllerMock('setup', Application::current());
    }

    private function writeSetupFile(string $code): void
    {
        file_put_contents($this->file, "<?php\n\n$code\n");
    }
}

class SetupControllerTestSeeder extends Seeder
{
    public static bool $ran = false;

    #[Override]
    public function run(?array $value): void
    {
        self::$ran = true;

        foreach ($value ?? [] as $requestUri => $url) {
            $redirect = Redirect::create();
            $redirect->request_uri = (string)$requestUri;
            $redirect->url = (string)$url;

            $this->insert($redirect);
        }
    }
}

class SetupControllerMock extends SetupController
{
    use StdOutBufferControllerTrait;
}
