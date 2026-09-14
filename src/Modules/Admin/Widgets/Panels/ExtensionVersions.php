<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Traits\CollapsedTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * The installed extensions as a wrapped row of badges, so a maintainer can read off the version a client's
 * installation actually runs without the list taking a screen of its own.
 */
class ExtensionVersions extends Widget
{
    use ContainerTrait;
    use CollapsedTrait;
    use TitleTrait;

    /**
     * @var list<string> the package vendors to leave out, since the framework's own version is reported separately.
     */
    public array $ignoredVendors = ['yiisoft'];

    /**
     * @var array<string, string>|null
     */
    protected ?array $extensions = null;

    public function __construct(array $config = [])
    {
        $this->title ??= Yii::t('skeleton', 'SYSTEM_EXTENSIONS');
        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->extensions ??= $this->findExtensions();
        parent::configure();
    }

    /**
     * @param array<string, string>|Closure(static): (void|static) $extensions
     */
    public function extensions(array|Closure $extensions): static
    {
        if ($extensions instanceof Closure) {
            return $this->prepare($extensions);
        }

        $this->extensions = $extensions;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getExtensions(): array
    {
        return $this->extensions ?? [];
    }

    /**
     * @return array<string, string>
     */
    protected function findExtensions(): array
    {
        $extensions = [];

        foreach (VersionHelper::getExtensions() as $name => $version) {
            if (!in_array(strtok($name, '/'), $this->ignoredVendors, true)) {
                $extensions[$name] = $version;
            }
        }

        return $extensions;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->extensions) {
            return '';
        }

        $list = Div::make()->class('badge-list');

        foreach ($this->extensions as $name => $version) {
            $list->addContent(Span::make()
                ->class('badge badge-info')
                ->attribute('title', $name)
                ->addText(basename($name))
                ->addContent(Span::make()
                    ->class('badge-value')
                    ->text($version)));
        }

        return Card::make()
            ->title($this->title)
            ->collapsed($this->collapsed)
            ->content($list);
    }
}
