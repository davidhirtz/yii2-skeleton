<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\models\AuthItem;
use Yii;
use yii\i18n\MessageSource;

/**
 * Trait MessageSourceTrait
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\grid
 *
 * @method ActiveRecord|StatusAttributeTrait getModel()
 */
trait MessageSourceTrait
{
    /**
     * @var string
     */
    public $messageSourceAttribute = 'description';

    /**
     * @var array
     */
    private $_translations;

    /**
     * Finds the correct translation source for the authItem description.
     * @return array
     */
    public function getTranslations(): array
    {
        if ($this->_translations === null) {
            $this->_translations = [];

            if (Yii::$app->language !== Yii::$app->sourceLanguage) {
                $i18n = Yii::$app->getI18n();
                $sources = array_keys($i18n->translations);

                /** @var AuthItem $authItem */
                foreach ($this->dataProvider->getModels() as $authItem) {
                    if ($message = $authItem->{$this->messageSourceAttribute}) {
                        /** @var MessageSource $source */
                        foreach ($sources as $source) {
                            if ($translation = $i18n->getMessageSource($source)->translate($source, $message, Yii::$app->language)) {
                                $this->_translations[$message] = $translation;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $this->_translations;
    }

    /**
     * @param array $translations
     */
    public function setTranslations(array $translations): void
    {
        $this->_translations = $translations;
    }
}