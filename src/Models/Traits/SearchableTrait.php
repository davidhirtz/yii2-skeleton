<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\AdminModelChain;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Search\SearchDocument;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchText;
use Yii;

/**
 * @mixin ActiveRecord
 */
trait SearchableTrait
{
    public function getSearchTitle(?string $language = null): string
    {
        $title = in_array('name', $this->attributes(), true)
            ? (string)$this->getSearchAttributeValue('name', $language)
            : '';

        return mb_substr(SearchText::normalize($title ?: $this->getAdminName()), 0, 255);
    }

    public function getSearchWeight(): float
    {
        return 1.0;
    }

    public function getSearchTenantId(): ?int
    {
        $tenantId = in_array('tenant_id', $this->attributes(), true) ? $this->getAttribute('tenant_id') : null;
        return $tenantId !== null ? (int)$tenantId : null;
    }

    public function getSearchStatus(): int
    {
        return $this instanceof StatusAttributeInterface
            ? (int)$this->getAttribute('status')
            : StatusAttributeInterface::STATUS_ENABLED;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * @param class-string<SearchableInterface>|null $modelClass
     * @return list<SearchDocument>
     */
    public function getSearchDocuments(?string $modelClass = null): array
    {
        $modelClass ??= static::class;
        $weight = Search::getComponent()->getWeight($modelClass) ?? $this->getSearchWeight();

        $status = $this->getSearchStatus();
        $tenantId = $this->getSearchTenantId();
        $modelId = (int)$this->getPrimaryKey();

        $documents = [];

        foreach ($this->getSearchLanguages() as $language) {
            $documents[] = new SearchDocument(
                modelClass: $modelClass,
                modelId: $modelId,
                language: $language,
                title: $this->getSearchTitle($language),
                content: $this->getSearchIndexContent($language),
                tenantId: $tenantId,
                status: $status,
                weight: $weight,
            );
        }

        return $documents;
    }

    /**
     * @return ActiveQuery<covariant ActiveRecord>
     */
    public static function findSearchable(): ActiveQuery
    {
        return static::find();
    }

    public function getSearchResult(): ?SearchResult
    {
        return $this->isSearchResultVisible() ? $this->createSearchResult() : null;
    }

    /**
     * The model knows its own `AUTH_*` constants, the search does not; there is no generic "can view" here.
     */
    protected function isSearchResultVisible(): bool
    {
        return true;
    }

    /**
     * A record edited through another is named the way its page header names it, "About — Section 2/5": its own
     * name would say nothing about where it is, and a section's is usually empty.
     */
    protected function createSearchResult(): SearchResult
    {
        $chain = AdminModelChain::fromModel($this);
        $title = $chain->models ? $chain->base->getAdminName() : $this->getSearchTitle();
        $updated = $this->getAttribute('updated_at') ?? $this->getAttribute('created_at');

        return new SearchResult(
            title: $title,
            route: $this->getAdminRoute(),
            description: $this->getSearchDescription($title),
            icon: $this->getAdminIcon(),
            updated: $updated instanceof DateTime ? $updated : null,
            subtitles: $chain->subtitles,
        );
    }

    /**
     * The title is usually the first searchable attribute, and repeating it under itself says nothing.
     */
    protected function getSearchDescription(string $title): ?string
    {
        $content = $this->getSearchContent(Yii::$app->language);

        if ($title !== '' && str_starts_with($content, $title)) {
            $content = ltrim(substr($content, strlen($title)));
        }

        return $content ?: null;
    }

    /**
     * @return list<string>
     */
    protected function getSearchLanguages(): array
    {
        return Yii::$app->getI18n()->getLanguages();
    }

    /**
     * The readable content plus what InnoDB would otherwise not have: the transliteration that makes `Mueller`
     * find `Müller`, and a prefixed copy of every stopword and short token, which is the only way `.com` or `IT`
     * reach the index at all. The title is read for its tokens but not repeated, so it keeps its own weight.
     */
    protected function getSearchIndexContent(?string $language = null): string
    {
        $content = $this->getSearchContent($language);
        $tokens = SearchText::getIndexTokens($this->getSearchTitle($language) . "\n" . $content);

        return $tokens !== '' ? "$content $tokens" : $content;
    }

    protected function getSearchContent(?string $language = null): string
    {
        $values = [];

        foreach ($this->getSearchAttributes() as $attribute) {
            $value = $this->getSearchAttributeValue($attribute, $language);

            if (is_scalar($value) && (string)$value !== '') {
                $values[] = (string)$value;
            }
        }

        return SearchText::normalize(implode("\n", $values));
    }

    /**
     * Read through the magic getter, so a searchable name is any readable property: a column, a translated or
     * custom attribute, or a getter such as the media `File::getFilename()`. A custom attribute only some types or
     * some projects declare is absent rather than empty, which is why a name the record cannot read is skipped.
     */
    protected function getSearchAttributeValue(string $attribute, ?string $language = null): mixed
    {
        $name = $this instanceof I18nAttributeInterface
            ? $this->getI18nAttributeName($attribute, $language, fallback: true)
            : $attribute;

        return $this->canGetProperty($name) ? $this->$name : null;
    }
}
