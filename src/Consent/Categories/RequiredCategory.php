<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent\Categories;

use Hirtz\Skeleton\Consent\Category;
use Hirtz\Skeleton\Consent\Services\ConsentCookie;
use Yii;

class RequiredCategory extends Category
{
    final public const string ID = 'required';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->id = self::ID;
        $this->title = Yii::t('skeleton', 'CONSENT_CATEGORY_REQUIRED_TITLE');
        $this->description = Yii::t('skeleton', 'CONSENT_CATEGORY_REQUIRED_TEXT');
        $this->required = true;
        $this->services = [ConsentCookie::make()];

        parent::__construct($config);
    }
}
