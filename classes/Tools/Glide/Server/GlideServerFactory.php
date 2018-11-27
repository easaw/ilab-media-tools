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

namespace ILAB\MediaCloud\Tools\Glide\Server;

use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\AutoEnhance;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Border;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Hue;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Monochrome;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Padding;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Rotation;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Saturation;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Sepia;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\Tint;
use ILAB\MediaCloud\Tools\Glide\Server\Manipulators\UnsharpMask;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Manipulators\Background;
use League\Glide\Manipulators\Blur;
use League\Glide\Manipulators\Brightness;
use League\Glide\Manipulators\Contrast;
use League\Glide\Manipulators\Crop;
use League\Glide\Manipulators\Encode;
use League\Glide\Manipulators\Filter;
use League\Glide\Manipulators\Flip;
use League\Glide\Manipulators\Gamma;
use League\Glide\Manipulators\Orientation;
use League\Glide\Manipulators\Pixelate;
use League\Glide\Manipulators\Sharpen;
use League\Glide\Manipulators\Size;
use League\Glide\Manipulators\Watermark;

class GlideServerFactory extends ServerFactory {
    /** @var GlideServerFactory|null  */
    private static $instance = null;

    /**
     * Create configured server.
     * @param  array  $config Configuration parameters.
     * @return Server Configured server.
     */
    public static function create(array $config = [])
    {
        static::$instance = new self($config);
        return static::$instance->getServer();
    }

    public static function instance() {
        return static::$instance;
    }

    public function getManipulators()
    {
        return [
            new Orientation(),
            new Crop(),
            new Size($this->getMaxImageSize()),
            new Flip(),
            new Rotation(),
            new AutoEnhance(),
            new Hue(),
            new Saturation(),
            new Brightness(),
            new Contrast(),
            new Gamma(),
            new Sharpen(),
            new UnsharpMask(),
            new Filter(),
            new Blur(),
            new Pixelate(),
            new Tint(),
            new Monochrome(),
            new Sepia(),
            new Watermark($this->getWatermarks(), $this->getWatermarksPathPrefix()),
            new Background(),
            new Padding(),
            new Border(),
            new Encode(),
        ];
    }
}