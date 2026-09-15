<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Exception;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use Closure;

/**
 * @property ActiveRecord|static $owner
 */
class RedirectBehavior extends Behavior
{
    private ?string $prevUrl = null;

    /**
     * @return array<string, string|Closure>
     */
    #[\Override]
    public function events(): array
    {
        return $this->hasUrlManagerConfigured()
            ? [
                ActiveRecord::EVENT_AFTER_FIND => $this->afterFind(...),
                ActiveRecord::EVENT_AFTER_INSERT => $this->afterSave(...),
                ActiveRecord::EVENT_AFTER_UPDATE => $this->afterSave(...),
                ActiveRecord::EVENT_AFTER_DELETE => $this->afterDelete(...),
            ]
            : [];
    }

    protected function hasUrlManagerConfigured(): bool
    {
        try {
            Yii::$app->getUrlManager()->getBaseUrl();
        } catch (Exception) {
            return false;
        }

        return true;
    }

    public function afterFind(): void
    {
        $this->prevUrl = !$this->owner->getIsNewRecord() ? Url::sanitize($this->owner->getUrl()) : false;
    }

    /**
     * Inserts and updates related {@see Redirect} models on owner's `url` change.
     */
    public function afterSave(): void
    {
        $url = Url::sanitize($this->owner->getUrl());

        if ($url && $this->prevUrl && $this->prevUrl !== $url) {
            $this->updatePreviousRedirectUrls($url);
            $this->insertRedirect($url);
        }

        $this->prevUrl = $url;
    }

    /**
     * Deletes all {@see Redirect} models pointing to owner's `url` on deleting.
     */
    public function afterDelete(): void
    {
        if ($url = Url::sanitize($this->owner->getUrl())) {
            $this->deleteRedirects($url);
        }
    }

    /**
     * Updates previous redirect URLs. This is not handled via `updateAll` to enable {@see Trail} records.
     *
     * A redirect the owner has just moved back onto is a no-op and is deleted rather than updated: the update
     * would fail {@see Redirect::validateUrl()} and, unreported, leave the row pointing at a URL that no longer
     * resolves — and the redirect recorded for this rename would then chain through it onto itself.
     *
     * That deletion comes first, and the ordering is load-bearing: `validateUrl()` resolves the chain its new
     * target starts, so while the no-op row is still there every other row updated in this pass follows it back
     * to the URL they are all being moved off.
     */
    protected function updatePreviousRedirectUrls(string $url): void
    {
        /** @var Redirect[] $redirects */
        $redirects = Redirect::find()
            ->where(['url' => $this->prevUrl])
            ->all();

        foreach ($redirects as $key => $redirect) {
            if ($redirect->request_uri === $url) {
                $redirect->delete();
                unset($redirects[$key]);
            }
        }

        foreach ($redirects as $redirect) {
            $redirect->url = $url;

            if (!$redirect->update()) {
                $this->warn("Redirect from $redirect->request_uri could not be updated", $redirect);
            }
        }
    }

    protected function insertRedirect(string $url): void
    {
        $redirect = Redirect::create();
        $redirect->request_uri = $this->prevUrl;
        $redirect->url = $url;

        if (!$redirect->insert()) {
            $this->warn("Redirect from $this->prevUrl could not be saved", $redirect);
        }
    }

    protected function warn(string $message, Redirect $redirect): void
    {
        Yii::warning("$message: " . implode(' ', $redirect->getErrorSummary(true)), __METHOD__);
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
    public function getUrl(): false|string
    {
        if (!method_exists($this->owner, 'getRoute')) {
            throw new InvalidConfigException($this->owner::class . ' needs to either implement a `getUrl` or `getRoute` method');
        }

        return ($route = $this->owner->getRoute()) ? Yii::$app->getUrlManager()->createUrl($route) : false;
    }
}
