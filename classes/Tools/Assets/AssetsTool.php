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

namespace ILAB\MediaCloud\Tools\Assets;

use ILAB\MediaCloud\Tools\Storage\StorageTool;
use ILAB\MediaCloud\Tools\Tool;
use ILAB\MediaCloud\Tools\ToolsManager;
use ILAB\MediaCloud\Utilities\EnvironmentOptions;
use function ILAB\MediaCloud\Utilities\gen_uuid;
use function ILAB\MediaCloud\Utilities\json_response;
use ILAB\MediaCloud\Utilities\Logging\Logger;
use ILAB\MediaCloud\Utilities\View;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class AssetsTool extends Tool {
    public function __construct($toolName, $toolInfo, $toolManager) {
        parent::__construct($toolName, $toolInfo, $toolManager);

        $this->testForBadPlugins();
        $this->testForUselessPlugins();
    }

    public function setup() {
        parent::setup();

        if ($this->enabled()) {
            if (EnvironmentOptions::Option('ilab-assets-store-css', null, true)) {
                add_action('wp_print_styles', function() {
                    $this->handleStyles();
                });
            }

            if (EnvironmentOptions::Option('ilab-assets-store-js', null, true)) {
                add_action('wp_print_styles', function() {
                    $this->handleScripts();
                });
            }
        }
    }

    private function rootPath() {
        $home    = set_url_scheme( get_option( 'home' ), 'http' );
        $siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
        if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
            $wp_path_rel_to_home = str_ireplace( $home, '', $siteurl );
            $pos = strpos(ABSPATH, $wp_path_rel_to_home);
            if ($pos > 0) {
                $home_path = substr(ABSPATH, 0, $pos);
            }
        } else {
            $home_path = ABSPATH;
        }

        return str_replace( '\\', '/', $home_path );
    }

    private function handleStyles() {
        $processed = get_option('ilab_processed_assets', []);

        /** @var \WP_Styles $wp_styles */
        $wp_styles = wp_styles();

        $wp_styles->base_url = null;
        $wp_styles->content_url = null;

        $baseUrl = home_url();
        $basePath = rtrim($this->rootPath(), '/');
        $wpVer = get_bloginfo('version');

        $fileList = [];

        /** @var \_WP_Dependency $registered */
        foreach($wp_styles->registered as $key => $registered) {
            if (($registered->src === true) || ($registered->src === false)) {
                continue;
            }

            $isWp = false;
            $ver = $registered->ver;

            if (empty($ver)) {
                if (strpos($registered->src, '/wp-includes/') !== false) {
                    $isWp = true;
                    $ver = $wpVer;
                } else {
                    Logger::warning("CSS file '{$registered->src}' registered without version, skipping.");
                    continue;
                }
            }

            $hash = sha1($registered->src.'?'.$ver);
            if (!empty($processed[$hash])) {
                $entry = $processed[$hash];
                $cssKey = $entry['cssKey'];
                $wp_styles->registered[$cssKey]->src = $entry['url'];

                continue;
            }

            $scheme = parse_url($registered->src, PHP_URL_SCHEME);
            if (empty($scheme)) {
                if ($isWp) {
                    $srcPath = rtrim(ABSPATH, '/').$registered->src;
                } else {
                    $srcPath = $basePath . $registered->src;
                }
            } else {
                if (strpos($registered->src, $baseUrl) !== 0) {
                    continue;
                }

                $srcPath = $basePath.str_replace($baseUrl, '', $registered->src);
            }

            if (!file_exists($srcPath)) {
                continue;
            }

             $cssEntry = [
                 'cssKey' => $key,
                 'src' =>  $srcPath
            ];

            $imgFiles = [];
            $this->parseCSSUrls($srcPath, $isWp, $ver, $imgFiles);
            if (!empty($imgFiles)) {
                $cssEntry['img'] = $imgFiles;
            }

            $fileList[$hash] = $cssEntry;
        }

        $this->uploadCSSFiles($fileList);

        foreach($fileList as $hash => $entry) {
            $key = $entry['cssKey'];
            $wp_styles->registered[$key]->src = $entry['url'];
        }

        $processed = array_merge($processed, $fileList);
        update_option('ilab_processed_assets', $processed);
    }

    private function parseCSSUrls($cssFile, $isWp, $version, &$fileList) {
        $re = '/[:,\s]\s*url\s*\(\s*(?:\'(\S*?)\'|"(\S*?)"|((?:\\\\\s|\\\\\)|\\\\\"|\\\\\'|\S)*?))\s*\)/m';

        $basePath = pathinfo($cssFile, PATHINFO_DIRNAME);
        $css = file_get_contents($cssFile);
        preg_match_all($re, $css, $matches, PREG_SET_ORDER, 0);

        if (!empty($matches)) {
            foreach($matches as $match) {
                $imgFile = array_pop($match);
                if (empty($imgFile)) {
                    continue;
                }

                $imgFile = realpath($basePath.DIRECTORY_SEPARATOR.$imgFile);
                if (file_exists($imgFile)) {
                    $hash = sha1($imgFile.'?'.$version);
                    $fileList[$hash] = $imgFile;
                } else {
                    Logger::warning("CSS file '{$cssFile}' contains a missing image '{$imgFile}'");
                }
            }
        }

        $nice = 'tits';

    }

    private function uploadCSSFiles(&$files) {
        /** @var StorageTool $storageTool */
        $storageTool = ToolsManager::instance()->tools['storage'];

        $basePath = rtrim($this->rootPath(), '/');


        foreach($files as $hash => $cssEntry) {
            $key = str_replace($basePath, '', $cssEntry['src']);
            $key = ltrim($key, '/');

            $url = $storageTool->client()->upload($key, $cssEntry['src'], 'public-read');
            if (!empty($url)) {
                $cssEntry['url'] = $url;
            }

            if (!empty($cssEntry['img'])) {
                foreach($cssEntry['img'] as $imageHash => $image) {
                    $imgKey = str_replace($basePath, '', $image);
                    $imgKey = ltrim($imgKey, '/');

                    $storageTool->client()->upload($imgKey, $image, 'public-read');
                }
            }

            $files[$hash] = $cssEntry;
        }
    }

    private function handleScripts() {

    }
}