<?php

namespace davidhirtz\yii2\skeleton\helpers;

use Imagick;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Yii;
use yii\db\Exception;
use yii\imagine\BaseImage;

class Image extends BaseImage
{
    public static function getImage(ImageInterface|string|null $image): ImageInterface
    {
        return static::ensureImageInterfaceInstance($image);
    }

    /**
     * Replacement for Imagick's native `writeImages` as it doesn't support stream wrappers. Image format
     * can be set via options, otherwise the filename extension will be used.
     */
    public static function saveImage(ImageInterface $image, string $filename, array $options = []): int|bool
    {
        if (!$format = ArrayHelper::remove($options, 'format')) {
            $format = pathinfo($filename, PATHINFO_EXTENSION);
        }

        return file_put_contents($filename, $image->get($format, $options));
    }

    /**
     * Resizes and crops image to exactly fit the given dimensions.
     */
    public static function fit(
        ImageInterface|string $image,
        int $width,
        int $height,
        ?string $bgColor = null,
        ?int $bgAlpha = null
    ): ImageInterface {
        $image = static::ensureImageInterfaceInstance($image);

        if ($bgColor) {
            $image = static::resize($image, $width, $height);
            $size = $image->getSize();

            $palette = new RGB();
            $thumb = static::getImagine()->create(new Box($width, $height), $palette->color($bgColor, $bgAlpha));

            $x = (int)ceil(($width - $size->getWidth()) / 2);
            $y = (int)ceil(($height - $size->getHeight()) / 2);

            return $thumb->paste($image, new Point($x, $y));
        }

        $image = $image->copy();

        $size = $image->getSize();
        $ratio = max($width / $size->getWidth(), $height / $size->getHeight());

        $newWidth = (int)ceil($size->getWidth() * $ratio);
        $newHeight = (int)ceil($size->getHeight() * $ratio);

        $image->resize(new Box($newWidth, $newHeight));

        if ($newWidth != $width || $newHeight != $height) {
            $x = (int)ceil(($newWidth - $width) / 2);
            $y = (int)ceil(($newHeight - $height) / 2);

            $image->crop(new Point($x, $y), new Box($width, $height));
        }

        return $image;
    }

    /**
     * @noinspection PhpUnused
     */
    public static function smartResize(
        ImageInterface|string $image,
        ?int $width = null,
        ?int $height = null,
        bool $allowUpscaling = false,
        ?string $bgColor = null,
        ?int $bgAlpha = null
    ): ImageInterface {
        return (!$width || !$height) ? static::resize($image, $width, $height, true, $allowUpscaling) : static::fit($image, $width, $height, $bgColor, $bgAlpha);
    }

    public static function resize($image, $width, $height, $keepAspectRatio = true, $allowUpscaling = false): ImageInterface
    {
        $image = static::ensureImageInterfaceInstance($image);
        return parent::resize($image, $width, $height, $keepAspectRatio, $allowUpscaling);
    }

    public static function rotate(ImageInterface|string $image, int $angle, ?ColorInterface $background = null): ImageInterface
    {
        $image = static::ensureImageInterfaceInstance($image);
        return static::setImageRotation($image)->rotate($angle, $background);
    }

    public static function autorotate($image, $color = '000000'): ImageInterface
    {
        return static::setImageRotation(parent::autorotate($image, $color));
    }

    public static function getImageSize(string $filename, ?string $extension = null): array|bool
    {
        if (!$extension) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
        }

        return strtolower($extension) == 'svg' ? static::getSvgDimensions($filename) : @getimagesize($filename);
    }

    /**
     * Extracts width and height from SVG attributes including viewBox.
     */
    public static function getSvgDimensions(string $filename): array|bool
    {
        try {
            $svg = simplexml_load_file($filename);
            $attributes = $svg->attributes();
            $dimensions = [0, 0];

            if ($attributes->width && $attributes->height) {
                $dimensions[0] = (int)$attributes->width;
                $dimensions[1] = (int)$attributes->height;
            } elseif (preg_match('/(\d+(\.\d+)?) (\d+(\.\d+)?)$/', (string) $attributes->viewBox, $match)) {
                $viewBox = explode(' ', $match[0]);
                $dimensions[0] = (int)$viewBox[0];
                $dimensions[1] = (int)$viewBox[1];
            }

            return $dimensions;
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (Exception $exception) {
            Yii::error($exception);
        }

        return false;
    }

    public static function setImageRotation(ImageInterface|string $image, ?int $rotation = null): ImageInterface
    {
        if ($image instanceof \Imagine\Imagick\Image) {
            $imagick = $image->getImagick();
            $imagick->setImageOrientation($rotation ?: Imagick::ORIENTATION_TOPLEFT);
        }

        return $image;
    }

    protected static function ensureImageInterfaceInstance($image): ImageInterface
    {
        // Prevent loading remote resources via Imagine as doesn't support stream wrappers
        // such as Amazon S3. This makes sure remote files are loaded via fopen.
        if (is_string($image) && !stream_is_local($image = Yii::getAlias($image))) {
            $image = fopen($image, 'r');
        }

        return parent::ensureImageInterfaceInstance($image);
    }
}
