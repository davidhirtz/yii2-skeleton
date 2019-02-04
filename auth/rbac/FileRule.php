<?php

namespace davidhirtz\yii2\skeleton\auth\rbac;

use davidhirtz\yii2\skeleton\modules\content\models\Category;
use davidhirtz\yii2\skeleton\modules\content\models\Page;
use davidhirtz\yii2\skeleton\modules\content\models\Section;
use yii\helpers\ArrayHelper;
use yii\rbac\Rule;
use Yii;

/**
 * Class FileRule.
 * @package davidhirtz\yii2\skeleton\auth\rbac
 */
class FileRule extends Rule
{
    /**
     * @var string
     */
    public $name = 'fileRule';

    /**
     * @inheritdoc
     */
    public function execute($userId, $item, $params)
    {
        /**
         * @var \davidhirtz\yii2\skeleton\modules\content\models\File $file
         */
        $file = ArrayHelper::getValue($params, 'file');
        return $file === null || $this->validateFileParent($file->getParent());
    }

    /**
     * @var \davidhirtz\yii2\skeleton\modules\content\models\Category|\davidhirtz\yii2\skeleton\modules\content\models\Page|\davidhirtz\yii2\skeleton\modules\content\models\Section $parent
     * @return bool
     */
    private function validateFileParent($parent)
    {
        switch (get_class($parent)) {
            case Category::class:

                if (Yii::$app->getUser()->can('categoryUpdate')) {
                    return true;
                }

                break;

            case Page::class:
            case Section::class:

                if (Yii::$app->getUser()->can('pageUpdate')) {
                    return true;
                }
        }

        return false;
    }
}