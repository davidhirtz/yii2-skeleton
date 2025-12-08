<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Web\Application;
use Override;
use Yii;
use yii\base\Event;
use yii\db\Transaction;
use yii\log\Logger;
use yii\test\FixtureTrait;
use yii\web\UploadedFile;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    protected array $config = [];
    protected TestMailer $mailer;

    private Transaction $transaction;

    #[Override]
    protected function setUp(): void
    {
        $this->createApplication();
        $this->initFixtures();

        $this->transaction = Yii::$app->getDb()->beginTransaction();


        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->transaction->rollBack();
        $this->unloadFixtures();

        Yii::$app->getErrorHandler()->unregister();
        $this->mailer->reset();

        UploadedFile::reset();
        Event::offAll();

        parent::tearDown();
    }

    protected function createApplication(): void
    {
        $config = [
            'class' => Application::class,
            'components' => [
                'db' => [
                    'dsn' => getenv('MYSQL_DSN'),
                    'username' => getenv('MYSQL_USER') ?: 'root',
                    'password' => getenv('MYSQL_PASSWORD') ?: '',
                    'charset' => 'utf8',
                ],
            ],
            'params' => [
                'cookieValidationKey' => 'test',
            ],
        ];

        Yii::createObject(ArrayHelper::merge($config, $this->config));

        $this->mailer = Yii::createObject(TestMailer::class);
        Yii::$app->set('mailer', $this->mailer);

        Yii::setLogger($this->getLogger());
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
