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

class Rotation extends BaseManipulator {

    /**
     * Perform the image manipulation.
     * @return Image The manipulated image.
     */
    public function run(Image $image) {
        $rotation = $this->rot;
        if (!empty($rotation)) {
            $w = $image->width();
            $h = $image->height();

            $image->rotate($rotation);

            if (($rotation != 180) && ($rotation != -180)) {
                $scale = min($w / $image->width(), $h / $image->height());
                $image->crop(floor($w * $scale), floor($h * $scale));
                $image->resize($w, $h);
            }

            return $image;
        }

        return $image;
    }
}