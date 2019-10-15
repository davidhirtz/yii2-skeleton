<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class CKEditorBootstrapAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class CKEditorBootstrapAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/ckeditor-bootstrap/';

    /**
     * @var string
     */
    public $editorAssetBundle = 'davidhirtz\yii2\skeleton\assets\AdminAsset';

    /**
     * @var array
     */
    public $depends = [
        '\dosamigos\ckeditor\CKEditorAsset',
    ];
}