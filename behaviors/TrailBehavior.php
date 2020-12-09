<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use DateTime;
use DateTimeZone;
use davidhirtz\yii2\datetime\DateTimeValidator;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Trail;
use ReflectionClass;
use Yii;
use yii\base\Behavior;
use yii\db\AfterSaveEvent;
use yii\helpers\Inflector;
use yii\validators\BooleanValidator;
use yii\validators\RangeValidator;

/**
 * Class TrailBehavior
 * @package davidhirtz\yii2\skeleton\behaviors
 *
 * @property string $trailModelName
 * @property ActiveRecord $owner
 */
class TrailBehavior extends Behavior
{
    private const VALUE_TYPE_BOOLEAN = 'bool';
    private const VALUE_TYPE_RANGE = 'range';
    private const VALUE_TYPE_DATETIME = 'datetime';

    /**
     * If `model` is not set, the default class of `owner` will be used
     * @var string
     */
    public $model;

    /**
     * The excluded default attributes if the owner class does not override {@link TrailBehavior::getTrailAttributes()}
     * @var string[]
     */
    public $exclude = [
        'position',
        'updated_by_user_id',
        'updated_at',
        'created_at',
    ];

    /**
     * @var string {@see TrailBehavior::getTrailModelName()}
     */
    private $_trailModelName;

    /**
     * @var array
     */
    private static $_modelAttributes = [];

    /**
     * @return array|string[]
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function afterInsert($event)
    {
        $this->afterSave(true, $event->changedAttributes);
    }


    /**
     * @param AfterSaveEvent $event
     */
    public function afterUpdate($event)
    {
        $this->afterSave(false, $event->changedAttributes);
    }


    /**
     */
    public function afterDelete()
    {
        $trail = $this->createTrail();
        $trail->type = Trail::TYPE_DELETE;
        $trail->insert();
    }

    /**
     * @param $insert
     * @param $changedAttributes
     */
    protected function afterSave($insert, $changedAttributes)
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $attributes = $this->owner->getTrailAttributes();
        $attributeNames = is_array($attributes) ? array_intersect($attributes, array_keys($changedAttributes)) : array_keys($changedAttributes);
        $data = [];

        foreach ($attributeNames as $attributeName) {
            $data[$attributeName] = $insert ? $this->owner->{$attributeName} : [$changedAttributes[$attributeName], $this->owner->{$attributeName}];
        }

        if ($insert) {
            $data = array_filter($data);
        }

        if ($data) {
            $trail = $this->createTrail();
            $trail->type = $insert ? Trail::TYPE_CREATE : Trail::TYPE_UPDATE;
            $trail->data = $data;
            $trail->insert();
        }
    }

    /**
     * @return Trail
     */
    protected function createTrail()
    {
        $trail = new Trail();
        $trail->model = $this->model ?: get_class($this->owner);
        $trail->model_id = $this->owner->getPrimaryKey(true);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $trail->parents = $this->owner->getTrailParents();

        return $trail;
    }

    /**
     * Returns the attributes that trigger a {@link Trail::TYPE_CREATE} or {@link Trail::TYPE_UPDATE} record. In the
     * default implementation this includes all attributes except attributes defined in {@link TrailBehavior::$exclude}.
     * This method can be overridden by the owner class to provide a more defined list of values which should be
     * logged.
     *
     * @return array
     */
    public function getTrailAttributes()
    {
        return array_diff($this->owner->attributes(), $this->exclude);
    }

    /**
     * This method can be overridden by the owner class to provide a more detailed description of the model
     * @return string|null
     */
    public function getTrailModelName()
    {
        if ($this->_trailModelName === null) {
            $this->_trailModelName = (new ReflectionClass($this->owner))->getShortName();
        }

        return $this->_trailModelName;
    }

    /**
     * This method can be overridden by the owner class to provide additional information about the model
     * @return string|void
     */
    public function getTrailModelType()
    {
    }

    /**
     * This method can be overridden by the owner class to provide a route to the admin route of the model
     * @return array|false
     */
    public function getTrailModelAdminRoute()
    {
        return false;
    }

    /**
     * This method can be overridden by the owner class to provide a real parent class
     * @return array|void
     */
    public function getTrailParents()
    {
        return null;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return string
     */
    public function formatTrailAttributeValue($attribute, $value)
    {
//        if ($attribute == 'status' && $this->owner->hasMethod('getStatuses')) {
//            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
//            return $this->owner::getStatuses()[$value]['name'] ?? $value;
//        }
//
//        if ($attribute == 'type' && $this->owner->hasMethod('getTypes')) {
//            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
//            return $this->owner::getTypes()[$value]['name'] ?? $value;
//        }

        switch ($this->getDefaultAttributeValues()[$attribute] ?? false) {
            case static::VALUE_TYPE_BOOLEAN:
                return $value ? Yii::t('yii', 'Yes') : Yii::t('yii', 'No');

            case static::VALUE_TYPE_DATETIME:
                return isset($value['date']) ? Yii::$app->getFormatter()->asDatetime(new DateTime($value['date'], new DateTimeZone($date['timezone'] ?? Yii::$app->timeZone)), 'medium') : $value;

            case static::VALUE_TYPE_RANGE:
                $rangeClass = 'get' . Inflector::camelize(Inflector::pluralize($attribute));
                return $this->owner->hasMethod($rangeClass) ? ($this->owner::$rangeClass()[$value]['name'] ?? $value) : $value;
        }

        return is_array($value) ? print_r($value, true) : (string)$value;
    }

    /**
     * Cycles through the owner model validators to detect default display values for attribute names.
     * @return array
     */
    protected function getDefaultAttributeValues()
    {
        $className = get_class($this->owner);

        if (!isset(static::$_modelAttributes[$className])) {
            // Common fields `status` and `type` often use the related Trait instead of the `RangeValidator`. This makes
            // sure they are still treated as such.
            $attributes = [
                'status' => static::VALUE_TYPE_RANGE,
                'type' => static::VALUE_TYPE_RANGE,
            ];

            $types = [
                static::VALUE_TYPE_BOOLEAN => BooleanValidator::class,
                static::VALUE_TYPE_DATETIME => DateTimeValidator::class,
                static::VALUE_TYPE_RANGE => RangeValidator::class,
            ];

            foreach ($this->owner->getValidators() as $validator) {
                foreach ($types as $type => $instance) {
                    if ($validator instanceof $instance) {
                        foreach ((array)$validator->attributes as $attribute) {
                            $attributes[$attribute] = $type;
                        }
                    }
                }
            }

            static::$_modelAttributes[$className] = $attributes;
        }

        return static::$_modelAttributes[$className];
    }
}