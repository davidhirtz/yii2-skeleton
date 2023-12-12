<?php

namespace davidhirtz\yii2\skeleton\behaviors\stubs;

use davidhirtz\yii2\skeleton\behaviors\SitemapBehavior;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\ActiveRecord;

abstract class SitemapBehaviorActiveRecord extends ActiveRecord
{
    /**
     * @see SitemapBehavior::getSitemapQuery()
     */
    public abstract function getSitemapQuery(): ActiveQuery;
}