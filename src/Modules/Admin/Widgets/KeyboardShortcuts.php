<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Html\Kbd;
use Hirtz\Skeleton\Html\Table;
use Hirtz\Skeleton\Html\Tbody;
use Hirtz\Skeleton\Html\Template;
use Hirtz\Skeleton\Html\Tr;
use Hirtz\Skeleton\Html\Th;
use Hirtz\Skeleton\Html\Td;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * The list `includes/shortcuts.ts` shows on `?`, and whether the account's letter shortcuts are on (`data-letters`).
 */
class KeyboardShortcuts extends Widget
{
    final public const string ID = 'keyboard-shortcuts';

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $letters = $this->webuser->getIdentity()?->usesKeyboardShortcuts() ?? false;

        $modifier = fn (string $key): string => Kbd::make()->attribute('data-modifier', true)->content('Ctrl') . ' ' . $this->key($key);

        $rows = [
            [$modifier('S'), Yii::t('skeleton', 'SHORTCUT_SAVE_TEXT')],
            [$modifier('K') . ($letters ? ' ' . $this->key('/') : ''), Yii::t('skeleton', 'SHORTCUT_SEARCH_TEXT')],
        ];

        if ($letters) {
            $rows = [
                ...$rows,
                [$this->key('M'), Yii::t('skeleton', 'SHORTCUT_ASIDE_TEXT')],
                [$this->key('T'), Yii::t('skeleton', 'SHORTCUT_TABS_TEXT')],
                [$this->key('A'), Yii::t('skeleton', 'SHORTCUT_ACTIONS_TEXT')],
                [Yii::t('skeleton', 'SHORTCUT_HOLD_FORMAT', ['key' => $this->key('Ctrl')]), Yii::t('skeleton', 'SHORTCUT_REVEAL_TEXT')],
                [$this->key('?'), Yii::t('skeleton', 'SHORTCUT_HELP_TEXT')],
            ];
        }

        $body = Tbody::make()->rows(...array_map(
            fn (array $row): Tr => Tr::make()->cells(Th::make()->content($row[0]), Td::make()->text($row[1])),
            $rows,
        ));

        return Template::make()
            ->attributes([
                'id' => self::ID,
                'data-letters' => $letters ? '1' : '0',
                'data-title' => Yii::t('skeleton', 'SHORTCUT_LIST_TITLE'),
                'data-close-label' => Yii::t('skeleton', 'MODAL_CLOSE_LABEL'),
            ])
            ->content(Table::make()
                ->class('shortcut-list')
                ->body($body));
    }

    #[Override]
    public function isVisible(): bool
    {
        return !$this->webuser->getIsGuest() && parent::isVisible();
    }

    private function key(string $key): string
    {
        return Kbd::make()->text($key)->render();
    }
}
