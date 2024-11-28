<?php

namespace davidhirtz\yii2\skeleton\modules;

use Yii;

trait ModuleTrait
{
    public bool $enableI18nTables = false;
    public string $tablePrefix = '';

    public function getI18nClassName(string $class): string
    {
        return $class . ($this->enableI18nTables ? ('::' . Yii::$app->language) : '');
    }

    public function getLanguages(): array
    {
        return $this->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->sourceLanguage];
    }

    public function getTableName(string $tableName): string
    {
        $tableName = $this->tablePrefix . $tableName;
        return '{{%' . ($this->enableI18nTables ? Yii::$app->getI18n()->getAttributeName($tableName) : $tableName) . '}}';
    }
}
