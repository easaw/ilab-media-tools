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
    "id" => "vision",
    "name" => "Vision",
	"description" => "Uses Amazon's Rekognition AI or Google's Cloud Vision service to automatically tag your uploaded images.",
	"class" => "ILAB\\MediaCloud\\Tools\\Vision\\VisionTool",
	"env" => "ILAB_VISION_ENABLED",
    "batchTools" => [
        "\\ILAB\\MediaCloud\\Tools\\Vision\\Batch\\ImportVisionBatchTool"
    ],
	"settings" => [
		"options-page" => "media-tools-vision",
		"options-group" => "ilab-media-vision",
		"groups" => [
            "ilab-media-s3-aws-settings" => [
                "watch" => true,
                "title" => "Provider Settings",
                "description" => "To get cloud storage working, you'll have to supply your credentials, specify the bucket and so on.  However, the better way of doing it, would be to place that information in a .env file, instead of storing it in the database.",
                "options" => [
                    "ilab-vision-provider" => [
                        "title" => "Vision Provider",
                        "description" => "Specify the service you are using for vision processing.  If you are supplying this value through a .env file, the key is: <strong>ILAB_VISION_PROVIDER</strong>.",
                        "type" => "select",
                        "options" => [
                            "rekognition" => "Amazon Rekognition",
                            'google' => 'Google Cloud Vision',
                        ],
                    ],
                    "ilab-media-s3-access-key" => [
                        "title" => "Access Key",
                        "description" => "If you are supplying this value through a .env file, or environment variables, the key is: <strong>ILAB_AWS_S3_ACCESS_KEY</strong>",
                        "type" => "text-field",
                        "conditions" => [
                            "ilab-vision-provider" => ["!google"]
                        ]
                    ],
                    "ilab-media-s3-secret" => [
                        "title" => "Secret",
                        "description" => "If you are supplying this value through a .env file, or environment variables, the key is: <strong>ILAB_AWS_S3_ACCESS_SECRET</strong>",
                        "type" => "password",
                        "conditions" => [
                            "ilab-vision-provider" => ["!google"]
                        ]
                    ],
                    "ilab-media-s3-bucket" => [
                        "title" => "Bucket",
                        "description" => "If you are supplying this value through a .env file, or environment variables, the key is: <strong>ILAB_AWS_S3_BUCKET</strong>",
                        "type" => "text-field",
                        "conditions" => [
                            "ilab-vision-provider" => ["!google"]
                        ]
                    ],
                    "ilab-media-s3-region" => [
                        "title" => "Region",
                        "description" => "The region that your bucket is in.  Setting this is only really useful if you are using compatible S3 services, and not if you are using Amazon S3 itself.  If you are supplying this value through a .env file, or environment variables, the key is: <strong>ILAB_AWS_S3_REGION</strong>",
                        "type" => "select",
                        "options" => [
                            "auto" => "Automatic",
                            'us-east-2' => 'US East (Ohio)',
                            'us-east-1' => 'US East (N. Virginia)',
                            'us-west-1' => 'US West (N. California)',
                            'us-west-2' => 'US West (Oregon)',
                            'ca-central-1' => 'Canada (Central)',
                            'ap-south-1' => 'Asia Pacific (Mumbai)',
                            'ap-northeast-2' => 'Asia Pacific (Seoul)',
                            'ap-southeast-1' => 'Asia Pacific (Singapore)',
                            'ap-southeast-2' => 'Asia Pacific (Sydney)',
                            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                            'eu-central-1' => 'EU (Frankfurt)',
                            'eu-west-1' => 'EU (Ireland)',
                            'eu-west-2' => 'EU (London)',
                            'sa-east-1' => 'South America (São Paulo)',
                        ],
                        "conditions" => [
                            "ilab-vision-provider" => ["!google"]
                        ]
                    ],
                    "ilab-media-google-credentials" => [
                        "title" => "Credentials",
                        "description" => "If you are supplying this value through a .env file, or environment variables, the key is: <strong>ILAB_CLOUD_GOOGLE_CREDENTIALS</strong>",
                        "type" => "text-area",
                        "conditions" => [
                            "ilab-vision-provider" => ["google"]
                        ]
                    ],
                ]
            ],
			"ilab-media-s3-vision-settings" => [
				"title" => "Vision Settings",
				"description" => "After you upload files to S3, those files will then be processed through the AWS Rekognition service.  The following settings control that process.  It's important to note that your S3 bucket must be in the same region that you are using the Rekognition service in.  Also, each option you select counts as 1 api call.  If you select detect faces, describe object <em>and</em> image moderation you will be using the service 3 times.",
				"options" => [
					"ilab-vision-detect-labels" => [
						"title" => "Detect Labels",
						"description" => "Detects instances of real-world labels within an image (JPEG or PNG) provided as input. This includes objects like flower, tree, and table; events like wedding, graduation, and birthday party; and concepts like landscape, evening, and nature.",
						"type" => "checkbox",
						"default" => false
					],
					"ilab-vision-detect-labels-tax" => [
						"title" => "Detect Labels Taxonomy",
						"description" => "The taxonomy to apply the detected labels to.",
						"type" => "select",
						"default" => "post_tag",
						"options" => 'attachmentTaxonomies'
					],
					"ilab-vision-detect-labels-confidence" => [
						"title" => "Detect Labels Confidence",
						"description" => "The minimum confidence (0-100) required to apply the returned label as tags.  Default is 70.",
						"type" => "number",
						"default" => 70
					],
					"ilab-vision-detect-moderation-labels" => [
						"title" => "Detect Moderation Labels",
						"description" => "Detects explicit or suggestive adult content in a specified JPEG or PNG format image. Use this to moderate images depending on your requirements. For example, you might want to filter images that contain nudity, but not images containing suggestive content.",
						"type" => "checkbox",
						"default" => false
					],
					"ilab-vision-detect-moderation-labels-tax" => [
						"title" => "Detect Moderation Labels Taxonomy",
						"description" => "The taxonomy to apply the detected moderation labels to.",
						"type" => "select",
						"default" => "post_tag",
						"options" => 'attachmentTaxonomies'
					],
					"ilab-vision-detect-moderation-labels-confidence" => [
						"title" => "Detect Moderation Labels Confidence",
						"description" => "The minimum confidence (0-100) required to apply the returned label as tags.  Default is 70.",
						"type" => "number",
						"default" => 70
					],
					"ilab-vision-detect-celebrity" => [
						"title" => "Detect Celebrity Faces",
						"description" => "Detects celebrity faces in the image.  This will also detect non-celebrity faces.  If you use this option, you should not use the <em>Detect Faces<</em> option as either will overwrite the other.  Detected faces will be stored as additional metadata for the image.  If you are using Imgix, you can use this for cropping images centered on a face.",
						"type" => "checkbox",
						"default" => false,
                        "conditions" => [
                            "ilab-vision-provider" => ["!google"]
                        ]
					],
					"ilab-vision-detect-celebrity-tax" => [
						"title" => "Detect Celebrity Faces Taxonomy",
						"description" => "The taxonomy to apply the detected moderation labels to.",
						"type" => "select",
						"default" => "post_tag",
						"options" => 'attachmentTaxonomies',
                        "conditions" => [
                            "ilab-vision-provider" => ["!google"]
                        ]
					],
					"ilab-vision-detect-faces" => [
						"title" => "Detect Faces",
						"description" => "Detects faces in the image.  If you use this option, you should not use the <em>Detect Celebrity Faces<</em> option as either will overwrite the other.  Detected faces will be stored as additional metadata for the image.  If you are using Imgix, you can use this for cropping images centered on a face.",
						"type" => "checkbox",
						"default" => false
					],
					"ilab-vision-ignored-tags" => [
						"title" => "Ignored Tags",
						"description" => "Add a comma separated list of tags to ignore when parsing the results from the Rekognition API.",
						"type" => "text-area"
					],
				]
			]
		]
	]
];