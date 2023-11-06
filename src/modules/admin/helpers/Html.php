<?php

namespace davidhirtz\yii2\skeleton\modules\admin\helpers;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
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

                $content .= Html::a(Html::tag('span', '&times;', ['aria-hidden' => true]), $route, [
                    'class' => 'close',
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
        return Html::tag('span', Icon::tag($icon, ['class' => 'fa-fw']) . Html::tag('span', $content), $options);
    }

    public static function info(string $content, array $options = []): string
    {
        static::addCssClass($options, 'alert-info');
        return static::alert($content, $options);
    }

    /** @noinspection PhpUnused */
    public static function buttonList(array|string $buttons, array $options = []): string
    {
        static::addCssClass($options, 'btn-list');
        return static::buttons($buttons, $options);
    }

    public static function buttons(array|string $buttons, array $options = []): string
    {
        if ($buttons) {
            $buttons = is_array($buttons) ? implode('', $buttons) : $buttons;
            return $options ? static::tag('div', $buttons, $options) : $buttons;
        }

        return '';
    }

    public static function input($type, $name = null, $value = null, $options = []): string
    {
        if (isset($options['prepend']) || isset($options['append'])) {
            if ($prepend = ArrayHelper::remove($options, 'prepend', '')) {
                $prepend = "<div class=\"input-group-prepend\"><span class=\"input-group-text\">$prepend</span></div>";
            }

            if ($append = ArrayHelper::remove($options, 'append', '')) {
                $append = "<div class=\"input-group-append\"><span class=\"input-group-text\">$append</span></div>";
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

            $rows[] .= static::tag('tr', implode('', $cells), $rowOptions);
        }

        return $rows ? Html::tag('tbody', implode('', $rows)) : '';
    }

    public static function errorSummary($models, $options = []): string
    {
        if ($models instanceof ActiveRecord) {
            if (!isset($options['header'])) {
                $options['header'] = $models->getIsNewRecord() ? Yii::t('skeleton', 'The record could not be created:') : Yii::t('skeleton', 'The record could not be updated:');
            }
        }

        if (isset($options['header'])) {
            $options['header'] = static::tag('div', $options['header'], ['class' => 'alert-heading']) . '<hr>';
        }

        self::addCssClass($options, ['alert', 'alert-error']);
        return parent::errorSummary($models, $options);
    }

    /** @noinspection PhpUnused */
    public static function formatInlineJs(string $js, array $params = []): string
    {
        $js = str_replace(["\r", "\n", "\t"], '', $js);
        return $params ? strtr($js, $params) : $js;
    }

    /** @noinspection PhpUnused */
    public static function nl2br(string $text): string
    {
        return nl2br($text, false);
    }

    /** @noinspection PhpUnused */
    public static function minify(string $html): string
    {
        return trim(preg_replace('/>\s+</', '><', $html));
    }

    public static function markKeywords(string $text, array|string|null $keywords, bool $wordBoundary = false): string
    {
        if ($keywords) {
            foreach ((array)$keywords as $keyword) {
                $text = preg_replace('~(' . ($wordBoundary ? '\b' : '') . preg_quote((string)$keyword) . ')~ui', '<mark>$1</mark>', $text);
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