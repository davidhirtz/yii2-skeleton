<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\StreamUploadedFile;
use Override;
use Yii;

/**
 * Only 127.0.0.1 is treated as public, so the fixture server is reachable while the private address check stays
 * armed for everything the test points it at — a redirect to a link-local address included.
 */
class LoopbackStreamUploadedFile extends StreamUploadedFile
{
    #[Override]
    protected function isPublicIp(string $ip): bool
    {
        return $ip === '127.0.0.1' || parent::isPublicIp($ip);
    }
}

class StreamUploadedFileTest extends TestCase
{
    protected const string UNENCODED_FILENAME = 'Ümlauts & Spaces.md';
    protected const string README_CONTENT = "# Skeleton\n";

    /**
     * @var resource|null
     */
    private static $server = null;
    private static string $serverPath = '';
    private static string $baseUrl = '';

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::startWebServer();
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        self::stopWebServer();
        parent::tearDownAfterClass();
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory(Upload::getComponent()->tempPath);
        parent::tearDown();
    }

    public function testSaveValidFile(): void
    {
        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('README.md')]);

        self::assertEquals('README.md', $upload->name);
        self::assertEquals('text/plain', $upload->type);
        self::assertEquals(UPLOAD_ERR_OK, $upload->error);
        self::assertEquals(strlen(self::README_CONTENT), $upload->size);

        self::assertTrue($upload->saveAs('@runtime/README.md'));

        $path = Yii::getAlias('@runtime/README.md');
        self::assertFileExists($path);
        @unlink($path);
    }

    public function testErrorsForInvalidFiles(): void
    {
        $upload = $this->getStreamUploadedFile();
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        $upload = $this->getStreamUploadedFile(['url' => 'invalid-file']);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
        self::assertFalse($upload->saveAs('@runtime/invalid-file'));

        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('missing')]);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        $upload = $this->getStreamUploadedFile([
            'url' => self::getUrl('README.md'),
            'allowedExtensions' => ['jpg', 'png'],
        ]);

        self::assertEquals(UPLOAD_ERR_EXTENSION, $upload->error);
    }

    public function testUploadFromUnencodedUrl(): void
    {
        $upload = $this->getStreamUploadedFile([
            'url' => self::$baseUrl . self::UNENCODED_FILENAME,
            'allowedExtensions' => ['md', 'txt'],
        ]);

        self::assertEquals(UPLOAD_ERR_OK, $upload->error);
    }

    public function testInvalidTempDirectory(): void
    {
        $upload = $this->getStreamUploadedFile([
            'url' => self::getUrl('README.md'),
            'tempName' => '/invalid/temp/path',
        ]);

        self::assertEquals(UPLOAD_ERR_CANT_WRITE, $upload->error);
    }

    public function testFollowsRedirect(): void
    {
        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('redirect')]);

        self::assertEquals(UPLOAD_ERR_OK, $upload->error);
        self::assertStringEqualsFile($upload->tempName, self::README_CONTENT);
    }

    public function testRefusesEndlessRedirect(): void
    {
        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('redirect-loop')]);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
    }

    public function testRefusesRedirectToPrivateAddress(): void
    {
        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('redirect-private')]);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
    }

    public function testRefusesPrivateAddress(): void
    {
        $upload = Yii::$container->get(StreamUploadedFile::class, [], ['url' => self::getUrl('README.md')]);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        Upload::getComponent()->allowPrivateStreamUploadHosts = true;

        $upload = Yii::$container->get(StreamUploadedFile::class, [], ['url' => self::getUrl('README.md')]);
        self::assertEquals(UPLOAD_ERR_OK, $upload->error);
    }

    public function testRefusesNonHttpScheme(): void
    {
        $upload = $this->getStreamUploadedFile(['url' => 'file://' . self::$serverPath . '/docroot/README.md']);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        $upload = $this->getStreamUploadedFile(['url' => self::$serverPath . '/docroot/README.md']);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
    }

    public function testRefusesDisabledStreamUploads(): void
    {
        Upload::getComponent()->enableStreamUploads = false;

        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('README.md')]);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
    }

    public function testRefusesSlowResponse(): void
    {
        Upload::getComponent()->streamUploadTimeout = 1;

        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('slow')]);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
    }

    public function testRefusesResponseAboveSizeLimit(): void
    {
        Upload::getComponent()->maxStreamUploadSize = 1024;

        $upload = $this->getStreamUploadedFile(['url' => self::getUrl('large')]);
        self::assertEquals(UPLOAD_ERR_INI_SIZE, $upload->error);
        self::assertFileDoesNotExist($upload->tempName);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function getStreamUploadedFile(array $config = []): StreamUploadedFile
    {
        return Yii::$container->get(LoopbackStreamUploadedFile::class, [], $config);
    }

    protected static function getUrl(string $path): string
    {
        return self::$baseUrl . $path;
    }

    /**
     * The fixture the download tests point at: a built-in server on a port of its own, so a parallel worker has
     * one too and nothing here reaches the network.
     */
    protected static function startWebServer(): void
    {
        self::$serverPath = sys_get_temp_dir() . '/' . uniqid('stream-uploaded-file-', true);
        $docRoot = self::$serverPath . '/docroot';

        FileHelper::createDirectory($docRoot);
        file_put_contents("$docRoot/README.md", self::README_CONTENT);
        file_put_contents("$docRoot/" . self::UNENCODED_FILENAME, "# Ümlauts & Spaces\n");

        $router = self::$serverPath . '/router.php';
        file_put_contents($router, self::getRouterScript());

        $port = self::findFreePort();

        $server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $docRoot, $router],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );

        self::assertNotFalse($server, 'The fixture web server could not be started.');

        self::$server = $server;
        self::$baseUrl = "http://127.0.0.1:$port/";

        self::waitForWebServer($port);
    }

    protected static function stopWebServer(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }

        FileHelper::removeDirectory(self::$serverPath);
    }

    protected static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        self::assertNotFalse($socket, "The fixture web server found no free port: $errorMessage");

        $name = (string)stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr($name, (int)strrpos($name, ':') + 1);
    }

    protected static function waitForWebServer(int $port): void
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $client = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.1);

            if ($client) {
                fclose($client);
                return;
            }

            usleep(50000);
        }

        self::fail("The fixture web server did not come up on port $port.");
    }

    protected static function getRouterScript(): string
    {
        return <<<'PHP'
            <?php

            $path = (string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);

            $redirects = [
                '/redirect' => '/README.md',
                '/redirect-loop' => '/redirect-loop',
                '/redirect-private' => 'http://169.254.169.254/latest/meta-data/',
            ];

            if (isset($redirects[$path])) {
                header('Location: ' . $redirects[$path], true, 302);
                return true;
            }

            if ($path === '/slow') {
                usleep(1500000);
                header('Content-Type: text/plain');
                echo 'slow';
                return true;
            }

            if ($path === '/large') {
                header('Content-Type: text/plain');
                echo str_repeat('x', 65536);
                return true;
            }

            if ($path === '/missing') {
                http_response_code(404);
                return true;
            }

            return false;
            PHP;
    }
}
