<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\db\ActiveRecord;
use yii\helpers\HtmlPurifier;
use yii\validators\Validator;

class HtmlValidator extends Validator
{
    /**
     * @var array|array[] containing CSS classes that should be allowed. Use tag name as a key and an array of allowed
     *     classes as value. Example: ['a' => ['btn', 'btn-primary']].
     *
     * Allowed classes can also have a human-readable name as key and the class name as value.
     * Example: ['a' => ['Primary Button' => 'btn btn-primary']].
     *
     * For backwards compatibility, this can also be a simple array of allowed classes. These will be applied to link
     * tags only
     */
    public array $allowedClasses = [];

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
     * @var array[] containing allowed HTML attributes, indexed by tag name.
     */
    public array $allowedHtmlAttributes = [];

    /**
     * @var array containing CSS properties that should be allowed.
     */
    public array $allowedCssProperties = [];

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
     * @var array containing options for HtmlPurifier. This should not be necessary in most cases.
     */
    public array $purifierOptions = [];

    public function init(): void
    {
        $this->setDefaultOptions();

        $this->setHtmlAllowed();
        $this->setAllowedClasses();
        $this->setAllowedCssProperties();

        parent::init();
    }

    protected function setDefaultOptions(): void
    {
        $this->purifierOptions = ['Attr.AllowedFrameTargets' => '_blank', 'Attr.AllowedRel' => 'nofollow', 'AutoFormat.RemoveEmpty' => true, 'AutoFormat.AutoParagraph' => true, 'HTML.TargetBlank' => true, ...$this->purifierOptions];
    }

    protected function setHtmlAllowed(): void
    {
        // Extract inline attributes for a tag (e.g. `a[href|rel]`) and add them to allowedHtmlAttributes.
        foreach ($this->allowedHtmlTags as $key => $value) {
            if (preg_match('/(\w+)\[([\w|]*)]/', (string)$value, $matches)) {
                $this->allowedHtmlTags[$key] = $matches[1];
                $this->allowedHtmlAttributes[$matches[1]] ??= explode('|', $matches[2]);
            }
        }

        // Sanitize user input
        $this->allowedHtmlTags = array_map('strtolower', array_filter($this->allowedHtmlTags));

        // Transform legacy allowedClasses to an array of allowed classes for the link tag.
        if (key($this->allowedClasses) === 0) {
            $this->allowedClasses = ['a' => $this->allowedClasses];
        }

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
            if (!in_array($tag, $this->allowedHtmlTags) && !in_array($tag, $this->excludedHtmlTags)) {
                $this->allowedHtmlTags[] = $tag;
            }
        }

        if (!isset($this->allowedHtmlAttributes['a']) && in_array('a', $this->allowedHtmlTags)) {
            $this->allowedHtmlAttributes['a'] = ['href', 'rel', 'title', 'target'];
        }

        if (in_array('img', $this->allowedHtmlTags)) {
            $this->allowedHtmlAttributes['img'] ??= ['alt', 'height', 'src', 'title', 'width'];
        }

        foreach ($this->allowedClasses as $tag => $classes) {
            if (in_array($tag, $this->allowedHtmlTags)) {
                $this->allowedHtmlAttributes[$tag][] = 'class';
            }
        }

        $allowedHtmlTags = [];

        foreach ($this->allowedHtmlTags as $tag) {
            if ($attributes = ($this->allowedHtmlAttributes[$tag] ?? false)) {
                if (is_array($attributes)) {
                    sort($attributes);
                    $attributes = implode('|', $attributes);
                }

                $tag .= "[$attributes]";
            }

            $allowedHtmlTags[] = $tag;
        }

        $this->purifierOptions['HTML.Allowed'] ??= implode(',', $allowedHtmlTags);
    }

    protected function setAllowedClasses(): void
    {
        if ($this->allowedClasses) {
            $allowedClasses = [];

            foreach ($this->allowedClasses as $values) {
                foreach ($values as $classes) {
                    $allowedClasses = [
                        ...$allowedClasses,
                        ...explode(' ', $classes),
                    ];
                }
            }

            $this->purifierOptions['Attr.AllowedClasses'] ??= array_values(array_unique($allowedClasses));
        }
    }

    protected function setAllowedCssProperties(): void
    {
        if ($this->allowedCssProperties) {
            $allowedProperties = array_unique(array_merge(...array_values($this->allowedCssProperties)));
            $this->purifierOptions['CSS.AllowedProperties'] ??= array_values($allowedProperties);
        }
    }

    /**
     * @param ActiveRecord $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute): void
    {
        $html = $model->getAttribute($attribute);

        // Unify line breaks..
        $html = str_replace(["\r\n", "\r"], "\n", (string)$html);

        // Fix HtmlPurifier AutoFormat.AutoParagraph removing <ul>...</ul> tags in some cases.
        // Additional line breaks seem to fix this.
        $html = preg_replace("#\n?<(blockquote|ol|ul)>#i", "\n\n<$1>", $html);

        //Fix HtmlPurifier "AutoFormat.AutoParagraph" not applying paragraphs to rows consisting of whitespaces.
        $html = preg_replace("#\n\s+\n#i", "\n\n", (string)$html);

        // Process html.
        $html = HtmlPurifier::process($html, $this->purifierOptions);

        // Change invalid break tags.
        $html = preg_replace('#<br />#', '<br>', $html);
        $html = preg_replace('#\s<br>#', '<br>', (string)$html);

        // Add break tags.
        $blocks = '(?:div|dl|dd|dt|ul|ol|li|pre|blockquote|address|style|p|h[1-6]|hr|legend|section|article|aside)';

        $html = preg_replace("#(<'.$blocks.'[^>]*>)#", "\n$1", (string)$html);
        $html = preg_replace("#(</'.$blocks.'>)#", "$1\n\n", (string)$html);

        // Remove multiple breaking whitespaces.
        $html = preg_replace('#\s{2,}#', ' ', (string)$html);

        // Make sure no empty paragraphs were generated.
        $html = preg_replace('#<p>\s*</p>#', '', (string)$html);

        // Clean breaks.
        $html = preg_replace("#(?<!<br>)\s*\n#", "<br>\n", (string)$html);
        $html = preg_replace('#(</?' . $blocks . '[^>]*>)\s*<br>#', '$1', (string)$html);
        $html = preg_replace('#<br>(\s*</?(?:div|dd|dl|dt|li|ol|p|pre|table|tbody|td|th|tr|ul)[^>]*>)#', '$1', (string)$html);

        // Remove empty elements at the beginning and end of paragraphs.
        $html = preg_replace("#\n*\s*<p>\n*\s*#", "\n<p>", (string)$html);
        $html = preg_replace("#\n*\s*</p>\n*\s*#", "</p>\n", (string)$html);

        // Remove empty elements and <br> added by the WYSIWYG editor at the end and beginning of tables.
        $html = preg_replace("#\n*\s*<table>#", '<table>', (string)$html);
        $html = preg_replace("#</table><br>\n*\s*#", '</table>', (string)$html);
        $html = preg_replace("#</table>\n*\s*#", '</table>', (string)$html);

        // Remove whitespaces in lists.
        $html = preg_replace("#\n*<li>\s*\n*(.*)\s*\n*</li>#i", "\n<li>$1</li>", (string)$html);

        // Add line breaks to closing list tags.
        $html = preg_replace("#\n*(</?ul>|</?ol>)\n*#i", "\n$1\n", (string)$html);

        // Done.
        $model->setAttribute($attribute, trim((string)$html));
    }
}
