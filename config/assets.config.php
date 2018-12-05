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

if (!defined('ABSPATH')) { header('Location: /'); die; }

return [
    "id" => "assets",
    "name" => "Assets",
    "description" => "Store and serve your theme and WordPress assets from cloud storage.",
    "class" => "ILAB\\MediaCloud\\Tools\\Assets\\AssetsTool",
    "dependencies" => ['storage'],
    "env" => "ILAB_MEDIA_ASSETS_ENABLED",
    "settings" => [
        "options-page" => "media-tools-assets",
        "options-group" => "ilab-media-assets-group",
        "groups" => [
            "ilab-media-asset-settings" => [
                "title" => "Asset Settings",
                "options" => [
                    "ilab-assets-store-css" => [
                        "title" => "Store CSS Assets",
                        "description" => "Theme and WordPress related CSS files will be copied to cloud storage and served from there.",
                        "type" => "checkbox",
                        "default" => true
                    ],
                    "ilab-assets-store-js" => [
                        "title" => "Store Javascript Assets",
                        "description" => "Theme and WordPress related javascript files will be copied to cloud storage and served from there.",
                        "type" => "checkbox",
                        "default" => true
                    ],
                ],
            ],
            "ilab-media-asset-performance" => [
                "title" => "CDN & Cache Settings",
                "options" => [
                    "ilab-assets-cache-control" => [
                        "title" => "Cache Control",
                        "description" => "Sets the Cache-Control metadata for assets, e.g. <code>public,max-age=2592000</code>.",
                        "type" => "text-field"
                    ],
                    "ilab-assets-expires" => [
                        "title" => "Content Expiration",
                        "description" => "Sets the Expire metadata for assets.  This is the number of minutes from the date of assets.",
                        "type" => "text-field"
                    ],
                    "ilab-assets-cdn-base" => [
                        "title" => "CDN Base URL",
                        "description" => "This is the base URL for your CDN for serving assets, including the scheme (meaning the http/https part).  If you don't have a CDN, you can simply leave blank to use the cloud storage URL.",
                        "type" => "text-field"
                    ],
                ],
            ],
        ]
    ]
];
