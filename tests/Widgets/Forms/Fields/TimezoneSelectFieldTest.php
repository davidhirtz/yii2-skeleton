<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use DateTimeZone;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\TimezoneSelectField;

class TimezoneSelectFieldTest extends TestCase
{
    /**
     * A zone the user record stores and the select lacks would be replaced by the first option on the next save.
     */
    public function testEveryZoneTheUserAcceptsIsOffered(): void
    {
        $user = User::create();
        $user->timezone = 'Europe/Kyiv';

        $html = (string)TimezoneSelectField::make()->model($user)->property('timezone');

        self::assertStringContainsString('<option value="Europe/Kyiv" selected', $html);
        self::assertSame(count(DateTimeZone::listIdentifiers()), substr_count($html, '<option value="'));
    }
}
