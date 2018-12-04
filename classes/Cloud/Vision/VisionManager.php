<?php
// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// Uses code from:
// Persist Admin Notices Dismissal
// by Agbonghama Collins and Andy Fragen
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace ILAB\MediaCloud\Cloud\Vision;

use ILAB\MediaCloud\Utilities\EnvironmentOptions;

if (!defined('ABSPATH')) { header('Location: /'); die; }

final class VisionManager {
    private static $driver = null;
    private static $registry = [];
    private static $instance = null;

    /**
     * Gets the currently configured vision instance.
     *
     * @throws VisionException
     * @return VisionDriver
     */
    public static function visionInstance() {
        if (self::$instance) {
            return self::$instance;
        }

        if (!isset(self::$registry[self::driver()])) {
            throw new VisionException("Invalid driver '".self::driver()."'");
        }

        $class = self::$registry[self::driver()];
        self::$instance = new $class();

        return self::$instance;
    }

    public static function driver() {
        if (!self::$driver) {
            self::$driver = EnvironmentOptions::Option('ilab-vision-provider','ILAB_VISION_PROVIDER', 'rekognition');
        }

        return self::$driver;
    }

    /**
     * Resets the current vision interface
     */
    public static function resetVisionInstance() {
        self::$instance = null;
    }

    /**
     * Registers a vision driver
     * @param $identifier
     * @param $class
     */
    public static function registerDriver($identifier, $class) {
        self::$registry[$identifier] = $class;
    }

    /**
     * @param $identifier
     *
     * @return VisionDriver
     */
    public static function driverClass($identifier) {
        if (!isset(self::$registry[$identifier])) {
            return null;
        }

        return self::$registry[$identifier];
    }
}