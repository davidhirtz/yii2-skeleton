<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Exception;
use Override;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use Closure;

/**
 * @property string $trailModelName
 * @mixin ActiveRecord
 *
 * @extends Behavior<ActiveRecord&TrailModelInterface>
 */
class TrailBehavior extends Behavior
{
    /**
     * @var class-string|null if not set, the default class of `owner` will be used
     */
    public ?string $modelClass = null;

    /**
     * @var list<string> the attributes excluded from the default trail attributes
     */
    public array $exclude = [
        'id',
        'position',
        'updated_by_user_id',
        'updated_at',
        'created_at',
    ];

    #[Override]
    public function attach($owner): void
    {
        $this->modelClass ??= $owner::class;
        parent::attach($owner);
    }

    /**
     * @return array<string, string|Closure>
     */
    #[Override]
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

    /**
     * @param array<string, mixed> $changedAttributes
     */
    protected function onAfterSave(bool $insert, array $changedAttributes): void
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
            $trail = $this->createTrail($insert ? Trail::TYPE_CREATE : Trail::TYPE_UPDATE);
            $trail->data = $data;
            $this->insertTrail($trail);
        }
    }

    protected function onAfterDelete(): void
    {
        $this->insertTrail($this->createTrail(Trail::TYPE_DELETE));
    }

    /**
     * The type is taken here rather than assigned afterwards: it decides the class, see
     * {@see Trail::instantiate()}.
     */
    protected function createTrail(int $type): Trail
    {
        $trail = Trail::instantiateByType($type);
        $trail->model_class = $this->modelClass;

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
