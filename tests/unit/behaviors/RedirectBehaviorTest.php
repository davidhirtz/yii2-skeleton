<?php

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\behaviors\RedirectBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Redirect;
use Yii;
use yii\base\InvalidConfigException;

class RedirectBehaviorTest extends Unit
{
    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'query' => 'string not null',
        ];

        Yii::$app->getDb()->createCommand()->createTable(RedirectActiveRecord::tableName(), $columns)->execute();

        Yii::$app->getUrlManager()->addRules([
            'test/<query>' => 'site/index',
        ]);

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()->dropTable(RedirectActiveRecord::tableName())->execute();
        Redirect::deleteAll();

        parent::_after();
    }

    public function testCreateUrl(): void
    {
        $model = $this->createRedirectActiveRecord();
        $this->assertEquals('/test/test-query-1', $model->getUrl());
    }

    public function testAfterSaveEvent(): void
    {
        $model = $this->createRedirectActiveRecord();
        $model->query = 'test-query-2';
        $model->save();

        $redirect = Redirect::find()->one();

        $this->assertEquals('test/test-query-1', $redirect->request_uri);
        $this->assertEquals('test/test-query-2', $redirect->url);
    }

    public function testAfterSaveEventWithCustomUrl(): void
    {
        $model = new class() extends RedirectActiveRecord {
            public function getUrl(): string
            {
                return "/custom/$this->query";
            }
        };

        $model->query = 'test-query-1';
        $model->save();

        $model->query = 'test-query-2';
        $model->save();

        $redirect = Redirect::find()->one();
        $this->assertEquals('custom/test-query-1', $redirect->request_uri);
        $this->assertEquals('custom/test-query-2', $redirect->url);
    }

    public function testAfterFindEvent(): void
    {
        $model = $this->createRedirectActiveRecord();
        $model = RedirectActiveRecord::findOne($model->id);

        $model->query = 'test-query-2';
        $model->save();

        $redirect = Redirect::find()->one();

        $this->assertEquals('test/test-query-1', $redirect->request_uri);
        $this->assertEquals('test/test-query-2', $redirect->url);
    }

    public function testAfterDeleteEvent(): void
    {
        $model = $this->createRedirectActiveRecord();
        $model->query = 'test-query-2';
        $model->save();

        $this->assertTrue(Redirect::find()->exists());

        $model->delete();

        $this->assertFalse(Redirect::find()->exists());
    }

    public function testUpdatePreviousRedirectUrls(): void
    {
        $model = $this->createRedirectActiveRecord();
        $model->query = 'test-query-2';
        $model->save();

        $model = $this->createRedirectActiveRecord($model->query);
        $model->query = 'test-query-3';
        $model->save();

        $redirects = Redirect::find()->all();

        $this->assertEquals('test/test-query-1', $redirects[0]->request_uri);
        $this->assertEquals('test/test-query-3', $redirects[0]->url);

        $this->assertEquals('test/test-query-2', $redirects[1]->request_uri);
        $this->assertEquals('test/test-query-3', $redirects[1]->url);
    }

    public function testMissingRouteMethod(): void
    {
        $this->expectException(InvalidConfigException::class);

        $model = new BaseRedirectActiveRecord();
        $model->query = 'test-query-1';
        $model->save();
    }

    protected function createRedirectActiveRecord(string $query = 'test-query-1'): RedirectActiveRecord
    {
        $model = new RedirectActiveRecord();
        $model->query = $query;
        $model->insert();

        return $model;
    }
}

/**
 * @property int $id
 * @property string $query
 *
 * @mixin RedirectBehavior
 */
class BaseRedirectActiveRecord extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'RedirectBehavior' => RedirectBehavior::class,
        ];
    }

    public static function tableName(): string
    {
        return 'test_redirect';
    }
}

class RedirectActiveRecord extends BaseRedirectActiveRecord
{
    public function getRoute(): array|false
    {
        return ['site/index', 'query' => $this->query];
    }
}
