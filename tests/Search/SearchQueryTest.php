<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Search;

use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Search\SearchDocument;
use Hirtz\Skeleton\Search\SearchRequest;
use Hirtz\Skeleton\Search\SearchText;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

/**
 * InnoDB fulltext does not see uncommitted rows, so the documents are committed and removed by hand; everything
 * else in the suite stays inside the rolled back transaction.
 */
class SearchQueryTest extends TestCase
{
    private const int ENTRY_ID = 101;
    private const int TITLE_ID = 102;
    private const int CONTENT_ID = 103;
    private const int GERMAN_ID = 104;
    private const int DISABLED_ID = 105;
    private const int LIGHT_ID = 106;
    private const int HEAVY_ID = 107;
    private const int EMAIL_ID = 108;
    private const int SHORT_ID = 109;
    private const int COMMERCE_ID = 110;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getDb()->getTransaction()?->commit();
        $this->indexDocuments();
    }

    #[Override]
    protected function tearDown(): void
    {
        Search::deleteAll();
        parent::tearDown();
    }

    public function testBooleanPrefixMatch(): void
    {
        self::assertSame([self::ENTRY_ID], $this->search('firm'));
        self::assertSame([self::ENTRY_ID], $this->search('firma'));
    }

    public function testTransliterationFindsTheUmlaut(): void
    {
        self::assertSame([self::ENTRY_ID], $this->search('Mueller'));
        self::assertSame([self::ENTRY_ID], $this->search('muller'));
    }

    public function testATitleHitOutranksAContentHit(): void
    {
        self::assertSame([self::TITLE_ID, self::CONTENT_ID], $this->search('Impressum'));
    }

    public function testAShortQueryIsAPrefixSearch(): void
    {
        self::assertSame([self::SHORT_ID, self::ENTRY_ID], $this->search('be'));
        self::assertSame([], $this->search('xy'));
    }

    /**
     * `GmbH` is two characters short of what InnoDB indexes and `com` is one of its stopwords, so only the
     * prefixed copies the write puts in the index can find either.
     */
    public function testATokenInnoDbDropsIsStillFound(): void
    {
        self::assertSame([self::SHORT_ID], $this->search('AG'));
        self::assertSame([self::EMAIL_ID], $this->search('hausmeister@domain.com'));
        self::assertSame([self::EMAIL_ID], $this->search('domain.com'));
    }

    /**
     * The plain half of the pair keeps an ordinary prefix search working, so `com` still reaches `Commerce`.
     */
    public function testAStopwordStillMatchesLongerWords(): void
    {
        self::assertSame([self::COMMERCE_ID, self::EMAIL_ID], $this->search('com'));
    }

    public function testEmptyQueryMatchesNothing(): void
    {
        self::assertSame([], $this->search('   '));
    }

    public function testLanguagesNarrowTheHits(): void
    {
        self::assertSame([self::GERMAN_ID], $this->search('Kontaktformular'));
        self::assertSame([], $this->search('Kontaktformular', languages: ['en-US']));
    }

    public function testTheLanguageRowsAreCollapsedIntoOneHit(): void
    {
        $set = Yii::$app->get('search')->search(new SearchRequest('Impressum'));
        $ids = array_map(fn ($hit): int => $hit->modelId, $set->hits);

        self::assertSame([self::TITLE_ID, self::CONTENT_ID], $ids);
    }

    public function testModelsNarrowTheHits(): void
    {
        self::assertSame([self::ENTRY_ID], $this->search('firma', models: [User::class]));
        self::assertSame([], $this->search('firma', models: [Search::class]));
    }

    /**
     * A stopword is an ordinary required term now that it is indexed, so it narrows like every other word
     * rather than being quietly ignored.
     */
    public function testAStopwordNarrowsLikeEveryOtherTerm(): void
    {
        self::assertSame([self::COMMERCE_ID], $this->search('com department'));
        self::assertSame([], $this->search('com Datenschutz'));
    }

    public function testTheFrontendPresetsFilterTenantAndStatus(): void
    {
        self::assertSame([self::DISABLED_ID], $this->search('Entwurf'));
        self::assertSame([], $this->search('Entwurf', status: StatusAttributeInterface::STATUS_ENABLED));
        self::assertSame([], $this->search('firma', tenantId: 2));
        self::assertSame([self::ENTRY_ID], $this->search('firma', tenantId: 1));
    }

    public function testTheWeightMultipliesTheScore(): void
    {
        $hits = Yii::$app->get('search')
            ->search(new SearchRequest('Sonderangebot'))
            ->hits;

        self::assertSame([self::HEAVY_ID, self::LIGHT_ID], array_map(fn ($hit): int => $hit->modelId, $hits));
        self::assertEqualsWithDelta($hits[1]->score * 4, $hits[0]->score, 0.0001);
    }

    public function testCountMatchesTheNumberOfRecords(): void
    {
        self::assertSame(2, Yii::$app->get('search')->count(new SearchRequest('Impressum')));
    }

    /**
     * @param list<string> $languages
     * @param list<class-string> $models
     * @return list<int>
     */
    private function search(
        string $query,
        array $languages = [],
        array $models = [],
        ?int $tenantId = null,
        ?int $status = null,
    ): array {
        $request = new SearchRequest($query, $languages, $models, $tenantId, $status);
        $set = Yii::$app->get('search')->search($request);

        return array_map(fn ($hit): int => $hit->modelId, $set->hits);
    }

    private function indexDocuments(): void
    {
        $documents = [
            $this->createDocument(self::ENTRY_ID, 'Bergfirma Müller', 'Die alte Firma von Herrn Müller'),
            $this->createDocument(self::TITLE_ID, 'Impressum', 'Rechtliche Angaben'),
            $this->createDocument(self::CONTENT_ID, 'Datenschutz', 'Siehe auch Impressum'),
            $this->createDocument(self::GERMAN_ID, 'Kontakt', 'Kontaktformular', language: 'de'),
            $this->createDocument(self::DISABLED_ID, 'Entwurf', '', status: StatusAttributeInterface::STATUS_DISABLED),
            $this->createDocument(self::LIGHT_ID, 'Sonderangebot', '', weight: 0.5),
            $this->createDocument(self::HEAVY_ID, 'Sonderangebot', '', weight: 2.0),
            $this->createDocument(self::EMAIL_ID, 'Hausmeister', 'hausmeister@domain.com'),
            $this->createDocument(self::SHORT_ID, 'Bergbahn AG', 'Die Bergbahn AG'),
            $this->createDocument(self::COMMERCE_ID, 'Commerce', 'Commerce department'),
        ];

        Yii::$app->get('search')->getDriver()->index(...$documents);
    }

    private function createDocument(
        int $modelId,
        string $title,
        string $content,
        string $language = 'en-US',
        int $status = StatusAttributeInterface::STATUS_ENABLED,
        float $weight = 1.0,
    ): SearchDocument {
        // The same transform {@see \Hirtz\Skeleton\Models\Traits\SearchableTrait::getSearchIndexContent()} applies.
        $content = trim("$title $content " . SearchText::getIndexTokens("$title $content"));

        return new SearchDocument(
            modelClass: User::class,
            modelId: $modelId,
            language: $language,
            title: $title,
            content: $content,
            tenantId: 1,
            status: $status,
            weight: $weight,
        );
    }
}
