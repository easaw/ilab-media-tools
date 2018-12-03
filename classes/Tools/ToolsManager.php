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

    /** @var array Associative array of tool classes */
    private static $registeredTools = [];

    /** @var ToolsManager The current instance */
    private static $instance;

    /** @var Tool[] Array of current tools  */
    public $tools;

    //endregion

	//region Constructor

    public function __construct() {
        MigrationsManager::instance()->migrate();

	    $this->tools=[];

        foreach(static::$registeredTools as $toolName => $toolInfo) {
            $className=$toolInfo['class'];
            $this->tools[$toolName]=new $className($toolName,$toolInfo,$this);
        }

        if (isset(static::$registeredTools['troubleshooting'])) {
            $this->tools['troubleshooting'] = new TroubleshootingTool('troubleshooting', static::$registeredTools['troubleshooting'], $this);
        }

        add_action('admin_menu', function() {
            add_menu_page('Settings', 'Media Cloud', 'manage_options', 'media-cloud-settings', [$this,'renderSettings'],'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjA0OCAxNzkyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGZpbGw9ImJsYWNrIiBkPSJNMTk4NCAxMTUycTAgMTU5LTExMi41IDI3MS41dC0yNzEuNSAxMTIuNWgtMTA4OHEtMTg1IDAtMzE2LjUtMTMxLjV0LTEzMS41LTMxNi41cTAtMTMyIDcxLTI0MS41dDE4Ny0xNjMuNXEtMi0yOC0yLTQzIDAtMjEyIDE1MC0zNjJ0MzYyLTE1MHExNTggMCAyODYuNSA4OHQxODcuNSAyMzBxNzAtNjIgMTY2LTYyIDEwNiAwIDE4MSA3NXQ3NSAxODFxMCA3NS00MSAxMzggMTI5IDMwIDIxMyAxMzQuNXQ4NCAyMzkuNXoiLz48L3N2Zz4=');
            add_submenu_page( 'media-cloud-settings', 'Media Cloud Settings', 'Settings', 'manage_options', 'media-cloud-settings', [$this,'renderSettings']);

            foreach($this->tools as $key => $tool)  {
                register_setting('ilab-media-tools',"ilab-media-tool-enabled-$key");

                register_setting($tool->optionsGroup(),"ilab-media-tool-enabled-$key");

                $tool->registerMenu('media-cloud-settings');
                $tool->registerSettings();

                if (!empty($tool->toolInfo['related'])) {
                    foreach($tool->toolInfo['related'] as $relatedKey) {
                        register_setting($tool->optionsGroup(),"ilab-media-tool-enabled-$relatedKey");
                    }
                }
            }

            foreach($this->tools as $key => $tool) {
                $tool->registerBatchToolMenu('media-cloud-settings');
            }

	        add_submenu_page( 'media-cloud-settings', 'Plugin Support', 'Help / Support', 'manage_options', 'media-tools-support', [$this,'renderSupport']);
        });

	    add_filter('plugin_action_links_'.ILAB_PLUGIN_NAME, function($links) {
		    $links[] = "<a href='http://www2.jdrf.org/site/TR?fr_id=6912&pg=personal&px=11429802' target='_blank'><b>Donate</b></a>";
		    $links[] = "<a href='admin.php?page=media-cloud-settings'>Settings</a>";
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

        add_action('admin_enqueue_scripts', function(){
            wp_enqueue_script('ilab-settings-js', ILAB_PUB_JS_URL . '/ilab-settings.js', ['jquery'], null, true );
            wp_enqueue_style('ilab-media-settings-css', ILAB_PUB_CSS_URL . '/ilab-media-tools.settings.min.css' );
        });
    }

    protected function setup() {
        foreach($this->tools as $key => $tool) {
            $tool->setup();
        }

        MigrationsManager::instance()->displayMigrationErrors();
    }

    //endregion

	//region Static Methods

    /**
     * Returns the singleton instance of the manager
     * @return ToolsManager
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $class=__CLASS__;
            self::$instance = new $class();
            self::$instance->setup();
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
        global $wp_settings_sections, $wp_settings_fields;

        if (!empty($_GET['tab']) && in_array($_GET['tab'], array_keys($this->tools))) {
            $tab = $_GET['tab'];
        } else {
            $tab = array_keys($this->tools)[0];
        }

        $selectedTool = $this->tools[$tab];
        $page = $selectedTool->optionsPage();
        $group = $selectedTool->optionsGroup();

        $sections = [];

        foreach((array)$wp_settings_sections[$page] as $section) {
            if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']])) {
                continue;
            }

            $sections[] = [
                'title' => $section['title'],
                'id' => $section['id']
            ];
        }

        echo View::render_view( 'base/ilab-all-settings.php', [
            'title' => 'All Settings',
            'tab' => $tab,
            'tools' => $this->tools,
            'tool' => $selectedTool,
            'group' => $group,
            'page' => $page,
            'manager' => $this,
            'sections' => $sections
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
