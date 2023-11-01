<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use DateTime;
use DateTimeZone;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Trail;
use Exception;
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
     * @var class-string|null if not set, the default class of `owner` will be used
     */
    public ?string $modelClass = null;

    /**
     * The excluded default attributes if the owner class does not override {@link TrailBehavior::getTrailAttributes()}
     */
    public array $exclude = [
        'position',
        'updated_by_user_id',
        'updated_at',
        'created_at',
    ];

    private ?string $_trailModelName = null;
    private static array $_modelAttributes = [];

    public function attach($owner): void
    {
        $this->modelClass ??= $owner::class;
        $this->sanitizeModelClass();

        parent::attach($owner);
    }

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /** @noinspection PhpUnused */
    public function afterInsert(AfterSaveEvent $event): void
    {
        $this->afterSave(true, $event->changedAttributes);
    }

    /** @noinspection PhpUnused */
    public function afterUpdate(AfterSaveEvent $event): void
    {
        $this->afterSave(false, $event->changedAttributes);
    }

    protected function afterSave($insert, $changedAttributes): void
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $attributes = $this->owner->getTrailAttributes();
        $attributeNames = $attributes ? array_intersect($attributes, array_keys($changedAttributes)) : array_keys($changedAttributes);
        $data = [];

        foreach ($attributeNames as $attributeName) {
            if ($insert) {
                $data[$attributeName] = $this->owner->{$attributeName};
            } elseif ($changedAttributes[$attributeName] != $this->owner->{$attributeName}) {
                $data[$attributeName] = [$changedAttributes[$attributeName], $this->owner->{$attributeName}];
            }
        }

        if ($insert) {
            $data = array_filter($data);
        }

        if ($data) {
            $trail = $this->createTrail();
            $trail->type = $insert ? Trail::TYPE_CREATE : Trail::TYPE_UPDATE;
            $trail->data = $data;
            $this->insertTrail($trail);
        }
    }

    public function afterDelete(): void
    {
        $trail = $this->createTrail();
        $trail->type = Trail::TYPE_DELETE;
        $this->insertTrail($trail);
    }

    /**
     * Tries to find the original model class by the definition in the DI container
     */
    protected function sanitizeModelClass(): void
    {
        foreach (Yii::$container->getDefinitions() as $definition => $options) {
            if ($options['class'] ?? null === $this->modelClass) {
                $this->modelClass = $definition;
                break;
            }
        }
    }

    protected function createTrail(): Trail
    {
        $trail = Trail::create();
        $trail->model = $this->modelClass;
        $trail->model_id = $this->owner instanceof ActiveRecord ? $this->owner->getPrimaryKey(true) : null;

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $trail->parents = $this->owner->getTrailParents();

        return $trail;
    }

    protected function insertTrail(Trail $trail): void
    {
        try {
            $trail->insert();
        } catch (Exception $exception) {
            Yii::error($exception);
        }
    }

    /**
     * Returns the attributes that trigger a {@link Trail::TYPE_CREATE} or {@link Trail::TYPE_UPDATE} record. In the
     * default implementation this includes all attributes except attributes defined in {@link TrailBehavior::$exclude}.
     * This method can be overridden by the owner class to provide a more defined list of values which should be
     * logged.
     *
     * @return array
     */
    public function getTrailAttributes(): array
    {
        return array_diff($this->owner->attributes(), $this->exclude);
    }

    /**
     * This method can be overridden by the owner class to provide a more detailed description of the model
     */
    public function getTrailModelName(): string
    {
        $this->_trailModelName ??= (new ReflectionClass($this->owner))->getShortName();
        return $this->_trailModelName;
    }

    /**
     * This method can be overridden by the owner class to provide additional information about the model
     */
    public function getTrailModelType(): ?string
    {
        return null;
    }

    /**
     * This method can be overridden by the owner class to provide a route to the admin route of the model
     */
    public function getTrailModelAdminRoute(): array|false
    {
        return false;
    }

    /**
     * This method can be overridden by the owner class to provide a real parent class
     */
    public function getTrailParents(): ?array
    {
        return null;
    }

    public function formatTrailAttributeValue(string $attribute, mixed $value): string
    {
        switch ($this->getDefaultAttributeValues()[$attribute] ?? false) {
            case static::VALUE_TYPE_BOOLEAN:
                return $value ? Yii::t('yii', 'Yes') : Yii::t('yii', 'No');

            case static::VALUE_TYPE_DATETIME:
                return isset($value['date']) ? Yii::$app->getFormatter()->asDatetime(new DateTime($value['date'], new DateTimeZone($value['timezone'] ?? Yii::$app->timeZone)), 'medium') : $value;

            case static::VALUE_TYPE_RANGE:
                $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));

                if ($this->owner->hasMethod($method)) {
                    if ($value = ($this->owner->{$method}()[$value] ?? false)) {
                        // Return string value or "name" key, as a fallback print out the array content
                        return is_string($value) ? $value : ($value['name'] ?? print_r($value, true));
                    }
                }

                return $value;
        }

        return is_array($value) ? print_r($value, true) : (string)$value;
    }

    /**
     * Cycles through the owner model validators to detect default display values for attribute names.
     */
    protected function getDefaultAttributeValues(): array
    {
        $className = $this->owner::class;

        if (!isset(static::$_modelAttributes[$className])) {
            $attributes = [];

            $types = [
                static::VALUE_TYPE_BOOLEAN => BooleanValidator::class,
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

            if ($this->owner instanceof ActiveRecord) {
                $schema = Yii::$app->getDb()->getSchema();
                $columns = $schema->getTableSchema($className::tableName())->columns;

                foreach ($columns as $column) {
                    if (in_array($column->dbType, [$schema::TYPE_DATE, $schema::TYPE_DATETIME, $schema::TYPE_TIMESTAMP])) {
                        $attributes[$column->name] = static::VALUE_TYPE_DATETIME;
                    }
                }
            }

            static::$_modelAttributes[$className] = $attributes;
        }

        return static::$_modelAttributes[$className];
    }
}