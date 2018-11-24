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
    "name" => "Glide",
    "title" => "Glide Image Server",
    "description" => "Serves on-demand dynamic images through the <a href='http://glide.thephpleague.com'>Glide library</a>, similar to Imgix but local.",
    "class" => "ILAB\\MediaCloud\\Tools\\Glide\\GlideTool",
    "env" => "ILAB_MEDIA_GLIDE_ENABLED",
    "dependencies" => [
        "crop",
        "!imgix"
    ],
    "helpers" => [
        "ilab-imgix-helpers.php"
    ],
    "incompatiblePlugins" => [
        "Smush" => [
            "plugin" => "wp-smushit/wp-smush.php",
            "description" => "The free version of this plugin does not optimize the main image, only thumbnails.  When the Imgix tool is enabled, thumbnails are not generated - therefore this plugin isn't any use.  The Pro (paid) version of this plugin DOES optimize the main image though."
        ],
    ],
    "badPlugins" => [
        "BuddyPress" => [
            "plugin" => "buddypress/bp-loader.php",
            "description" => "Uploading profile or cover images results in broken images."
        ]
    ],
    "settings" => [
        "title" => "Glide Settings",
        "menu" => "Glide Settings",
        "options-page" => "media-tools-glide",
        "options-group" => "ilab-media-glide",
        "groups" => [
            "ilab-media-glide-general-settings" => [
                "title" => "General Settings",
                "description" => "Required settings for glide server to work.",
                "options" => [
                    "ilab-media-glide-image-path" => [
                        "title" => "Image Path",
                        "description" => "The base path for the generated image URLs.  This cannot be blank or empty.  This path is prepended to the URL for the generated images and when the URL is requested, the plugin will intercept them, render the dynamic image (if needed) and send the image to the requester.",
                        "type" => "text-field",
                        "default" => "/__images/"
                    ],
                    "ilab-media-glide-signing-key" => [
                        "title" => "Glide Signing Key",
                        "description" => "The signing key used to create secure URLs.  This is generated for you, though you can override the auto-generated one here.",
                        "type" => "text-field"
                    ],

                ]
            ],
            "ilab-media-glide-performance-settings" => [
                "title" => "Performance Settings",
                "description" => "Put your imgix image settings here",
                "options" => [
                    "ilab-media-glide-cache-remote" => [
                        "title" => "Cache Master Images",
                        "description" => "This option will cache any master images that are fetched from remote storage (S3, Google Cloud Storage, etc) locally.  If this option is turned off, everytime you request a dynamic size for an image, that image will be pulled from storage.  It is very much recommended that you keep this option <strong>turned on</strong>.",
                        "type" => "checkbox",
                        "default" => "true"
                    ],
                    "ilab-media-glide-cdn" => [
                        "title" => "CDN",
                        "description" => "The base path for the generated image URLs.  This cannot be blank or empty.  This path is prepended to the URL for the generated images and when the URL is requested, the plugin will intercept them, render the dynamic image (if needed) and send the image to the requester.",
                        "type" => "text-field",
                        "default" => "/__images/"
                    ],
                ]
            ],
            "ilab-media-glide-image-settings" => [
                "title" => "Image Settings",
                "description" => "Put your imgix image settings here",
                "options" => [
                    "ilab-media-glide-default-quality" => [
                        "title" => "Lossy Image Quality",
                        "type" => "number",
                        "max" => 100,
                        "min" => 1,
                        "default" => 85
                    ],
                    "ilab-media-glide-max-size" => [
                        "title" => "Max. Image Width/Height",
                        "description" => "The maximum image width or height for a generated image.",
                        "type" => "number",
                        "max" => 10000,
                        "min" => 1,
                        "default" => 4000
                    ],
                    "ilab-media-glide-convert-png" => [
                        "title" => "Convert PNG to JPEG",
                        "description" => "Selecting this will convert all uploaded PNG files to JPEG files when rendering.",
                        "type" => "checkbox",
                        "default" => false
                    ],
                    "ilab-media-glide-progressive-jpeg" => [
                        "title" => "Use Progressive JPEG",
                        "description" => "When rendering an image and the output is JPEG, turning this on will generate a progressive JPEG file.",
                        "type" => "checkbox",
                        "default" => true
                    ],
                    "ilab-media-glide-generate-thumbnails" => [
                        "title" => "Keep WordPress Thumbnails",
                        "description" => "Because Glide can dynamically create new sizes for existing images, having WordPress create thumbnails is potentially pointless, a probable waste of space and definitely slows down uploads.  However, if you plan to stop using Glide, having those thumbnails on S3 or locally will save you having to regenerate thumbnails later.  <strong>IMPORTANT:</strong> Thumbnails will not be generated when you perform a direct upload because those uploads are sent directly to S3 without going through your WordPress server.",
                        "type" => "checkbox",
                        "default" => true
                    ]
                ]
            ]
        ],
        "params" => [
            "adjust" => [
                "Orientation" => [
                    "or" => [
                        "type" => "pillbox",
                        "radio" => true,
                        "no-icon" => true,
                        "options" => [
                            "90" => [
                                "title" => "90°",
                                "default" => 0
                            ],
                            "180" => [
                                "title" => "180°",
                                "default" => 0
                            ],
                            "270" => [
                                "title" => "270°",
                                "default" => 0
                            ],
                        ],
                        "selected" => function($settings, $currentValue, $selectedOutput, $unselectedOutput){
                            if (isset($settings['or']) && ($settings['or'] == $currentValue)) {
                                return $selectedOutput;
                            }

                            return $unselectedOutput;
                        }
                    ]
                ],
                "Flip" => [
                    "flip" => [
                        "type" => "pillbox",
                        "options" => [
                            "h" => [
                                "title" => "Horizontal",
                                "default" => 0
                            ],
                            "v" => [
                                "title" => "Vertical",
                                "default" => 0
                            ]
                        ],
                        "selected" => function($settings, $currentValue, $selectedOutput, $unselectedOutput){
                            if (isset($settings['flip'])) {
                                $parts=explode(',',$settings['flip']);
                                foreach($parts as $part) {
                                    if ($part==$currentValue) {
                                        return $selectedOutput;
                                    }
                                }
                            }

                            return $unselectedOutput;
                        }
                    ]
                ],
                "Transform" => [
                    "rot" => [
                        "title" => "Rotation",
                        "type" => "slider",
                        "min" => -359,
                        "max" => 359,
                        "default" => 0
                    ]
                ],
                "Enhance" => [
                    "auto" => [
                        "type" => "pillbox",
                        "options" => [
                            "enhance" => [
                                "title" => "Auto Enhance",
                                "default" => 0
                            ]
                        ],
                        "hidden" => (!class_exists("Imagick")),
                        "selected" => function($settings, $currentValue, $selectedOutput, $unselectedOutput){
                            if (isset($settings['auto'])) {
                                $parts=explode(',',$settings['auto']);
                                foreach($parts as $part) {
                                    if ($part==$currentValue) {
                                        return $selectedOutput;
                                    }
                                }
                            }

                            return $unselectedOutput;
                        }
                    ]
                ],
                "Luminosity Controls" => [
                    "bri" => [
                        "title" => "Brightness",
                        "type" => "slider",
                        "min" => -100,
                        "max" => 100,
                        "default" => 0
                    ],
                    "con" => [
                        "title" => "Contrast",
                        "type" => "slider",
                        "min" => -100,
                        "max" => 100,
                        "default" => 0
                    ],
                    "gam" => [
                        "title" => "Gamma",
                        "type" => "slider",
                        "min" => 0.1,
                        "max" => 9.99,
                        "inc" => 0.01,
                        "default" => 1
                    ]
                ],
                "Color Controls" => [
                    "hue" => [
                        "title" => "Hue",
                        "type" => "slider",
                        "min" => -359,
                        "max" => 359,
                        "default" => 0,
                        "hidden" => (!class_exists("Imagick"))
                    ],
                    "sat" => [
                        "title" => "Saturation",
                        "type" => "slider",
                        "min" => -100,
                        "max" => 100,
                        "default" => 0,
                        "hidden" => (!class_exists("Imagick"))
                    ]
                ],
                "Sharpen/Blur" => [
                    "sharp" => [
                        "title" => "Sharpen",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 0
                    ],
                    "usm" => [
                        "title" => "Unsharp Mask",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 0,
                        "hidden" => (!class_exists("Imagick"))
                    ],
                    "usmrad" => [
                        "title" => "Unsharp Mask Radius",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 500,
                        "default" => 0,
                        "hidden" => (!class_exists("Imagick"))
                    ],
                    "blur" => [
                        "title" => "Blur",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 0
                    ]
                ]
            ],
            "stylize" => [
                "Stylize" => [
                    "blend" => [
                        "title" => "Tint",
                        "type" => "color",
                        "hidden" => (!class_exists("Imagick"))
                    ],
                    "px" => [
                        "title" => "Pixellate",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 0
                    ],
                    "mono" => [
                        "title" => "Monochrome",
                        "type" => "color",
                        "hidden" => (!class_exists("Imagick"))
                    ],
                    "sepia" => [
                        "title" => "Sepia",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 0,
                        "hidden" => (!class_exists("Imagick"))
                    ]
                ],
                "Border" => [
                    "border-color" => [
                        "title" => "Border Color",
                        "type" => "color",
                        "no-alpha" => true
                    ],
                    "border-width" => [
                        "title" => "Border Width",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 500,
                        "default" => 0
                    ]
                ],
                "Padding" => [
                    "padding-color" => [
                        "title" => "Padding Color",
                        "type" => "color",
                        "no-alpha" => true
                    ],
                    "padding-width" => [
                        "title" => "Padding Width",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 500,
                        "default" => 0
                    ]
                ]
            ],
            "watermark" => [
                "Watermark Media" => [
                    "media" => [
                        "title" => "Watermark Image",
                        "type" => "media-chooser",
                        "imgix-param" => "mark",
                        "dependents" => [
                            "markalign",
                            "markalpha",
                            "markpad",
                            "markscale"
                        ]
                    ]
                ],
                "Watermark Settings" => [
                    "markalign" => [
                        "title" => "Watermark Alignment",
                        "type" => "alignment"
                    ],
                    "markalpha" => [
                        "title" => "Watermark Alpha",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 100
                    ],
                    "markpad" => [
                        "title" => "Watermark Padding",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 100,
                        "default" => 0
                    ],
                    "markscale" => [
                        "title" => "Watermark Scale",
                        "type" => "slider",
                        "min" => 0,
                        "max" => 200,
                        "default" => 100
                    ]
                ]
            ]
        ]
    ]
];