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
    "envMap" => [

    ],
    "tools" => [
        "vision" => [
            "transition" => [
                "3.0.0" => [
                    'ilab-media-s3-rekognition-detect-labels' => 'ilab-vision-detect-labels',
                    'ilab-media-s3-rekognition-detect-labels-tax' => 'ilab-vision-detect-labels-tax',
                    'ilab-media-s3-rekognition-detect-labels-confidence' => 'ilab-vision-detect-labels-confidence',
                    'ilab-media-s3-rekognition-detect-moderation-labels' => 'ilab-vision-detect-moderation-labels',
                    'ilab-media-s3-rekognition-detect-moderation-labels-tax' => 'ilab-vision-detect-moderation-labels-tax',
                    'ilab-media-s3-rekognition-detect-moderation-labels-confidence' => 'ilab-vision-detect-moderation-labels-confidence',
                    'ilab-media-s3-rekognition-detect-celebrity' => 'ilab-vision-detect-celebrity',
                    'ilab-media-s3-rekognition-detect-celebrity-tax' => 'ilab-vision-detect-celebrity-tax',
                    'ilab-media-s3-rekognition-detect-faces' => 'ilab-vision-detect-faces',
                    'ilab-media-s3-rekognition-ignored-tags' => 'ilab-vision-ignored-tags'
                ]
            ],
            "deprecated" => [
                'ILAB_AWS_REKOGNITION_DETECT_LABELS' => 'ILAB_VISION_DETECT_LABELS',
                'ILAB_AWS_REKOGNITION_DETECT_LABELS_TAX' => 'ILAB_VISION_DETECT_LABELS_TAX',
                'ILAB_AWS_REKOGNITION_DETECT_LABELS_CONFIDENCE' => 'ILAB_VISION_DETECT_LABELS_CONFIDENCE',
                'ILAB_AWS_REKOGNITION_MODERATION_LABELS' => 'ILAB_VISION_MODERATION_LABELS',
                'ILAB_AWS_REKOGNITION_MODERATION_LABELS_TAX' => 'ILAB_VISION_MODERATION_LABELS_TAX',
                'ILAB_AWS_REKOGNITION_MODERATION_LABELS_CONFIDENCE' => 'ILAB_VISION_MODERATION_LABELS_CONFIDENCE',
                'ILAB_AWS_REKOGNITION_DETECT_CELEBRITY' => 'ILAB_VISION_DETECT_CELEBRITY',
                'ILAB_AWS_REKOGNITION_DETECT_CELEBRITY_TAX' => 'ILAB_VISION_DETECT_CELEBRITY_TAX',
                'ILAB_AWS_REKOGNITION_DETECT_FACES' => 'ILAB_VISION_DETECT_FACES'
            ],
        ],
        "storage" => [
            "copy" => [
                "3.0.0" => [
                    "ilab-media-s3-bucket" => "ilab-media-google-bucket"
                ]
            ]
        ]
    ]
];
