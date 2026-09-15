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
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The upload endpoint of {@see UploadCustomAttribute}, shared by every form that renders such a field: the file is
 * parked in {@see Upload::$tempPath} and the field is answered re-rendered, carrying the token.
 *
 * It is reached from a form the acting user was already allowed to open, which is what the field's signature says —
 * so it is checked here rather than a permission of the model, which the skeleton cannot know. Nothing is published
 * either way: the record's own save is what moves a file out of the temporary directory.
 *
 * @extends Controller<Module>
 */
class UploadController extends Controller
{
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
     * A translatable definition is one field per language, and the form builds those by cloning the definition's own
     * — see {@see \Hirtz\Skeleton\Widgets\Forms\Fieldset::configure()}. The response has to carry the same
     * container id as the one the form rendered, so the field is bound to the attribute that was asked for.
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
     * @return string|null the token, or `null` when the response says what went wrong instead
     */
    protected function upload(UploadCustomAttribute $definition, Upload $upload): ?string
    {
        $file = ChunkedUploadedFile::getInstanceByName('upload');

        if ($file === null) {
            throw new NotFoundHttpException();
        }

        if ($file->isPartial()) {
            $this->response->setStatusCode(201);
            return null;
        }

        $error = $definition->validateUploadedFile($file);

        if ($error !== null) {
            // An upload PHP rejected carries no temporary file — an aborted transfer reports an empty `tmp_name`.
            if ($file->tempName) {
                FileHelper::unlink($file->tempName);
            }

            $this->response->setStatusCode(400, $error);

            return null;
        }

        $upload->collectGarbage(force: false);
        $token = $upload->createTempFile($file);

        if ($token === null) {
            $this->response->setStatusCode(400, Yii::t('skeleton', 'UPLOAD_FAILED_ERROR'));
        }

        return $token;
    }

    /**
     * A record of the right shape, never one from the database: only the definitions are wanted, and the file is not
     * attached to anything until the form that opened this is saved.
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
