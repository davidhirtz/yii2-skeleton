<?php

namespace davidhirtz\yii2\skeleton\helpers;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use yii\imagine\BaseImage;
use Imagine\Image\Palette\RGB;
use Yii;

/**
 * Class Image
 * @package davidhirtz\yii2\skeleton\helpers
 */
class Image extends BaseImage
{
    /**
     * @inheritDoc
     */
    protected static function ensureImageInterfaceInstance($image)
    {
        // Prevent loading remote resources via Imagine as doesn't support stream wrappers
        // such as Amazon S3. This makes sure remote files are loaded via fopen.
        if (is_string($image) && !stream_is_local($image = Yii::getAlias($image))) {
            $image = fopen($image, 'r');
        }

        return parent::ensureImageInterfaceInstance($image);
    }

    /**
     * Resizes and crops image to exactly fit the given dimensions.
     *
     * @param string|resource|ImageInterface $image
     * @param int $width
     * @param int $height
     * @param string|null $bgColor
     * @param int|null $bgAlpha
     *
     * @return ImageInterface
     */
    public static function fit($image, $width, $height, $bgColor = null, $bgAlpha = null)
    {
        if ($bgColor) {
            $img = static::resize($image, $width, $height);
            $size = $img->getSize();

            $palette = new RGB();
            $thumb = static::getImagine()->create(new Box($width, $height), $palette->color($bgColor, $bgAlpha));

            $x = ceil(($width - $size->getWidth()) / 2);
            $y = ceil(($height - $size->getHeight()) / 2);

            return $thumb->paste($img, new Point($x, $y));
        } else {
            $img = static::ensureImageInterfaceInstance($image)
                ->copy();

            $size = $img->getSize();
            $ratio = max($width / $size->getWidth(), $height / $size->getHeight());

            $newWidth = ceil($size->getWidth() * $ratio);
            $newHeight = ceil($size->getHeight() * $ratio);

            $img->resize(new Box($newWidth, $newHeight));

            if ($newWidth != $width || $newHeight != $height) {
                $x = ceil(($newWidth - $width) / 2);
                $y = ceil(($newHeight - $height) / 2);

                $img->crop(new Point($x, $y), new Box($width, $height));
            }

            return $img;
        }
    }

    /**
     * Shortcut method.
     *
     * @param string|resource|ImageInterface $image
     * @param int|null $width
     * @param int|null $height
     * @param bool $allowUpscaling
     * @param string|null $bgColor
     * @param int|null $bgAlpha
     *
     * @return ImageInterface
     */
    public static function smartResize($image, $width = null, $height = null, $allowUpscaling = false, $bgColor = null, $bgAlpha = null)
    {
        return (!$width || !$height) ? static::resize($image, $width, $height, true, $allowUpscaling) : static::fit($image, $width, $height, $bgColor, $bgAlpha);
    }

    /**
     * @inheritDoc
     */
    public static function resize($image, $width, $height, $keepAspectRatio = true, $allowUpscaling = false)
    {
        // Ensure actual implementation of "ensureImageInterfaceInstance".
        return parent::resize(static::ensureImageInterfaceInstance($image), $width, $height, $keepAspectRatio, $allowUpscaling);
    }

    /**
     * @param string $filename
     * @param string|null $extension
     * @return array
     */
    public static function getImageSize($filename, $extension = null)
    {
        if (!$extension) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
        }

        return strtolower($extension) == 'svg' ? static::getSvgDimensions($filename) : @getimagesize($filename);
    }

    /**
     * @param string $filename
     * @return array
     */
    public static function getSvgDimensions($filename)
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $svg = simplexml_load_file($filename);
        $attributes = $svg->attributes();

        $dimensions = array_fill(0, 1, 0);

        if ($attributes->width && $attributes->height) {
            $dimensions[0] = (int)$attributes->width;
            $dimensions[1] = (int)$attributes->height;
        } elseif (preg_match('/(\d+) (\d+)$/', $attributes->viewBox, $match)) {
            $dimensions[0] = (int)$match[1];
            $dimensions[1] = (int)$match[2];
        }

        return $dimensions;
    }
}
