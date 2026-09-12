<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Search\Search;
use Override;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;

/**
 * Attached by {@see \Hirtz\Skeleton\Db\ActiveRecord::behaviors()} to every {@see SearchableInterface}. Unlike the
 * translation write there is no ordering constraint against {@see TrailBehavior}, so a behavior is enough.
 *
 * Writes that bypass `save()` leave the index stale — `updateAll()`, `updateAttributes()`, `batchInsert()` and the
 * `parent_status` propagation in cms; `search/rebuild` reconciles.
 *
 * @extends Behavior<ActiveRecord&SearchableInterface>
 */
class SearchBehavior extends Behavior
{
    /**
     * @var class-string|null if not set, the default class of `owner` will be used
     */
    public ?string $modelClass = null;

    /**
     * @var list<string> the attributes that change a document besides the searchable ones
     */
    public array $attributes = [
        'status',
        'tenant_id',
        'type',
    ];

    #[Override]
    public function attach($owner): void
    {
        $this->modelClass ??= $owner::class;
        parent::attach($owner);
    }

    #[Override]
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => $this->onAfterInsert(...),
            ActiveRecord::EVENT_AFTER_UPDATE => $this->onAfterUpdate(...),
            ActiveRecord::EVENT_AFTER_DELETE => $this->onAfterDelete(...),
        ];
    }

    protected function onAfterInsert(): void
    {
        $this->index();
    }

    protected function onAfterUpdate(AfterSaveEvent $event): void
    {
        if ($this->hasChangedSearchAttributes($event->changedAttributes)) {
            $this->index();
        }
    }

    protected function onAfterDelete(): void
    {
        if ($this->getSearch()->isEnabled()) {
            $this->deleteDocuments();
        }
    }

    protected function index(): void
    {
        $search = $this->getSearch();

        if (!$search->isEnabled()) {
            return;
        }

        $documents = $this->owner->isSearchable() ? $this->owner->getSearchDocuments($this->modelClass) : [];

        if ($documents) {
            $search->getDriver()->index(...$documents);
            return;
        }

        $this->deleteDocuments();
    }

    protected function deleteDocuments(): void
    {
        /** @var class-string $modelClass */
        $modelClass = $this->modelClass;
        $this->getSearch()->getDriver()->delete($modelClass, (int)$this->owner->getPrimaryKey());
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    protected function hasChangedSearchAttributes(array $changedAttributes): bool
    {
        $owner = $this->owner;
        $attributes = $owner->getSearchAttributes();

        if ($owner instanceof I18nAttributeInterface) {
            $attributes = $owner->getI18nAttributesNames($attributes);
        }

        return (bool)array_intersect([...$attributes, ...$this->attributes], array_keys($changedAttributes));
    }

    protected function getSearch(): Search
    {
        return Search::getComponent();
    }
}
