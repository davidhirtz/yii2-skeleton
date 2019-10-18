<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class CKEditorBootstrapAsset
 * @package davidhirtz\yii2\skeleton\assets
 */
class CKEditorBootstrapAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/ckeditor-bootstrap/';

    /**
     * @var array
     */
    public $publishOptions = [
        'except' => [
            'scss/',
        ],
    ];
}