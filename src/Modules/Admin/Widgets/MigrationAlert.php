<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class MigrationAlert extends Widget
{
    use ContainerTrait;
    use TagContentTrait;
    use IconTrait;

    /**
     * @var list<string>|null
     */
    protected ?array $pending = null;

    #[Override]
    protected function configure(): void
    {
        $this->pending ??= $this->findPendingMigrations();

        if ($this->pending) {
            $this->icon ??= 'exclamation-triangle';

            if (!$this->content) {
                $this->addText(Yii::t('skeleton', 'MIGRATION_ALERT_MESSAGE', [
                    'count' => count($this->pending),
                ]));
            }
        }

        parent::configure();
    }

    /**
     * @param list<string>|null $pending
     */
    public function pending(?array $pending): static
    {
        $this->pending = $pending;
        return $this;
    }

    /**
     * @return list<string>
     */
    public function getPending(): array
    {
        return $this->pending ?? [];
    }

    /**
     * @return list<string>
     */
    protected function findPendingMigrations(): array
    {
        $history = Yii::createObject(MigrationHistory::class, [Yii::$app->getDb()]);
        return $history->getPending();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->pending
            ? Alert::make()
                ->attributes($this->attributes)
                ->content(...$this->content)
                ->danger()
                ->icon($this->icon)
            : '';
    }

    #[Override]
    public function isVisible(): bool
    {
        return (bool)$this->pending && parent::isVisible();
    }
}
