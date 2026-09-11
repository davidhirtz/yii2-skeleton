<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Web\Application;
use Override;
use Yii;
use yii\base\Event;
use yii\caching\ArrayCache;
use yii\db\Transaction;
use yii\di\Container;
use yii\log\Logger;
use yii\test\FixtureTrait;
use yii\web\UploadedFile;
use yii\web\View;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    protected string $applicationClass = Application::class;
    protected array $config;
    protected TestMailer $mailer;
    protected TestLogger $logger;

    private Transaction $transaction;
    protected string $webroot = '@runtime/web';

    private array $originalServerParams;
    private array $originalRequestParams;

    private static ?ArrayCache $schemaCache = null;

    #[Override]
    protected function setUp(): void
    {
        $this->config ??= require(__DIR__ . '/../../config/test.php');

        $this->originalServerParams = $_SERVER;
        $this->originalRequestParams = [$_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES];

        $_SERVER = [
            ...$_SERVER,
            ...$this->getServerParams()
        ];

        // a functional test's request leaves them behind, and the next test's URLs would carry its query string
        $_GET = $_POST = $_COOKIE = $_REQUEST = $_FILES = [];

        $this->setUpApplication();
        $this->setUpSchema();

        $this->transaction = Yii::$app->getDb()->beginTransaction();
        $this->initFixtures();

        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        if (Yii::$app->get('session', false)) {
            Yii::$app->getSession()->close();
        }

        if ($this->transaction->getIsActive() && $this->transaction->db->pdo?->inTransaction()) {
            $this->transaction->rollBack();
        } else {
            // DDL run mid-test committed the transaction; `inTransaction()` reports the server's state, not PDO's
            $this->unloadFixtures();
        }

        $this->tearDownSchema();
        $this->tearDownApplication();

        $_SERVER = $this->originalServerParams;
        [$_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES] = $this->originalRequestParams;

        parent::tearDown();
    }

    /**
     * DDL belongs here, before the transaction: in `setUp()` it would commit the fixtures and the test's own rows.
     */
    protected function setUpSchema(): void
    {
    }

    protected function tearDownSchema(): void
    {
    }

    protected function getServerParams(): array
    {
        return [
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => __DIR__ . '/../../runtime/web/index.php',
            'SCRIPT_NAME' => '/index.php',
            'HTTP_HOST' => 'www.test.localhost',
            'HTTPS' => 'on',
        ];
    }

    protected function setUpApplication(): void
    {
        $config = [
            'basePath' => getcwd(),
            'class' => $this->applicationClass,
            'components' => [
                'db' => [
                    'schemaCache' => self::$schemaCache ??= new ArrayCache(),
                ],
                'mailer' => [
                    'class' => TestMailer::class,
                ],
            ],
            'params' => [
                'cookieValidationKey' => 'test',
                'email' => 'test@test.localhost',
            ],
        ];

        // paratest numbers its workers; each one gets its own runtime directory next to its own database
        if ($token = getenv('TEST_TOKEN')) {
            $config['runtimePath'] = getcwd() . "/runtime/paratest/$token";
        }

        Yii::createObject(ArrayHelper::merge($config, $this->config));
        Yii::setAlias('@webroot', $this->webroot);

        FileHelper::createDirectory("$this->webroot/assets");

        $this->mailer = Yii::$app->get('mailer');
        $this->logger = $this->getLogger();

        Yii::setLogger($this->logger);
    }

    protected function tearDownApplication(): void
    {
        $this->mailer->reset();

        Yii::$app->getErrorHandler()->unregister();
        Yii::$app->getCache()->flush();
        Yii::$app->getDb()->close();

        FileHelper::removeDirectory($this->webroot);

        Html::reset();
        UploadedFile::reset();
        Event::offAll();

        // A request writes to the container (the cookie domain, for one), and the next test must not inherit it.
        Yii::$container = new Container();
    }

    /**
     * Reads and writes alike.
     */
    protected function countQueries(callable $callback): int
    {
        $this->logger->messages = [];
        $this->logger->isRecording = true;

        try {
            $callback();
        } finally {
            $this->logger->isRecording = false;
        }

        $messages = array_filter(
            $this->logger->messages,
            fn (array $message): bool => $message[1] === Logger::LEVEL_PROFILE_BEGIN
                && in_array($message[2], ['yii\db\Command::query', 'yii\db\Command::execute'], true)
        );

        $this->logger->messages = [];

        return count($messages);
    }

    private function getLogger(): TestLogger
    {
        if (Yii::$container->hasSingleton(Logger::class)) {
            return Yii::$container->get(TestLogger::class);
        }

        Yii::$container->setSingleton(Logger::class, TestLogger::class);
        return $this->getLogger();
    }
}
