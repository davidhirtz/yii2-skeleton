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
use Closure;

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
     * @var class-string|null the class the documents are stored under; if not set, the registered class the owner
     * extends, so a container subclass or a per-type class writes the rows `search/rebuild` writes
     */
    public ?string $modelClass = null;

    /**
     * @var list<string> the attributes every searchable model reindexes on, whether it names them or not
     */
    final public const array STATE_ATTRIBUTES = [
        'status',
        'tenant_id',
        'type',
    ];

    /**
     * @var list<string> the attributes that change a document besides the searchable ones. A model whose searchable
     * attribute is a getter has to name the columns behind it here — the media `File` indexes `filename`, which is
     * `getFilename()`, so a rename would leave the index stale without `basename`.
     */
    public array $attributes = self::STATE_ATTRIBUTES;

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

        $documents = $this->owner->isSearchable() ? $this->owner->getSearchDocuments($this->getModelClass()) : [];

        if ($documents) {
            $search->getDriver()->index(...$documents);
            return;
        }

        $this->deleteDocuments();
    }

    protected function deleteDocuments(): void
    {
        $this->getSearch()->getDriver()->delete($this->getModelClass(), (int)$this->owner->getPrimaryKey());
    }

    /**
     * @return class-string<SearchableInterface>
     */
    protected function getModelClass(): string
    {
        /** @var class-string<SearchableInterface> $modelClass */
        $modelClass = $this->modelClass ??= $this->getSearch()->getRegisteredClass($this->owner::class);
        return $modelClass;
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
