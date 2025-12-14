<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Stringable;
use yii\base\InvalidArgumentException;

readonly class Dsn implements Stringable
{
    public function __construct(
        public string $driver,
        public string $host,
        public string $database,
        public ?int $port = null,
        public ?string $charset = null,
    ) {
    }

    public function __toString(): string
    {
        $dsn = "$this->driver:host=$this->host;dbname=$this->database";

        if ($this->port) {
            $dsn .= ";port=$this->port";
        }

        if ($this->charset) {
            $dsn .= ";charset=$this->charset";
        }

        return $dsn;
    }

    public static function fromString(string $dsn): self
    {
        if (($pos = strpos($dsn, ':')) === false) {
            throw new InvalidArgumentException("Invalid DSN: $dsn");
        }

        $driver = strtolower(substr($dsn, 0, $pos));

        $dsn = substr($dsn, $pos + 1);

        foreach (explode(';', $dsn) as $part) {
            [$key, $value] = array_pad(explode('=', $part), 2, '');
            $parts[trim((string) $key)] = trim((string) $value);
        }

        if (empty($parts['host'])) {
            throw new InvalidArgumentException("Invalid DSN, missing host: $dsn");
        }

        if (empty($parts['dbname'])) {
            throw new InvalidArgumentException("Invalid DSN, missing dbname: $dsn");
        }

        return new self(
            driver: $driver,
            host: $parts['host'],
            database: $parts['dbname'],
            port: isset($parts['port']) ? (int)$parts['port'] : null,
            charset: $parts['charset'] ?? null,
        );
    }
}
