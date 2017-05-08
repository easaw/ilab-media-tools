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

class ILabMediaVideoJobDef implements \JsonSerializable {
	public $outputs = [];
	public $playlists = [];

	public function __construct($jdef = null) {
		if ($jdef) {
			if ($jdef->outputs && is_array($jdef->outputs)) {
				foreach($jdef->outputs as $output) {
					$this->outputs[] = new ILabMediaVideoJobOutput($output);
				}
			}

			if ($jdef->playlists && is_array($jdef->playlists)) {
				foreach($jdef->playlists as $playlist) {
					$this->playlists[] = new ILabMediaVideoJobPlaylist($playlist);
				}
			}
		}
	}

	public function validate() {
		if (count($this->outputs)==0) {
			return [];
		}

		$results = [];

		$invalidOutputs = [];
		foreach($this->outputs as $output) {
			if (!$output->isValid($this)) {
				$invalidOutputs[] = $output->outputId;
			}
		}

		if (!empty($invalidOutputs)) {
			$results['outputs'] = $invalidOutputs;
		}

		$invalidPlaylists = [];
		foreach($this->playlists as $playlist) {
			if (!$playlist->isValid($this)) {
				$invalidPlaylists[] = $playlist->playlistId;
			}
		}

		if (!empty($invalidPlaylists)) {
			$results['playlists'] = $invalidPlaylists;
		}

		if (!empty($results)) {
			return $results;
		}

		return true;
	}


	private function genUUID() {
		return sprintf('%04x%04x%04x%03x4%04x%04x%04x%04x',
		               mt_rand(0, 65535),
		               mt_rand(0, 65535),
		               mt_rand(0, 65535),
		               mt_rand(0, 4095),
		               bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
		               mt_rand(0, 65535),
		               mt_rand(0, 65535),
		               mt_rand(0, 65535)
		);
	}

	private function genUUIDPath() {
		$uid = $this->genUUID();
		$result='/';

		$segments = 8;
		if ($segments>strlen($uid)/2)
			$segments=strlen($uid)/2;
		for($i=0; $i<$segments; $i++)
			$result.=substr($uid,$i*2,2).'/';

		return $result;
	}

	private function parsePrefix($prefix, $baseName) {
		$host = parse_url(get_home_url(), PHP_URL_HOST);

		$user = wp_get_current_user();
		$userName = '';
		if ($user->ID != 0) {
			$userName = sanitize_title($user->display_name);
		}

		if ($baseName != null) {
			$prefix = str_replace("@{filename}", $baseName, $prefix);
		}

		$prefix = str_replace("@{site-id}", sanitize_title(strtolower(get_current_blog_id())), $prefix);
		$prefix = str_replace("@{site-name}", sanitize_title(strtolower(get_bloginfo('name'))), $prefix);
		$prefix = str_replace("@{site-host}", $host, $prefix);
		$prefix = str_replace("@{user-name}", $userName, $prefix);
		$prefix = str_replace("@{unique-id}", $this->genUUID(), $prefix);
		$prefix = str_replace("@{unique-path}", $this->genUUIDPath(), $prefix);
		$prefix = str_replace("//","/", $prefix);

		$matches = [];
		preg_match_all('/\@\{date\:([^\}]*)\}/', $prefix, $matches);
		if (count($matches)==2) {
			for($i = 0; $i<count($matches[0]); $i++) {
				$prefix = str_replace($matches[0][$i],date($matches[1][$i]), $prefix);
			}
		}

		return trim($prefix, '/');
	}

	public function submit($pipelineId, $input, $outputPrefix, \ILAB_Aws\ElasticTranscoder\ElasticTranscoderClient $et, $presets){
		$inputParts = explode('/', $input);
		$inputLast = array_pop($inputParts);
		$inputNameParts = explode('.', $inputLast);
		if (count($inputNameParts) > 1) {
			array_pop($inputNameParts);
			$baseName = implode('.',$inputNameParts);
		} else {
			$baseName = $inputNameParts[0];
		}

		$outputPath = $this->parsePrefix($outputPrefix, null).'/';

		$job = [
			'Input' => [
				'Key' => $input
			],
		    'OutputKeyPrefix' => $outputPath,
			'Outputs' => [],
		    'PipelineId' => $pipelineId
		];

		$outputList = [];
		foreach($this->outputs as $output) {
			$preset = $presets[$output->presetId];

			$outputData = [
				'PresetId' => $output->presetId
			];

			if ((($preset['Container'] == 'ts') || ($preset['Container'] == 'fmp4')) && (($output->segmentDuration != null) && ($output->segmentDuration > 0))) {
				$outputData['Key'] = $this->parsePrefix($output->outputFilenameFormat, $baseName);
			} else {
				$outputData['Key'] = $this->parsePrefix($output->outputFilenameFormat, $baseName).'.'.$preset['Container'];

			}

			$outputList[$output->outputId] = $outputData['Key'];

			if ($output->createThumbnails) {
				$outputData['ThumbnailPattern']	 = $this->parsePrefix($output->thumbnailFilenamePattern, $baseName).'{resolution}-{count}';
			}
			if ((($preset['Container'] == 'ts') || ($preset['Container'] == 'fmp4')) && ($output->segmentDuration != null)) {
				$outputData['SegmentDuration'] = $output->segmentDuration;
			}

			$job['Outputs'][] = $outputData;
		}

		$playlists = [];
		foreach($this->playlists as $playlist) {
			$playlistData = [
				'Format' => $playlist->playlistFormat,
			    'Name' => $this->parsePrefix($playlist->playlistNameFormat, $baseName),
				'OutputKeys' => []
			];

			foreach($playlist->outputIds as $oid) {
				$playlistData['OutputKeys'][] = $outputList[$oid];
			}

			$playlists[] = $playlistData;
		}

		if (!empty($playlists)) {
			$job['Playlists'] = $playlists;
		}

		try {
			$result = $et->createJob($job);
			return $result;
		} catch (Exception $ex) {
			error_log($ex->getMessage());
		}

		return false;
	}

	public function jsonSerialize() {
		return [
			'outputs' => $this->outputs,
		    'playlists' => $this->playlists
		];
	}
}