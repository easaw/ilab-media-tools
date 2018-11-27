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

namespace ILAB\MediaCloud\Utilities;

use duncan3dc\Laravel\Blade;
use duncan3dc\Laravel\BladeInstance;
use ILAB\MediaCloud\Utilities\Logging\Logger;

if (!defined('ABSPATH')) { header('Location: /'); die; }

final class View {
    private static $bladeInstance = null;

    private function __construct($view) {
    }

    private static function getTempDir() {
        $temp = EnvironmentOptions::Option(null, 'ILAB_MEDIA_CLOUD_VIEW_CACHE', null);
        if (!empty($temp)) {
            return trailingslashit($temp);
        }

        if (defined('WP_TEMP_DIR')) {
            return trailingslashit(WP_TEMP_DIR);
        }

        if (function_exists('sys_get_temp_dir')) {
            $temp = sys_get_temp_dir();
            if (@is_dir($temp) && wp_is_writable($temp)) {
                return trailingslashit($temp);
            }
        }

        $temp = ini_get('upload_tmp_dir');
        if (!empty($temp) && @is_dir($temp) && wp_is_writable($temp)) {
            return trailingslashit($temp);
        }

        $temp = WP_CONTENT_DIR . '/';
        if (@is_dir($temp) && wp_is_writable($temp)) {
            return $temp;
        }

        $temp = '/tmp/';
        if (@is_dir($temp) && wp_is_writable($temp)) {
            return $temp;
        }

        return null;
    }

    private static function bladeInstance() {
        if (static::$bladeInstance == null) {
            $cacheDir = static::getTempDir();

            Logger::info("Cache Dir: $cacheDir");
            static::$bladeInstance = new BladeInstance(ILAB_VIEW_DIR, $cacheDir.'media-cloud-views');
        }

        return static::$bladeInstance;
    }
    public static function render_view($view, $data) {
        if (strpos($view, '.php') == (strlen($view) - 4)) {
            $view = substr($view, 0,  (strlen($view) - 4));
        }

        return self::bladeInstance()->render(str_replace('.php', '', $view), $data);
    }
}
