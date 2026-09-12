<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Search;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Test\TestCase;
use Override;

class SearchTest extends TestCase
{
    public function testWeightIsFoundThroughASubclass(): void
    {
        $search = new Search([
            'models' => [
                User::class => 0.9,
                WeightedActiveRecord::class,
            ],
        ]);

        $subclass = new class () extends User {
        };

        self::assertSame(0.9, $search->getWeight(User::class));
        self::assertSame(0.9, $search->getWeight($subclass::class));
        self::assertNull($search->getWeight(WeightedActiveRecord::class));
        self::assertSame([User::class, WeightedActiveRecord::class], $search->getModelClasses());
    }
}

class WeightedActiveRecord extends ActiveRecord implements SearchableInterface
{
    use SearchableTrait;

    public function getSearchAttributes(): array
    {
        return ['name'];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%test_weighted}}';
    }
}
