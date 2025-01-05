<?php

namespace davidhirtz\yii2\skeleton\html;

use Yii;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Div;

class Card extends BaseTag
{
    protected array $attributes = [
        'class' => 'card card-default',
    ];

    private ?string $title = null;
    private Div $body;
    private ?bool $collapse = null;

    private static int $counter = 0;

    public static function danger(): self
    {
        $new = static::tag();
        $new->attributes['class'] = 'card card-danger';
        return $new;
    }

    public function body(string $html, array $attributes = []): self
    {
        $this->body ??= Div::tag()
            ->addClass('card-body')
            ->encode(false);

        if ($attributes) {
            $this->body = $this->body->addAttributes($attributes);
        }

        $this->body = $this->body->content($html);

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function collapse(?bool $collapse): self
    {
        $this->collapse = $collapse;
        return $this;
    }

    protected function generateContent(): string
    {
        if ($this->collapse !== null) {
            $this->body->attributes['id'] ??= 'card-' . ++self::$counter;
            $this->body->attributes['aria-expanded'] ??= $this->collapse ? 'false' : 'true';

            if ($this->collapse) {
                Html::addCssClass($this->attributes, 'd-none');
            }
        }

        $content = [];

        if ($this->title) {
            $content[] = Div::tag()
                ->class('card-header')
                ->content(Div::tag()
                    ->class('card-title')
                    ->content(
                        $this->collapse !== null
                        ? Button::button($this->title)
                            ->attributes([
                                'aria-label' => Yii::t('skeleton', 'Collapse'),
                                'class' => 'btn btn-link',
                                'data-collapse' => '#' . $this->body->attributes['id'],
                            ])
                        : $this->title
                    ));
        }

        $content[] = $this->body;


        return implode('', $content);
    }
}
