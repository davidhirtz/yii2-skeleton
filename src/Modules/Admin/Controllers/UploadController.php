<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\ChunkedUploadedFile;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Web\Traits\UploadControllerTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Checks the field's signature rather than a model permission the skeleton cannot know; only the record's save moves
 * the file out of {@see Upload::$tempPath}.
 *
 * @extends Controller<Module>
 */
class UploadController extends Controller
{
    use UploadControllerTrait;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => [User::ROLE_AUTHENTICATED],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
                ],
            ],
        ];
    }

    public function actionCreate(
        string $model,
        string $attribute,
        string $signature,
        ?int $type = null,
        bool $remove = false,
        ?string $token = null,
    ): Response|string {
        $upload = Upload::getComponent();

        if (!$upload->isValidSignature($signature, $model, $attribute, $type)) {
            throw new NotFoundHttpException();
        }

        $record = $this->findRecord($model, $type);
        $definition = $record->getCustomAttribute($attribute);

        if (!$definition instanceof UploadCustomAttribute || $definition->isDisabled($record)) {
            throw new NotFoundHttpException();
        }

        $permission = $definition->getPermission();

        if ($permission !== null && !$this->webuser->can($permission)) {
            throw new ForbiddenHttpException();
        }

        if ($remove) {
            // The file a pending upload parked goes now rather than waiting for the collector: the form still
            // holds its token, so nothing else will ever ask for it. A file the record already has is deleted by
            // the save, which is the only point at which the removal is more than a cleared input.
            $upload->deleteTempFile($token);
        } else {
            $token = $this->upload($definition, $upload);

            if ($token === null) {
                return $this->response;
            }

            $record->{$attribute} = $token;
        }

        return (string)$this->createField($definition, $record, $attribute);
    }

    /**
     * Bound to the attribute asked for, since the response must carry the container id of the cloned per-language field
     * ({@see \Hirtz\Skeleton\Widgets\Forms\Fieldset::configure()}).
     */
    protected function createField(
        UploadCustomAttribute $definition,
        ActiveRecord&CustomAttributeInterface $record,
        string $attribute,
    ): Field {
        $field = $definition->createField($record);

        if ($attribute === $definition->name || !$record instanceof I18nAttributeInterface) {
            return $field;
        }

        foreach ($record->getI18nAttributeNames($definition->name) as $language => $property) {
            if ($property === $attribute) {
                return $field->language($language)->property($property);
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * `null` when the response says what went wrong instead.
     */
    protected function upload(UploadCustomAttribute $definition, Upload $upload): ?string
    {
        // Counted when an upload starts, before anything is written: a chunked upload answers every chunk but the
        // last before the file is known, and a chunk at any other offset only lands on a file a counted one opened.
        if (ChunkedUploadedFile::isUploadStart()) {
            if ($upload->isUploadLimitReached()) {
                $this->refuseUpload(429, Yii::t('skeleton', 'UPLOAD_LIMIT_ERROR'));
                return null;
            }

            $upload->addUpload();
        }

        $file = $this->receiveUpload();

        if ($file === null) {
            if ($this->response->getIsOk()) {
                throw new NotFoundHttpException();
            }

            return null;
        }

        $error = $definition->validateUploadedFile($file);

        if ($error !== null) {
            // An upload PHP rejected carries no temporary file — an aborted transfer reports an empty `tmp_name`.
            if ($file->tempName) {
                FileHelper::unlink($file->tempName);
            }

            $this->refuseUpload(400, $error);

            return null;
        }

        $token = $upload->createTempFile($file);

        if ($token === null) {
            $this->refuseUpload(400, Yii::t('skeleton', 'UPLOAD_FAILED_ERROR'));

            return null;
        }

        return $token;
    }

    /**
     * Never one from the database: only the definitions are wanted, and nothing is attached until the form is saved.
     */
    protected function findRecord(string $model, ?int $type): ActiveRecord&CustomAttributeInterface
    {
        if (!is_subclass_of($model, ActiveRecord::class) || !is_subclass_of($model, CustomAttributeInterface::class)) {
            throw new NotFoundHttpException();
        }

        // Through `instantiate()`, never `createObject()`: a type may name a class of its own
        // ({@see \Hirtz\Skeleton\Models\Types\Type::getModelClass()}) and the definitions live on that one.
        /** @var ActiveRecord&CustomAttributeInterface $record */
        $record = $model::instantiate($type === null ? [] : ['type' => $type]);

        // The definitions hang off the type, so a record given none has to reach its default first.
        if ($type === null) {
            $record->loadDefaultValues();
        }

        return $record;
    }
}
