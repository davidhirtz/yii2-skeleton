<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;

/**
 * Class WidgetConfigTrait.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets
 */
trait WidgetConfigTrait
{
    /**
     * @param array $config
     * @return static
     */
    public static function begin($config = [])
    {
        return parent::begin(static::getConfig($config));
    }

    /**
     * @param array $config
     * @return string
     */
    static public function widget($config = []): string
    {
        return parent::widget(static::getConfig($config));
    }

    /**
     * @param array $config
     * @return array
     */
    protected static function getConfig($config): array
    {
        $className = get_called_class();
        return isset(Yii::$app->widgets[$className]) ? ArrayHelper::merge(Yii::$app->widgets[$className], $config) : $config;
    }
}
