<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\db;

use Codeception\Test\Unit;
use Hirtz\Skeleton\Db\Dsn;
use yii\base\InvalidArgumentException;

class DsnTest extends Unit
{
    public function testToString(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'test');
        self::assertSame('mysql:host=localhost;dbname=test', (string)$dsn);

        $dsn = new Dsn('pgsql', '127.0.0.1', 'db', 5432);
        self::assertSame('pgsql:host=127.0.0.1;dbname=db;port=5432', (string)$dsn);

        $dsn = new Dsn('mysql', 'db', 'db', null, 'utf8mb4');
        self::assertSame('mysql:host=db;dbname=db;charset=utf8mb4', (string)$dsn);

        $dsn = new Dsn('mysql', 'db', 'db', 3306, 'utf8mb4');
        self::assertSame('mysql:host=db;dbname=db;port=3306;charset=utf8mb4', (string)$dsn);
    }

    public function testFromString(): void
    {
        $dsn = Dsn::fromString('mysql:host=localhost;dbname=test');
        self::assertSame('mysql', $dsn->driver);
        self::assertSame('localhost', $dsn->host);
        self::assertSame('test', $dsn->database);
        self::assertNull($dsn->port);
        self::assertNull($dsn->charset);

        $dsn = Dsn::fromString('pgsql:host=127.0.0.1;dbname=db;port=5432');
        self::assertSame('pgsql', $dsn->driver);
        self::assertSame('127.0.0.1', $dsn->host);
        self::assertSame('db', $dsn->database);
        self::assertSame(5432, $dsn->port);
        self::assertNull($dsn->charset);

        $dsn = Dsn::fromString('mysql:host=db;dbname=db;charset=utf8mb4');
        self::assertSame('mysql', $dsn->driver);
        self::assertSame('db', $dsn->host);
        self::assertSame('db', $dsn->database);
        self::assertNull($dsn->port);
        self::assertSame('utf8mb4', $dsn->charset);

        $dsn = Dsn::fromString('mysql:host=db;dbname=db;port=3306;charset=utf8mb4');
        self::assertSame('mysql', $dsn->driver);
        self::assertSame('db', $dsn->host);
        self::assertSame('db', $dsn->database);
        self::assertSame(3306, $dsn->port);
        self::assertSame('utf8mb4', $dsn->charset);
    }

    public function testFromStringInvalid(): void
    {
        self::expectException(InvalidArgumentException::class);
        Dsn::fromString('invalid');

        self::expectException(InvalidArgumentException::class);
        Dsn::fromString('mysql:dbname=test');

        self::expectException(InvalidArgumentException::class);
        Dsn::fromString('mysql:host=localhost');
    }
}
