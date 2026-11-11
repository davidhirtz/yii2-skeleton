<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\DashboardHeader;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;

class DashboardHeaderTest extends TestCase
{
    use UserFixtureTrait;

    /**
     * @return array<string, array{int, string}>
     */
    public static function provideHours(): array
    {
        return [
            'after midnight' => [0, 'DASHBOARD_NIGHT_TITLE'],
            'early' => [4, 'DASHBOARD_NIGHT_TITLE'],
            'morning' => [5, 'DASHBOARD_MORNING_TITLE'],
            'late morning' => [10, 'DASHBOARD_MORNING_TITLE'],
            'midday' => [11, 'DASHBOARD_MIDDAY_TITLE'],
            'afternoon' => [14, 'DASHBOARD_AFTERNOON_TITLE'],
            'evening' => [18, 'DASHBOARD_EVENING_TITLE'],
            'late evening' => [22, 'DASHBOARD_EVENING_TITLE'],
            'night' => [23, 'DASHBOARD_NIGHT_TITLE'],
        ];
    }

    #[DataProvider('provideHours')]
    public function testAReturningUserIsGreetedByTheTimeOfDay(int $hour, string $key): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->login_count = 2;
        $this->getWebUser()->setIdentity($user);

        DashboardHeaderAtHour::$hour = $hour;
        $html = DashboardHeaderAtHour::make()->render();

        self::assertStringContainsString(Yii::t('skeleton', $key, ['user' => $user->getUsername()]), $html);
    }

    public function testAFirstLoginIsWelcomed(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->login_count = 1;
        $this->getWebUser()->setIdentity($user);

        $html = DashboardHeader::make()->render();

        self::assertStringContainsString(Yii::t('skeleton', 'DASHBOARD_WELCOME_TITLE', ['user' => $user->getUsername()]), $html);
    }
}

class DashboardHeaderAtHour extends DashboardHeader
{
    public static int $hour = 12;

    #[Override]
    protected function getHour(): int
    {
        return self::$hour;
    }
}
