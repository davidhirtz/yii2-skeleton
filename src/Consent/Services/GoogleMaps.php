<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Yii;

class GoogleMaps extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = 'Google Maps';
        $this->provider = 'Google Ireland Limited';
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_GOOGLE_MAPS_PURPOSE');
        $this->privacyUrl = 'https://policies.google.com/privacy';
        $this->cookies = [
            Cookie::make()->name('NID')->duration('P6M')->thirdParty(),
        ];

        parent::__construct($config);
    }
}
