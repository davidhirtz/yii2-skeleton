<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Sitemap;

use Closure;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Override;
use Yii;
use yii\base\InvalidConfigException;

/**
 * @template T of ActiveRecord
 *
 * @see https://www.sitemaps.org/protocol.html
 */
class ModelSitemap extends AbstractSitemap
{
    /**
     * @var class-string<T>
     */
    public string $modelClass;

    /**
     * @var Closure(T, string|null): (array<string, mixed>|list<array<string, mixed>>|false)|null the URL or URLs of a
     * single record. Required unless {@see getRecordUrls()} is overridden.
     */
    public ?Closure $url = null;

    public int $batchSize = 100;

    public ?string $changeFrequency = null;

    /**
     * @var float|null the default priority, leave empty to omit. Valid values range from 0.0 to 1.0, the default of a
     * page is 0.5.
     */
    public ?float $priority = null;

    private ?int $recordCount = null;

    #[Override]
    public function init(): void
    {
        if (!isset($this->modelClass) || !is_subclass_of($this->modelClass, ActiveRecord::class)) {
            throw new InvalidConfigException(static::class . '::$modelClass must extend ' . ActiveRecord::class . '.');
        }

        parent::init();
    }

    public function generateUrls(?int $offset = null): array
    {
        $query = $this->getQuery();

        if ($offset !== null) {
            $recordsPerPage = $this->getRecordsPerPage();
            $query->limit($recordsPerPage)->offset($offset * $recordsPerPage);
        }

        $languages = $this->getLanguages();
        $previousLanguage = Yii::$app->language;
        $urls = [];

        try {
            foreach ($query->each($this->batchSize) as $record) {
                foreach ($languages as $language) {
                    // the language is switched per record so the translated attributes resolve, and whatever renders
                    // the sitemap runs in the request's own language again
                    Yii::$app->language = $language ?? $previousLanguage;
                    $urls = [...$urls, ...$this->getRecordUrls($record, $language)];
                }
            }
        } finally {
            Yii::$app->language = $previousLanguage;
        }

        return $urls;
    }

    public function getPageCount(): int
    {
        return (int)ceil($this->getRecordCount() / $this->getRecordsPerPage());
    }

    /**
     * A record produces one URL per language, so a page holds fewer records than URLs whenever the URLs are
     * translated. Integer division keeps the page below {@see getMaxUrlCount()} — a fractional limit is silently
     * dropped by the query builder, which would put every record on every page.
     */
    public function getRecordsPerPage(): int
    {
        return max(1, intdiv($this->getMaxUrlCount(), count($this->getLanguages())));
    }

    public function getRecordCount(): int
    {
        return $this->recordCount ??= (int)$this->getQuery()->count();
    }

    /**
     * The record count is what the pages are calculated from, so a record the URL generation skips — a disabled or
     * routeless one the query cannot filter — shortens its page rather than moving a URL to the next one.
     *
     * @param T $record
     * @return list<array<string, mixed>>
     */
    protected function getRecordUrls(ActiveRecord $record, ?string $language = null): array
    {
        if (!$this->url instanceof Closure) {
            throw new InvalidConfigException(static::class . '::$url must be set or ::getRecordUrls() overridden.');
        }

        $result = ($this->url)($record, $language);

        if (!$result) {
            return [];
        }

        $urls = is_int(key($result)) ? $result : [$result];

        return array_values(array_map($this->normalizeUrl(...), $urls));
    }

    /**
     * @param array<string, mixed> $url
     * @return array<string, mixed>
     */
    protected function normalizeUrl(array $url): array
    {
        if (!isset($url['loc'])) {
            throw new InvalidConfigException(static::class . '::$url must return an array with a "loc" key.');
        }

        $url['changefreq'] ??= $this->changeFrequency;
        $url['priority'] ??= $this->priority;

        return array_filter($url, fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return ActiveQuery<T>
     */
    protected function getQuery(): ActiveQuery
    {
        /** @var ActiveQuery<T> $query */
        $query = $this->getModel()::find();
        return $query;
    }

    /**
     * Resolved through the container, so a project mapping the model class to its own gets its own definitions.
     *
     * @return T
     */
    protected function getModel(): ActiveRecord
    {
        return $this->modelClass::instance();
    }

    /**
     * The languages a record is repeated for, `[null]` for a model whose URLs are not translated.
     *
     * @return list<string|null>
     */
    protected function getLanguages(): array
    {
        $manager = Yii::$app->getUrlManager();
        $model = $this->getModel();

        return $manager->i18nUrl && $model instanceof I18nAttributeInterface && $model->getI18nAttributes()
            ? array_keys((array)$manager->languages)
            : [null];
    }
}
