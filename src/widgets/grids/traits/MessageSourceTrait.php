<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\traits;

use Hirtz\Skeleton\models\AuthItem;
use Yii;
use yii\i18n\PhpMessageSource;

trait MessageSourceTrait
{
    public string $messageSourceAttribute = 'description';

    private ?array $_translations = null;

    /**
     * Finds the correct translation source for the authItem description.
     */
    public function getTranslations(): array
    {
        if ($this->_translations === null) {
            $this->_translations = [];

            $i18n = Yii::$app->getI18n();
            $sources = [];

            // Make sure to only include sources that are actually available. This is necessary because Yii adds an
            // "app" category without making sure it actually is necessary.
            foreach (array_keys($i18n->translations) as $category) {
                $source = $i18n->getMessageSource($category);

                if (!$source instanceof PhpMessageSource || is_dir(Yii::getAlias($source->basePath . '/' . Yii::$app->language))) {
                    $sources[$category] = $source;
                }
            }

            /** @var AuthItem $authItem */
            foreach ($this->provider->getModels() as $authItem) {
                if ($message = $authItem->{$this->messageSourceAttribute}) {
                    foreach ($sources as $category => $source) {
                        if ($translation = $source->translate($category, $message, Yii::$app->language)) {
                            $this->_translations[$message] = $translation;
                            break;
                        }
                    }
                }
            }
        }

        return $this->_translations;
    }

    public function setTranslations(array $translations): void
    {
        $this->_translations = $translations;
    }
}
