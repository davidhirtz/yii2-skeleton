<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\Response;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\caching\ArrayCache;
use yii\db\Transaction;
use yii\di\Container;
use yii\log\Logger;
use yii\test\FixtureTrait;
use yii\web\Session;
use yii\web\UploadedFile;
use yii\web\View;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    protected string $applicationClass = Application::class;
    /**
     * @var array<string, mixed>
     */
    protected array $config;
    protected TestMailer $mailer;
    protected TestLogger $logger;

    private Transaction $transaction;
    protected string $webroot = '@runtime/web';

    /**
     * @var array<string, mixed>
     */
    private array $originalServerParams;
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $originalRequestParams;
    private int $originalErrorReportingLevel;

    private static ?ArrayCache $schemaCache = null;

    #[Override]
    protected function setUp(): void
    {
        $this->config ??= require(__DIR__ . '/../../config/test.php');

        $this->originalServerParams = $_SERVER;
        $this->originalRequestParams = [$_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES];

        // PHPUnit lowers `error_reporting()` to the levels it cannot handle, because its own handler is called
        // whatever the mask says. The application's is not: `yii\base\ErrorHandler::handleError()` answers `false`
        // for a masked level and PHP then drops it — and since the application's handler *replaces* PHPUnit's,
        // every deprecation, warning and notice was silently green here while being a 500 in a browser (#129).
        $this->originalErrorReportingLevel = error_reporting(E_ALL);

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
            Application::current()->getSession()->close();
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

        error_reporting($this->originalErrorReportingLevel);

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

    /**
     * @return array<string, mixed>
     */
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
            // Pinned: `date_default_timezone_set()` is process wide and outlives the application, and Yii only
            // calls it when `date.timezone` is absent from the php.ini — so one test switching zones would
            // silently move every timestamp of every test after it in the same worker.
            'timeZone' => 'UTC',
            'components' => [
                // a fresh one per test: a file cache would carry rate limit counters into the next test and run
                'cache' => [
                    'class' => ArrayCache::class,
                ],
                'db' => [
                    'schemaCache' => self::$schemaCache ??= new ArrayCache(),
                ],
                'mailer' => [
                    'class' => TestMailer::class,
                    'transport' => 'null://null',
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

        /** @var array{class: class-string<Application<User>>, ...} $config */
        $config = ArrayHelper::merge($config, $this->config);
        Yii::createObject($config);
        Yii::setAlias('@webroot', $this->webroot);

        FileHelper::createDirectory("$this->webroot/assets");

        $mailer = Yii::$app->getMailer();

        if (!$mailer instanceof TestMailer) {
            throw new InvalidConfigException('The test application must be configured with a ' . TestMailer::class . '.');
        }

        $this->mailer = $mailer;
        $this->logger = $this->getLogger();

        Yii::setLogger($this->logger);
    }

    /**
     * `Yii::$app` is the `Console|Web` union, which is what keeps a console command reaching for one of these a
     * static analysis error. A test case that overrides `$applicationClass` with the console application — the
     * two under `tests/Console` — calls none of them.
     */
    protected function getWebUser(): WebUser
    {
        return Application::current()->getUser();
    }

    protected function getWebSession(): Session
    {
        return Application::current()->getSession();
    }

    protected function getWebRequest(): Request
    {
        return Application::current()->getRequest();
    }

    protected function getWebResponse(): Response
    {
        return Application::current()->getResponse();
    }

    /**
     * A second application on the connection of the first, so the test keeps its open transaction. This is how a
     * test pins what a request must not inherit from the one before it — a static a `Bootstrap` has to clear.
     */
    protected function reloadApplication(): void
    {
        $db = Yii::$app->getDb();

        Yii::$app->getErrorHandler()->unregister();
        Event::offAll();

        $this->setUpApplication();

        // before anything asks for it, or the second application opens a connection of its own and the rows the
        // test wrote in its transaction are invisible to it
        Yii::$app->set('db', $db);
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
     * Reads and writes alike, or only the statements whose SQL matches `$pattern`.
     */
    protected function countQueries(callable $callback, ?string $pattern = null): int
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
                && ($pattern === null || preg_match($pattern, (string)$message[0]) === 1)
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
