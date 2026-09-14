<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class DirectoryAlert extends Widget
{
    use ContainerTrait;
    use TagContentTrait;
    use IconTrait;

    /**
     * @var list<string> the path aliases whose directories have to be writable. A bundle adds its own upload path
     * through `EVENT_CONFIGURE`.
     */
    public array $directories = [
        '@runtime',
        '@webroot/assets',
    ];

    /**
     * @var list<string>|null
     */
    protected ?array $unwritable = null;

    #[Override]
    protected function configure(): void
    {
        $this->unwritable ??= $this->findUnwritableDirectories();

        if ($this->unwritable) {
            $this->icon ??= 'exclamation-triangle';

            if (!$this->content) {
                $this->addText(Yii::t('skeleton', 'DIRECTORY_ALERT_MESSAGE', [
                    'count' => count($this->unwritable),
                    'directories' => implode(', ', $this->unwritable),
                ]));
            }
        }

        parent::configure();
    }

    /**
     * @param list<string>|null $unwritable
     */
    public function unwritable(?array $unwritable): static
    {
        $this->unwritable = $unwritable;
        return $this;
    }

    /**
     * @return list<string>
     */
    public function getUnwritable(): array
    {
        return $this->unwritable ?? [];
    }

    /**
     * @return list<string> the resolved paths, since that is what has to be created or chmodded.
     */
    protected function findUnwritableDirectories(): array
    {
        $unwritable = [];

        foreach ($this->directories as $alias) {
            $path = Yii::getAlias($alias, false);

            if (!is_string($path)) {
                $unwritable[] = $alias;
            } elseif (!is_dir($path) || !is_writable($path)) {
                $unwritable[] = $path;
            }
        }

        return $unwritable;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->unwritable
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
        return (bool)$this->unwritable && parent::isVisible();
    }
}
