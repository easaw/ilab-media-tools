<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace ILAB\MediaCloud\Tools\Glide\Server\Manipulators;

use Intervention\Image\Image;
use League\Glide\Manipulators\BaseManipulator;

class Tint extends BaseManipulator
{
    /**
     * Perform filter image manipulation.
     * @param  Image $image The source image.
     * @return Image The manipulated image.
     */
    public function run(Image $image)
    {
        if (!$this->blend) {
            return $image;
        }

        $core = $image->getCore();
        if (is_a($core, '\Imagick')) {
            $tint = str_replace('#', '', $this->blend);

            $alpha = 1.0;
            if (strlen($tint) == 8) {
                $alpha = hexdec(substr($tint, 0, 2)) / 255.0;
                $tint = substr($tint, 2);
            }

            $rval = hexdec(substr($tint, 0, 2));
            $gval = hexdec(substr($tint, 2, 2));
            $bval = hexdec(substr($tint, 4, 2));

            $color = new \ImagickPixel("#{$tint}");
            $opacity = new \ImagickPixel("rgba($rval, $gval, $bval, 1.0)");

            $tintedImage = clone $image;
            $tintedImage->getCore()->tintImage($color, $opacity);
            $tintedImage->opacity($alpha * 100);

            $image->insert($tintedImage);
        }

        return $image;
    }
}