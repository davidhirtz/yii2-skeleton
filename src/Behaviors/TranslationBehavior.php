<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Translation;
use yii\base\Behavior;
use yii\db\AfterSaveEvent;

/**
 * Persists and deletes the {@see Translation} records of a {@see TranslationInterface} model.
 *
 * This must be attached before {@see TrailBehavior}: it appends the translation changes to the event's changed
 * attributes and Yii calls the handlers in attach order, which is what makes the trail log `name_de` the way it
 * logged the column.
 *
 * @extends Behavior<ActiveRecord&TranslationInterface>
 */
class TranslationBehavior extends Behavior
{
    #[\Override]
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => $this->onAfterSave(...),
            ActiveRecord::EVENT_AFTER_UPDATE => $this->onAfterSave(...),
            ActiveRecord::EVENT_AFTER_DELETE => $this->onAfterDelete(...),
        ];
    }

    protected function onAfterSave(AfterSaveEvent $event): void
    {
        $event->changedAttributes = [
            ...$event->changedAttributes,
            ...$this->owner->saveTranslations(),
        ];

        $this->owner->updateOldTranslationAttributes();
    }

    protected function onAfterDelete(): void
    {
        $this->owner->deleteTranslations();
    }
}
