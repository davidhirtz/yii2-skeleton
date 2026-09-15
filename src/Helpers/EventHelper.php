<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\base\Event;

class EventHelper
{
    /**
     * Yii's `Event::on()` cannot be typed, since it also takes wildcard patterns — so every handler starts with an
     * inline `@var` on `$event->sender`. This narrows the sender for a single class string and hands it to the
     * handler; the event itself is the optional second argument. An event triggered without an instance
     * (`Event::trigger(Foo::class, …)`) carries no sender and reaches no handler registered here.
     *
     * A handler that reads more than the base event names its class in `$eventClass`, which is checked here as
     * well as narrowed — nothing maps an event name to its class, so the two could otherwise disagree silently.
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
