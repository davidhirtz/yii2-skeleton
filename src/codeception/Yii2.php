<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\codeception;

use Codeception\Lib\ModuleContainer;
use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\web\Application;

class Yii2 extends \Codeception\Module\Yii2
{
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $config['applicationClass'] ??= Application::class;
        $config['entryUrl'] ??= 'https://www.example.com:443/index.php';

        parent::__construct($moduleContainer, $config);
    }

    public function setDraftHttpHost(): void
    {
        $draftUrl = Url::draft($this->backupConfig['entryUrl']);
        $host = parse_url($draftUrl, PHP_URL_HOST);

        $this->haveServerParameter('HTTP_HOST', $host);
    }

    public function setProductionHttpHost(): void
    {
        $host = parse_url((string) $this->backupConfig['entryUrl'], PHP_URL_HOST);

        $this->haveServerParameter('HTTP_HOST', $host);
    }
}
