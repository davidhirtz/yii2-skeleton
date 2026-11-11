<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;
use Override;
use Stringable;
use Yii;

/**
 * Bundles add their checks to the one panel; a check counting nothing is left out, and with it an empty panel.
 */
class ContentHealthPanel extends DashboardPanel
{
    /**
     * @var array<int|string, array{count: Closure(): int, text: Closure(int): string, url: array<int|string, mixed>|string, roles: list<string>, icon: string}>
     */
    protected array $checks = [];

    /**
     * The count is called only for a user holding one of the roles, since the link leads behind them. The text
     * follows the count, which the panel renders in bold, so it leaves the number out.
     *
     * @param Closure(): int $count
     * @param Closure(int): string $text
     * @param array<int|string, mixed>|string $url
     * @param list<string> $roles
     */
    public function addCheck(
        string $name,
        Closure $count,
        Closure $text,
        array|string $url,
        array $roles = [],
        string $icon = 'circle-exclamation',
    ): static {
        $this->checks[$name] = [
            'count' => $count,
            'text' => $text,
            'url' => $url,
            'roles' => $roles,
            'icon' => $icon,
        ];

        return $this;
    }

    public function removeCheck(string ...$names): static
    {
        foreach ($names as $name) {
            unset($this->checks[$name]);
        }

        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'DASHBOARD_HEALTH_TITLE');
        $this->icon ??= 'list-check';

        parent::configure();
    }

    #[Override]
    protected function getBody(): string|Stringable
    {
        $list = Ul::make()->class('dashboard-list');
        $isEmpty = true;

        foreach ($this->checks as $check) {
            if (!$this->canAccess($check['roles'])) {
                continue;
            }

            $count = $check['count']();

            if ($count > 0) {
                $list->addContent(Li::make()->content(A::make()
                    ->class('dashboard-list-row')
                    ->href($check['url'])
                    ->content(
                        Icon::make()->name($check['icon'])->addClass('dashboard-list-icon'),
                        Span::make()->content(
                            Span::make()->class('strong')->text(Yii::$app->getFormatter()->asInteger($count)),
                            ' ',
                            Span::make()->text($check['text']($count)),
                        ),
                    )));

                $isEmpty = false;
            }
        }

        return $isEmpty ? '' : $list;
    }

    /**
     * @param list<string> $roles
     */
    protected function canAccess(array $roles): bool
    {
        if (!$roles) {
            return true;
        }

        foreach ($roles as $role) {
            if ($this->webuser->can($role)) {
                return true;
            }
        }

        return false;
    }
}
