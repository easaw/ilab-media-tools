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

use ILAB\MediaCloud\Tasks\BatchManager;
use ILAB\MediaCloud\Tools\BatchTool;
use function ILAB\MediaCloud\Utilities\json_response;

class ClearCacheBatchTool extends BatchTool {
    //region Properties
    /**
     * Name/ID of the batch
     * @return string
     */
    public function batchIdentifier() {
        return 'glide-cache';
    }

    /**
     * Title of the batch
     * @return string
     */
    public function title() {
        return "Clear Glide Cache";
    }

    public function menuTitle() {
        return "Clear Cache";
    }

    /**
     * The prefix to use for action names
     * @return string
     */
    public function batchPrefix() {
        return 'ilab_clear_glide_cache';
    }

    /**
     * Fully qualified class name for the BatchProcess class
     * @return string
     */
    public function batchProcessClassName() {
        return "\\ILAB\\MediaCloud\\Tasks\\ClearCacheBatchProcess";
    }

    /**
     * The view to render for instructions
     * @return string
     */
    public function instructionView() {
        return 'importer/clear-cache-instructions.php';
    }

    /**
     * The menu slug for the tool
     * @return string
     */
    function menuSlug() {
        return 'media-tools-cloud-clear-cache';
    }
    //endregion

    //region Bulk Actions
    /**
     * Registers any bulk actions for integeration into the media list
     * @param $actions array
     * @return array
     */
    public function registerBulkActions($actions) {
        $actions['ilab_clear_glide_cache'] = 'Clear Dynamic Image Cache';
        return $actions;
    }

    /**
     * Called to handle a bulk action
     *
     * @param $redirect_to
     * @param $action_name
     * @param $post_ids
     * @return string
     */
    public function handleBulkActions($redirect_to, $action_name, $post_ids) {
        if('ilab_clear_glide_cache' === $action_name) {
            if(count($post_ids) > 0) {
                set_site_transient($this->batchPrefix().'_post_selection', $post_ids, 10);
                return 'admin.php?page='.$this->menuSlug();
            }
        }

        return $redirect_to;
    }
    //endregion

    //region Actions
    protected function filterPostArgs($args) {
        $args['post_mime_type'] ='image';
        return $args;
    }

    /**
     * Allows subclasses to filter the data used to render the tool
     * @param $data
     * @return array
     */
    protected function filterRenderData($data) {
        $data['disabledText'] = 'enable Glide';
        $data['commandLine'] = 'wp dynamicImages clearCache';
        $data['commandTitle'] = 'Clear Dynamic Image Cache';
        $data['cancelCommandTitle'] = 'Cancel';

        return $data;
    }

    /**
     * Process the import manually.  $_POST will contain a field `post_id` for the post to process
     */
    public function manualAction() {
        if (!isset($_POST['post_id'])) {
            BatchManager::instance()->setErrorMessage($this->batchIdentifier(), 'Missing required post data.');
            json_response(['status' => 'error']);
        }

        $pid = $_POST['post_id'];
        $fileName = get_attached_file($pid);

        if (!empty($fileName)) {
            $localUploadPath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            if (strpos($fileName, $localUploadPath) === 0) {
                $fileName = str_replace($localUploadPath, '', $fileName);
            }
            $this->owner->clearCache($fileName);
        }

        json_response(["status" => 'ok']);
    }
    //endregion

    //region BatchToolInterface
    public function toolInfo() {
        return [
            'title' => 'Clear Dynamic Image Cache',
            'link' => admin_url('admin.php?page='.$this->menuSlug()),
            'description' => 'Clears dynamically generated images from the file system cache.'
        ];
    }
    //endregion
}