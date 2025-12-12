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
use yii\db\Transaction;
use yii\di\Container;
use yii\log\Logger;
use yii\test\FixtureTrait;
use yii\web\UploadedFile;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    protected array $config;
    protected TestMailer $mailer;

    private Transaction $transaction;
    private string $webroot = '@runtime/web';

    #[Override]
    protected function setUp(): void
    {
        $this->config ??= require(__DIR__ . '/../../tests/config.php');

        $_SERVER = [
            ...$_SERVER,
            ...$this->getServerParams(),
        ];

        $this->setUpApplication();
        $this->initFixtures();

        $this->transaction = Yii::$app->getDb()->beginTransaction();

        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->transaction->rollBack();
        $this->unloadFixtures();

        $this->tearDownApplication();

        parent::tearDown();
    }

    protected function getServerParams(): array
    {
        return [
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => __DIR__ . '/../../runtime/web/index.php',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'www.example.com',
            'HTTPS' => 'on',
         ];
    }

    protected function setUpApplication(): void
    {
        $config = [
            'basePath' => getcwd(),
            'class' => Application::class,
            'components' => [
                'db' => [
                    'dsn' => getenv('MYSQL_DSN'),
                    'username' => getenv('MYSQL_USER') ?: 'root',
                    'password' => getenv('MYSQL_PASSWORD') ?: '',
                    'charset' => 'utf8',
                ],
                'mailer' => [
                    'class' => TestMailer::class,
                ],
            ],
            'params' => [
                'cookieValidationKey' => 'test',
            ],
        ];

        Yii::createObject(ArrayHelper::merge($config, $this->config));
        Yii::setAlias('@webroot', $this->webroot);

        FileHelper::createDirectory("$this->webroot/assets");

        $this->mailer = Yii::$app->get('mailer');

        Yii::setLogger($this->getLogger());
    }

    protected function tearDownApplication(): void
    {
        $this->mailer->reset();

        Yii::$app->getErrorHandler()->unregister();
        Yii::$app->getCache()->flush();
        Yii::$app->getSession()->close();
        Yii::$app->getDb()->close();

        FileHelper::removeDirectory($this->webroot);
        Html::reset();
        UploadedFile::reset();
        Event::offAll();

        Yii::$app = null;
        Yii::$container = new Container();
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
