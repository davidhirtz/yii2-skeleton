<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\HtmlPurifier;

/**
 * Class HtmlValidator.
 * @package davidhirtz\yii2\skeleton\validators
 */
class HtmlValidator extends \yii\validators\Validator
{
    /**
     * @var array common tags are h1-h5 for format,
     * table, th, td, tr for tables
     * blockquote, strike, u for font styles
     */
    public $allowedHtmlTags = [];

    /**
     * @var array|string
     */
    public $excludedHtmlTags = [];

    /**
     * @var array|string
     */
    public $allowedCssProperties;

    /**
     * @var array|string
     */
    public $allowedClasses;

    /**
     * @var array
     */
    public $purifierOptions = [];

    /**
     * Init.
     */
    public function init()
    {
        $this->purifierOptions = array_merge([
            'Attr.AllowedFrameTargets' => '_blank',
            'Attr.AllowedRel' => 'nofollow',
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.Linkify' => true,
            'AutoFormat.AutoParagraph' => true,
            'CSS.AllowedProperties' => 'text-decoration',
            'HTML.TargetBlank' => true,
            'HTML.Nofollow' => true,
        ], $this->purifierOptions);

        if (isset($this->purifierOptions['HTML.Allowed'])) {
            throw new InvalidConfigException('Please use HtmlValidator::$allowedHtmlTags instead of "HTML.Allowed"');
        }

        $this->allowedHtmlTags = array_merge($this->allowedHtmlTags, [
            'a[href|rel|target]',
            'br',
            'div',
            'em',
            'img[alt|height|src|title|width]',
            'li',
            'ol',
            'p',
            'strong',
            'ul',
        ]);

        $this->allowedHtmlTags = array_unique(array_filter(array_diff($this->allowedHtmlTags, $this->excludedHtmlTags)));
        $this->purifierOptions['HTML.Allowed'] = implode(',', $this->allowedHtmlTags);

        if ($this->allowedCssProperties) {
            $this->purifierOptions['CSS.AllowedProperties'] = ArrayHelper::getValue($this->purifierOptions, 'CSS.AllowedProperties');
            $this->purifierOptions['CSS.AllowedProperties'] .= implode(',', (array)$this->allowedCssProperties);
        }

        if ($this->allowedClasses) {
            $this->purifierOptions['Attr.AllowedClasses'] = ArrayHelper::getValue($this->purifierOptions, 'Attr.AllowedClasses');
            $this->purifierOptions['Attr.AllowedClasses'] .= implode(',', (array)$this->allowedClasses);
        }

        parent::init();
    }

    /**
     * Purifies html.
     * @param \yii\db\ActiveRecord $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        // Set options.
        $html = $model->getAttribute($attribute);

        // Unify line breaks..
        $html = str_replace(array("\r\n", "\r"), "\n", $html);

        // Fix HtmlPurifier AutoFormat.AutoParagraph removing <ul>...</ul> tags in some cases.
        // Additional line breaks seem to fix this.
        $html = preg_replace("#\n?<(blockquote|ol|ul)>#i", "\n\n<$1>", $html);

        //Fix HtmlPurifier "AutoFormat.AutoParagraph" not applying paragraphs to rows consisting of whitespaces.
        $html = preg_replace("#\n\s+\n#i", "\n\n", $html);

        // Process html.
        $html = HtmlPurifier::process($html, $this->purifierOptions);

        // Change invalid break tags.
        $html = preg_replace('#<br />#', "<br>", $html);
        $html = preg_replace('#\s<br>#', "<br>", $html);

        // Add break tags.
        $blocks = '(?:div|dl|dd|dt|ul|ol|li|pre|blockquote|address|style|p|h[1-6]|hr|legend|section|article|aside)';

        $html = preg_replace("#(<'.$blocks.'[^>]*>)#", "\n$1", $html);
        $html = preg_replace("#(</'.$blocks.'>)#", "$1\n\n", $html);

        // Remove multiple breaking whitespaces.
        $html = preg_replace('#\s{2,}#', " ", $html);

        // Make sure no empty paragraphs were generated.
        $html = preg_replace("#<p>\s*</p>#", '', $html);

        // Clean breaks.
        $html = preg_replace("#(?<!<br>)\s*\n#", "<br>\n", $html);
        $html = preg_replace("#(</?" . $blocks . "[^>]*>)\s*<br>#", "$1", $html);
        $html = preg_replace("#<br>(\s*<\/?(?:div|dd|dl|dt|li|ol|p|pre|table|tbody|td|th|ul)[^>]*>)#", "$1", $html);

        // Remove empty elements at beginning and end of paragraphs.
        $html = preg_replace("#\n*\s*<p>\n*\s*#", "\n<p>", $html);
        $html = preg_replace("#\n*\s*</p>\n*\s*#", "</p>\n", $html);

        // Remove empty elements at the end and beginning of tables.
        $html = preg_replace("#\n*\s*<table>#", "<table>", $html);
        $html = preg_replace("#</table>\n*\s*#", "</table>", $html);

        // Remove whitespaces in lists.
        $html = preg_replace("#\n*<li>\s*\n*(.*)\s*\n*</li>#i", "\n<li>$1</li>", $html);

        // Add line breaks to closing list tags.
        $html = preg_replace("#\n*(</?ul>|</?ol>)\n*#i", "\n$1\n", $html);

        // Done.
        $model->setAttribute($attribute, trim($html));
    }
}