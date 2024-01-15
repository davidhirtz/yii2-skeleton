<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\web\Sitemap;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

/**
 * SitemapBehavior implements the needed methods for an active records to be used in the sitemap component.
 * All methods can be overwritten by the implementing model to customize the sitemap URL generation.
 *
 * @see https://www.sitemaps.org/protocol.html
 *
 * @property ActiveRecord&SitemapBehavior $owner
 * @mixin ActiveRecord
 */
class SitemapBehavior extends Behavior
{
    /**
     * @var callable required method, which returns a single or nested array with sitemap URL or valid route as "loc"
     * key.
     */
    public $callback;

    /**
     * @var int|null the maximum number rows selected by the default database query, if this is null the default value from
     * {@see Sitemap} will be used. Change this value if one record produces more than one URL (for example for
     * multiple languages).
     */
    public ?int $maxUrlCount = null;

    /**
     * @var int the batch size for the default database query.
     */
    public int $batchSize = 100;

    /**
     * @var string|null the default change frequency, leave empty to omit.
     */
    public ?string $defaultChangeFrequency = null;

    /**
     * @var float|null the default priority frequency, leave empty to omit. Valid values range from 0.0 to 1.0, the default
     * of a page is 0.5.
     */
    public ?float $defaultPriority = null;

    public function init(): void
    {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('SitemapBehavior::$callback must be callable.');
        }

        if (!$this->maxUrlCount) {
            $this->maxUrlCount = Yii::$app->sitemap->maxUrlCount;
        }

        parent::init();
    }

    public function generateSitemapUrls(int $offset = 0): array
    {
        $query = $this->owner->getSitemapQuery();
        $urls = [];

        if (Yii::$app->sitemap->useSitemapIndex) {
            $query->limit($this->maxUrlCount)
                ->offset($offset * $this->maxUrlCount);
        }

        foreach ($query->each($this->batchSize) as $model) {
            $result = call_user_func($this->callback, $model);

            if ($result) {
                foreach (is_int(key($result)) ? $result : [$result] as $data) {
                    if (isset($data['loc'])) {
                        $data['changefreq'] ??= $this->defaultChangeFrequency;
                        $data['priority'] ??= $this->defaultPriority;
                        $urls[] = array_filter($data);
                    }
                }
            }
        }

        return $urls;
    }

    public function getSitemapUrlCount(): int
    {
        $query = $this->owner->getSitemapQuery();
        return $query->count();
    }

    public function getSitemapQuery(): ActiveQuery
    {
        return $this->owner::find();
    }
}
