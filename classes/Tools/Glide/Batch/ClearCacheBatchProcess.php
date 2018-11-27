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

namespace ILAB\MediaCloud\Tools\Glide\Batch;

use ILAB\MediaCloud\Tasks\BackgroundProcess;
use ILAB\MediaCloud\Tasks\BatchManager;
use ILAB\MediaCloud\Tools\Glide\GlideTool;
use ILAB\MediaCloud\Tools\ToolsManager;
use ILAB\MediaCloud\Utilities\Logging\Logger;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class RegenerateThumbnailsProcess
 *
 * Background processing job for regenerating thumbnails
 */
class ClearCacheBatchProcess extends BackgroundProcess {
    protected $action = 'ilab_cloud_clear_cache_process';

    protected function shouldHandle() {
        return !BatchManager::instance()->shouldCancel('glide-cache');
    }

    public function task($item) {
        $startTime = microtime(true);

        Logger::info( 'Start Task', $item);
        if (!$this->shouldHandle()) {
            Logger::info( 'Task cancelled', $item);
            return false;
        }

        $index = $item['index'];
        $post_id = $item['post'];

        BatchManager::instance()->setCurrentID('glide-cache', $post_id);
        BatchManager::instance()->setCurrent('glide-cache', $index + 1);

        $fileName = get_attached_file($post_id);

        if (!empty($fileName)) {
            BatchManager::instance()->setCurrentFile('glide-cache', pathinfo($fileName, PATHINFO_FILENAME));
            Logger::info("Processing $fileName");
            $localUploadPath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            if (strpos($fileName, $localUploadPath) === 0) {
                $fileName = str_replace($localUploadPath, '', $fileName);
            }
            Logger::info("Processing $fileName");


            /** @var GlideTool $tool */
            $tool = ToolsManager::instance()->tools['glide'];
            $tool->clearCache($fileName);
        }

        $endTime = microtime(true) - $startTime;
        BatchManager::instance()->incrementTotalTime('glide-cache', $endTime);

        return false;
    }

    public function dispatch() {
        Logger::info( 'Task dispatch');
        parent::dispatch();
    }

    protected function complete() {
        Logger::info( 'Task complete');
        BatchManager::instance()->reset('glide-cache');
        parent::complete();
    }

    public function cancel_process() {
        Logger::info( 'Cancel process');

        parent::cancel_process();

        BatchManager::instance()->reset('glide-cache');
    }

    public static function cancelAll() {
        Logger::info( 'Cancel all processes');

        wp_clear_scheduled_hook('wp_ilab_cloud_clear_cache_process_cron');

        global $wpdb;

        $res = $wpdb->get_results("select * from {$wpdb->options} where option_name like 'wp_ilab_cloud_clear_cache_process_batch_%'");
        foreach($res as $batch) {
            Logger::info( "Deleting batch {$batch->option_name}");
            delete_option($batch->option_name);
        }

        BatchManager::instance()->reset('glide-cache');

        Logger::info( "Current cron", get_option( 'cron', []));
        Logger::info( 'End cancel all processes');
    }
}
