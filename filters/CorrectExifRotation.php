<?php

namespace davidhirtz\yii2\skeleton\filters;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Image;
use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use yii\base\InvalidConfigException;

/**
 * Class CorrectExifRotation
 * @package davidhirtz\yii2\skeleton\filters
 *
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
        $angle = 0;

        switch ($orientation) {
            case 8:
                $angle = -90;
                break;

            case 3:
                $angle = 180;
                break;

            case 6:
                $angle = 90;
                break;
        }

        return $angle ? Image::rotate($image, $angle, $this->background) : $image;
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

        $data = exif_read_data("data://image/jpeg;base64," . base64_encode($image->get('jpg')));
        return is_array($data) ? $data : [];
    }
}