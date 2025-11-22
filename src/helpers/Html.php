<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers;

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\widgets\Alert;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use Override;
use Stringable;
use Yii;
use yii\helpers\BaseHtml;

class Html extends BaseHtml
{
    private static int $counter = 0;

    public static function alert(string $html, string $status): string
    {
        if (!$html) {
            return '';
        }

        return (string)Container::make()->content(
            Alert::make()
                ->content($html)
                ->icon('check-circle')
                ->danger()
        );
    }

    public static function danger(string $html): string
    {
        return static::alert($html, 'danger');
    }

    #[\Override]
    public static function getInputIdByName($name): string
    {
        return strtr(
            mb_strtolower($name, 'UTF-8'),
            [
                '[]' => '',
                '][' => '-',
                '[' => '-',
                ']' => '',
                ' ' => '-',
                '.' => '-',
                '--' => '-',
                '_' => '-',
            ]
        );
    }

    public static function icon(string $name, array $attributes = []): Icon
    {
        return Icon::make()->name($name)->attributes($attributes);
    }

    public static function getId(): string
    {
        return 'i' . ++self::$counter;
    }

    public static function formText(string $content, array $options = []): string
    {
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        Html::addCssClass($options, 'form-text');

        return Html::tag($tag, $content, $options);
    }

    public static function buttons(array|string $buttons, array $options = []): string
    {
        if (is_array($buttons)) {
            $buttons = implode('', $buttons);
        }

        if ($buttons) {
            static::addCssClass($options, 'btn-group');
            return static::tag('div', $buttons, $options);
        }

        return '';
    }

    #[Override]
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

    /**
     * @deprecated use {@link ErrorSummary} directly instead.
     */
    #[Override]
    public static function errorSummary($models, $options = []): string
    {
        $title = ArrayHelper::remove($options, 'header');

        return (string)ErrorSummary::make()
            ->models($models)
            ->title($title);
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


    public static function warning(string $html): string
    {
        return static::alert($html, 'warning');
    }
}
