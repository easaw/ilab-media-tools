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

class ILabMediaVideoJobOutput implements \JsonSerializable {
	public $outputId;
	public $presetId;
	public $segmentDuration;
	public $outputFilenameFormat;
	public $createThumbnails = false;
	public $thumbnailFilenamePattern;

	public function __construct($output = null) {
		$this->outputId = uniqid();

		if (!empty($output)) {
			if ($output->outputId) {
				$this->outputId = $output->outputId;
			}

			if ($output->presetId) {
				$this->presetId = $output->presetId;
			}

			if ($output->segmentDuration) {
				$this->segmentDuration = $output->segmentDuration;
			}

			if ($output->outputFilenameFormat) {
				$this->outputFilenameFormat = $output->outputFilenameFormat;
			}

			if ($output->createThumbnails) {
				$this->createThumbnails = $output->createThumbnails;
			}

			if ($output->thumbnailFilenamePattern) {
				$this->thumbnailFilenamePattern = $output->thumbnailFilenamePattern;
			}
		}

	}

	public function isValid(ILabMediaVideoJobDef $jobDef) {
		if (empty($this->outputFilenameFormat)) {
			return false;
		}

		if (($this->createThumbnails) && (empty($this->thumbnailFilenamePattern))) {
			return false;
		}

		return true;
	}

	public function jsonSerialize() {
		return [
			'outputId' => $this->outputId,
			'presetId' => $this->presetId,
			'segmentDuration' => $this->segmentDuration,
			'outputFilenameFormat' => $this->outputFilenameFormat,
			'createThumbnails' => ($this->createThumbnails) ? (int)0 : (int)1,
			'thumbnailFilenamePattern' => $this->thumbnailFilenamePattern,
		];
	}
}