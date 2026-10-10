<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Yii;

class ConsentCookie extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = Yii::t('skeleton', 'CONSENT_SERVICE_CONSENT_NAME');
        $this->provider = Yii::$app->name;
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_CONSENT_PURPOSE');
        $this->cookies = [
            Cookie::make()->name('_cc')->duration('P1Y'),
        ];

        parent::__construct($config);
    }
}
