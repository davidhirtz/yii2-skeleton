<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Service;
use Yii;

class GoogleTagManager extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = 'Google Tag Manager';
        $this->provider = 'Google Ireland Limited';
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_GOOGLE_TAG_MANAGER_PURPOSE');
        $this->privacyUrl = 'https://policies.google.com/privacy';

        parent::__construct($config);
    }
}
