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

namespace ILAB\MediaCloud\Utilities;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class EnvironmentOptions
 * @package ILAB\MediaCloud\Utilities
 */
final class EnvironmentOptions {
	/**
	 * Fetches an option from WordPress or the environment.
	 *
	 * @param string|null $optionName
	 * @param string|array|null $envVariableName
	 * @param bool $default
	 *
	 * @return array|false|mixed|string|null
	 */
	public static function Option($optionName = null, $envVariableName = null, $default = false) {
		if (empty($optionName) && empty($envVariableName)) {
			return $default;
		}

		if ($envVariableName == null) {
			$envVariableName = str_replace('-','_', strtoupper($optionName));
		}

		if (is_array($envVariableName)) {
			foreach($envVariableName as $envVariable) {
				$envval = getenv($envVariable);
				if ($envval) {
					return $envval;
				}
			}
		} else {
			$envval = getenv($envVariableName);
			if ($envval) {
				return $envval;
			}
		}

		if (empty($optionName)) {
			return $default;
		}

		return get_option($optionName, $default);
	}

    /**
     * Transitions options from older versions of the plugin to the new option name
     *
     * @param $optionGroup
     * @param $version
     * @param $options
     */
	public static function TransitionOptions($optionGroup, $version, $options) {
	    $currentVersion = get_option("ilab_migration_$optionGroup", null);
	    if ($currentVersion == $version) {
	        return;
        }

        foreach($options as $fromOptionName => $toOptionName) {
            $val = get_option($fromOptionName, null);
            if ($val !== null) {
                update_option($toOptionName, $val);
                delete_option($fromOptionName);
            }
        }

        update_option("ilab_migration_$optionGroup", $version);
    }

    /**
     * Determines if any the following environment variables exist
     * @param $optionGroup
     * @param $version
     * @param $envVars
     * @return bool
     */
    public static function DeprecatedEnvironmentVariables($toolName, $envVars) {
        $exist = [];

        foreach($envVars as $oldEndVar => $newEnvVar) {
            $val = getenv($oldEndVar);
            if ($val !== false) {
                $exist[] = "<code>$oldEndVar</code> is now <code>$newEnvVar</code>";
            }
        }

        if (empty($exist)) {
            return false;
        }

        $message = "You have have outdated environmental variables defined.  $toolName will not work until you change them.  ";
        $message .= implode(', ', $exist);
        $message .= '.';

        NoticeManager::instance()->displayAdminNotice('error', $message, false);

        return true;
    }
}