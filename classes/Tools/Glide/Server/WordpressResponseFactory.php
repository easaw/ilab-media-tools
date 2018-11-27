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

use ILAB\MediaCloud\Utilities\EnvironmentOptions;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use League\Glide\Responses\ResponseFactoryInterface;

class WordpressResponseFactory implements ResponseFactoryInterface {

    /**
     * Create response.
     * @param  FilesystemInterface $cache Cache file system.
     * @param  string $path Cached file path.
     * @return mixed               The response object.
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function create(FilesystemInterface $cache, $path) {
        try {
            $contentType = $cache->getMimetype($path);
            $contentLength = (string) $cache->getSize($path);
        } catch (FileNotFoundException $ex) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            nocache_headers();
            require get_404_template();
            die();
        }

        header('Content-Type:'.$contentType);
        header('Content-Length:'.$contentLength);

        $cacheTTL = EnvironmentOptions::Option('ilab-media-glide-cache-ttl', null, 525600);
        if ($cacheTTL > 0) {
            $cacheTTLSeconds = $cacheTTL * 60;
            header('Cache-Control:'."max-age={$cacheTTLSeconds}, public");
            header('Expires:'.date_create("+{$cacheTTL} minutes")->format('D, d M Y H:i:s').' GMT');
        }

        $stream = $cache->readStream($path);

        if (ftell($stream) !== 0) {
            rewind($stream);
        }

        fpassthru($stream);
        fclose($stream);
        die;
    }
}