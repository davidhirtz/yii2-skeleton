<?php

namespace davidhirtz\yii2\skeleton\modules;

use Yii;

/**
 * Class ModuleTrait.
 * @package davidhirtz\yii2\skeleton\modules\admin
 */
trait ModuleTrait
{
    /**
     * @var bool
     */
    public $enableI18nTables = false;

    /**
     * @var string
     */
    public $tablePrefix;

    /**
     * @param string $tableName
     * @return string
     */
    public function getTableName($tableName)
    {
        $tableName = $this->tablePrefix . $tableName;
        return '{{%' . ($this->enableI18nTables ? Yii::$app->getI18n()->getAttributeName($tableName) : $tableName) . '}}';
    }
}