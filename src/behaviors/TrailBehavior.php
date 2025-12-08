<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\behaviors;

use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\interfaces\TrailModelInterface;
use Hirtz\Skeleton\models\Trail;
use Exception;
use Yii;
use yii\base\Behavior;
use yii\db\AfterSaveEvent;

/**
 * @property string $trailModelName
 * @property TrailModelInterface $owner
 * @mixin ActiveRecord
 */
class TrailBehavior extends Behavior
{
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

    #[\Override]
    public function attach($owner): void
    {
        $this->modelClass ??= $owner::class;
        parent::attach($owner);
    }

    #[\Override]
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => $this->onAfterInsert(...),
            ActiveRecord::EVENT_AFTER_UPDATE => $this->onAfterUpdate(...),
            ActiveRecord::EVENT_AFTER_DELETE => $this->onAfterDelete(...),
        ];
    }

    protected function onAfterInsert(AfterSaveEvent $event): void
    {
        $this->onAfterSave(true, $event->changedAttributes);
    }

    protected function onAfterUpdate(AfterSaveEvent $event): void
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
            } elseif ($changedAttributes[$attributeName] !== $this->owner->{$attributeName}) {
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

    protected function onAfterDelete(): void
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
}
