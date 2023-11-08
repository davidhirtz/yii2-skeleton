<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

/**
 * @property ActiveRecord|static $owner
 */
class RedirectBehavior extends Behavior
{
    private ?string $prevUrl = null;

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => $this->afterFind(...),
            ActiveRecord::EVENT_AFTER_INSERT => $this->afterSave(...),
            ActiveRecord::EVENT_AFTER_UPDATE => $this->afterSave(...),
            ActiveRecord::EVENT_AFTER_DELETE => $this->afterDelete(...),
        ];
    }

    public function afterFind(): void
    {
        $this->prevUrl = !$this->owner->getIsNewRecord() ? Redirect::sanitizeUrl($this->owner->getUrl()) : false;
    }

    /**
     * Inserts and updates related {@see Redirect} models on owner's `url` change.
     */
    public function afterSave(): void
    {
        $url = Redirect::sanitizeUrl($this->owner->getUrl());

        if ($url && $this->prevUrl && $this->prevUrl != $url) {
            $this->updatePreviousRedirectUrls($url);
            $this->insertRedirect($url);
        }
    }

    /**
     * Deletes all {@see Redirect} models pointing to owner's `url` on deleting.
     */
    public function afterDelete(): void
    {
        if ($url = Redirect::sanitizeUrl($this->owner->getUrl())) {
            $this->deleteRedirects($url);
        }
    }

    /**
     * Updates previous redirect URLs. This is not handled via `updateAll` to enable {@see Trail} records.
     */
    protected function updatePreviousRedirectUrls(string $url): void
    {
        /** @var Redirect[] $redirects */
        $redirects = Redirect::find()
            ->where(['url' => $this->prevUrl])
            ->all();

        foreach ($redirects as $redirect) {
            $redirect->url = $url;
            $redirect->update();
        }
    }

    protected function insertRedirect(string $url): void
    {
        $redirect = Redirect::create();
        $redirect->request_uri = $this->prevUrl;
        $redirect->url = $url;
        $redirect->insert();
    }

    /**
     * Deletes redirect records matching given `attribute`. This is not handled via `deleteAll` to enable {@see Trail}
     * records.
     */
    protected function deleteRedirects(string $url): void
    {
        /** @var Redirect[] $redirects */
        $redirects = Redirect::find()
            ->where(['url' => $url])
            ->all();

        foreach ($redirects as $redirect) {
            $redirect->delete();
        }
    }

    /**
     * This method tries to generate a URL from owner's `getUrl` method if it does not implement a `getUrl` method.
     */
    public function getUrl(): bool|string
    {
        if (!method_exists($this->owner, 'getRoute')) {
            throw new InvalidConfigException($this->owner::class . ' needs to either implement a `getUrl` or `getRoute` method');
        }

        return ($route = $this->owner->getRoute()) ? Yii::$app->getUrlManager()->createUrl($route) : false;
    }
}
