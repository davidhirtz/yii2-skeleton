<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Categories;

use Hirtz\Skeleton\Consent\Category;
use Yii;

class AnalyticsCategory extends Category
{
    final public const string ID = 'analytics';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->id = self::ID;
        $this->title = Yii::t('skeleton', 'CONSENT_CATEGORY_ANALYTICS_TITLE');
        $this->description = Yii::t('skeleton', 'CONSENT_CATEGORY_ANALYTICS_TEXT');

        parent::__construct($config);
    }
}
