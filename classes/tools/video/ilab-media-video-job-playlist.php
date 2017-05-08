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

class ILabMediaVideoJobPlaylist implements \JsonSerializable {
	public $playlistId;
	public $playlistNameFormat;
	public $playlistFormat = 'HLSv3';
	public $outputIds = [];

	public function __construct($playlist = null) {
		$this->playlistId = uniqid();

		if (!empty($playlist)) {
			if ($playlist->outputId) {
				$this->playlistId = $playlist->playlistId;
			}

			if ($playlist->playlistNameFormat) {
				$this->playlistNameFormat = $playlist->playlistNameFormat;
			}

			if ($playlist->playlistFormat) {
				$this->playlistFormat = $playlist->playlistFormat;
			}

			if ($playlist->outputIds && !empty($playlist->outputIds) && is_array($playlist->outputIds)) {
				$this->outputIds = $playlist->outputIds;
			}
		}
	}

	public function isValid(ILabMediaVideoJobDef $jobDef) {
		if (empty($this->playlistNameFormat)) {
			return false;
		}

		if (!in_array($this->playlistFormat, ['HLSv3','HLSv4','Smooth','MPEG-DASH'])) {
			return false;
		}

		if (empty($this->outputIds)) {
			return false;
		}

		$joids = [];
		foreach($jobDef->outputs as $output) {
			$joids[] = $output->outputId;
		}

		foreach($this->outputIds as $outputId) {
			if (!in_array($outputId, $joids)) {
				return false;
			}
		}

		return true;
	}

	public function jsonSerialize() {
		return [
			'playlistId' => $this->playlistId,
			'playlistNameFormat' => $this->playlistNameFormat,
		    'playlistFormat' => $this->playlistFormat,
		    'outputIds' => $this->outputIds
		];
	}
}