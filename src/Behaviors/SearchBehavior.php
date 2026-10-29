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
 * Writes bypassing `save()` (`updateAll()`, `updateAttributes()`, cms `parent_status`) leave the index stale until
 * `search/rebuild`.
 *
 * @extends Behavior<ActiveRecord&SearchableInterface>
 */
class SearchBehavior extends Behavior
{
    /**
     * Defaults to the registered class the owner extends, so a container subclass writes the rows `search/rebuild`
     * writes.
     *
     * @var class-string|null
     */
    public ?string $modelClass = null;

    /**
     * @var list<string>
     */
    final public const array STATE_ATTRIBUTES = [
        'status',
        'tenant_id',
        'type',
    ];

    /**
     * A searchable getter needs its columns named here, as the media `File` names `basename` behind `getFilename()`.
     *
     * @var list<string>
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
