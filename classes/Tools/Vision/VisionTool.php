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

namespace ILAB\MediaCloud\Tools\Vision;

use ILAB\MediaCloud\Cloud\Vision\VisionDriver;
use ILAB\MediaCloud\Cloud\Vision\VisionManager;
use ILAB\MediaCloud\Tasks\BatchManager;
use ILAB\MediaCloud\Tools\Vision\Batch\ImportVisionBatchProcess;
use ILAB\MediaCloud\Tools\Tool;
use ILAB\MediaCloud\Utilities\NoticeManager;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class VisionTool
 *
 * Vision tool.
 */
class VisionTool extends Tool {
	//region Class Variables
    /** @var VisionDriver|null  */
    private $driver = null;
	//endregion

	//region Constructor
	public function __construct($toolName, $toolInfo, $toolManager) {
		parent::__construct($toolName, $toolInfo, $toolManager);

		new ImportVisionBatchProcess();

		$this->driver = VisionManager::visionInstance();

		if (is_admin()) {
            BatchManager::instance()->displayAnyErrors('rekognizer');
		}

		add_filter('ilab_vision_enabled', function($enabled){
			return $this->enabled();
		});

		add_filter('ilab_vision_detects_faces', function($enabled){
		    return $this->driver->config()->detectFaces() || $this->driver->config()->detectCelebrities();
		});

	}

	public function setup() {
        parent::setup();

        if ($this->haveSettingsChanged()) {
            $this->settingsChanged();
        }

        $this->testForBadPlugins();
        $this->testForUselessPlugins();

        if ($this->enabled()) {
            if (VisionManager::driver() == 'rekognition') {
                add_filter('ilab_s3_after_upload', [$this, 'processImageMeta'], 1000, 2);
            } else {
                add_filter('wp_update_attachment_metadata', function($data, $id) {
                    return $this->processImageMeta($data, $id);
                }, 1000, 2);
            }
        }
    }

	private function settingsChanged() {
        if (!$this->driver->enabled()) {
            if (!empty($this->driver->enabledError())) {
                NoticeManager::instance()->displayAdminNotice('error', $this->driver->enabledError());
            }
        }
    }
	//endregion

	//region Tool Overrides
	public function enabled() {
		if (!parent::enabled()) {
			return false;
		}

		if (empty($this->driver)) {
		    return false;
        }

		if (!$this->driver->enabled()) {
            if (!empty($this->driver->enabledError())) {
                NoticeManager::instance()->displayAdminNotice('error', $this->driver->enabledError());
            }

		    return false;
        }

        return true;
	}
	//endregion

	//region Settings Helpers
	/**
	 * Returns a list of taxonomies for Attachments, used in the Rekognition settings page.
	 * @return array
	 */
	public function attachmentTaxonomies() {
		$taxonomies = [
			'category' => 'Category',
			'post_tag' => 'Tag'
		];

		$attachTaxes = get_object_taxonomies('attachment');
		if (!empty($attachTaxes)) {
			foreach($attachTaxes as $attachTax) {
				if (!in_array($attachTax, ['post_tag', 'category'])) {
					$taxonomies[$attachTax] = ucwords(str_replace('_', ' ', $attachTax));
				}
			}
		}


		return $taxonomies;
	}
	//endregion

	//region Processing
	/**
	 * Process an image through Rekognition
	 *
	 * @param array $meta
	 * @param int $postID
	 *
	 * @return array
	 */
	public function processImageMeta($meta, $postID) {
	    return $this->driver->processImage($postID, $meta);
	}
	//endregion
}