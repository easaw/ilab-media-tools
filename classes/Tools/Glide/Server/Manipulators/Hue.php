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

class Hue extends BaseManipulator
{
    /**
     * Perform filter image manipulation.
     * @param  Image $image The source image.
     * @return Image The manipulated image.
     */
    public function run(Image $image)
    {
        if (!$this->hue) {
            return $image;
        }

        $core = $image->getCore();
        if (is_a($core, '\Imagick')) {
            $hue = 100 + (100.0 * ($this->hue / 180.0));
            $core->modulateImage(100, 100, $hue);
        }

        return $image;
    }
}