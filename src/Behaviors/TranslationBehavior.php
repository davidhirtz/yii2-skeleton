<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Translation;
use yii\base\Behavior;
use yii\db\AfterSaveEvent;

/**
 * Attach before {@see TrailBehavior}: the changes are appended to the event, and handlers run in attach order.
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
