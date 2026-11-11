<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use DateTimeImmutable;
use DateTimeZone;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Yii;

class DashboardHeader extends Header
{
    #[Override]
    protected function configure(): void
    {
        $identity = $this->webuser->getIdentity();
        $params = ['user' => $identity->getUsername()];

        $this->title ??= $identity->login_count > 1
            ? $this->getGreeting($params)
            : Yii::t('skeleton', 'DASHBOARD_WELCOME_TITLE', $params);

        $lastLogin = Application::current()->getSession()->get('last_login_timestamp');

        if ($lastLogin) {
            $this->subheading ??= Div::make()
                ->class('hidden-sticky')
                ->content(Yii::t('skeleton', 'DASHBOARD_LAST_LOGIN_TEXT', [
                    'timestamp' => RelativeTime::make()->value($lastLogin),
                ]));
        }

        parent::configure();
    }

    /**
     * @param array<string, string|null> $params
     */
    protected function getGreeting(array $params): string
    {
        $hour = $this->getHour();

        return match (true) {
            $hour < 5 => Yii::t('skeleton', 'DASHBOARD_NIGHT_TITLE', $params),
            $hour < 11 => Yii::t('skeleton', 'DASHBOARD_MORNING_TITLE', $params),
            $hour < 14 => Yii::t('skeleton', 'DASHBOARD_MIDDAY_TITLE', $params),
            $hour < 18 => Yii::t('skeleton', 'DASHBOARD_AFTERNOON_TITLE', $params),
            $hour < 23 => Yii::t('skeleton', 'DASHBOARD_EVENING_TITLE', $params),
            default => Yii::t('skeleton', 'DASHBOARD_NIGHT_TITLE', $params),
        };
    }

    /** In the application's time zone, which the user's own timezone sets. */
    protected function getHour(): int
    {
        return (int)(new DateTimeImmutable('now', new DateTimeZone(Yii::$app->getTimeZone())))->format('G');
    }
}
