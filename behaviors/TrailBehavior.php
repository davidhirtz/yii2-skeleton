<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Trail;
use ReflectionClass;
use yii\base\Behavior;
use yii\db\AfterSaveEvent;

/**
 * Class TrailBehavior
 * @package davidhirtz\yii2\skeleton\behaviors
 *
 * @property string $trailModelName
 * @property ActiveRecord $owner
 */
class TrailBehavior extends Behavior
{
    /**
     * @var string
     */
    public $model;

    /**
     * @var ActiveRecord
     */
    public $parent;

    /**
     * @var array containing the attributes that trigger a {@link Trail::TYPE_INSERT} or {@link Trail::TYPE_UPDATE}
     * record. If empty all attributes are used. This list is checked against {@link TrailBehavior::$exclude}.
     */
    public $attributes;

    /**
     * @var string[]
     */
    public $exclude = ['updated_by_user_id', 'updated_at', 'created_at'];

    /**
     * @var string {@see TrailBehavior::getTrailModelName()}
     */
    private $_trailModelName;

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
     * @param AfterSaveEvent $event
     */
    public function afterDelete($event)
    {
    }

    /**
     * @param $insert
     * @param $changedAttributes
     */
    protected function afterSave($insert, $changedAttributes)
    {
        $attributeNames = is_array($this->attributes) ? array_intersect($this->attributes, array_keys($changedAttributes)) : array_keys($changedAttributes);

        if (is_array($this->exclude)) {
            $attributeNames = array_diff($attributeNames, $this->exclude);
        }

        $data = [];

        foreach ($attributeNames as $attributeName) {
            $data[$attributeName] = [$changedAttributes[$attributeName], $this->owner->{$attributeName}];
        }

        if ($data) {
            $trail = new Trail();
            $trail->model = $this->model ?: get_class($this->owner);
            $trail->model_id = implode('-', $this->owner->getPrimaryKey(true));
            $trail->related = $this->parent;
            $trail->type = $insert ? Trail::TYPE_INSERT : Trail::TYPE_UPDATE;
            $trail->data = $data;
            $trail->insert();
        }
    }

    /**
     * @return string
     */
    public function getTrailModelName(): string
    {
        if ($this->_trailModelName === null) {
            $this->_trailModelName = (new ReflectionClass($this->owner))->getShortName();
        }

        return $this->_trailModelName;
    }
}