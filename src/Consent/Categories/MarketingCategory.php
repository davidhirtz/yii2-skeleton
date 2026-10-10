<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Categories;

use Hirtz\Skeleton\Consent\Category;
use Yii;

class MarketingCategory extends Category
{
    final public const string ID = 'marketing';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->id = self::ID;
        $this->title = Yii::t('skeleton', 'CONSENT_CATEGORY_MARKETING_TITLE');
        $this->description = Yii::t('skeleton', 'CONSENT_CATEGORY_MARKETING_TEXT');

        parent::__construct($config);
    }
}
