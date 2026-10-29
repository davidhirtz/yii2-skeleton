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
 * @property ActiveRecord $owner
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
        $this->prevUrl = !$this->owner->getIsNewRecord() ? Url::sanitize($this->getUrl()) : null;
    }

    public function afterSave(): void
    {
        $url = Url::sanitize($this->getUrl());

        if ($url && $this->prevUrl && $this->prevUrl !== $url) {
            $this->updatePreviousRedirectUrls($url);
            $this->insertRedirect($url);
        }

        $this->prevUrl = $url;
    }

    public function afterDelete(): void
    {
        if ($url = Url::sanitize($this->getUrl())) {
            $this->deleteRedirects($url);
        }
    }

    /**
     * Must delete a redirect the owner moved back onto first, or {@see Redirect::validateUrl()} chains every other row
     * back through it.
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
     * One by one rather than `deleteAll()`, so each deletion writes its {@see Trail}.
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

    public function getUrl(): false|string
    {
        if (method_exists($this->owner, 'getUrl')) {
            return $this->owner->getUrl();
        }

        if (!method_exists($this->owner, 'getRoute')) {
            throw new InvalidConfigException($this->owner::class . ' needs to either implement a `getUrl` or `getRoute` method');
        }

        return ($route = $this->owner->getRoute()) ? Yii::$app->getUrlManager()->createUrl($route) : false;
    }
}
