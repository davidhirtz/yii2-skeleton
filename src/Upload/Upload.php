<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Upload;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\web\UploadedFile;

/**
 * The `upload` application component: where a file attached to a record is kept, and where one is parked while the
 * record it belongs to is still being filled in.
 *
 * A file is uploaded before the record exists, so it lands in {@see Upload::$tempPath} under a token first and is
 * moved into place once the record has an id. The temporary files are collected by `upload/clear` and, with
 * {@see Upload::$gcProbability}, by the upload action itself.
 *
 * @see \Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute
 */
class Upload extends Component
{
    public const int TOKEN_LENGTH = 16;

    private const string TOKEN_PATTERN = '/^[\w-]{' . self::TOKEN_LENGTH . '}-[\w-]+\.[a-zA-Z0-9]+$/';

    /**
     * @var string where the attached files are kept, an alias or an absolute path. It must be readable by the web
     * server for {@see Upload::getUrl()} to resolve.
     */
    public string $path = '@webroot/attachments';

    /**
     * @var string|null the URL the path is served under, defaults to the path's location below `@webroot`. A CDN
     * serving the directory is named here.
     */
    public ?string $baseUrl = null;

    public string $tempPath = '@runtime/attachments';

    /**
     * @var int how long a file nobody saved is kept, in seconds.
     */
    public int $tempLifetime = 86400;

    /**
     * @var int the percentage of upload requests that also collect the abandoned temporary files.
     */
    public int $gcProbability = 1;

    /**
     * @var int the ceiling no definition may raise, in bytes.
     */
    public int $maxSize = 67108864;

    public static function getComponent(): self
    {
        /** @var self $upload */
        $upload = Yii::$app->get('upload');
        return $upload;
    }

    #[Override]
    public function init(): void
    {
        $this->path = rtrim((string)Yii::getAlias($this->path), '/') . '/';
        $this->tempPath = rtrim((string)Yii::getAlias($this->tempPath), '/') . '/';

        $webroot = rtrim((string)Yii::getAlias('@webroot'), '/') . '/';

        $this->baseUrl ??= str_starts_with($this->path, $webroot)
            ? '/' . substr($this->path, strlen($webroot))
            : '/';

        $this->baseUrl = rtrim($this->baseUrl, '/') . '/';

        parent::init();
    }

    /**
     * The attribute name is hashed rather than spelled out: the directory is public and the column it would
     * otherwise name is not.
     */
    public function getAttributePath(ActiveRecord $owner, string $attribute): string
    {
        return $this->getRecordPath($owner) . $this->getAttributeDirectory($attribute) . '/';
    }

    public function getUrl(ActiveRecord $owner, string $attribute, string $filename): string
    {
        return $this->baseUrl . $this->getRecordDirectory($owner) . '/'
            . $this->getAttributeDirectory($attribute) . '/' . rawurlencode($filename);
    }

    public function getFilePath(ActiveRecord $owner, string $attribute, string $filename): string
    {
        return $this->getAttributePath($owner, $attribute) . $filename;
    }

    /**
     * Moves what the upload action parked under the token into the record's own directory, replacing whatever was
     * there — an attribute holds one file.
     */
    public function store(ActiveRecord $owner, string $attribute, string $token): ?string
    {
        $tempFile = $this->getTempFile($token);

        if ($tempFile === null) {
            return null;
        }

        $path = $this->getAttributePath($owner, $attribute);
        FileHelper::removeDirectory($path);

        if (!FileHelper::createDirectory($path)) {
            return null;
        }

        $filename = $this->getFilenameFromToken($token);

        return FileHelper::rename($tempFile, $path . $filename) ? $filename : null;
    }

    public function copy(ActiveRecord $source, ActiveRecord $target, string $attribute): void
    {
        $from = $this->getAttributePath($source, $attribute);

        if (is_dir($from)) {
            FileHelper::copyDirectory($from, $this->getAttributePath($target, $attribute));
        }
    }

    public function delete(ActiveRecord $owner, string $attribute): void
    {
        FileHelper::removeDirectory($this->getAttributePath($owner, $attribute));
        $this->removeRecordPathIfEmpty($owner);
    }

    /**
     * @return string|null the token the field carries until the record is saved
     */
    public function createTempFile(UploadedFile $upload): ?string
    {
        $token = Yii::$app->getSecurity()->generateRandomString(self::TOKEN_LENGTH)
            . '-' . $this->createFilename($upload);

        if (!$this->isToken($token) || !FileHelper::createDirectory($this->tempPath)) {
            return null;
        }

        return $upload->saveAs($this->tempPath . $token) ? $token : null;
    }

    public function getTempFile(?string $token): ?string
    {
        if (!$this->isToken($token)) {
            return null;
        }

        $path = $this->tempPath . $token;

        return is_file($path) ? $path : null;
    }

    public function deleteTempFile(?string $token): void
    {
        $path = $this->getTempFile($token);

        if ($path !== null) {
            FileHelper::unlink($path);
        }
    }

    /**
     * A token and a stored filename share the attribute, so they have to be told apart by shape.
     */
    public function isToken(mixed $token): bool
    {
        return is_string($token) && (bool)preg_match(self::TOKEN_PATTERN, $token);
    }

    public function getFilenameFromToken(string $token): string
    {
        return substr($token, self::TOKEN_LENGTH + 1);
    }

    /**
     * @return string the name behind an attribute value, whichever of the two it is
     */
    public function getFilename(string $value): string
    {
        return $this->isToken($value) ? $this->getFilenameFromToken($value) : $value;
    }

    /**
     * The upload action is reached from every form that renders such a field, so what the field was allowed to offer
     * travels with the request rather than being taken from it. The acting user is part of it: a signature is not a
     * capability to hand on.
     */
    public function sign(string $modelClass, string $attribute, ?int $type = null): string
    {
        $data = implode('|', [$modelClass, $attribute, $type, WebUser::current()?->getId()]);
        return hash_hmac('sha256', $data, SecretKey::get());
    }

    public function isValidSignature(
        string $signature,
        string $modelClass,
        string $attribute,
        ?int $type = null,
    ): bool {
        return hash_equals($this->sign($modelClass, $attribute, $type), $signature);
    }

    public function collectGarbage(bool $force = true): int
    {
        if (!$force && random_int(1, 10000) > $this->gcProbability * 100) {
            return 0;
        }

        $lifetime = time() - $this->tempLifetime;
        $count = 0;

        foreach (glob(rtrim($this->tempPath, '/') . '/*') ?: [] as $file) {
            if (filemtime($file) <= $lifetime && FileHelper::unlink($file)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @return string the sanitized name the file is stored and offered for download under. A name that survives
     * neither the slug nor an extension is answered as one {@see Upload::isToken()} refuses, so the upload is
     * reported rather than silently dropped later.
     */
    public function createFilename(UploadedFile $upload): string
    {
        $extension = strtolower($upload->getExtension());
        $basename = Inflector::slug($upload->getBaseName(), '-', false);

        return ($basename ?: Yii::$app->getSecurity()->generateRandomString(8)) . ".$extension";
    }

    protected function getRecordPath(ActiveRecord $owner): string
    {
        return $this->path . $this->getRecordDirectory($owner) . '/';
    }

    protected function getRecordDirectory(ActiveRecord $owner): string
    {
        $table = $owner::getDb()->getSchema()->getRawTableName($owner::tableName());
        $key = $owner->getPrimaryKey();

        return $table . '/' . implode('-', array_map(strval(...), is_array($key) ? $key : [$key]));
    }

    protected function getAttributeDirectory(string $attribute): string
    {
        return substr(md5($attribute), 0, 8);
    }

    protected function removeRecordPathIfEmpty(ActiveRecord $owner): void
    {
        $path = $this->getRecordPath($owner);

        if (is_dir($path) && !(glob("$path*") ?: [])) {
            FileHelper::removeDirectory($path);
        }
    }
}
