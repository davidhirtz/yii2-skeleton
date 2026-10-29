<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\base\Event;

class EventHelper
{
    /**
     * A sender-less `Event::trigger(Foo::class, …)` reaches no handler registered here; `$eventClass` is checked as
     * well as narrowed, since nothing maps an event name to its class.
     *
     * @template T of object
     * @template TEvent of Event
     * @phpstan-param class-string<T> $class
     * @param callable(T, TEvent): mixed $handler
     * @phpstan-param class-string<TEvent> $eventClass
     */
    public static function on(
        string $class,
        string $name,
        callable $handler,
        string $eventClass = Event::class,
        mixed $data = null,
        bool $append = true
    ): void {
        Event::on($class, $name, static function (Event $event) use ($class, $eventClass, $handler): void {
            if ($event->sender instanceof $class && $event instanceof $eventClass) {
                $handler($event->sender, $event);
            }
        }, $data, $append);
    }
}
