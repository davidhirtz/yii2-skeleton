<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\behaviors;

use DateTime;
use DateTimeZone;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\collections\TrailModelCollection;
use davidhirtz\yii2\skeleton\models\Trail;
use Exception;
use ReflectionClass;
use Yii;
use yii\base\Behavior;
use yii\base\Model;
use yii\db\AfterSaveEvent;
use yii\helpers\Inflector;
use yii\validators\BooleanValidator;
use yii\validators\RangeValidator;

/**
 * @property string $trailModelName
 * @property ActiveRecord|TrailBehavior $owner
 * @mixin Model
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
     * @array containing the excluded default attributes if the owner class does not override
     * {@see TrailBehavior::getTrailAttributes()}
     */
    public array $exclude = [
        'id',
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
        parent::attach($owner);
    }

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => $this->onAfterInsert(...),
            ActiveRecord::EVENT_AFTER_UPDATE => $this->onAfterUpdate(...),
            ActiveRecord::EVENT_AFTER_DELETE => $this->onAfterDelete(...),
        ];
    }

    public function onAfterInsert(AfterSaveEvent $event): void
    {
        $this->onAfterSave(true, $event->changedAttributes);
    }

    public function onAfterUpdate(AfterSaveEvent $event): void
    {
        $this->onAfterSave(false, $event->changedAttributes);
    }

    protected function onAfterSave($insert, $changedAttributes): void
    {
        $data = [];

        $attributes = $this->owner->getTrailAttributes();
        $attributeNames = $attributes
            ? array_intersect($attributes, array_keys($changedAttributes))
            : array_keys($changedAttributes);

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

    public function onAfterDelete(): void
    {
        $trail = $this->createTrail();
        $trail->type = Trail::TYPE_DELETE;
        $this->insertTrail($trail);
    }

    protected function createTrail(): Trail
    {
        $trail = Trail::create();
        $trail->model = $this->modelClass;

        if ($this->owner instanceof ActiveRecord) {
            $trail->model_id = $this->owner->getPrimaryKey(true);
        }

        $trail->parents = $this->owner->getTrailParents();

        return $trail;
    }

    protected function insertTrail(Trail $trail): void
    {
        try {
            $trail->insert();
        } catch (Exception $exception) {
            Yii::error($exception->getMessage());
        }
    }

    /**
     * Returns the attributes that trigger a {@see Trail::TYPE_CREATE} or {@see Trail::TYPE_UPDATE} record. In the
     * default implementation this includes all attributes except attributes defined in {@see TrailBehavior::$exclude}.
     * This method can be overridden by the owner class to provide a more defined list of values which should be
     * logged.
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
        return method_exists($this->owner, 'getAdminRoute') ? $this->owner->getAdminRoute() : false;
    }

    /**
     * This method can be overridden by the owner class to provide a real parent class
     */
    public function getTrailParents(): ?array
    {
        return null;
    }

    /**
     * This is the fallback method to format the value based on the attribute name. It can be overridden by the owner
     * class to provide a more detailed description of the attribute value.
     */
    public function formatTrailAttributeValue(string $attribute, mixed $value): mixed
    {
        switch ($this->getDefaultAttributeValues()[$attribute] ?? null) {
            case self::VALUE_TYPE_BOOLEAN:
                return $value ? Yii::t('yii', 'Yes') : Yii::t('yii', 'No');

            case self::VALUE_TYPE_DATETIME:
                return is_array($value) && isset($value['date'])
                    ? Yii::$app->getFormatter()->asDatetime(new DateTime($value['date'], new DateTimeZone($value['timezone'] ?? Yii::$app->timeZone)), 'medium')
                    : $value;

            case self::VALUE_TYPE_RANGE:
                $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));

                if ($this->owner->hasMethod($method)) {
                    if ($value = ($this->owner->{$method}()[$value] ?? false)) {
                        // Return string value or "name" key, as a fallback print out the array content
                        return is_string($value) ? $value : ($value['name'] ?? print_r($value, true));
                    }
                }

                return $value;
        }

        if ($this->owner instanceof ActiveRecord) {
            if ($relation = $this->owner->getRelationFromForeignKey($attribute)) {
                return TrailModelCollection::getModelByNameAndId($relation->modelClass, $value);
            }
        }

        return is_array($value) ? print_r($value, true) : (string)$value;
    }

    /**
     * Cycles through the owner model validators to detect default display values for attribute names.
     */
    protected function getDefaultAttributeValues(): array
    {
        $className = $this->owner::class;

        if (!isset(self::$_modelAttributes[$className])) {
            $attributes = [];

            $types = [
                self::VALUE_TYPE_BOOLEAN => BooleanValidator::class,
                self::VALUE_TYPE_RANGE => RangeValidator::class,
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
                $columns = $schema->getTableSchema($this->owner::tableName())->columns;

                $dateTypes = [
                    $schema::TYPE_DATE,
                    $schema::TYPE_DATETIME,
                    $schema::TYPE_TIMESTAMP,
                ];

                foreach ($columns as $column) {
                    if (in_array($column->dbType, $dateTypes)) {
                        $attributes[$column->name] = self::VALUE_TYPE_DATETIME;
                    }
                }
            }

            self::$_modelAttributes[$className] = $attributes;
        }

        return self::$_modelAttributes[$className];
    }
}
