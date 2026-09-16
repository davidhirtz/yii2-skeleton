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

    /**
     * @var list<string>|null applied migrations whose class cannot be loaded, which means this database has
     * not been upgraded to the namespaces this code uses.
     */
    protected ?array $unresolved = null;

    #[Override]
    protected function configure(): void
    {
        $this->pending ??= $this->findPendingMigrations();
        $this->unresolved ??= $this->findUnresolvedMigrations();

        if ($this->pending || $this->unresolved) {
            $this->icon ??= 'exclamation-triangle';

            if (!$this->content) {
                // An unresolved history is the louder of the two and answers for the pending count as well:
                // every migration looks pending to a database that predates the namespace rename.
                $this->addText($this->unresolved !== []
                    ? Yii::t('skeleton', 'MIGRATION_ALERT_UNRESOLVED_MESSAGE', [
                        'count' => count($this->unresolved),
                    ])
                    : Yii::t('skeleton', 'MIGRATION_ALERT_MESSAGE', [
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

    /**
     * @return list<string>
     */
    protected function findUnresolvedMigrations(): array
    {
        $history = Yii::createObject(MigrationHistory::class, [Yii::$app->getDb()]);
        return $history->getUnresolved();
    }

    /**
     * @return list<string>
     */
    public function getUnresolved(): array
    {
        return $this->unresolved ?? [];
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->pending || $this->unresolved
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
        return ($this->pending || $this->unresolved) && parent::isVisible();
    }
}
