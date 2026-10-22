<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Search;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchHit;
use Hirtz\Skeleton\Search\SearchRequest;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchResultBuilder;
use Hirtz\Skeleton\Search\SearchResultSet;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class SearchResultBuilderTest extends TestCase
{
    use UserFixtureTrait;

    /**
     * A hit is dropped after the fetch, here one of a record deleted since it was indexed.
     */
    public function testPagesNeverRepeatAHit(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER);
        $this->getWebUser()->setIdentity($user);

        $owner = $this->getUserFromFixture('owner');

        $search = new SearchResultBuilderTestSearch([
            new SearchHit(User::class, 987654, 1),
            new SearchHit(User::class, $owner->id, 1),
        ]);

        $builder = new SearchResultBuilder($search);
        $request = new SearchRequest(query: 'x', limit: 1);

        $first = $builder->build($request, false);
        $second = $builder->build($request->withLimit(1, 1), false);

        self::assertSame([], $first);
        self::assertSame([$owner->id], array_map(fn (SearchResult $result): mixed => $result->route['id'] ?? null, $second));
    }
}

class SearchResultBuilderTestSearch extends Search
{
    /**
     * @param list<SearchHit> $hits
     */
    public function __construct(private readonly array $hits)
    {
        parent::__construct();
    }

    #[Override]
    public function search(SearchRequest $request): SearchResultSet
    {
        return new SearchResultSet(array_slice($this->hits, $request->offset, $request->limit));
    }
}
