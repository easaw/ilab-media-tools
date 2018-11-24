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

class Border extends \League\Glide\Manipulators\Border {
    public function runOverlay(Image $image, $width, $color) {
        $image->rectangle(0, 0, $image->width(), $width, function ($draw) use ($color) {
            $draw->background($color);
        });

        $image->rectangle(0, 0, $width, $image->height(), function ($draw) use ($color) {
            $draw->background($color);
        });

        $image->rectangle(0, $image->height() - $width, $image->width(), $image->height(), function ($draw) use ($color) {
            $draw->background($color);
        });

        $image->rectangle($image->width() - $width, 0, $image->width(), $image->height(), function ($draw) use ($color) {
            $draw->background($color);
        });

        return $image;
    }
}