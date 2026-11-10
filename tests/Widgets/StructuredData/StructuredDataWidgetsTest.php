<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\StructuredData;

use DateTimeInterface;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\StructuredData\BreadcrumbList;
use Hirtz\Skeleton\Widgets\StructuredData\Event;
use Hirtz\Skeleton\Widgets\StructuredData\JobPosting;
use Hirtz\Skeleton\Widgets\StructuredData\Organization;
use Hirtz\Skeleton\Widgets\StructuredData\Thing;
use Hirtz\Skeleton\Widgets\StructuredData\WebSite;
use Yii;

class StructuredDataWidgetsTest extends TestCase
{
    public function testAThingIsAnyTypeWithItsProperties(): void
    {
        $node = Thing::make()
            ->type('Event')
            ->id('https://www.test.localhost/concert#event')
            ->properties(['name' => 'Concert', 'description' => null])
            ->addProperties(['startDate' => '2026-10-09'])
            ->getNode();

        self::assertSame([
            '@type' => 'Event',
            '@id' => 'https://www.test.localhost/concert#event',
            'name' => 'Concert',
            'startDate' => '2026-10-09',
        ], $node);
    }

    public function testAThingWithoutATypeRegistersNothing(): void
    {
        Thing::make()->properties(['name' => 'Nothing'])->register();
        self::assertSame([], $this->getView()->getStructuredData());
    }

    public function testRegisteringAgainReplacesTheNodeWithTheSameId(): void
    {
        Thing::make()->type('Event')->id('https://www.test.localhost/#event')->properties(['name' => 'Old'])->register();
        Thing::make()->type('Event')->id('https://www.test.localhost/#event')->properties(['name' => 'New'])->register();
        Thing::make()->type('Place')->properties(['name' => 'Anonymous'])->register();

        $nodes = $this->getView()->getStructuredData();

        self::assertCount(2, $nodes);
        self::assertSame('New', $nodes['https://www.test.localhost/#event']['name'] ?? null);
    }

    public function testRenderedOnItsOwnAThingIsAScript(): void
    {
        self::assertSame(
            '<script type="application/ld+json">{"@context":"https:\/\/schema.org","@type":"Place","name":"Å"}</script>',
            Thing::make()->type('Place')->properties(['name' => 'Å'])->render(),
        );
    }

    public function testAnOrganizationWithoutANameRegistersNothing(): void
    {
        Organization::make()->register();
        self::assertSame([], $this->getView()->getStructuredData());
    }

    public function testTheOrganizationIsConfiguredThroughTheContainer(): void
    {
        Yii::$container->set(Organization::class, [
            'name' => 'Example GmbH',
            'logo' => '/logo.png',
            'sameAs' => ['https://www.instagram.com/example'],
        ]);

        Organization::make()->register();

        self::assertSame([
            Organization::getDefaultId() => [
                '@type' => 'Organization',
                '@id' => 'https://www.test.localhost/#organization',
                'name' => 'Example GmbH',
                'url' => 'https://www.test.localhost/',
                'logo' => ['@type' => 'ImageObject', 'url' => 'https://www.test.localhost/logo.png'],
                'sameAs' => ['https://www.instagram.com/example'],
            ],
        ], $this->getView()->getStructuredData());
    }

    public function testTheWebSiteIsNamedAfterTheApplicationAndPointsToItsPublisher(): void
    {
        $node = WebSite::make()
            ->publisher(Organization::getDefaultId());

        $node->register();

        self::assertSame([
            '@type' => 'WebSite',
            '@id' => 'https://www.test.localhost/#website',
            'name' => Yii::$app->name,
            'url' => 'https://www.test.localhost/',
            'inLanguage' => Yii::$app->language,
            'publisher' => ['@id' => 'https://www.test.localhost/#organization'],
        ], $node->getNode());
    }

    public function testABreadcrumbListJoinsTheGraph(): void
    {
        BreadcrumbList::make()
            ->id('https://www.test.localhost/shoes/red#breadcrumb')
            ->breadcrumbs([new Breadcrumb('Shoes', '/shoes'), new Breadcrumb('Red')])
            ->register();

        self::assertSame([
            'https://www.test.localhost/shoes/red#breadcrumb' => [
                '@type' => 'BreadcrumbList',
                '@id' => 'https://www.test.localhost/shoes/red#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Shoes', 'item' => 'https://www.test.localhost/shoes'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Red'],
                ],
            ],
        ], $this->getView()->getStructuredData());
    }

    public function testAnEventNeedsAStartAndAPlace(): void
    {
        $start = new DateTime('2026-10-09 18:00:00', new \DateTimeZone('UTC'));

        self::assertNull(Event::make()->location('Hall')->build());
        self::assertNull(Event::make()->startDate($start)->build());
        self::assertNull(Event::make()->startDate($start)->location('')->build());
        self::assertNotNull(Event::make()->startDate($start)->location('Hall')->build());
        self::assertNotNull(Event::make()->startDate($start)->virtualLocation('https://example.com/stream')->build());
    }

    public function testAnEvent(): void
    {
        $node = Event::make()
            ->startDate(new DateTime('2026-10-09 18:00:00', new \DateTimeZone('UTC')))
            ->endDate(new Date('2026-10-11'))
            ->eventStatus(Event::STATUS_POSTPONED)
            ->location('Hall', 'Mu' . "\u{308}" . 'llerstraße 1')
            ->virtualLocation('https://example.com/stream')
            ->offer('https://example.com/tickets', '19.90', 'EUR')
            ->organizer(Organization::getDefaultId())
            ->addProperties(['name' => 'Å concert'])
            ->build();

        self::assertSame([
            '@type' => 'Event',
            'startDate' => '2026-10-09T18:00:00+00:00',
            'endDate' => '2026-10-11',
            'eventStatus' => 'https://schema.org/EventPostponed',
            'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
            'location' => [
                ['@type' => 'Place', 'name' => 'Hall', 'address' => 'Mu' . "\u{308}" . 'llerstraße 1'],
                ['@type' => 'VirtualLocation', 'url' => 'https://example.com/stream'],
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => 'https://example.com/tickets',
                'price' => '19.90',
                'priceCurrency' => 'EUR',
            ],
            'organizer' => ['@id' => 'https://www.test.localhost/#organization'],
            'name' => 'Å concert',
        ], $node);
    }

    public function testAnOnlineEventAtANodeOfItsOwn(): void
    {
        $online = Event::make()
            ->startDate(new DateTime('2026-10-09 18:00:00'))
            ->virtualLocation('https://example.com/stream')
            ->build();

        self::assertSame('https://schema.org/OnlineEventAttendanceMode', $online['eventAttendanceMode'] ?? null);

        $place = Thing::make()->type('Place')->properties(['name' => 'Hall']);
        $node = Event::make()->startDate(new DateTime('2026-10-09 18:00:00'))->location($place)->build();

        self::assertSame(['@type' => 'Place', 'name' => 'Hall'], $node['location'] ?? null);
        self::assertSame('https://schema.org/OfflineEventAttendanceMode', $node['eventAttendanceMode'] ?? null);
    }

    public function testAJobPostingNamesTheOrganizationAndItsPlace(): void
    {
        Yii::$container->set(Organization::class, ['name' => 'Å Example GmbH']);

        $node = JobPosting::make()
            ->title('Gärtner (m/w/d) 🌱')
            ->description('<p>Mu' . "\u{308}" . 'ller sucht Verstärkung.</p>')
            ->datePosted(new Date('2026-10-01'))
            ->validThrough(new Date('2999-12-31'))
            ->employmentTypes([JobPosting::EMPLOYMENT_FULL_TIME, JobPosting::EMPLOYMENT_PART_TIME])
            ->address([
                'streetAddress' => 'Müllerstraße 1',
                'postalCode' => '10115',
                'addressLocality' => 'Berlin',
                'addressCountry' => 'DE',
            ])
            ->identifier('42')
            ->build();

        self::assertSame([
            '@type' => 'JobPosting',
            'title' => 'Gärtner (m/w/d) 🌱',
            'description' => '<p>Mu' . "\u{308}" . 'ller sucht Verstärkung.</p>',
            'datePosted' => '2026-10-01',
            'validThrough' => '2999-12-31',
            'employmentType' => ['FULL_TIME', 'PART_TIME'],
            'hiringOrganization' => [
                '@type' => 'Organization',
                '@id' => 'https://www.test.localhost/#organization',
                'name' => 'Å Example GmbH',
                'url' => 'https://www.test.localhost/',
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Müllerstraße 1',
                    'postalCode' => '10115',
                    'addressLocality' => 'Berlin',
                    'addressCountry' => 'DE',
                ],
            ],
            'identifier' => ['@type' => 'PropertyValue', 'name' => 'Å Example GmbH', 'value' => '42'],
        ], $node);
    }

    public function testARemoteJobPostingNeedsNoAddressButACountry(): void
    {
        $posting = fn (): JobPosting => JobPosting::make()
            ->title('Developer')
            ->description('<p>Remote</p>')
            ->datePosted(new Date('2026-10-01'))
            ->hiringOrganization(['@type' => 'Organization', 'name' => 'Client AG']);

        self::assertNull($posting()->remote()->build());

        $node = $posting()->remote(applicantCountry: 'CH')->build();

        self::assertSame('Client AG', $node['hiringOrganization']['name'] ?? null);
        self::assertSame('TELECOMMUTE', $node['jobLocationType'] ?? null);
        self::assertSame(['@type' => 'Country', 'name' => 'CH'], $node['applicantLocationRequirements'] ?? null);
        self::assertArrayNotHasKey('jobLocation', $node);

        $node = $posting()->remote()->address(['streetAddress' => 'Hauptstraße 1', 'addressCountry' => 'AT'])->build();

        self::assertSame(['@type' => 'Country', 'name' => 'AT'], $node['applicantLocationRequirements'] ?? null);
        self::assertSame('Hauptstraße 1', $node['jobLocation']['address']['streetAddress'] ?? null);
    }

    public function testAnIncompleteJobPostingRendersNothing(): void
    {
        $posting = fn (): JobPosting => JobPosting::make()
            ->title('Developer')
            ->description('<p>Office</p>')
            ->datePosted(new Date('2026-10-01'))
            ->address(['streetAddress' => 'Hauptstraße 1']);

        Yii::$container->set(Organization::class, ['name' => 'Example GmbH']);
        self::assertNotNull($posting()->build());

        self::assertNull($posting()->description('')->build());
        self::assertNull($posting()->title(null)->build());
        self::assertNull($posting()->datePosted(null)->build());
        self::assertNull($posting()->address(['addressLocality' => 'Berlin'])->build());

        Yii::$container->set(Organization::class, ['name' => null]);
        self::assertNull($posting()->build());
    }

    public function testAnExpiredJobPostingRendersNothing(): void
    {
        Yii::$container->set(Organization::class, ['name' => 'Example GmbH']);

        $posting = fn (DateTimeInterface $validThrough): ?array => JobPosting::make()
            ->title('Developer')
            ->description('<p>Office</p>')
            ->datePosted(new Date('2026-01-01'))
            ->address(['streetAddress' => 'Hauptstraße 1'])
            ->validThrough($validThrough)
            ->build();

        self::assertNull($posting(new Date('-2 days')));
        self::assertNotNull($posting(new DateTime('now')));
        self::assertNotNull($posting(new Date('+2 days')));
    }

    private function getView(): View
    {
        $view = Yii::$app->getView();
        self::assertInstanceOf(View::class, $view);

        return $view;
    }
}
