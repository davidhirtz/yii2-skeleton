<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

trait TagImageAttributesTrait
{
    public function sizes(?string ...$sizes): self
    {
        $items = array_diff($sizes, [null]);
        return $this->attribute('sizes', $items ? implode(',', $items) : null);
    }

    public function src(?string $src): static
    {
        return $this->attribute('src', $src);
    }

    /**
     * @param array<int|string, string>|string $srcset
     */
    public function srcset(array|string $srcset): static
    {
        if (is_array($srcset)) {
            $items = [];

            foreach ($srcset as $descriptor => $url) {
                $items[] = "$url " . (is_numeric($descriptor) ? "{$descriptor}w" : $descriptor);
            }

            if (count($items) === 1 && !array_key_exists('src', $this->attributes)) {
                return $this->src(array_pop($srcset));
            }

            $srcset = implode(',', $items);
        }

        return $this->attribute('srcset', $srcset);
    }
}
