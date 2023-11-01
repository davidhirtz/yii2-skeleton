<?php

namespace davidhirtz\yii2\skeleton\modules;

use Yii;

trait ModuleTrait
{
    public bool $enableI18nTables = false;
    public string $tablePrefix = '';

    public function getTableName(string $tableName): string
    {
        $tableName = $this->tablePrefix . $tableName;
        return '{{%' . ($this->enableI18nTables ? Yii::$app->getI18n()->getAttributeName($tableName) : $tableName) . '}}';
    }
}