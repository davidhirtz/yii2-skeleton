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
 * @property ActiveRecord $owner
 */
class SitemapBehavior extends Behavior
{
    public const CHANGE_FREQUENCY_ALWAYS = 'always';
    public const CHANGE_FREQUENCY_HOURLY = 'hourly';
    public const CHANGE_FREQUENCY_DAILY = 'daily';
    public const CHANGE_FREQUENCY_WEEKLY = 'weekly';
    public const CHANGE_FREQUENCY_MONTHLY = 'monthly';
    public const CHANGE_FREQUENCY_YEARLY = 'yearly';
    public const CHANGE_FREQUENCY_NEVER = 'never';

    /**
     * @var callable required method, that returns a single or nested array with sitemap URL or valid route as "loc"
     *     key.
     */
    public $callback;

    /**
     * @var int the maximum number rows selected by the default database query, if this is null the default value from
     * {@see Sitemap} will be used. Change this value if one record produces more than one URL (for example for
     * multiple languages).
     */
    public $maxUrlCount;

    /**
     * @var int the batch size for the default database query.
     */
    public $batchSize = 100;

    /**
     * @var string the default change frequency, leave empty to omit.
     */
    public $defaultChangeFrequency;

    /**
     * @var float the default priority frequency, leave empty to omit. Valid values range from 0.0 to 1.0, the default
     * of a page is 0.5.
     */
    public $defaultPriority;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('SitemapBehavior::$callback must be callable.');
        }

        if (!$this->maxUrlCount) {
            $this->maxUrlCount = Yii::$app->sitemap->maxUrlCount;
        }

        parent::init();
    }

    /**
     * Generates XML site map urls from record.
     *
     * @param int $offset
     * @return array
     */
    public function generateSitemapUrls($offset = 0)
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        /** @var ActiveQuery $query */
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

    /**
     * @return int
     */
    public function getSitemapUrlCount(): int
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        /** @var ActiveQuery $query */
        $query = $this->owner->getSitemapQuery();
        return $query->count();
    }

    /**
     * @return ActiveQuery
     */
    public function getSitemapQuery()
    {
        return $this->owner::find();
    }
}