<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use yii\base\Widget;
use yii\helpers\Html;

class Panel extends Widget
{
    public const TYPE_DEFAULT = 'default';
    public const TYPE_DANGER = 'danger';

    /**
     * @var string|null the panel title
     */
    public ?string $title = null;

    /**
     * @var string|null the panel content
     */
    public ?string $content = null;

    /**
     * @var string the panel type defining the color
     */
    public string $type = self::TYPE_DEFAULT;

    /**
     * @var array the HTML attributes for the panel container tag
     */
    public array $options = [];

    /**
     * @var array the HTML attributes for the panel body container tag
     */
    public array $bodyOptions = [];

    /**
     * @var bool whether the panel is collapsable
     */
    public bool $isCollapsable = false;

    /**
     * @var bool whether the panel is collapsed
     */
    public bool $isCollapsed = true;

    public function init(): void
    {
        if ($this->getId(false)) {
            $this->options['id'] = $this->getId();
        }

        Html::addCssClass($this->options, 'card card-' . $this->type);
        Html::addCssClass($this->bodyOptions, 'card-body');

        parent::init();

        if ($this->content === null) {
            ob_start();
            ob_implicit_flush(false);
        }
    }

    public function run(): void
    {
        $this->content ??= ob_get_clean();

        if ($this->content) {
            $collapseId = $this->getId() . '-body';
            echo Html::beginTag('div', $this->options);

            if ($this->title) {
                $title = $this->isCollapsable ? Html::a($this->title, "#$collapseId", ['data-toggle' => 'collapse']) : $this->title;
                echo Html::tag('div', Html::tag('h2', $title, ['class' => 'card-title']), ['class' => 'card-header']);
            }

            $body = Html::tag('div', $this->content, $this->bodyOptions);
            echo $this->isCollapsable ? Html::tag('div', $body, ['class' => $this->isCollapsed ? 'collapse' : 'collapse show', 'id' => $collapseId]) : $body;

            echo Html::endTag('div');
        }
    }
}
