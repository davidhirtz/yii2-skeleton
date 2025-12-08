<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\db;

use Codeception\Test\Unit;
use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\traits\I18nAttributesTrait;
use Yii;

class I18nActiveQueryTest extends Unit
{
    protected function _before(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $columns = [
            'id' => 'pk',
            'content' => 'string null',
            'content_de' => 'string not null',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(I18nActiveRecord::tableName(), $columns)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(I18nActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testI18nAttributeName(): void
    {
        $model = new I18nActiveRecord();
        $tableName = $model::tableName();

        self::assertEquals("$tableName.[[id]]", $model::find()->getI18nAttributeName('id'));
        self::assertEquals("$tableName.[[content]]", $model::find()->getI18nAttributeName('content'));

        Yii::$app->language = 'de';
        self::assertEquals("$tableName.[[content_de]]", $model::find()->getI18nAttributeName('content'));
    }

    public function testReplaceI18nAttributes(): void
    {
        $model = new I18nActiveRecord();

        $sql = $model::find()
            ->select(['id'])
            ->replaceI18nAttributes()
            ->createCommand()
            ->sql;

        self::assertEquals("SELECT `id` FROM `i18n_test`", $sql);

        $sql = $model::find()
            ->select(['id', 'content'])
            ->replaceI18nAttributes()
            ->createCommand()
            ->sql;

        self::assertEquals("SELECT `id`, `content` FROM `i18n_test`", $sql);

        Yii::$app->language = 'de';

        $sql = $model::find()
            ->selectAllColumns()
            ->replaceI18nAttributes()
            ->createCommand()
            ->sql;

        self::assertEquals("SELECT `i18n_test`.`id`, `i18n_test`.`content_de` FROM `i18n_test`", $sql);
    }
}

/**
 * @property int $id
 * @property string $content
 * @property string $content_de
 */
class I18nActiveRecord extends ActiveRecord
{
    use I18nAttributesTrait;

    #[\Override]
    public function init(): void
    {
        $this->i18nAttributes = ['content'];
        parent::init();
    }

    #[\Override]
    public static function tableName(): string
    {
        return '{{%i18n_test}}';
    }
}
