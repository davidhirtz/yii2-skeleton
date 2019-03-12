<?php

namespace davidhirtz\yii2\skeleton\filters;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use yii\base\InvalidConfigException;

/**
 * Class CorrectExifRotation.
 * @package davidhirtz\yii2\skeleton\filters
 *
 * @example
 * $imagine = new \Imagine\Imagick\Imagine();
 * $image = $imagine->open('/path/to/image.ext');
 *
 * $filter = new CorrectExifRotation();
 * $image = $filter->apply($image);
 */
class CorrectExifRotation implements FilterInterface
{
    /**
     * @var ColorInterface
     */
    public $background;

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $orientation = (int)ArrayHelper::getValue($this->getExifFromImage($image), 'Orientation');
        $rotate = 0;

        switch ($orientation) {
            case 8:
                $rotate = -90;
                break;

            case 3:
                $rotate = 180;
                break;

            case 6:
                $rotate = 90;
                break;
        }

        if ($rotate) {
            $image->rotate($rotate, $this->background);

            if ($image instanceof \Imagine\Imagick\Image) {
                $imagick = $image->getImagick();
                $imagick->setImageOrientation($imagick::ORIENTATION_TOPLEFT);
            }
        }

        return $image;
    }

    /**
     * @param ImageInterface $image
     * @return array|bool
     */
    private function getExifFromImage(ImageInterface $image)
    {
        if (!extension_loaded('exif')) {
            throw new InvalidConfigException('Exif extension must be enabled for this filter.');
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        $data = exif_read_data("data://image/jpeg;base64," . base64_encode($image->get('jpg')));
        return is_array($data) ? $data : [];
    }
}