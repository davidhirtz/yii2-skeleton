<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

/**
 * Class BlameableBehavior
 * @package davidhirtz\yii2\skeleton\behaviors
 *
 * @property ActiveRecord $owner
 */
class RedirectBehavior extends Behavior
{
    /**
     * @var string
     */
    private $prevUrl;

    /**
     * @return array|string[]
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Caches the previous url
     */
    public function afterFind()
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->prevUrl = !$this->owner->getIsNewRecord() ? Redirect::sanitizeUrl($this->owner->getUrl()) : false;
    }

    /**
     * Inserts and updates related {@link Redirect} models on owner's `url` change.
     */
    public function afterSave()
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $url = Redirect::sanitizeUrl($this->owner->getUrl());

        if ($url && $this->prevUrl && $this->prevUrl != $url) {
            $this->updatePreviousRedirectUrls($url);
            $this->insertRedirect($url);
        }
    }

    /**
     * Deletes all {@link Redirect} models pointing to owner's `url` on delete.
     */
    public function afterDelete()
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        if ($url = Redirect::sanitizeUrl($this->owner->getUrl())) {
            $this->deleteRedirects($url);
        }
    }

    /**
     * Updates previous redirect URLs. This is not handled via `updateAll` to enable {@link Trail} records.
     * @param string $url
     */
    protected function updatePreviousRedirectUrls($url)
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
     * Deletes redirect records matching given `attribute`. This is not handled via `deleteAll` to enable {@link Trail}
     * records.
     *
     * @param string $url
     */
    protected function deleteRedirects($url)
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
     * This method tries to generate a URL from owner's `getUrl` method, if it does not implement a `getUrl` method.
     * @return false|string
     */
    public function getUrl()
    {
        if (!method_exists($this->owner, 'getRoute')) {
            throw new InvalidConfigException($this->owner::class . ' needs to either implement a `getUrl` or `getRoute` method');
        }

        return ($route = $this->owner->getRoute()) ? Yii::$app->getUrlManager()->createUrl($route) : false;
    }
}