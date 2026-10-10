<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Yii;

class YouTube extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = 'YouTube';
        $this->provider = 'Google Ireland Limited';
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_YOUTUBE_PURPOSE');
        $this->privacyUrl = 'https://policies.google.com/privacy';
        $this->cookies = [
            Cookie::make()->name('YSC')->thirdParty(),
            Cookie::make()->name('VISITOR_INFO1_LIVE')->duration('P6M')->thirdParty(),
            Cookie::make()->name('VISITOR_PRIVACY_METADATA')->duration('P6M')->thirdParty(),
        ];

        parent::__construct($config);
    }
}
