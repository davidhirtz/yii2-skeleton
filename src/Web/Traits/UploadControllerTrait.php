<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web\Traits;

use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\ChunkedUploadedFile;
use Hirtz\Skeleton\Web\Controller;
use yii\base\Model;
use yii\base\Module;

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
     * what happened: `201` for a chunk that landed and wants the next one.
     */
    protected function receiveUpload(?Model $model = null, string $attribute = 'upload'): ?ChunkedUploadedFile
    {
        $upload = $model
            ? ChunkedUploadedFile::getInstance($model, $attribute)
            : ChunkedUploadedFile::getInstanceByName($attribute);

        if ($upload?->isPartial()) {
            $this->response->setStatusCode(201);
            return null;
        }

        Upload::getComponent()->collectGarbageOncePerSession();

        return $upload;
    }
}
