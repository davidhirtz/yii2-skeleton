<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ExpressionInterface;

/**
 * Class SitemapBehavior.
 * @package davidhirtz\yii2\skeleton\behaviors
 *
 * @see http://www.sitemaps.org/protocol.html
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
     * @var int
     */
    public $batchSize = 100;

    /**
     * @var string
     */
    public $defaultChangeFrequency;

    /**
     * @var float
     */
    public $defaultPriority;

    /**
     * @var callable
     */
    public $query;

    /**
     * @var callable must return single or nested array with URL or valid route as "loc" key.
     */
    public $callback;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('SitemapBehavior::$dataClosure isn\'t callable.');
        }

        parent::init();
    }

    /**
     * Generates XML site map urls from record.
     * @param int|ExpressionInterface|null $offset
     * @return array
     */
    public function generateSitemapUrls($offset = null)
    {
        $urls = [];

        /**
         * @var ActiveRecord $owner
         */
        $owner = $this->owner;
        $query = $owner::find()->offset($offset);

        if (is_callable($this->query)) {
            call_user_func($this->query, $query);
        }

        foreach ($query->each($this->batchSize) as $model) {
            $result = call_user_func($this->callback, $model);

            if ($result) {
                foreach (is_int(key($result)) ? $result : [$result] as $data) {
                    if (isset($data['loc'])) {
                        ArrayHelper::setDefaultValue($data, 'changefreq', $this->defaultChangeFrequency);
                        ArrayHelper::setDefaultValue($data, 'priority', $this->defaultPriority);

                        $urls[] = array_filter($data);
                    }
                }
            }
        }

        return $urls;
    }
}