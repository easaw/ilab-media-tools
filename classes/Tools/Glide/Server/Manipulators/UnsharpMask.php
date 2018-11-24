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

class UnsharpMask extends BaseManipulator
{
    /**
     * Perform filter image manipulation.
     * @param  Image $image The source image.
     * @return Image The manipulated image.
     */
    public function run(Image $image)
    {
        if (!$this->usm) {
            return $image;
        }

        $core = $image->getCore();
        if (is_a($core, '\Imagick')) {
            $radius = (!$this->usmrad) ? 0 : $this->usmrad;

            $core->unsharpMaskImage($radius, 1, $this->usm, 0);
        }

        return $image;
    }
}