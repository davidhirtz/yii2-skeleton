<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\web\Application;
use Yii;

use yii\db\Transaction;


use yii\test\FixtureTrait;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use FixtureTrait;

    protected bool $isTransactional = true;
    private Transaction $transaction;

    protected bool $cleanup = true;

    protected array $config = [];

    protected function setUp(): void
    {
        $this->createApplication();
        $this->loadFixtures();

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

    protected function tearDown(): void
    {
        Yii::$app->getErrorHandler()->unregister();
        parent::tearDown();
    }
}
