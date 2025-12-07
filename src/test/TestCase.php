<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\test;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\web\Application;
use Override;
use Yii;
use yii\db\Transaction;
use yii\test\FixtureTrait;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    private Transaction $transaction;
    protected bool $isTransactional = true;

    protected bool $cleanup = true;

    protected array $config = [];

    #[Override]
    protected function setUp(): void
    {
        $this->createApplication();
        $this->initFixtures();

        $this->transaction = Yii::$app->getDb()->beginTransaction();

        parent::setUp();
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
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->transaction->rollBack();
        $this->unloadFixtures();

        Yii::$app->getErrorHandler()->unregister();

        parent::tearDown();
    }
}
