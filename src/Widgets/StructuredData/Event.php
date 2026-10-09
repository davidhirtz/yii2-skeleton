<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use DateTimeInterface;
use Hirtz\Skeleton\Helpers\StructuredData;
use Override;

/**
 * An event. It is visible only with what Google requires besides the name: a start and a place, physical or
 * online, so an incomplete record never ships invalid data. Name, description, image and URL usually come from the
 * page the event is the main entity of.
 */
class Event extends Thing
{
    final public const string STATUS_SCHEDULED = 'EventScheduled';
    final public const string STATUS_CANCELLED = 'EventCancelled';
    final public const string STATUS_POSTPONED = 'EventPostponed';
    final public const string STATUS_RESCHEDULED = 'EventRescheduled';
    final public const string STATUS_MOVED_ONLINE = 'EventMovedOnline';

    protected ?string $type = 'Event';
    protected ?DateTimeInterface $startDate = null;
    protected ?DateTimeInterface $endDate = null;
    protected ?string $eventStatus = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $location = null;
    protected ?string $virtualLocation = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $offer = null;
    protected ?string $organizer = null;

    public function startDate(?DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function endDate(?DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @param string|null $eventStatus one of the `STATUS_*` constants, `null` for scheduled
     */
    public function eventStatus(?string $eventStatus): static
    {
        $this->eventStatus = $eventStatus;
        return $this;
    }

    /**
     * @param Thing|array<string, mixed>|string|null $location a place's name, or a node of its own (a `Place` with a
     * `PostalAddress`)
     * @param string|null $address the address of a named place, as one line
     */
    public function location(Thing|array|string|null $location, ?string $address = null): static
    {
        $this->location = match (true) {
            $location instanceof Thing => $location->build(),
            is_string($location) && $location !== '' => ['@type' => 'Place', 'name' => $location, 'address' => $address],
            is_array($location) => $location,
            default => null,
        };

        return $this;
    }

    public function virtualLocation(?string $url): static
    {
        $this->virtualLocation = $url;
        return $this;
    }

    public function offer(?string $url, float|int|string|null $price = null, ?string $currency = null): static
    {
        $this->offer = $url !== null && $url !== '' ? [
            '@type' => 'Offer',
            'url' => $url,
            'price' => $price,
            'priceCurrency' => $currency,
        ] : null;

        return $this;
    }

    /**
     * @param string|null $organizer the `@id` of the organizer, such as {@see Organization::getDefaultId()}
     */
    public function organizer(?string $organizer): static
    {
        $this->organizer = $organizer;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->startDate !== null
            && ($this->location !== null || $this->virtualLocation)
            && parent::isVisible();
    }

    #[Override]
    protected function getProperties(): array
    {
        $location = $this->location;

        if ($this->virtualLocation) {
            $virtual = ['@type' => 'VirtualLocation', 'url' => $this->virtualLocation];
            $location = $location !== null ? [$location, $virtual] : $virtual;
        }

        return [
            'startDate' => StructuredData::date($this->startDate),
            'endDate' => StructuredData::date($this->endDate),
            'eventStatus' => 'https://schema.org/' . ($this->eventStatus ?: self::STATUS_SCHEDULED),
            'eventAttendanceMode' => 'https://schema.org/' . $this->getAttendanceMode(),
            'location' => $location,
            'offers' => $this->offer,
            'organizer' => $this->organizer !== null ? ['@id' => $this->organizer] : null,
            ...parent::getProperties(),
        ];
    }

    protected function getAttendanceMode(): string
    {
        return match (true) {
            $this->location !== null && (bool)$this->virtualLocation => 'MixedEventAttendanceMode',
            (bool)$this->virtualLocation => 'OnlineEventAttendanceMode',
            default => 'OfflineEventAttendanceMode',
        };
    }
}
