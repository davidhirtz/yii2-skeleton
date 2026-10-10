<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Yii;

class Vimeo extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = 'Vimeo';
        $this->provider = 'Vimeo.com, Inc.';
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_VIMEO_PURPOSE');
        $this->privacyUrl = 'https://vimeo.com/privacy';
        $this->cookies = [
            Cookie::make()->name('vuid')->duration('P2Y')->thirdParty(),
        ];

        parent::__construct($config);
    }
}
