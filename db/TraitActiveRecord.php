<?php

namespace davidhirtz\yii2\skeleton\db;

/**
 * Class TraitActiveRecord.
 * @package davidhirtz\yii2\skeleton\db
 *
 * Note: This class is only used to inherit active record methods
 * for PhpStorm's trait autocomplete. It's not an actual class.
 */
abstract class TraitActiveRecord extends ActiveRecord
{
    use NestedTreeTrait, I18nAttributesTrait;
}