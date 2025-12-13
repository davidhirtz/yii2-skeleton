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
use yii\log\Logger;
use yii\test\FixtureTrait;
use yii\web\UploadedFile;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    protected string $applicationClass = Application::class;
    protected array $config;
    protected TestMailer $mailer;

    private Transaction $transaction;
    protected string $webroot = '@runtime/web';

    private array $originalServerParams;

    #[Override]
    protected function setUp(): void
    {
        $this->config ??= require(__DIR__ . '/../../tests/config.php');

        $this->originalServerParams = $_SERVER;

        $_SERVER = [
            ...$_SERVER,
            ...$this->getServerParams()
        ];

        $this->setUpApplication();
        $this->initFixtures();

        $this->transaction = Yii::$app->getDb()->beginTransaction();

        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        if (Yii::$app->get('session', false)) {
            Yii::$app->getSession()->close();
        }

        $this->transaction->rollBack();
        $this->unloadFixtures();

        $this->tearDownApplication();

        $_SERVER = $this->originalServerParams;

        parent::tearDown();
    }

    protected function getServerParams(): array
    {
        return [
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => __DIR__ . '/../../runtime/web/index.php',
            'SCRIPT_NAME' => '/index.php',
            'HTTP_HOST' => 'www.example.com',
            'HTTPS' => 'on',
         ];
    }

    protected function setUpApplication(): void
    {
        $config = [
            'basePath' => getcwd(),
            'class' => $this->applicationClass,
            'components' => [
                'mailer' => [
                    'class' => TestMailer::class,
                ],
            ],
        ];

        Yii::createObject(ArrayHelper::merge($config, $this->config));
        Yii::setAlias('@webroot', $this->webroot);

        Yii::$app->params = [
            'cookieValidationKey' => 'test',
            'email' => 'test@example.com',
        ];

        FileHelper::createDirectory("$this->webroot/assets");

        $this->mailer = Yii::$app->get('mailer');

        Yii::setLogger($this->getLogger());
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
