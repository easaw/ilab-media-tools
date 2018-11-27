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

namespace ILAB\MediaCloud\Tools\Glide\CLI;

use ILAB\MediaCloud\CLI\Command;
use ILAB\MediaCloud\Cloud\Storage\StorageSettings;
use ILAB\MediaCloud\Tasks\BatchManager;
use ILAB\MediaCloud\Tools\Glide\GlideTool;
use ILAB\MediaCloud\Tools\Storage\DefaultProgressDelegate;
use ILAB\MediaCloud\Tools\Storage\StorageTool;
use ILAB\MediaCloud\Tools\ToolsManager;
use ILAB\MediaCloud\Utilities\Logging\Logger;

if (!defined('ABSPATH')) { header('Location: /'); die; }

/**
 * Clear the Dynamic Image Resizing Server cache
 * @package ILAB\MediaCloud\CLI\Storage
 */
class GlideCommands extends Command {
    private $debugMode = false;

	/**
	 * Clears the cache for the Dynamic Image Resizing Server
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function clearCache($args, $assoc_args) {
	    $this->debugMode = (\WP_CLI::get_config('debug') == 'mediacloud');

	    // Force the logger to initialize
	    Logger::instance();

		/** @var GlideTool $tool */
		$tool = ToolsManager::instance()->tools['glide'];

		if (!$tool || !$tool->enabled()) {
			Command::Error('Dynamic image resizing tool is not enabled in Media Cloud or the settings are incorrect.');
			return;
		}

		$postArgs = [
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'nopaging' => true,
            'post_mime_type' => 'image',
			'fields' => 'ids',
		];

		$query = new \WP_Query($postArgs);

		if($query->post_count > 0) {
		    BatchManager::instance()->reset('glide-cache');

            BatchManager::instance()->setStatus('glide-cache', true);
            BatchManager::instance()->setTotalCount('glide-cache', $query->post_count);
            BatchManager::instance()->setCurrent('glide-cache', 1);
            BatchManager::instance()->setShouldCancel('glide-cache', false);

			Command::Info("Total posts found: %Y{$query->post_count}.", true);

			$pd = new DefaultProgressDelegate();

			for($i = 1; $i <= $query->post_count; $i++) {
				$postId = $query->posts[$i - 1];
				$upload_file = get_attached_file($postId);
				$fileName = basename($upload_file);

                BatchManager::instance()->setCurrentFile('glide-cache', $fileName);
                BatchManager::instance()->setCurrent('glide-cache', $i);

				Command::Info("%w[%C{$i}%w of %C{$query->post_count}%w] %wClearing cache for %Y$fileName%N %w(Post ID %N$postId%w)%N ... ", $this->debugMode);

                if (!empty($upload_file)) {
                    $localUploadPath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
                    if (strpos($upload_file, $localUploadPath) === 0) {
                        $upload_file = str_replace($localUploadPath, '', $upload_file);
                    }

                    /** @var GlideTool $tool */
                    $tool->clearCache($upload_file);
                }

				if (!$this->debugMode) {
                    Command::Info("%YDone%N.", true);
                }
			}

			BatchManager::instance()->reset('glide-cache');
		}
	}

	public static function Register() {
		\WP_CLI::add_command('dynamicImages', __CLASS__);
	}

}