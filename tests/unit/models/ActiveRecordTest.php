<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;

class ActiveRecordTest extends Unit
{
    public function testBatchInsert(): void
    {
        $data = require __DIR__ . '/../../support/data/user.php';
        $expected = count($data);

        $count = User::batchInsert($data);
        static::assertEquals($expected, $count);

        $count = User::find()->count();
        static::assertEquals($expected, $count);
    }

    /**
     * Tests overriding the identical checks for date attributes.
     */
    public function testGetDirtyAttributes(): void
    {
        $this->insertRedirectRecord();

        $model = Redirect::find()->one();
        $model->updated_at = (new DateTime())->setTimestamp($model->updated_at->getTimestamp());

        static::assertEquals(0, count($model->getDirtyAttributes()));
        static::assertFalse($model->hasChangedAttributes(['url', 'updated_at']));
    }

    public function testIsBatch(): void
    {
        $model = new ActiveRecord();
        $model->setIsBatch(true);
        static::assertTrue($model->getIsBatch());
    }

    public function testIsDeleted(): void
    {
        $model = $this->insertRedirectRecord();
        static::assertFalse($model->isDeleted());

        $model->delete();
        static::assertTrue($model->isDeleted());
    }

    public function testUpdateAttributesBlameable(): void
    {
        $model = $this->insertRedirectRecord();

        $model->updateAttributesBlameable([
            'url' => '/another-url',
            'updated_by_user_id',
            'updated_at',
        ]);

        static::assertEquals('/another-url', $model->url);
        static::assertNull($model->updated_by_user_id);
    }


    public function testTypecastAttributesBeforeValidate(): void
    {
        $model = new UserLogin();
        $model->setAttribute('user_id', '1');
        $model->type = $model::TYPE_LOGIN;
        $model->browser = '';
        $model->ip_address = '';

        $model->validate();

        static::assertEquals(1, $model->user_id);
        static::assertEquals(null, $model->browser);
        static::assertEquals(null, $model->ip_address);
    }

    private function insertRedirectRecord(): Redirect
    {
        $model = new Redirect();

        $model->request_uri = '/old-url';
        $model->url = '/new-url';
        $model->save();

        return $model;
    }
}
