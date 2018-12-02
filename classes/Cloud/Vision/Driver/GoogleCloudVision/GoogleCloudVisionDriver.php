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

namespace ILAB\MediaCloud\Cloud\Vision\Driver\GoogleCloudVision;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Vision\VisionClient;
use ILAB\MediaCloud\Cloud\Storage\StorageManager;
use ILAB\MediaCloud\Cloud\Vision\VisionDriver;
use ILAB\MediaCloud\Tools\Storage\StorageTool;
use ILAB\MediaCloud\Tools\ToolsManager;
use ILAB\MediaCloud\Utilities\EnvironmentOptions;
use ILAB\MediaCloud\Utilities\Logging\ErrorCollector;
use ILAB\MediaCloud\Utilities\Logging\Logger;
use function ILAB\MediaCloud\Utilities\arrayPath;

if (!defined('ABSPATH')) { header('Location: /'); die; }

class GoogleCloudVisionDriver extends VisionDriver {
    /*** @var string */
    private $credentials = null;

    /*** @var string */
    private $bucket = null;

    /** @var null|string */
    private $enabledError = null;

    public function __construct() {
        parent::__construct();

        $this->bucket = EnvironmentOptions::Option('ilab-media-s3-bucket', [
            'ILAB_AWS_S3_BUCKET',
            'ILAB_CLOUD_BUCKET'
        ]);

        $credFile = EnvironmentOptions::Option(null, 'ILAB_CLOUD_GOOGLE_CREDENTIALS');
        if (!empty($credFile)) {
            if (file_exists($credFile)) {
                $this->credentials = json_decode(file_get_contents($credFile), true);
            } else {
                Logger::error("Credentials file '$credFile' could not be found.");
            }
        }

        if (empty($this->credentials)) {
            $creds = EnvironmentOptions::Option('ilab-media-google-credentials');
            if (!empty($creds)) {
                $this->credentials = json_decode($creds, true);
            }
        }
    }

    /**
     * Insures that all the configuration settings are valid and that the vision api is enabled.
     * @return bool
     */
    public function enabled() {
        if (!$this->config->valid()) {
            $this->enabledError = "Configuration for Google Cloud Vision is invalid, probably from using old environment variables that are no longer supported or that have been renamed.";
            return false;
        }

        if (empty($this->credentials)) {
            $this->enabledError = "Missing credentials for Google Cloud Vision.";
            return false;
        }

        if (!$this->getClient()) {
            $this->enabledError = "Invalid Google Cloud Vision credentials.";
            return false;
        }

        $this->enabledError = null;
        return ($this->config->detectLabels() || $this->config->detectFaces() || $this->config->detectExplicit());
    }

    /**
     * If the driver isn't enabled, this returns the error message as to why
     * @return string|null
     */
    public function enabledError() {
        return $this->enabledError;
    }

    /**
     * Processes the image through the driver's vision API
     * @param $postID
     * @param $meta
     * @return array
     */
    public function processImage($postID, $meta) {
        $client = $this->getClient();
        if (!$client) {
            return $meta;
        }

        $features = [];
        if ($this->config->detectFaces()) {
            $features[] = 'faces';
        }

        if ($this->config->detectLabels()) {
            $features[] = 'landmarks';
            $features[] = 'labels';
            $features[] = 'logos';
        }

        if ($this->config->detectExplicit()) {
            $features[] = 'safeSearch';
        }

        /** @var StorageTool $storageTool */
        $storageTool = ToolsManager::instance()->tools['storage'];

        $urlOrResource = null;

        $provider = arrayPath($meta, 's3/provider', null);
        $key = arrayPath($meta, 's3/key', null);

        if (!empty($provider) && !empty($key) && ($provider != 'google') && (StorageManager::driver() == $provider) && $storageTool->enabled()) {
            $urlOrResource = $storageTool->client()->presignedUrl($key);
        } else if (!empty($provider) && !empty($key) && ($provider == 'google') && (StorageManager::driver() == $provider)) {
            /** @var StorageClient $storageClient */
            $storageClient = $storageTool->client()->client();
            $urlOrResource = $storageClient->bucket($storageTool->client()->bucket())->object($key);
        } else {
            $file = get_attached_file($postID);
            if (file_exists($file)) {
                $urlOrResource = fopen($file,'r');
            }
        }

        if (!$urlOrResource) {
            $urlOrResource = wp_get_attachment_url($postID);
        }

        if (!$urlOrResource) {
            return $meta;
        }

        $image = $client->image($urlOrResource, $features);
        $result = $client->annotate($image);
        vomit($result);

        return $meta;
    }


    //region Client Creation
    /**
     * @param ErrorCollector|null $errorCollector
     * @return VisionClient|null
     */
    protected function getClient($errorCollector = null) {
        if(!$this->enabled()) {
            if ($errorCollector) {
                $errorCollector->addError("Google configuration is incorrect or missing.");
            }

            return null;
        }

        $client = null;
        if (!empty($this->credentials) && is_array($this->credentials)) {
            $client = new VisionClient([
                'keyFile' => $this->credentials
            ]);
        }

        if(!$client) {
            if ($errorCollector) {
                $errorCollector->addError("Google configuration is incorrect or missing.");
            }

            Logger::info('Could not create Google storage client.');
        }

        return $client;
    }
    //endregion
}