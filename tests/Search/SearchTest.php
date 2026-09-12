<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Search;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Test\TestCase;

class SearchTest extends TestCase
{
    public function testWeightIsFoundThroughASubclass(): void
    {
        $search = new Search([
            'models' => [
                Redirect::class => 0.9,
                User::class,
            ],
        ]);

        $subclass = (new class () extends Redirect {
        })::class;

        self::assertSame(0.9, $search->getWeight(Redirect::class));
        self::assertSame(0.9, $search->getWeight($subclass));
        self::assertNull($search->getWeight(User::class));
        self::assertSame([Redirect::class, User::class], $search->getModelClasses());
    }
}
