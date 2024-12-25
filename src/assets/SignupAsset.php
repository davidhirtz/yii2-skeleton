<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\helpers\Json;
use yii\web\AssetBundle;

class SignupAsset extends AssetBundle
{
    public string $module = 'signup.js';
    public $sourcePath = '@skeleton/assets/signup/dist';

    public function getModuleUrl(): string
    {
        return $this->baseUrl . '/' . $this->module;
    }

    public static function registerModule(string|array|null $options = null): void
    {
        $asset = static::register($view = Yii::$app->getView());
        $options = $options ? Json::htmlEncode($options) : '';

        $view->registerJs("import m from '{$asset->getModuleUrl()}';m($options);", $view::POS_MODULE);
    }
}
