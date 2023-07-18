<?php

namespace davidhirtz\yii2\skeleton\helpers;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\base\Model;
use yii\helpers\BaseHtml;

/**
 * Extends the base HTML helper class by adding custom methods.
 */
class Html extends BaseHtml
{
    /**
     * @param string $content
     * @param array $options
     * @return string
     */
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

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function formText($content, $options = [])
    {
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        Html::addCssClass($options, 'form-text');

        return Html::tag($tag, $content, $options);
    }

    /**
     * @param string $icon
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function iconText($icon, $content, $options = [])
    {
        static::addCssClass($options, 'icon-text');
        return Html::tag('span', Icon::tag($icon, ['class' => 'fa-fw']) . Html::tag('span', $content), $options);
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function info(string $content, array $options = []): string
    {
        static::addCssClass($options, 'alert-info');
        return static::alert($content, $options);
    }

    /**
     * @param array|string $buttons
     * @param array $options
     * @return string
     */
    public static function buttonList(array|string $buttons, array $options = []): string
    {
        static::addCssClass($options, 'btn-list');
        return static::buttons($buttons, $options);
    }

    /**
     * @param array|string $buttons
     * @param array $options
     * @return string
     */
    public static function buttons(array|string $buttons, array $options = []): string
    {
        if ($buttons) {
            $buttons = is_array($buttons) ? implode('', $buttons) : $buttons;
            return $options ? static::tag('div', $buttons, $options) : $buttons;
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public static function input($type, $name = null, $value = null, $options = [])
    {
        if (isset($options['prepend']) || isset($options['append'])) {
            if ($prepend = ArrayHelper::remove($options, 'prepend', '')) {
                $prepend = "<div class=\"input-group-prepend\"><span class=\"input-group-text\">{$prepend}</span></div>";
            }

            if ($append = ArrayHelper::remove($options, 'append', '')) {
                $append = "<div class=\"input-group-append\"><span class=\"input-group-text\">{$append}</span></div>";
            }

            $input = parent::input($type, $name, $value, $options);
            return "<div class=\"input-group\">{$prepend}{$input}{$append}</div>";
        }

        return parent::input($type, $name, $value, $options);
    }

    /**
     * @param array $items
     * @param array $rowOptions
     * @param array $cellOptions
     * @return string
     */
    public static function tableBody($items, $rowOptions = [], $cellOptions = []): string
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

    /**
     * @param Model|Model[] $models
     * @inheritdoc
     */
    public static function errorSummary($models, $options = [])
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

    /**
     * @param string $js
     * @param array $params
     *
     * @return string
     */
    public static function formatInlineJs($js, $params = [])
    {
        $js = str_replace(["\r", "\n", "\t"], '', $js);
        return $params ? strtr($js, $params) : $js;
    }

    /**
     * @param string $text
     * @return string
     */
    public static function nl2br($text)
    {
        return nl2br($text, false);
    }

    /**
     * @param string $html
     * @return string
     */
    public static function minify($html)
    {
        return trim(preg_replace('/>\s+</', '><', $html));
    }

    /**
     * @param string $text
     * @param array|string $keywords
     * @param bool $wordBoundary
     * @return string
     */
    public static function markKeywords($text, $keywords, $wordBoundary = false)
    {
        if ($keywords) {
            foreach ((array)$keywords as $keyword) {
                $text = preg_replace('~(' . ($wordBoundary ? '\b' : '') . preg_quote($keyword) . ')~ui', '<mark>$1</mark>', $text);
            }
        }

        return $text;
    }

    /**
     * @param User $user
     * @param array|string|null $url
     * @param array $options
     * @return string
     */
    public static function username($user, $url = null, $options = [])
    {
        if ($user && $url) {
            return self::a($user->getUsername(), $url, $options);
        }

        if (!$user || $options) {
            return self::tag($user ? 'span' : 'em', $user ? $user->getUsername() : Yii::t('skeleton', 'Deleted'), $options);
        }

        return $user->getUsername();
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public static function warning(string $content, array $options = []): string
    {
        static::addCssClass($options, 'alert-warning');
        return static::alert($content, $options);
    }
}