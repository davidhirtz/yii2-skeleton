<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Services;

use Hirtz\Skeleton\Consent\Cookie;
use Hirtz\Skeleton\Consent\Service;
use Hirtz\Skeleton\Web\Request;
use Yii;

class CsrfCookie extends Service
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->name = Yii::t('skeleton', 'CONSENT_SERVICE_CSRF_NAME');
        $this->provider = Yii::$app->name;
        $this->purpose = Yii::t('skeleton', 'CONSENT_SERVICE_CSRF_PURPOSE');
        $this->cookies = [
            Cookie::make()->name(Request::current()->csrfParam ?? '_csrf'),
        ];

        parent::__construct($config);
    }
}
