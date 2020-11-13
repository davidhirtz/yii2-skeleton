<?php

namespace davidhirtz\yii2\skeleton\helpers;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Point;
use yii\db\Exception;
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
     * Replacement for Imagick's native `writeImages` as it doesn't support stream wrappers. Image format
     * can be set via options, otherwise the filename extension will be used.
     *
     * @param ImageInterface $image
     * @param string $filename
     * @param array $options
     * @return false|int
     */
    public static function saveImage($image, $filename, $options = [])
    {
        if (!$format = ArrayHelper::remove($options, 'format')) {
            $format = pathinfo($filename, PATHINFO_EXTENSION);
        }

        return file_put_contents($filename, $image->get($format, $options));
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
        $image = static::ensureImageInterfaceInstance($image);

        if ($bgColor) {
            $image = static::resize($image, $width, $height);
            $size = $image->getSize();

            $palette = new RGB();
            $thumb = static::getImagine()->create(new Box($width, $height), $palette->color($bgColor, $bgAlpha));

            $x = ceil(($width - $size->getWidth()) / 2);
            $y = ceil(($height - $size->getHeight()) / 2);

            return $thumb->paste($image, new Point($x, $y));
        }

        $image = $image->copy();

        $size = $image->getSize();
        $ratio = max($width / $size->getWidth(), $height / $size->getHeight());

        $newWidth = ceil($size->getWidth() * $ratio);
        $newHeight = ceil($size->getHeight() * $ratio);

        $image->resize(new Box($newWidth, $newHeight));

        if ($newWidth != $width || $newHeight != $height) {
            $x = ceil(($newWidth - $width) / 2);
            $y = ceil(($newHeight - $height) / 2);

            $image->crop(new Point($x, $y), new Box($width, $height));
        }

        return $image;
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
     * @param string|resource|ImageInterface $image
     * @param int $angle
     * @param ColorInterface|null $background
     * @return ImageInterface
     */
    public static function rotate($image, $angle, $background = null)
    {
        return static::ensureImageInterfaceInstance($image)->rotate($angle, $background);
    }

    /**
     * @param string $filename
     * @param string|null $extension
     * @return array|bool
     */
    public static function getImageSize($filename, $extension = null)
    {
        if (!$extension) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
        }

        return strtolower($extension) == 'svg' ? static::getSvgDimensions($filename) : @getimagesize($filename);
    }

    /**
     * Extracts width and height from SVG attributes including viewBox.
     *
     * @param string $filename
     * @return array|bool
     */
    public static function getSvgDimensions($filename)
    {
        try {
            $svg = simplexml_load_file($filename);
            $attributes = $svg->attributes();
            $dimensions = [0, 0];

            if ($attributes->width && $attributes->height) {
                $dimensions[0] = (int)$attributes->width;
                $dimensions[1] = (int)$attributes->height;
            } elseif (preg_match('/(\d+(\.\d+)?) (\d+(\.\d+)?)$/', $attributes->viewBox, $match)) {
                $viewBox = explode(' ', $match[0]);
                $dimensions[0] = (int)$viewBox[0];
                $dimensions[1] = (int)$viewBox[1];
            }

            return $dimensions;
        } catch (Exception $exception) {
            Yii::error($exception);
        }

        return false;
    }

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
}
