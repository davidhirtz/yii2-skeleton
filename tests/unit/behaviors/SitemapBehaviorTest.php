<?php

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\behaviors\SitemapBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Yii;
use yii\base\InvalidConfigException;

class SitemapBehaviorTest extends Unit
{
    private ?int $now = null;

    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'url' => 'string not null',
            'updated' => 'int unsigned',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(SitemapActiveRecord::tableName(), $columns)
            ->execute();

        $this->now = time();

        Yii::$app->getDb()->createCommand()->batchInsert(SitemapActiveRecord::tableName(), ['url', 'updated'], [
            ['https://www.test.com', $this->now],
            ['https://www.test.com/one-year-ago', $this->now - 31536000],
            ['https://www.test.com/one-month-ago', $this->now - 2592000],
            ['https://www.test.com/one-week-ago', $this->now - 604800],
            ['https://www.test.com/one-day-ago', $this->now - 86400],
            ['https://www.test.com/one-hour-ago', $this->now - 3600],
        ])->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(SitemapActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testSitemapUrlCount(): void
    {
        $this->assertEquals(6, SitemapActiveRecord::instance()->getSitemapBehavior()->getSitemapUrlCount());
    }

    public function testGenerateSitemapUrls(): void
    {
        $behavior = SitemapActiveRecord::instance()->getSitemapBehavior();
        $this->assertEquals('https://www.test.com', $behavior->generateSitemapUrls()[0]['loc']);
        $this->assertEquals(date('c', $this->now), $behavior->generateSitemapUrls()[0]['lastmod']);
    }

    public function testGenerateSitemapUrlsWithOffset(): void
    {
        Yii::$app->sitemap->useSitemapIndex = true;

        $model = new class extends SitemapActiveRecord {
            public function init(): void
            {
                $this->maxUrlCount = 2;
                parent::init();
            }
        };

        $behavior = $model->getSitemapBehavior();
        $this->assertCount(2, $behavior->generateSitemapUrls());

        $urls = $behavior->generateSitemapUrls(1);
        $this->assertEquals('https://www.test.com/one-month-ago', $urls[0]['loc']);
    }

    public function testMissingCallback()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('SitemapBehavior::$callback must be callable.');

        new class () extends ActiveRecord {
            public function behaviors(): array
            {
                return [
                    'SitemapBehavior' => SitemapBehavior::class,
                ];
            }
        };
    }

    public function testInvalidUrl()
    {
        $model = new class () extends SitemapActiveRecord {
            public function generateSitemapUrl(SitemapActiveRecord $model): array
            {
                return ['url' => $model->url];
            }
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('SitemapBehavior::$callback must return an array with a "loc" key.');

        /** @var SitemapBehavior $behavior */
        $behavior = $model->getBehavior('SitemapBehavior');
        $behavior->generateSitemapUrls();
    }
}

/**
 * @property int $id
 * @property string $url
 * @property int $updated
 */
class SitemapActiveRecord extends ActiveRecord
{
    public ?string $defaultChangeFrequency = null;
    public ?float $defaultPriority = null;
    public ?int $maxUrlCount = null;

    public function behaviors(): array
    {
        return [
            'SitemapBehavior' => [
                'class' => SitemapBehavior::class,
                'callback' => $this->generateSitemapUrl(...),
                'defaultChangeFrequency' => $this->defaultChangeFrequency,
                'defaultPriority' => $this->defaultPriority,
                'maxUrlCount' => $this->maxUrlCount,
            ]
        ];
    }

    public function getSitemapBehavior(): SitemapBehavior
    {
        /** @var SitemapBehavior $behavior */
        $behavior = $this->getBehavior('SitemapBehavior');
        return $behavior;
    }

    public function generateSitemapUrl(self $model): array
    {
        return [
            'loc' => $model->url,
            'lastmod' => date('c', $model->updated),
        ];
    }

    public static function tableName(): string
    {
        return 'sitemap_test';
    }
}
