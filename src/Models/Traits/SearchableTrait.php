<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminRouteInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
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

        if ($title === '' && $this instanceof TrailModelInterface) {
            $title = $this->getTrailModelName();
        }

        return mb_substr(SearchText::normalize($title), 0, 255);
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
     * @param class-string|null $modelClass
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

    protected function createSearchResult(): SearchResult
    {
        $updated = $this->getAttribute('updated_at') ?? $this->getAttribute('created_at');

        return new SearchResult(
            title: $this->getSearchResultTitle(),
            route: $this instanceof AdminRouteInterface ? $this->getAdminRoute() : false,
            description: $this->getSearchDescription(),
            icon: $this->getSearchIcon(),
            badge: $this->getSearchBadge(),
            updated: $updated instanceof DateTime ? $updated : null,
        );
    }

    /**
     * What the hit shows, as opposed to what is indexed: a child record names its parent here without the parent's
     * name outranking it in the index.
     */
    protected function getSearchResultTitle(): string
    {
        return $this->getSearchTitle();
    }

    /**
     * The title is usually the first searchable attribute, and repeating it under itself says nothing.
     */
    protected function getSearchDescription(): ?string
    {
        $content = $this->getSearchContent(Yii::$app->language);
        $title = $this->getSearchTitle();

        if ($title !== '' && str_starts_with($content, $title)) {
            $content = ltrim(substr($content, strlen($title)));
        }

        return $content ?: null;
    }

    protected function getSearchIcon(): ?string
    {
        if ($this instanceof TypeAttributeInterface && ($icon = $this->getTypeIcon())) {
            return $icon;
        }

        return $this instanceof StatusAttributeInterface ? ($this->getStatusIcon() ?: null) : null;
    }

    protected function getSearchBadge(): ?string
    {
        return $this instanceof TrailModelInterface ? ($this->getTrailModelType() ?: null) : null;
    }

    /**
     * @return list<string>
     */
    protected function getSearchLanguages(): array
    {
        return array_values(Yii::$app->getI18n()->getLanguages());
    }

    /**
     * The transliteration is what makes `Mueller` find `Müller`; the collation only folds `Muller`.
     */
    protected function getSearchIndexContent(?string $language = null): string
    {
        $content = $this->getSearchContent($language);
        $transliterated = SearchText::transliterate($content);

        return $transliterated !== '' ? "$content $transliterated" : $content;
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
     * Reads translated and custom attributes alike: both are plain attributes.
     */
    protected function getSearchAttributeValue(string $attribute, ?string $language = null): mixed
    {
        return $this instanceof I18nAttributeInterface
            ? $this->getI18nAttribute($attribute, $language, fallback: true)
            : $this->getAttribute($attribute);
    }
}
