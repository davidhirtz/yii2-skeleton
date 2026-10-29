<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web\Traits;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\ChunkedUploadedFile;
use Hirtz\Skeleton\Web\Controller;
use Yii;
use yii\base\Model;
use yii\base\Module;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @mixin Controller<Module>
 */
trait UploadControllerTrait
{
    /**
     * `null` when the request carried no file or the response already answers: `201` for a chunk that wants the next
     * one, `400` for one that disagrees with its range.
     */
    protected function receiveUpload(?Model $model = null, string $attribute = 'upload'): ?ChunkedUploadedFile
    {
        try {
            $upload = $model
                ? ChunkedUploadedFile::getInstance($model, $attribute)
                : ChunkedUploadedFile::getInstanceByName($attribute);
        } catch (BadRequestHttpException) {
            $this->refuseUpload(400, Yii::t('skeleton', 'UPLOAD_FAILED_ERROR'));
            return null;
        }

        if ($upload?->isPartial()) {
            $this->response->setStatusCode(201);
            return null;
        }

        Upload::getComponent()->collectGarbageOncePerSession();

        return $upload;
    }

    /**
     * HTTP/2 has no reason phrase, so the message also travels URL-encoded in `X-Upload-Error` (a header is Latin-1).
     */
    protected function refuseUpload(int $statusCode, string $message): Response
    {
        $this->response->setStatusCode($statusCode, $message);
        $this->response->getHeaders()->set('X-Upload-Error', rawurlencode($message));
        $this->response->content = Html::encode($message);

        return $this->response;
    }
}
