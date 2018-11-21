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

use ILAB\MediaCloud\Tools\Debugging\TroubleshootingTool;
use ILAB\MediaCloud\Utilities\NoticeManager;
use ILAB\MediaCloud\Utilities\View;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class ILabMediaToolsManager
 *
 * Manages all of the tools for the ILab Media Tools plugin
 */
class ToolsManager
{
	//region Class variables
    private static $registeredTools = [];
    private static $instance;
    public $tools;
    //endregion

	//region Constructor
    public function __construct()
    {
	    $this->tools=[];

        foreach(static::$registeredTools as $toolName => $toolInfo) {
            $className=$toolInfo['class'];
            $this->tools[$toolName]=new $className($toolName,$toolInfo,$this);
        }

        if (isset(static::$registeredTools['troubleshooting'])) {
            $this->tools['troubleshooting'] = new TroubleshootingTool('troubleshooting', static::$registeredTools['troubleshooting'], $this);
        }

        foreach($this->tools as $key => $tool) {
            $tool->setup();
        }

        add_action('admin_menu', function() {
            add_menu_page('Settings', 'Media Cloud', 'manage_options', 'media-cloud-top', [$this,'renderSettings'],'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjA0OCAxNzkyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGZpbGw9ImJsYWNrIiBkPSJNMTk4NCAxMTUycTAgMTU5LTExMi41IDI3MS41dC0yNzEuNSAxMTIuNWgtMTA4OHEtMTg1IDAtMzE2LjUtMTMxLjV0LTEzMS41LTMxNi41cTAtMTMyIDcxLTI0MS41dDE4Ny0xNjMuNXEtMi0yOC0yLTQzIDAtMjEyIDE1MC0zNjJ0MzYyLTE1MHExNTggMCAyODYuNSA4OHQxODcuNSAyMzBxNzAtNjIgMTY2LTYyIDEwNiAwIDE4MSA3NXQ3NSAxODFxMCA3NS00MSAxMzggMTI5IDMwIDIxMyAxMzQuNXQ4NCAyMzkuNXoiLz48L3N2Zz4=');
            add_submenu_page( 'media-cloud-top', 'Media Cloud Features', 'Enable/Disable Features', 'manage_options', 'media-cloud-top', [$this,'renderSettings']);



            add_settings_section('ilab-media-tools','Enabled Features',[$this,'renderSettingsSection'],'media-cloud-top');

            $hasTools = false;
            foreach($this->tools as $key => $tool)
            {
                register_setting('ilab-media-tools',"ilab-media-tool-enabled-$key");

                if ($key != 'troubleshooting') {
                    add_settings_field("ilab-media-tool-enabled-$key",$tool->toolInfo['title'],[$this,'renderToolSettings'],'media-cloud-top','ilab-media-tools',['key'=>$key]);
                }

                $tool->registerMenu('media-cloud-top');
                $tool->registerSettings();

                if (!$hasTools && $tool->hasBatchTools()) {
                    $hasTools = true;
                }
            }

	        add_submenu_page( 'media-cloud-top', 'Plugin Support', 'Help / Support', 'manage_options', 'media-tools-support', [$this,'renderSupport']);

            if ($hasTools) {
                add_menu_page('Tools', 'Media Tools', 'manage_options', 'media-tools-top', [$this,'renderTools'],'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjA0OCAxNzkyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGZpbGw9ImJsYWNrIiBkPSJNMTM0NCA4NjRxMC0xNC05LTIzbC0zNTItMzUycS05LTktMjMtOXQtMjMgOWwtMzUxIDM1MXEtMTAgMTItMTAgMjQgMCAxNCA5IDIzdDIzIDloMjI0djM1MnEwIDEzIDkuNSAyMi41dDIyLjUgOS41aDE5MnExMyAwIDIyLjUtOS41dDkuNS0yMi41di0zNTJoMjI0cTEzIDAgMjIuNS05LjV0OS41LTIyLjV6bTY0MCAyODhxMCAxNTktMTEyLjUgMjcxLjV0LTI3MS41IDExMi41aC0xMDg4cS0xODUgMC0zMTYuNS0xMzEuNXQtMTMxLjUtMzE2LjVxMC0xMzAgNzAtMjQwdDE4OC0xNjVxLTItMzAtMi00MyAwLTIxMiAxNTAtMzYydDM2Mi0xNTBxMTU2IDAgMjg1LjUgODd0MTg4LjUgMjMxcTcxLTYyIDE2Ni02MiAxMDYgMCAxODEgNzV0NzUgMTgxcTAgNzYtNDEgMTM4IDEzMCAzMSAyMTMuNSAxMzUuNXQ4My41IDIzOC41eiIvPjwvc3ZnPg==');
                foreach($this->tools as $key => $tool) {
                    $tool->registerToolMenu('media-tools-top');
                }
            }
        });

	    add_filter('plugin_action_links_'.ILAB_PLUGIN_NAME, function($links) {
		    $links[] = "<a href='http://www2.jdrf.org/site/TR?fr_id=6912&pg=personal&px=11429802' target='_blank'><b>Donate</b></a>";
		    $links[] = "<a href='admin.php?page=media-tools-top'>Settings</a>";
		    $links[] = "<a href='https://wordpress.org/support/plugin/ilab-media-tools' target='_blank'>Support</a>";

		    return $links;
	    });

	    $maxTime = ini_get('max_execution_time');
	    if (($maxTime > 0) && ($maxTime < 90)) {
	    	NoticeManager::instance()->displayAdminNotice('warning',"The <code>max_execution_time</code> is set to a value that might be too low ($maxTime).  You should set it to about 90 seconds.  Additionally, if you are using Nginx or Apache, you may need to set the respective <code>fastcgi_read_timeout</code>, <code>request_terminate_timeout</code> or <code>TimeOut</code> settings too.", true,'ilab-media-tools-extime-notice');
	    }

	    $runTime = get_option('ilab_media_tools_run_time', 0);
	    if ($runTime == 0) {
	    	update_option('ilab_media_tools_run_time',microtime(true));
	    } else if ((microtime(true) - floatval($runTime)) > 1209600) {
		    NoticeManager::instance()->displayAdminNotice('info',"Thanks for using Media Cloud!  If you like it, please <a href='https://wordpress.org/support/plugin/ilab-media-tools/reviews/#new-post' target=_blank>leave a review</a>.  If you really like it, please consider donating to <a href='http://www2.jdrf.org/site/TR?fr_id=6912&pg=personal&px=11429802' target='_blank'>juvenile type 1 diabetes research</a>.  Thank you!", true,'ilab-media-tools-nag-notice');
        }
    }
    //endregion

	//region Static Methods
    /**
     * Returns the singleton instance of the manager
     * @return ToolsManager
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $class=__CLASS__;
            self::$instance = new $class();
        }

        return self::$instance;
    }

    /**
     * Registers a tool
     *
     * @param $identifier string The identifier of the tool
     * @param $config array The configuration for the tool
     */
    public static function registerTool($identifier, $config) {
        static::$registeredTools[$identifier] = $config;
    }
    //endregion

	//region Plugin installation
	/**
	 * Perform plugin installation
	 */
	public function install() {
		foreach($this->tools as $key => $tool)
			$tool->install();
	}

	/**
	 * Perform plugin removal
	 */
	public function uninstall() {
		foreach($this->tools as $key => $tool)
			$tool->uninstall();
	}
	//endregion

	//region Tool Settings
    /**
     * Determines if a tool is enabled or not
     *
     * @param $toolName
     * @return bool
     */
    public function toolEnabled($toolName) {
        if (isset($this->tools[$toolName]))
            return $this->tools[$toolName]->enabled();

        return false;
    }
    /**
     * Determines if a tool is enabled or not via environment settings
     *
     * @param $toolName
     * @return bool
     */
    public function toolEnvEnabled($toolName) {
        if (isset($this->tools[$toolName]))
            return $this->tools[$toolName]->envEnabled();

        return false;
    }
	//endregion


	//region Settings
    /**
     * Render the options page
     */
    public function renderSettings() {
        echo View::render_view( 'base/ilab-settings.php', [
            'title'=>'Enabled Features',
            'group'=>'ilab-media-tools',
            'page'=>'media-cloud-top'
        ]);
    }

    /**
     * Render the options page
     */
    public function renderTools() {
        $toolInfo = [];

        foreach($this->tools as $tool) {
            if ($tool->enabled()) {
                $toolInfo = array_merge($toolInfo, $tool->batchToolInfo());
            }
        }

        echo View::render_view( 'base/ilab-tools.php', [
            'title'=>'Media Cloud Tools',
            'group'=>'ilab-media-tools',
            'page'=>'media-tools-top',
            'tools' => $toolInfo
        ]);
    }

    /**
     * Render the settings section
     */
    public function renderSettingsSection() {
        echo 'Enabled/disable tools.';
    }

    public function renderSupport() {
        echo View::render_view( 'base/ilab-support.php', []);
    }

    public function renderToolSettings($args) {
        $tool=$this->tools[$args['key']];

        echo View::render_view( 'base/ilab-tool-settings.php', [
            'name'=>$args['key'],
            'tool'=>$tool,
            'manager'=>$this
        ]);
    }
    //endregion
}
