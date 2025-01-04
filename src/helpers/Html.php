<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\helpers\BaseHtml;

class Html extends BaseHtml
{
    public static function alert(string $content, array $options = []): string
    {
        if ($content) {
            static::addCssClass($options, 'alert');

            if ($route = ArrayHelper::remove($options, 'route', false)) {
                static::addCssClass($options, 'alert-dismissible');

                $content .= Html::a(Html::tag('span', '', ['aria-hidden' => true]), $route, [
                    'class' => 'btn-close',
                    'aria-label' => Yii::t('skeleton', 'Close'),
                ]);
            }

            return static::tag('div', $content, $options);
        }

        return '';
    }

    public static function formText(string $content, array $options = []): string
    {
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        Html::addCssClass($options, 'form-text');

        return Html::tag($tag, $content, $options);
    }

    public static function iconText(string $icon, string $content, array $options = []): string
    {
        static::addCssClass($options, 'icon-text');
        return Html::tag('div', Icon::tag($icon, ['class' => 'fa-fw'])->render() . Html::tag('span', $content), $options);
    }

    public static function info(string $content, array $options = []): string
    {
        static::addCssClass($options, 'alert-info');
        return static::alert($content, $options);
    }

    public static function buttons(array|string $buttons, array $options = []): string
    {
        if (is_array($buttons)) {
            $buttons = implode('', $buttons);
        }

        if ($buttons) {
            static::addCssClass($options, 'btn-toolbar');
            return static::tag('div', $buttons, $options);
        }

        return '';
    }

    public static function input($type, $name = null, $value = null, $options = []): string
    {
        if (isset($options['prepend']) || isset($options['append'])) {
            if ($prepend = ArrayHelper::remove($options, 'prepend', '')) {
                $prepend = "<div class=\"input-group-prepend input-group-text\">$prepend</div>";
            }

            if ($append = ArrayHelper::remove($options, 'append', '')) {
                $append = "<div class=\"input-group-append input-group-text\">$append</div>";
            }

            $input = parent::input($type, $name, $value, $options);
            return "<div class=\"input-group\">$prepend$input$append</div>";
        }

        return parent::input($type, $name, $value, $options);
    }

    public static function tableBody(array $items, array $rowOptions = [], array $cellOptions = []): string
    {
        $rows = [];

        foreach ($items as $row) {
            $cells = [];

            foreach ($row as $cell) {
                $cells[] = static::tag('td', $cell, $cellOptions);
            }

            $rows[] = static::tag('tr', implode('', $cells), $rowOptions);
        }

        return $rows ? Html::tag('tbody', implode('', $rows)) : '';
    }

    public static function truncateText(string $text, int|false $maxLength, string $ellipsis = '…'): string
    {
        if ($maxLength !== false && mb_strlen($text) > $maxLength) {
            return Html::tag('span', mb_substr($text, 0, $maxLength - 3) . $ellipsis, [
                'title' => $text,
            ]);
        }

        return $text;
    }

    public static function errorSummary($models, $options = []): string
    {
        if ($models instanceof ActiveRecord) {
            $options['header'] ??= $models->getIsNewRecord()
                ? Yii::t('skeleton', 'The record could not be created:')
                : Yii::t('skeleton', 'The record could not be updated:');
        }

        if (($options['header'] ?? false) !== false) {
            $options['header'] = static::tag('div', $options['header'], ['class' => 'alert-heading']);
        }

        self::addCssClass($options, ['alert', 'alert-danger']);
        return parent::errorSummary($models, $options);
    }

    /**
     * @noinspection PhpUnused
     */
    public static function formatInlineJs(string $js, array $params = []): string
    {
        $js = str_replace(["\r", "\n", "\t"], '', $js);
        return $params ? strtr($js, $params) : $js;
    }

    /**
     * @noinspection PhpUnused
     */
    public static function nl2br(string $text): string
    {
        return nl2br($text, false);
    }

    /**
     * @noinspection PhpUnused
     */
    public static function minify(string $html): string
    {
        return trim((string)preg_replace('/>\s+</', '><', $html));
    }

    public static function markKeywords(string $text, array|string|null $keywords, bool $wordBoundary = false): string
    {
        if ($keywords) {
            foreach ((array)$keywords as $keyword) {
                $keyword = preg_quote((string)$keyword);

                if ($wordBoundary) {
                    $keyword = "\b$keyword";
                }

                $text = preg_replace("#($keyword)#ui", '<mark>$1</mark>', (string)$text);
            }
        }

        return $text;
    }

    public static function username(?User $user, array|string|null $route = null, array $options = []): string
    {
        if ($user && $route) {
            return self::a($user->getUsername(), $route, $options);
        }

        if (!$user || $options) {
            return self::tag($user ? 'span' : 'em', $user ? $user->getUsername() : Yii::t('skeleton', 'Deleted'), $options);
        }

        return $user->getUsername();
    }

    public static function warning(string $content, array $options = []): string
    {
        static::addCssClass($options, 'alert-warning');
        return static::alert($content, $options);
    }
}
