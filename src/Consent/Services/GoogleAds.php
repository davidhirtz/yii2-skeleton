<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Yii;

class GoogleAds extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = 'Google Ads';
        $this->provider = 'Google Ireland Limited';
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_GOOGLE_ADS_PURPOSE');
        $this->privacyUrl = 'https://policies.google.com/privacy';
        $this->cookies = [
            Cookie::make()->name('_gcl_au')->duration('P3M'),
        ];

        parent::__construct($config);
    }
}
