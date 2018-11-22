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

namespace ILAB\MediaCloud\Tools\Glide;

use ILAB\MediaCloud\Tools\DynamicImages\DynamicImagesToolBase;
use ILAB\MediaCloud\Tools\DynamicImages\WordPressUploadsAdapter;
use ILAB\MediaCloud\Utilities\EnvironmentOptions;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Urls\UrlBuilder;
use underDEV\Utils\Files;

if(!defined('ABSPATH')) {
    header('Location: /');
    die;
}


/**
 * Class GlideTool
 *
 * Glide tool.
 */
class GlideTool extends DynamicImagesToolBase {
    protected $basePath = null;
    protected $cdnHost = null;
    protected $convertPNG = false;
    protected $usePJPEG = true;
    protected $cacheMasterImages = true;
    protected $maxWidth = 4000;

    public function __construct($toolName, $toolInfo, $toolManager) {
        parent::__construct($toolName, $toolInfo, $toolManager);

        $this->signingKey = EnvironmentOptions::Option('ilab-media-glide-signing-key', null, false);
        if (empty($this->signingKey)) {
            $this->signingKey = bin2hex(random_bytes(32));
            update_option('ilab-media-glide-signing-key', $this->signingKey);
        }

        $this->basePath = EnvironmentOptions::Option('ilab-media-glide-image-path', null, false);
        if (empty($this->basePath)) {
            $this->basePath = '/__images/';
            update_option('ilab-media-glide-image-path', $this->basePath);
        }
        $this->basePath = '/'.trim($this->basePath,'/').'/';

        $this->imageQuality = EnvironmentOptions::Option('ilab-media-glide-default-quality');
        $this->keepThumbnails = EnvironmentOptions::Option('ilab-media-glide-generate-thumbnails', null, true);
        $this->cdnHost = EnvironmentOptions::Option('ilab-media-glide-cdn', null, null);
        $this->convertPNG = EnvironmentOptions::Option('ilab-media-glide-convert-png', null, false);
        $this->usePJPEG = EnvironmentOptions::Option('ilab-media-glide-progressive-jpeg', null, true);
        $this->maxWidth = EnvironmentOptions::Option('ilab-media-glide-max-size', null, 4000);
        $this->cacheMasterImages = EnvironmentOptions::Option('ilab-media-glide-cache-remote', null, true);

        add_filter('do_parse_request', function($do, \WP $wp) {
            if (strpos($_SERVER['REQUEST_URI'], $this->basePath) === 0) {
                $file = str_replace($this->basePath, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

                $this->renderImage($file);
                die;
            }

            return $do;
        }, 100, 2);
    }

    //region Render Image

    protected function renderImage($file) {
        try {
            SignatureFactory::create($this->signingKey)->validateRequest(trim($this->basePath.$file,'/'), $_GET);
        } catch (SignatureException $e) {
            return false;
        }


        if ($this->toolManager->toolEnabled('storage')) {
            if (!$this->cacheMasterImages) {
                $source = new Filesystem($this->toolManager->tools['storage']->client()->adapter());
            } else {
                $source = new Filesystem(new WordPressUploadsAdapter($this->toolManager->tools['storage']->client()->adapter()));
            }
        } else {
            $source = new Files(new Local(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'uploads'));
        }

        $server = ServerFactory::create([
            'source' => $source,
            'cache' => new Filesystem(new Local(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'uploads/glide-cache/')),
            'max_image_size' => $this->maxWidth * $this->maxWidth,
            'base_url' => $this->basePath
        ]);

        $server->outputImage($file, $_GET);
    }

    //endregion

    //region ToolBase Override
    public function enabled() {
        $enabled = parent::enabled();

        if ($enabled) {
            return !empty($this->signingKey);
        }

        return false;
    }
    //endregion

    //region URL Generation
    private function createAbsoluteURL($relativeURL) {
        if (!empty($this->cdnHost)) {
            return trim($this->cdnHost, '/').$relativeURL;
        }

        return home_url($relativeURL);
    }

    /**
     * Builds the parameters for generating Imgix URLs
     *
     * @param array $params
     * @param string $mimetype
     *
     * @return array
     */
    private function buildGlideParams($params, $mimetype = '') {
        $format = null;
        if(empty($params['fm'])) {
            if ($mimetype == 'image/gif') {
                $format = 'gif';
            } else if ($mimetype == 'image/png') {
                if ($this->convertPNG) {
                    $format = ($this->usePJPEG) ? 'pjpg' : 'jpg';
                } else {
                    $format = 'png';
                }
            } else {
                $format = ($this->usePJPEG) ? 'pjpg' : 'jpg';
            }
        } else {
            $format = $params['fm'];
        }
        unset($params['fm']);

        if (!empty($format)) {
            $params['fm'] = $format;
        }


        if (isset($params['flip']) && (strpos($params['flip'], ',') > 0)) {
            $params['flip'] = 'both';
        }


        if($this->imageQuality) {
            $params['q'] = $this->imageQuality;
        }

        foreach($this->paramPropsByType['media-chooser'] as $key => $info) {
            if(isset($params[$key]) && !empty($params[$key])) {
                $media_id = $params[$key];
                unset($params[$key]);
                $markMeta = wp_get_attachment_metadata($media_id);
                if (isset($markMeta['s3'])) {
                    $params[$info['imgix-param']] = '/'.$markMeta['s3']['key'];
                } else {
                    $params[$info['imgix-param']] = '/'.$markMeta['file'];
                }
            } else {
                unset($params[$key]);
                if(isset($info['dependents'])) {
                    foreach($info['dependents'] as $depKey) {
                        unset($params[$depKey]);
                    }
                }
            }
        }

        if(isset($params['border-width']) && isset($params['border-color'])) {
            $color = $params['border-color'];
            if (strpos($color, '#') === 0) {
                $color = substr($color, 1);
            }

            if (strlen($color) == 8) {
                $color = substr($color, 2);
            }

            $borderType = (empty($params['border-type'])) ? 'overlay' : $params['border-type'];

            $params['border'] = $params['border-width'].','.$color.','.$borderType;
        }

        unset($params['border-width']);
        unset($params['border-color']);
        unset($params['border-type']);

        unset($params['padding-width']);
        unset($params['padding-color']);

        unset($params['auto']);
        unset($params['enhance']);
        unset($params['redeye']);

        return $params;
    }

    protected function buildSizedImage($id, $size) {
        $meta = wp_get_attachment_metadata($id);
        if(!$meta || empty($meta)) {
            return false;
        }

        $imageFile = (!empty($meta['s3']['key'])) ? $meta['s3']['key'] : $meta['file'];
        if (empty($imageFile)) {
            return false;
        }

        $builder = new UrlBuilder($this->basePath, $this->signingKey);


        $is_crop = ((count($size) >= 3) && ($size[2] == 'crop'));
        if (!$is_crop && $this->shouldCrop) {
            $this->shouldCrop = false;
            $is_crop = true;
        }

        if ($is_crop && (($size[0] === 0) || ($size[1] === 0))) {
            if ($size[0] === 0) {
                $size[0] = 10000;
            } else {
                $size[1] = 10000;
            }

            $is_crop = false;
        }

        if(isset($size['width'])) {
            $size = [
                $size['width'],
                $size['height']
            ];
        }

        $params = [
            'fit' => ($is_crop) ? 'crop' : 'fit',
            'w' => $size[0],
            'h' => $size[1],
            'fm' => 'jpg'
        ];

        $params = apply_filters('ilab-imgix-filter-parameters', $params, $size, $id, $meta);

        $result = [
            $this->createAbsoluteURL($builder->getUrl($imageFile, $params)),
            $size[0],
            $size[1]
        ];

        return $result;
    }

    protected function buildImage($id, $size, $params = null, $skipParams = false, $mergeParams = null, $newSize = null, $newMeta=null) {
        if(is_array($size)) {
            return $this->buildSizedImage($id, $size);
        }

        $meta = wp_get_attachment_metadata($id);
        if(!$meta || empty($meta)) {
            if(!$meta || empty($meta)) {
                if (!empty($newMeta)) {
                    $meta = $newMeta;
                } else {
                    return false;
                }
            }
        }

        $imageFile = (!empty($meta['s3']['key'])) ? $meta['s3']['key'] : $meta['file'];
        if (empty($imageFile)) {
            return false;
        }

        $mimetype = get_post_mime_type($id);

        $builder = new UrlBuilder($this->basePath, SignatureFactory::create($this->signingKey));

        if($size == 'full' && !$newSize) {
            if(!isset($meta['width']) || !isset($meta['height'])) {
                return false;
            }

            if(!$params) {
                if(isset($meta['imgix-params'])) {
                    $params = $meta['imgix-params'];
                } else {
                    $params = [];
                }
            }

            $params = $this->buildGlideParams($params, $mimetype);
            $params = apply_filters('ilab-imgix-filter-parameters', $params, $size, $id, $meta);

            $result = [
                $this->createAbsoluteURL($builder->getUrl($imageFile, ($skipParams) ? [] : $params)),
                $meta['width'],
                $meta['height'],
                false
            ];

            return $result;
        }

        if($newSize) {
            $sizeInfo = $newSize;
        } else {
            $sizeInfo = ilab_get_image_sizes($size);
        }

        if(!$sizeInfo) {
            return false;
        }

        $metaSize = null;
        if(isset($meta['sizes'][$size])) {
            $metaSize = $meta['sizes'][$size];
        }

        $doCrop = !empty($sizeInfo['crop']);

        if(empty($params)) {
            $sizeParams = (!empty($sizeInfo['imgix']) && is_array($sizeInfo['imgix'])) ? $sizeInfo['imgix'] : [];

            // get the settings for this image at this size
            if(isset($meta['imgix-size-params'][$size])) {
                $params = array_merge($sizeParams, $meta['imgix-size-params'][$size]);
            }


            if(empty($params)) // see if a preset has been globally assigned to a size and use that
            {
                $presets = get_option('ilab-imgix-presets');
                $sizePresets = get_option('ilab-imgix-size-presets');

                if($presets && $sizePresets && isset($sizePresets[$size]) && isset($presets[$sizePresets[$size]])) {
                    $params = array_merge($sizeParams, $presets[$sizePresets[$size]]['settings']);
                }
            }

            // still no parameters?  use any that may have been assigned to the full size image
            if(empty($params) && (isset($meta['imgix-params']))) {
                $params = array_merge($sizeParams, $meta['imgix-params']);
            } else if(empty($params)) // too bad so sad
            {
                $params = $sizeParams;
            }
        }

        if ($doCrop) {
            $params['w'] = $sizeInfo['width'] ?: $sizeInfo['height'];
            $params['h'] = $sizeInfo['height'] ?: $sizeInfo['width'];
            $params['fit'] = 'crop';

            if($metaSize) {
                $metaSize = $meta['sizes'][$size];
                if(isset($metaSize['crop'])) {
                    $metaSize['crop']['x'] = round($metaSize['crop']['x']);
                    $metaSize['crop']['y'] = round($metaSize['crop']['y']);
                    $metaSize['crop']['w'] = round($metaSize['crop']['w']);
                    $metaSize['crop']['h'] = round($metaSize['crop']['h']);
                    $params['crop'] = "{$metaSize['crop']['w']},{$metaSize['crop']['h']},{$metaSize['crop']['x']},{$metaSize['crop']['y']}";
                }
            }

            if (isset($params['focalpoint'])) {
                unset($params['crop']);
                unset($params['fit']);
                $params['fit'] = "crop-{$params['fp-x']}-{$params['fp-y']}";
            } else {
                if (!empty($sizeInfo['crop']) && is_array($sizeInfo['crop'])) {
                    list($cropX, $cropY) = $sizeInfo['crop'];
                    if (!empty($cropX) && !empty($cropY)) {
                        unset($params['crop']);
                        $params['fit'] = "crop-{$cropX}-{$cropY}";
                    }
                }
            }
        } else {
            $mw = !empty($meta['width']) ? $meta['width'] : 10000;
            $mh = !empty($meta['height']) ? $meta['height'] : 10000;

            $w = !empty($sizeInfo['width']) ? $sizeInfo['width'] : 10000;
            $h = !empty($sizeInfo['height']) ? $sizeInfo['height'] : 10000;

            $newSize = sizeToFitSize($mw, $mh, $w, $h);
            $params['w'] = $newSize[0];
            $params['h'] = $newSize[1];
            $params['fit'] = 'contain';
        }

        unset($params['fp-x']);
        unset($params['fp-y']);
        unset($params['fp-z']);

        unset($params['focalpoint']);
        unset($params['faceindex']);

        if($mergeParams && is_array($mergeParams)) {
            $params = array_merge($params, $mergeParams);
        }

        if($size && !is_array($size)) {
            $params['wpsize'] = $size;
        }

        $params = $this->buildGlideParams($params, $mimetype);
        $params = apply_filters('ilab-imgix-filter-parameters', $params, $size, $id, $meta);

        $imageFile = (isset($meta['s3'])) ? $meta['s3']['key'] : $meta['file'];

        $result = [
            $this->createAbsoluteURL($builder->getUrl($imageFile, $params)),
            $params['w'],
            $params['h'],
            true
        ];

        return $result;
    }
    //endregion
}