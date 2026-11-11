<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets;

use Hirtz\Skeleton\Helpers\Html;
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

        $modifier = fn (string $key): string => Html::tag('kbd', 'Ctrl', ['data-modifier' => true]) . ' ' . $this->key($key);

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

        $content = implode('', array_map(
            fn (array $row): string => Html::tag('tr', Html::tag('th', $row[0]) . Html::tag('td', Html::encode($row[1]))),
            $rows,
        ));

        return Html::tag('template', Html::tag('table', $content, ['class' => 'shortcut-list']), [
            'id' => self::ID,
            'data-letters' => $letters ? '1' : '0',
            'data-title' => Yii::t('skeleton', 'SHORTCUT_LIST_TITLE'),
            'data-close-label' => Yii::t('skeleton', 'MODAL_CLOSE_LABEL'),
        ]);
    }

    #[Override]
    public function isVisible(): bool
    {
        return !$this->webuser->getIsGuest() && parent::isVisible();
    }

    private function key(string $key): string
    {
        return Html::tag('kbd', Html::encode($key));
    }
}
