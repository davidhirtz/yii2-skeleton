<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Caching;

use Hirtz\Skeleton\Caching\TagDependency;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class TagDependencyTest extends TestCase
{
    public function testATagInvalidatedInsideATransactionIsInvalidatedAgainAfterTheCommit(): void
    {
        $db = Yii::$app->getDb();
        $db->getTransaction()?->rollBack();

        $cache = Yii::$app->getCache();
        $dependency = new TagDependency(['tags' => ['zz-tag']]);

        $transaction = $db->beginTransaction();
        TagDependency::invalidate($cache, 'zz-tag');

        $cache->set('zz-page', 'old', 0, $dependency);
        $transaction->commit();

        self::assertFalse($cache->get('zz-page'));

        $cache->set('zz-page', 'new', 0, $dependency);
        $db->beginTransaction()->commit();

        self::assertSame('new', $cache->get('zz-page'));
    }

    public function testARolledBackTransactionInvalidatesNothingOnTheNextCommit(): void
    {
        $db = Yii::$app->getDb();
        $db->getTransaction()?->rollBack();

        $cache = Yii::$app->getCache();

        $transaction = $db->beginTransaction();
        TagDependency::invalidate($cache, 'zz-tag');
        $transaction->rollBack();

        $cache->set('zz-page', 'page', 0, new TagDependency(['tags' => ['zz-tag']]));
        $db->beginTransaction()->commit();

        self::assertSame('page', $cache->get('zz-page'));
    }
}
