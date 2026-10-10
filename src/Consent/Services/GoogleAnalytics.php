<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Yii;

class GoogleAnalytics extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = 'Google Analytics';
        $this->provider = 'Google Ireland Limited';
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_GOOGLE_ANALYTICS_PURPOSE');
        $this->privacyUrl = 'https://policies.google.com/privacy';
        $this->cookies = [
            Cookie::make()->name('_ga')->duration('P2Y'),
            Cookie::make()->name('_ga_*')->duration('P2Y'),
        ];

        parent::__construct($config);
    }
}
