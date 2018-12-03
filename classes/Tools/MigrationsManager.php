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

namespace ILAB\MediaCloud\Tools;

use ILAB\MediaCloud\Utilities\EnvironmentOptions;
use ILAB\MediaCloud\Utilities\Logging\Logger;
use function \ILAB\MediaCloud\Utilities\arrayPath;
use ILAB\MediaCloud\Utilities\NoticeManager;

/**
 * Manages migrating options/settings between major plugin versions
 *
 * @package ILAB\MediaCloud\Tools
 */
final class MigrationsManager {
    /** @var MigrationsManager The current instance */
    private static $instance;

    /** @var array  */
    private $config = [];

    /** @var array  */
    private $deprecatedErrors = [];

    //region Constructor
    public function __construct() {
        $configFile = ILAB_CONFIG_DIR.'/migrations/migrations.php';
        if (file_exists($configFile)) {
            $this->config = include $configFile;
        } else {
            Logger::warning("Could not find migrations config '$configFile'.");
        }
    }

    public static function instance() {
        if (!isset(self::$instance)) {
            $class=__CLASS__;
            self::$instance = new $class();
        }

        return self::$instance;
    }
    //endregion

    //region Migration

    /**
     * Migrates all tools
     */
    public function migrate() {
        $tools = arrayPath($this->config, 'tools', []);
        foreach($tools as $toolName => $migration) {
            foreach($migration as $migrationType => $migrationData) {
                if ($migrationType == 'transition') {
                    foreach($migrationData as $version => $versionData) {
                        EnvironmentOptions::TransitionOptions($toolName, $version, $versionData);
                    }
                } else if ($migrationType == 'deprecated') {
                    $deprecated = EnvironmentOptions::DeprecatedEnvironmentVariables($migrationData);
                    if (is_array($deprecated) && !empty($deprecated)) {
                        $exist = [];
                        foreach($deprecated as $oldEndVar => $newEnvVar) {
                            $exist[] = "<code>$oldEndVar</code> is now <code>$newEnvVar</code>";
                        }

                        $message = "You have have outdated environmental variables defined.  $toolName will not work until you change them.  ";
                        $message .= implode(', ', $exist);
                        $message .= '.';

                        $this->deprecatedErrors[$toolName] = $message;
                    }
                } else if ($migrationType == 'copy') {
                    foreach($migrationData as $version => $copyData) {
                        EnvironmentOptions::CopyOptions($toolName, $version, $copyData);
                    }
                }
            }
        }
    }
    //endregion

    //region Utilities
    /**
     * Checks to see if deprecated environment variables exist for a specific tool
     * @param $toolName
     * @return bool
     */
    public function hasDeprecatedEnvironment($toolName) {
        return !empty($this->deprecatedErrors[$toolName]);
    }

    /**
     * Displays migration errors
     */
    public function displayMigrationErrors() {
        foreach($this->deprecatedErrors as $tool => $error) {
            NoticeManager::instance()->displayAdminNotice('error', $error);
        }
    }
    //endregion
}