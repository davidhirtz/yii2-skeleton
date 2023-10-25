<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\db\ActiveRecord;
use yii\helpers\HtmlPurifier;
use yii\validators\Validator;

class HtmlValidator extends Validator
{
    /**
     * @var array containing allowed HTML tags like h1-h5 for format, table, th, td, tr for tables or blockquote,
     * strike, em for font styles.
     */
    public array $allowedHtmlTags = [];

    /**
     * @var array containing a list of excluded HTML tags. Use this to override the default allowedHtmlTags.
     */
    public array $excludedHtmlTags = [];

    /**
     * @var array[] containing allowed HTML attributes, indexed by tag name. Use "*" as a key to allow the attributes on
     * all tags.
     */
    public array $allowedHtmlAttributes = [];

    /**
     * @var array containing CSS properties that should be allowed.
     */
    public array $allowedCssProperties = [];

    /**
     * @var array|string containing CSS classes that should be allowed.
     */
    public array|string $allowedClasses = [];

    /**
     * @var bool whether images should be allowed. This is a shorthand for adding img[alt|height|src|title|width] to
     * allowedHtmlTags.
     */
    public bool $allowImages = false;

    /**
     * @var bool whether tables should be allowed. This is a shorthand for adding table, th, td, tr to allowedHtmlTags.
     */
    public bool $allowTables = false;

    /**
     * @var array containing options for HtmlPurifier. This should not be needed in most cases.
     */
    public array $purifierOptions = [];

    public function init(): void
    {
        $this->purifierOptions = array_merge([
            'Attr.AllowedFrameTargets' => '_blank',
            'Attr.AllowedRel' => 'nofollow',
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.AutoParagraph' => true,
            'HTML.TargetBlank' => true,
        ], $this->purifierOptions);

        $this->setHtmlAllowed();
        $this->setAllowedClasses();
        $this->setAllowedProperties();

        parent::init();
    }

    protected function setHtmlAllowed(): void
    {
        // Extract inline attributes for a tag (e.g. `a[href|rel]`) and add them to allowedHtmlAttributes.
        foreach ($this->allowedHtmlTags as $key => $value) {
            if (preg_match('/(\w+)\[([\w|]*)]/', $value, $matches)) {
                $this->allowedHtmlTags[$key] = $matches[1];
                $this->allowedHtmlAttributes[$matches[1]] ??= explode('|', $matches[2]);
            }
        }

        // Sanitize user input
        $this->allowedHtmlTags = array_filter($this->allowedHtmlTags);

        $defaultTags = [
            'a',
            'br',
            'li',
            'ol',
            'p',
            'span',
            'strong',
            'ul',
        ];

        if ($this->allowImages) {
            $defaultTags[] = 'img';
        }

        if ($this->allowTables) {
            $defaultTags[] = 'table';
            $defaultTags[] = 'th';
            $defaultTags[] = 'tr';
            $defaultTags[] = 'td';
        }

        foreach ($defaultTags as $tag) {
            if (!in_array($tag, $this->excludedHtmlTags)) {
                $this->allowedHtmlTags[] = $tag;
            }
        }

        if (!isset($this->allowedHtmlAttributes['a']) && in_array('a', $this->allowedHtmlTags)) {
            $this->allowedHtmlAttributes['a'] = ['href', 'title', 'target', 'rel'];

            if ($this->allowedClasses) {
                $this->allowedHtmlAttributes['a'][] = 'class';
            }
        }

        if (in_array('img', $this->allowedHtmlTags)) {
            $this->allowedHtmlAttributes['img'] ??= ['alt', 'height', 'src', 'title', 'width'];
        }

        if (in_array('span', $this->allowedHtmlTags) && $this->allowedClasses) {
            $this->allowedHtmlAttributes['span'] ??= ['class'];
        }

        $allowedHtmlTags = [];

        foreach ($this->allowedHtmlTags as $tag) {
            if ($attributes = ($this->allowedHtmlAttributes[$tag] ?? false)) {
                $tag .= '[' . (is_array($attributes) ? implode('|', $attributes) : $attributes) . ']';
            }

            $allowedHtmlTags[] = $tag . $attributes;
        }

        $this->purifierOptions['HTML.Allowed'] ??= $allowedHtmlTags;
    }

    protected function setAllowedClasses(): void
    {
        if ($this->allowedClasses) {
            $this->purifierOptions['Attr.AllowedClasses'] ??= $this->allowedClasses;
        }
    }

    protected function setAllowedProperties(): void
    {
        if ($this->allowedCssProperties) {
            $this->purifierOptions['CSS.AllowedProperties'] ??= $this->allowedCssProperties;
        }
    }

    /**
     * Purifies html.
     * @param ActiveRecord $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute): void
    {
        // Set options.
        $html = $model->getAttribute($attribute);

        // Unify line breaks..
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // Fix HtmlPurifier AutoFormat.AutoParagraph removing <ul>...</ul> tags in some cases.
        // Additional line breaks seem to fix this.
        $html = preg_replace("#\n?<(blockquote|ol|ul)>#i", "\n\n<$1>", $html);

        //Fix HtmlPurifier "AutoFormat.AutoParagraph" not applying paragraphs to rows consisting of whitespaces.
        $html = preg_replace("#\n\s+\n#i", "\n\n", $html);

        // Process html.
        $html = HtmlPurifier::process($html, $this->purifierOptions);

        // Change invalid break tags.
        $html = preg_replace('#<br />#', '<br>', $html);
        $html = preg_replace('#\s<br>#', '<br>', $html);

        // Add break tags.
        $blocks = '(?:div|dl|dd|dt|ul|ol|li|pre|blockquote|address|style|p|h[1-6]|hr|legend|section|article|aside)';

        $html = preg_replace("#(<'.$blocks.'[^>]*>)#", "\n$1", $html);
        $html = preg_replace("#(</'.$blocks.'>)#", "$1\n\n", $html);

        // Remove multiple breaking whitespaces.
        $html = preg_replace('#\s{2,}#', ' ', $html);

        // Make sure no empty paragraphs were generated.
        $html = preg_replace('#<p>\s*</p>#', '', $html);

        // Clean breaks.
        $html = preg_replace("#(?<!<br>)\s*\n#", "<br>\n", $html);
        $html = preg_replace('#(</?' . $blocks . '[^>]*>)\s*<br>#', '$1', $html);
        $html = preg_replace('#<br>(\s*</?(?:div|dd|dl|dt|li|ol|p|pre|table|tbody|td|th|ul)[^>]*>)#', '$1', $html);

        // Remove empty elements at the beginning and end of paragraphs.
        $html = preg_replace("#\n*\s*<p>\n*\s*#", "\n<p>", $html);
        $html = preg_replace("#\n*\s*</p>\n*\s*#", "</p>\n", $html);

        // Remove empty elements and <br> added by the WYSIWYG editor at the end and beginning of tables.
        $html = preg_replace("#\n*\s*<table>#", '<table>', $html);
        $html = preg_replace("#</table><br>\n*\s*#", '</table>', $html);
        $html = preg_replace("#</table>\n*\s*#", '</table>', $html);

        // Remove whitespaces in lists.
        $html = preg_replace("#\n*<li>\s*\n*(.*)\s*\n*</li>#i", "\n<li>$1</li>", $html);

        // Add line breaks to closing list tags.
        $html = preg_replace("#\n*(</?ul>|</?ol>)\n*#i", "\n$1\n", $html);

        // Done.
        $model->setAttribute($attribute, trim($html));
    }
}