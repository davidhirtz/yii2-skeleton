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
 * The half of an upload action every controller that takes one writes the same way: read the chunk, tell the
 * uploader to send the next one when that is all this was, and collect what earlier uploads abandoned.
 *
 * @mixin Controller<Module>
 */
trait UploadControllerTrait
{
    /**
     * @param Model|null $model the model the file input is named after, or `null` for a bare `$_FILES` entry
     * @return ChunkedUploadedFile|null `null` when the request carried no file, or when the response already says
     * what happened: `201` for a chunk that landed and wants the next one, `400` for one that disagrees with its range.
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
     * The uploader shows the message. HTTP/2 has no reason phrase, so it also travels URL-encoded in
     * `X-Upload-Error` (a header is Latin-1), and encoded in the body for a client rendering the response.
     */
    protected function refuseUpload(int $statusCode, string $message): Response
    {
        $this->response->setStatusCode($statusCode, $message);
        $this->response->getHeaders()->set('X-Upload-Error', rawurlencode($message));
        $this->response->content = Html::encode($message);

        return $this->response;
    }
}
