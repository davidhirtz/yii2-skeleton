<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Yii;
use yii\db\Connection as BaseConnection;

final class DatabaseComponents
{
    /**
     * @var array<string, class-string<BaseConnection>>|null
     */
    private static ?array $components = null;

    /**
     * @return array<string, class-string<BaseConnection>>
     */
    public static function getAll(): array
    {
        return self::$components ??= self::findConnections();
    }

    public static function reset(): void
    {
        self::$components = null;
    }

    /**
     * @return array<string, class-string<BaseConnection>>
     */
    private static function findConnections(): array
    {
        $connections = [];

        foreach (Yii::$app->getComponents() as $name => $component) {
            if ($component instanceof BaseConnection) {
                $connections[$name] = $component::class;
            } elseif (is_array($component) && isset($component['class']) && self::isConnectionClass($component['class'])) {
                $connections[$name] = $component['class'];
            } elseif (is_string($component) && self::isConnectionClass($component)) {
                $connections[$name] = $component;
            }
        }

        ksort($connections);
        return $connections;
    }

    private static function isConnectionClass(string $className): bool
    {
        return is_a($className, BaseConnection::class, true);
    }
}
