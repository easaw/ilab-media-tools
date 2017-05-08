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

require_once(ILAB_CLASSES_DIR.'/ilab-media-tool-base.php');
require_once(ILAB_CLASSES_DIR.'/tasks/ilab-s3-import-process.php');
require_once(ILAB_CLASSES_DIR.'/tools/video/ilab-media-video-job-def.php');
require_once(ILAB_CLASSES_DIR.'/tools/video/ilab-media-video-job-output.php');
require_once(ILAB_CLASSES_DIR.'/tools/video/ilab-media-video-job-playlist.php');

/**
 * Class ILabMediaVideoTool
 *
 * Video Tool.
 */
class ILabMediaVideoTool extends ILabMediaToolBase {

	private $key = null;
	private $secret = null;
	private $bucket = null;
	private $region = null;
	private $jobDef = null;
	private $outputKey = null;
	private $pipelineId = null;

	public function __construct($toolName, $toolInfo, $toolManager) {
		parent::__construct($toolName, $toolInfo, $toolManager);

		$this->bucket = $this->getOption('ilab-media-s3-bucket', 'ILAB_AWS_S3_BUCKET');
		$this->key = $this->getOption('ilab-media-s3-access-key', 'ILAB_AWS_S3_ACCESS_KEY');
		$this->secret = $this->getOption('ilab-media-s3-secret', 'ILAB_AWS_S3_ACCESS_SECRET');
		$this->region = $this->getOption('ilab-media-et-region', null, 'us-east-1');
		$this->outputKey = $this->getOption('ilab-media-et-output-prefix', null, '');
		$this->pipelineId = $this->getOption('ilab-media-et-pipeline', null, null);
		$this->jobDef = get_option('ilab-media-et-job-def', null);
		if (!$this->jobDef) {
		    $this->jobDef = new ILabMediaVideoJobDef();
        }

		if (is_admin()) {
			$this->setupAdmin();
		}

		add_action('ilab_s3_uploaded_attachment',function($postID, $file, $s3Info){
			if ($postID) {
				error_log('updated');
			}
		}, 1000, 3);
	}

	public function enabled() {
		$penabled = parent::enabled();

		if (!$penabled) {
			return false;
		}

		$s3Tool = $this->toolManager->tools['s3'];
		$enabled = $s3Tool->enabled();

		if (!$enabled)
			return false;

		return true;
	}

	public function presets() {
	    static $presets = null;

	    if ($presets == null) {
	        $presets = get_site_transient('ilab-media-et-presets-list');
	        if (!empty($presets)) {
	            return $presets;
            }

		    $et = $this->etClient();

		    $presets = [];

		    try {
			    $token = null;
			    while(true) {
				    $args=['Ascending' => 'true'];
				    if ($token) {
					    $args['PageToken'] = $token;
				    }

				    $results = $et->listPresets($args);
				    $resultPresets = $results->get('Presets');
				    $presets = array_merge($presets, $resultPresets);
				    $nextToken = $results->get('NextPageToken');

				    if (($nextToken == $token) || ($nextToken == null)) {
					    break;
				    }

				    $token = $nextToken;
			    }
		    } catch (Exception $ex) {
			    error_log($ex->getMessage());
		    }

		    $userPresets = [];
		    $sysPresets = [];

		    foreach($presets as $preset) {
			    if ($preset['Type'] == 'Custom') {
				    $userPresets[$preset['Id']] = $preset;
			    } else {
				    $sysPresets[$preset['Id']] = $preset;
			    }
		    }

		    $presets=array_merge($sysPresets, $userPresets);

		    set_site_transient('ilab-media-et-presets-list', $presets, 120);
        }

        return $presets;
    }

	public function getPipelineOptions() {
		static $options = null;

		if ($options == null) {
			$options = get_site_transient('ilab-media-et-pipelines-list');
			if ($options) {
				return $options;
			}

			$et = $this->etClient();

			$res = $et->listPipelines();
			$pipelines = $res->get('Pipelines');

			$options = [
				'' => 'None'
			];

			foreach($pipelines as $pipeline) {
				if ($pipeline['Status'] != 'Active') {
					continue;
				}

				if (($pipeline['InputBucket'] != $this->bucket) || ($pipeline['OutputBucket'] != $this->bucket)) {
					continue;
				}

				$options[$pipeline['Id']] = $pipeline['Name'];
			}

			set_site_transient('ilab-media-et-pipelines-list', $options, 120);
		}

		return $options;
	}

	public function etClient() {
	    static $client = null;

	    if ($client == null) {
		    $client = new \ILAB_Aws\ElasticTranscoder\ElasticTranscoderClient([
			                                                                  'version' => 'latest',
			                                                                  'region' => $this->region,
			                                                                  'credentials' => [
				                                                                  'key'    => $this->key,
				                                                                  'secret' => $this->secret
			                                                                  ]

		                                                                  ]);
        }

		return $client;
	}

	private function setupAdmin() {
		add_action( 'admin_enqueue_scripts', function(){
			wp_enqueue_script( 'wp-util' );
        });

		add_filter('ajax_query_attachments_args',function($query){
			if ($query) {
				$query['post_status'] = "inherit,private,draft";
			}

			return $query;
		}, 10000, 1);

		$this->hookUI();

		add_filter('ilab_s3_should_handle_upload', function($shouldHandle, $upload){
			if (isset($upload['type'])) {
				$mime = explode('/', $upload['type']);
				if ($mime[0] == 'video') {
					return true;
				}
			}

			return $shouldHandle;
		}, 1000, 2);


		add_action('wp_ajax_ilab_et_save_job_def',function(){
			$this->saveJobDef();
		});

		add_action('wp_ajax_ilab_et_clear_cache',function(){
			$input = "incoming/MVI_1330.MOV";

			if ($this->jobDef && ($this->jobDef->validate() === true)) {
				$this->jobDef->submit($this->pipelineId, $input, $this->outputKey, $this->etClient(), $this->presets());
			}

			delete_site_transient('ilab-media-et-pipelines-list');
			delete_site_transient('ilab-media-et-presets-list');

			json_response(['status'=>'ok']);
		});
	}

	private function hookUI() {
		add_action( 'wp_enqueue_media', function () {
			add_action('admin_footer', function(){
				$etLogo = ILAB_PUB_IMG_URL.'/ilab-et-logo.png';
				?>
				<script>
                    jQuery(document).ready(function() {
                        var attachTemplate = jQuery('#tmpl-attachment');
                        if (attachTemplate) {
                            var txt = attachTemplate.text();

                            var idx = txt.indexOf('<div class="thumbnail">');
                            var startText = txt.slice(0, idx);
                            var alteration  = '<# if ( (data.status=="draft") && (data.type=="video") ) { #><div class="thumbnail"><div style="position: absolute; left:50%; top:50%; transform:translate(-50%, -18px); font-weight: bold; color: black; z-index:1000">Transcoding&nbsp...</div><img style="z-index: 1001; position: absolute; left:50%; top:50%; width: auto; height: 24px; transform:translate(-50%, -48px);" src="<?php echo $etLogo?>"><# } else { #><div class="thumbnail"><# } #>';
                            var endText = txt.slice(idx+'<div class="thumbnail">'.length);
                            txt = startText + alteration + endText;

                            idx = txt.indexOf('<div class="centered">');
                            startText = txt.slice(0, idx);
                            alteration  = '<# if ( (data.status=="draft") && (data.type=="video") ) { #><div class="centered" style="opacity:0.25"><# } else { #><div class="centered"><# } #>';
                            endText = txt.slice(idx+'<div class="centered">'.length);
                            txt = startText + alteration + endText;

                            idx = txt.indexOf('<div class="centered">', idx+alteration.length);
                            if (idx>-1) {
                                startText = txt.slice(0, idx);
                                alteration  = '<# if ( (data.status=="draft") && (data.type=="video") ) { #><div class="centered" style="opacity:0.25"><# } else { #><div class="centered"><# } #>';
                                endText = txt.slice(idx+'<div class="centered">'.length);
                                txt = startText + alteration + endText;
                            }


                            idx = txt.indexOf('<div class="filename">');
                            startText = txt.slice(0, idx);
                            alteration  = '<# if ( data.status=="draft" ) { #><div class="filename" style="opacity:0.25"><# } else { #><div class="filename"><# } #>';
                            endText = txt.slice(idx+'<div class="centered">'.length);
                            txt = startText + alteration + endText;

                            attachTemplate.text(txt);
                        }
                    });
				</script>
				<?php
			} );
		} );
	}

	/**
	 * Render settings.
	 */
	public function renderSettings() {
		$result = render_view('video/ilab-video-settings.php',[
			'title'=>$this->toolInfo['title'],
			'group'=>$this->options_group,
			'page'=>$this->options_page,
            'presets' => $this->presets(),
            'jobDef' => $this->jobDef,
            'region' => $this->region
		]);

		echo $result;
	}

	protected function saveJobDef() {
        $jd = $_POST['job_def'];
		$jd = stripslashes($jd);
        $jdef = json_decode($jd, false);

        if (json_last_error() != JSON_ERROR_NONE) {
	        json_response(['status' => 'error', 'error' => 'Bad JSON']);
        }

        $jobDef = new ILabMediaVideoJobDef($jdef);
		update_option('ilab-media-et-job-def', $jobDef);

		$result = $jobDef->validate();

		if ($result === true) {
	        json_response(['status' => 'valid', 'jobDef' => $jobDef]);
        } else {
	        json_response(['status' => 'invalid', 'jobDef' => $jobDef, 'invalids'=>$result]);
        }
	}

	public function testJob() {
	    $input = "incoming/MVI_1330.MOV";

	    if ($this->jobDef && ($this->jobDef->validate() === true)) {
	        $this->jobDef->submit($this->pipelineId, $input, $this->outputKey, $this->etClient(), $this->presets());
        }
    }
}