<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * The installed extensions as a wrapped row of badges, so a maintainer can read off the version a client's
 * installation runs without the list taking a card of its own. The version is the badge's tooltip.
 */
class ExtensionVersions extends Widget
{
    /**
     * @var list<string> the packages to leave out, matched against the full name with `fnmatch()`. The framework's
     * own extensions carry its version and `yii2-datetime-behavior` is a dependency rather than a bundle.
     */
    public array $excluded = [
        'yiisoft/*',
        'davidhirtz/yii2-datetime-behavior',
    ];

    /**
     * @var array<string, string>|null
     */
    protected ?array $extensions = null;

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
            foreach ($this->excluded as $pattern) {
                if (fnmatch($pattern, $name)) {
                    continue 2;
                }
            }

            $extensions[$name] = $version;
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
                ->addAttributes(['data-tooltip' => '', 'title' => $version])
                ->text(basename($name)));
        }

        return $list;
    }
}
