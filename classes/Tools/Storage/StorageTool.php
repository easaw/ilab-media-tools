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

namespace ILAB\MediaCloud\Tools\Storage;

use ILAB\MediaCloud\Cloud\Storage\StorageException;
use ILAB\MediaCloud\Cloud\Storage\StorageInterface;
use ILAB\MediaCloud\Cloud\Storage\StorageManager;
use ILAB\MediaCloud\Tools\ToolBase;
use ILAB\MediaCloud\Utilities\EnvironmentOptions;
use function ILAB\MediaCloud\Utilities\json_response;
use ILAB\MediaCloud\Utilities\NoticeManager;
use ILAB\MediaCloud\Utilities\View;
use ILAB\MediaCloud\Tasks\StorageImportProcess;
use ILAB\MediaCloud\Utilities\Logger;
use Smalot\PdfParser\Parser;

if(!defined('ABSPATH')) {
	header('Location: /');
	die;
}

/**
 * Class StorageTool
 *
 * Storage Tool.
 */
class StorageTool extends ToolBase {
	//region Properties/Class Variables

	/** @var string|null */
	private $docCdn = null;

	/** @var string|null */
	private $cdn = null;

	/** @var bool */
	private $deleteOnUpload = false;

	/** @var bool */
	private $deleteFromStorage = false;

	/** @var string|null */
	private $prefixFormat = '';

	/** @var array */
	private $uploadedDocs = [];

	/** @var array */
	private $pdfInfo = [];

	/** @var string|null */
	private $cacheControl = null;

	/** @var string|null */
	private $expires = null;

	/** @var array */
	private $versionedIds = [];

	/** @var array */
	private $ignoredMimeTypes = [];

	/** @var bool */
	private $uploadDocs = true;

	/** @var string */
	private $privacy = 'public-read';

	/** @var bool */
	private $skipUpdate = false;

	/** @var bool */
	private $displayBadges = true;

	/** @var bool */
	private $mediaListIntegration = true;

	/** @var StorageInterface|null */
	private $client = null;

	//endregion

	//region Constructor
	public function __construct($toolName, $toolInfo, $toolManager) {
		parent::__construct($toolName, $toolInfo, $toolManager);

		new StorageImportProcess();

		$this->deleteOnUpload = EnvironmentOptions::Option('ilab-media-s3-delete-uploads');
		$this->deleteFromStorage = EnvironmentOptions::Option('ilab-media-s3-delete-from-s3');
		$this->prefixFormat = EnvironmentOptions::Option('ilab-media-s3-prefix', '');
		$this->uploadDocs = EnvironmentOptions::Option('ilab-media-s3-upload-documents', null, true);
		$this->displayBadges = EnvironmentOptions::Option('ilab-media-s3-display-s3-badge', null, true);
		$this->mediaListIntegration = EnvironmentOptions::Option('ilab-cloud-storage-display-media-list', null, true);

		$this->privacy = EnvironmentOptions::Option('ilab-media-s3-privacy', null, "public-read");
		if(!in_array($this->privacy, ['public-read', 'authenticated-read'])) {
			NoticeManager::instance()->displayAdminNotice('error', "Your AWS S3 settings are incorrect.  The ACL '{$this->privacy}' is not valid.  Defaulting to 'public-read'.");
			$this->privacy = 'public-read';
		}

		$ignored = EnvironmentOptions::Option('ilab-media-s3-ignored-mime-types', null, '');
		$ignored_lines = explode("\n", $ignored);
		if(count($ignored_lines) <= 1) {
			$ignored_lines = explode(',', $ignored);
		}
		foreach($ignored_lines as $d) {
			if(!empty($d)) {
				$this->ignoredMimeTypes[] = trim($d);
			}
		}

		$this->cdn = EnvironmentOptions::Option('ilab-media-s3-cdn-base', 'ILAB_AWS_S3_CDN_BASE');
		if($this->cdn) {
			$this->cdn = rtrim($this->cdn, '/');
		}

		$this->docCdn = EnvironmentOptions::Option('ilab-doc-s3-cdn-base', 'ILAB_AWS_S3_DOC_CDN_BASE', $this->cdn);

		$this->cacheControl = EnvironmentOptions::Option('ilab-media-s3-cache-control', 'ILAB_AWS_S3_CACHE_CONTROL');

		$expires = EnvironmentOptions::Option('ilab-media-s3-expires', 'ILAB_AWS_S3_EXPIRES');
		if(!empty($expires)) {
			$this->expires = gmdate('D, d M Y H:i:s \G\M\T', time() + ($expires * 60));
		}

		$this->client = StorageManager::storageInstance();

		if($this->haveSettingsChanged()) {
			$this->settingsChanged();
		}


		if(is_admin()) {
			add_action('wp_ajax_ilab_s3_import_media', [$this, 'importMedia']);
			add_action('wp_ajax_ilab_s3_import_progress', [$this, 'importProgress']);
			add_action('wp_ajax_ilab_s3_cancel_import', [$this, 'cancelImportMedia']);
		}
	}
	//endregion

	//region Properties
	/**
	 * Returns the storage client
	 * @return StorageInterface|null
	 */
	public function client() {
		return $this->client;
	}

	/**
	 * Determines if document uploads are allowed
	 * @return bool
	 */
	public function documentUploadsEnabled() {
		return $this->uploadDocs;
	}
	//endregion

	//region ToolBase Overrides
	public function enabled() {
		$enabled = parent::enabled();

		if($enabled) {
			$enabled = ($this->client && $this->client->enabled());
		}

		return $enabled;
	}

	public function setup() {
		parent::setup();

		if($this->enabled()) {
			add_filter('wp_update_attachment_metadata', [$this, 'updateAttachmentMetadata'], 1000, 2);
			add_action('delete_attachment', [$this, 'deleteAttachment'], 1000);
			add_filter('wp_handle_upload', [$this, 'handleUpload'], 10000);
			add_filter('get_attached_file', [$this, 'getAttachedFile'], 10000, 2);
			add_filter('image_downsize', [$this, 'imageDownsize'], 999, 3);
			add_action('add_attachment', [$this, 'addAttachment'], 1000);
			add_action('edit_attachment', [$this, 'editAttachment']);

			add_filter('ilab_s3_process_crop', [$this, 'processCrop'], 10000, 4);

			add_filter('ilab_s3_process_file_name', function($filename) {
				if(!$this->client) {
					return $filename;
				}

				if(strpos($filename, '/'.$this->client->bucket()) === 0) {
					return str_replace('/'.$this->client->bucket(), '', $filename);
				}

				return $filename;
			}, 10000, 1);
		}

		add_filter('wp_calculate_image_srcset', [$this, 'calculateSrcSet'], 10000, 5);
		add_filter('wp_prepare_attachment_for_js', [$this, 'prepareAttachmentForJS'], 999, 3);
		add_filter('wp_get_attachment_url', [$this, 'getAttachmentURL'], 1000, 2);

		$this->hookupUI();
	}

	public function settingsChanged() {
		$this->client->validateSettings();
	}

	public function registerMenu($top_menu_slug) {
		parent::registerMenu($top_menu_slug);

		if($this->enabled()) {
			add_submenu_page($top_menu_slug, 'Storage Importer', 'Storage Importer', 'manage_options', 'media-tools-s3-importer', [
				$this,
				'renderImporter'
			]);
		}
	}
	//endregion

	//region WordPress Upload/Attachment Hooks & Filters
	/**
	 * Filter for when attachments are updated (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/post.php#L5013)
	 *
	 * @param array $data
	 * @param integer $id
	 *
	 * @return array
	 */
	public function updateAttachmentMetadata($data, $id) {
		if($this->skipUpdate) {
			return $data;
		}

		if(!$data) {
			return $data;
		}

		$mime = (isset($data['ilab-mime'])) ? $data['ilab-mime'] : null;
		if($mime) {
			unset($data['ilab-mime']);
		}

		if(!isset($data['file'])) {
			if(!$mime) {
				$mime = get_post_mime_type($id);
			}

			if($mime == 'application/pdf') {
				$renderPDF = apply_filters('ilab_imgix_render_pdf', false);

				if(!$renderPDF) {
					unset($data['sizes']);
				}

				$s3Info = get_post_meta($id, 'ilab_s3_info', true);
				if($s3Info) {
					$pdfInfo = $this->pdfInfo[$s3Info['file']];
					$data['width'] = $pdfInfo['width'];
					$data['height'] = $pdfInfo['height'];
					$data['file'] = $s3Info['s3']['key'];
					$data['s3'] = $s3Info['s3'];
					if($renderPDF) {
						$data['sizes']['full']['file'] = $s3Info['s3']['key'];
						$data['sizes']['full']['width'] = $data['width'];
						$data['sizes']['full']['height'] = $data['height'];
					}
				}
			}

			return $data;
		}

		$upload_info = wp_upload_dir();
		$upload_path = $upload_info['basedir'];
		$path_base = pathinfo($data['file'])['dirname'];

		if(!file_exists($upload_path.'/'.$data['file'])) {
			return $data;
		}

		if(!$mime) {
			$mime = wp_get_image_mime($upload_path.'/'.$data['file']);
		}

		if($mime && in_array($mime, $this->ignoredMimeTypes)) {
			return $data;
		}

		if($this->client && $this->client->enabled()) {
			if(!isset($data['s3'])) {
				$data = $this->processFile($upload_path, $data['file'], $data, $id);

				if(isset($data['sizes'])) {
					foreach($data['sizes'] as $key => $size) {
						if(!is_array($size)) {
							continue;
						}

						$file = $path_base.'/'.$size['file'];
						if($file == $data['file']) {
							$data['sizes'][$key]['s3'] = $data['s3'];
						} else {
							$data['sizes'][$key] = $this->processFile($upload_path, $file, $size, $id);
						}
					}
				}

				if(isset($data['s3'])) {
					$data = apply_filters('ilab_s3_after_upload', $id, $data);
				}
			}
		}

		return $data;
	}

	/**
	 * Filters for when attachments are deleted
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function deleteAttachment($id) {
		if(!$this->deleteFromStorage) {
			return $id;
		}

		$data = wp_get_attachment_metadata($id);
		if(isset($data['file']) && !isset($data['s3'])) {
			return $id;
		}

		if($this->client && $this->client->enabled()) {
			if(!isset($data['file'])) {
				$file = get_attached_file($id);
				if($file) {
					if(strpos($file, 'http') === 0) {
						$pi = parse_url($file);
						$file = trim($pi['path'], '/');
						if(0 === strpos($file, $this->client->bucket())) {
							$file = substr($file, strlen($this->client->bucket())).'';
							$file = trim($file, '/');
						}
					} else {
						$pi = pathinfo($file);
						$upload_info = wp_upload_dir();
						$upload_path = $upload_info['basedir'];

						$file = trim(str_replace($upload_path, '', $pi['dirname']), '/').'/'.$pi['basename'];
					}

					$this->deleteFile($file);
				}

			} else {
				$this->deleteFile($data['s3']['key']);

				if(isset($data['sizes'])) {
					$pathParts = explode('/', $data['s3']['key']);
					array_pop($pathParts);
					$path_base = implode('/', $pathParts);

					foreach($data['sizes'] as $key => $size) {
						$file = $path_base.'/'.$size['file'];
						try {
							$this->deleteFile($file);
						}
						catch(\Exception $ex) {
							error_log($ex->getMessage());
						}
					}
				}
			}
		}

		return $id;
	}

	/**
	 * Filters the data after a file has been uploaded to WordPress (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-admin/includes/file.php#L416)
	 *
	 * @param array $upload
	 * @param string $context
	 *
	 * @return array
	 */
	public function handleUpload($upload, $context = 'upload') {
		if(!isset($upload['file'])) {
			return $upload;
		}

		if(isset($upload['type']) && in_array($upload['type'], $this->ignoredMimeTypes)) {
			return $upload;
		}

		if(file_is_displayable_image($upload['file'])) {
			return $upload;
		}

		if(isset($_REQUEST["action"]) && ($_REQUEST["action"] == "upload-plugin")) {
			return $upload;
		}

		$shouldHandle = apply_filters('ilab_s3_should_handle_upload', false, $upload);

		if(!$shouldHandle && !$this->uploadDocs) {
			return $upload;
		}

		if($this->client && $this->client->enabled()) {
			$pi = pathinfo($upload['file']);

			$upload_info = wp_upload_dir();
			$upload_path = $upload_info['basedir'];

			$file = trim(str_replace($upload_path, '', $pi['dirname']), '/').'/'.$pi['basename'];

			if(($upload['type'] == 'application/pdf') && file_exists($upload_path.'/'.$file)) {
				set_error_handler(function($errno, $errstr, $errfile, $errline) {
					throw new \Exception($errstr);
				}, E_RECOVERABLE_ERROR);

				try {
					$parser = new Parser();
					$pdf = $parser->parseFile($upload_path.'/'.$file);
					$pages = $pdf->getPages();
					if(count($pages) > 0) {
						$page = $pages[0];
						$details = $page->getDetails();
						if(isset($details['MediaBox'])) {
							$data = [];
							$data['width'] = $details['MediaBox'][2];
							$data['height'] = $details['MediaBox'][3];
							$this->pdfInfo[$upload_path.'/'.$file] = $data;
						}
					}
				}
				catch(\Exception $ex) {
					Logger::error('PDF Parsing Error', ['exception' => $ex->getMessage()]);
				}

				restore_error_handler();
			}

			$upload = $this->processFile($upload_path, $file, $upload);
			if(isset($upload['s3'])) {
				if($this->docCdn) {
					$upload['url'] = trim($this->docCdn, '/').'/'.$file;
				} else if(isset($upload['s3']['url'])) {
					$upload['url'] = $upload['s3']['url'];
				}
			}

			$this->uploadedDocs[$file] = $upload;
		}

		return $upload;
	}

	/**
	 * Filters the attached file based on the given ID (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/post.php#L293)
	 *
	 * @param string $file
	 * @param int $attachment_id
	 *
	 * @return null|string
	 */
	public function getAttachedFile($file, $attachment_id) {
		if(!file_exists($file)) {
			$meta = wp_get_attachment_metadata($attachment_id);

			$new_url = null;
			if($meta) {
				$new_url = $this->getAttachmentURLFromMeta($meta);
			}

			if(!$new_url) {
				$meta = get_post_meta($attachment_id, 'ilab_s3_info', true);
				if($meta) {
					$new_url = $this->getAttachmentURLFromMeta($meta);
				} else if(!$meta && $this->docCdn) {
					$post = \WP_Post::get_instance($attachment_id);
					if($post && (strpos($post->guid, $this->docCdn) === 0)) {
						$new_url = $post->guid;
					}
				}
			}

			if($new_url) {
				return $new_url;
			}
		}

		return $file;
	}

	/**
	 * Filters whether to preempt the output of image_downsize().  (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/media.php#L201)
	 *
	 * @param bool $fail
	 * @param int $id
	 * @param array|string $size
	 *
	 * @return bool|array
	 */
	public function imageDownsize($fail, $id, $size) {
		if(apply_filters('ilab_imgix_enabled', false)) {
			return $fail;
		}

		if(empty($size) || empty($id) || is_array($size)) {
			return $fail;
		}

		$meta = wp_get_attachment_metadata($id);

		if(empty($meta)) {
			return $fail;
		}

		if(!isset($meta['sizes'])) {
			return $fail;
		}

		if(!isset($meta['sizes'][$size])) {
			return $fail;
		}

		$sizeMeta = $meta['sizes'][$size];
		if(!isset($sizeMeta['s3'])) {
			return $fail;
		}

		$url = $sizeMeta['s3']['url'];

		$result = [
			$url,
			$sizeMeta['width'],
			$sizeMeta['height'],
			true
		];

		return $result;
	}

	/**
	 * Fires once an attachment has been added. (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/post.php#L3457)
	 *
	 * @param int $post_id
	 */
	public function addAttachment($post_id) {
		$file = get_post_meta($post_id, '_wp_attached_file', true);
		if(isset($this->uploadedDocs[$file])) {
			add_post_meta($post_id, 'ilab_s3_info', $this->uploadedDocs[$file]);
			do_action('ilab_s3_uploaded_attachment', $post_id, $file, $this->uploadedDocs[$file]);
		}
	}

	/**
	 * Fires once an existing attachment has been updated.  (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/post.php#L3528)
	 *
	 * @param int $post_id
	 */
	public function editAttachment($post_id) {
		$meta = wp_get_attachment_metadata($post_id);
		if(!isset($meta['s3'])) {
			$meta = get_post_meta($post_id, 'ilab_s3_info', true);
			if(empty($meta) || !isset($meta['s3'])) {
				return;
			}

			$meta = $this->updateAttachmentS3Props($post_id, $meta);
			update_post_meta($post_id, 'ilab_s3_info', $meta);

			return;
		}

		$meta = $this->updateAttachmentS3Props($post_id, $meta);
		wp_update_attachment_metadata($post_id, $meta);
	}

	/**
	 * Updates the attachment's properties, as well as updates the metadata on the storage service.
	 *
	 * @param int $id
	 * @param array $meta
	 *
	 * @return mixed
	 */
	private function updateAttachmentS3Props($id, $meta) {
		if(isset($_POST['s3-access-acl']) || isset($_POST['s3-cache-control']) || isset($_POST['s3-expires'])) {
			$mime = get_post_mime_type($id);

			$acl = (isset($meta['s3']['privacy'])) ? $meta['s3']['privacy'] : $this->privacy;
			$acl = (isset($_POST['s3-access-acl'])) ? $_POST['s3-access-acl'] : $acl;
			$meta['s3']['privacy'] = $acl;

			$cacheControl = false;
			$expires = false;

			if(isset($_POST['s3-cache-control'])) {
				$cacheControl = $_POST['s3-cache-control'];
			}

			if(isset($_POST['s3-expires'])) {
				$expires = $_POST['s3-expires'];
				if(!empty($expires)) {
					if(!is_numeric($expires)) {
						$expires = strtotime($expires) - time();
						if($expires !== false) {
							$expires = round($expires / 60);
						}
					}

					if(($expires !== false) && is_numeric($expires)) {
						$expires = gmdate('D, d M Y H:i:00 \G\M\T', time() + ($expires * 60));
					}
				}
			}

			try {
				$this->client->copy($meta['s3']['key'], $meta['s3']['key'], $acl, $mime, $cacheControl, $expires);

				if(!empty($cacheControl)) {
					if(!isset($meta['s3']['options'])) {
						$meta['s3']['options'] = [];
					}

					if(!isset($meta['s3']['options']['params'])) {
						$meta['s3']['options']['params'] = [];
					}

					$meta['s3']['options']['params']['CacheControl'] = $cacheControl;
				}

				if(!empty($expires)) {
					if(!isset($meta['s3']['options'])) {
						$meta['s3']['options'] = [];
					}

					if(!isset($meta['s3']['options']['params'])) {
						$meta['s3']['options']['params'] = [];
					}

					$meta['s3']['options']['params']['Expires'] = $expires;
				}
			}
			catch(StorageException $ex) {
				Logger::error('Error Copying Object', ['exception' => $ex->getMessage()]);
			}
		}

		return $meta;
	}

	/**
	 * Filters an image’s ‘srcset’ sources.  (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/media.php#L1203)
	 *
	 * @param array $sources
	 * @param array $size_array
	 * @param string $image_src
	 * @param array $image_meta
	 * @param int $attachment_id
	 *
	 * @return array
	 */
	public function calculateSrcSet($sources, $size_array, $image_src, $image_meta, $attachment_id) {
		if(!apply_filters('ilab_s3_can_calculate_srcset', true)) {
			return $sources;
		}

		foreach($image_meta['sizes'] as $sizeName => $sizeData) {
			$width = $sizeData['width'];
			if(isset($sources[$width])) {
				$src = wp_get_attachment_image_src($attachment_id, $sizeName);

				if(is_array($src)) {
					$sources[$width]['url'] = $src[0];
				} else {
					unset($sources[$width]);
				}
			}
		}

		if(isset($image_meta['width'])) {
			$width = $image_meta['width'];
			if(isset($sources[$width])) {
				$src = wp_get_attachment_image_src($attachment_id, 'full');

				if(is_array($src)) {
					$sources[$width]['url'] = $src[0];
				} else {
					unset($sources[$width]);
				}
			}
		}

		return $sources;
	}

	/**
	 * Filters the attachment data prepared for JavaScript. (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/media.php#L3279)
	 *
	 * @param array $response
	 * @param int|object $attachment
	 * @param array $meta
	 *
	 * @return array
	 */
	public function prepareAttachmentForJS($response, $attachment, $meta) {
		if(empty($meta) || !isset($meta['s3'])) {
			$meta = get_post_meta($attachment->ID, 'ilab_s3_info', true);
		}

		if(isset($meta['s3'])) {
			$response['s3'] = $meta['s3'];

			if(!isset($response['s3']['privacy'])) {
				$response['s3']['privacy'] = $this->privacy;
			}
		}

		return $response;
	}

	/**
	 * Filters the attachment's url. (https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/post.php#L5077)
	 *
	 * @param string $url
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function getAttachmentURL($url, $post_id) {
		$meta = wp_get_attachment_metadata($post_id);

		$new_url = null;
		if($meta) {
			$new_url = $this->getAttachmentURLFromMeta($meta);
		}

		if(!$new_url) {
			$meta = get_post_meta($post_id, 'ilab_s3_info', true);
			if($meta) {
				$new_url = $this->getAttachmentURLFromMeta($meta);
			}

			if(!$new_url) {
				$meta = get_post_meta($post_id, 'amazonS3_info');

				if($meta) {
					$new_url = $this->getOffloadS3URL($post_id, $meta);

					$s3Data = $meta[0];
					$s3Data['url'] = $new_url;
					$s3Data['privacy'] = 'public-read';

					$this->skipUpdate = true;

					$imageMeta = wp_get_attachment_metadata($post_id);
					if($imageMeta) {
						$imageMeta['s3'] = $s3Data;
						wp_update_attachment_metadata($post_id, $imageMeta);
					} else {
						update_post_meta($post_id, 'ilab_s3_info', ['s3' => $s3Data]);
					}

					$this->skipUpdate = false;
				}
			}

			if(!$meta && $this->docCdn) {
				$post = \WP_Post::get_instance($post_id);
				if($post && (strpos($post->guid, $this->docCdn) === 0)) {
					$new_url = $post->guid;
				}
			}
		}

		return $new_url ?: $url;
	}

	/**
	 * Attempts to get the url based on the S3/Storage metadata
	 *
	 * @param array $meta
	 *
	 * @return null|string
	 */
	private function getAttachmentURLFromMeta($meta) {
		if(isset($meta['s3']) && $this->cdn) {
			return $this->cdn.'/'.$meta['s3']['key'];
		} else if(isset($meta['s3']) && isset($meta['s3']['url'])) {
			if(isset($meta['file']) && $this->docCdn) {
				$ext = strtolower(pathinfo($meta['file'], PATHINFO_EXTENSION));
				$image_exts = array('jpg', 'jpeg', 'jpe', 'gif', 'png');
				if(!in_array($ext, $image_exts)) {
					return trim($this->docCdn, '/').'/'.$meta['s3']['key'];
				}
			}

			return $meta['s3']['url'];
		}

		return null;
	}

	/**
	 * For compatibility with Offload S3.
	 *
	 * @param int $post_id
	 * @param array $info
	 *
	 * @return null|string
	 */
	private function getOffloadS3URL($post_id, $info) {
		if(!is_array($info) && (count($info) < 1)) {
			return null;
		}

		$region = $info[0]['region'];
		$bucket = $info[0]['bucket'];
		$file = $info[0]['key'];

		return "http://s3-$region.amazonaws.com/$bucket/$file";
	}
	//endregion

	//region Crop Tool Related
	/**
	 * Processes a file after a crop has been performed, uploading it to storage if it exists
	 *
	 * @param string $size
	 * @param string $upload_path
	 * @param string $file
	 * @param array $sizeMeta
	 *
	 * @return array
	 */
	public function processCrop($size, $upload_path, $file, $sizeMeta) {
		$upload_info = wp_upload_dir();
		$subdir = trim(str_replace($upload_info['basedir'], '', $upload_path), '/');
		$upload_path = rtrim(str_replace($subdir, '', $upload_path), '/');

		if($this->client && $this->client->enabled()) {
			$sizeMeta = $this->processFile($upload_path, $subdir.'/'.$file, $sizeMeta);
		}

		return $sizeMeta;
	}
	//endregion

	//region Prefix
	/**
	 * Generates a UUID string.
	 * @return string
	 */
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

	/**
	 * Generates a UUID path
	 * @return string
	 */
	private function genUUIDPath() {
		$uid = $this->genUUID();
		$result = '/';

		$segments = 8;
		if($segments > strlen($uid) / 2) {
			$segments = strlen($uid) / 2;
		}
		for($i = 0; $i < $segments; $i ++) {
			$result .= substr($uid, $i * 2, 2).'/';
		}

		return $result;
	}

	/**
	 * Gets an Offload S3 compatible object version string.
	 *
	 * @param int $id
	 *
	 * @return string|null
	 */
	private function getObjectVersion($id = null) {

		if(!empty($id) && !empty($this->versionedIds[$id])) {
			return $this->versionedIds[$id];
		}

		$date_format = 'dHis';
		// Use current time so that object version is unique
		$time = current_time('timestamp');

		$object_version = date($date_format, $time).'/';
		$object_version = apply_filters('as3cf_get_object_version_string', $object_version);

		if(!empty($id)) {
			$this->versionedIds[$id] = $object_version;
		}

		return $object_version;
	}

	/**
	 * Generates a prefix string from a prefix format string.
	 *
	 * @param string $prefix
	 * @param int|null $id
	 *
	 * @return string
	 */
	private function parsePrefix($prefix, $id = null) {
		$host = parse_url(get_home_url(), PHP_URL_HOST);

		$user = wp_get_current_user();
		$userName = '';
		if($user->ID != 0) {
			$userName = sanitize_title($user->display_name);
		}

		if($id) {
			$prefix = str_replace("@{versioning}", $this->getObjectVersion($id), $prefix);
		}

		$prefix = str_replace("@{site-id}", sanitize_title(strtolower(get_current_blog_id())), $prefix);
		$prefix = str_replace("@{site-name}", sanitize_title(strtolower(get_bloginfo('name'))), $prefix);
		$prefix = str_replace("@{site-host}", $host, $prefix);
		$prefix = str_replace("@{user-name}", $userName, $prefix);
		$prefix = str_replace("@{unique-id}", $this->genUUID(), $prefix);
		$prefix = str_replace("@{unique-path}", $this->genUUIDPath(), $prefix);
		$prefix = str_replace("//", "/", $prefix);

		$matches = [];
		preg_match_all('/\@\{date\:([^\}]*)\}/', $prefix, $matches);
		if(count($matches) == 2) {
			for($i = 0; $i < count($matches[0]); $i ++) {
				$prefix = str_replace($matches[0][$i], date($matches[1][$i]), $prefix);
			}
		}

		return trim($prefix, '/').'/';
	}
	//endregion

	//region Storage File Processing
	/**
	 * Uploads a file to storage and updates the related metadata.
	 *
	 * @param string $upload_path
	 * @param string $filename
	 * @param array $data
	 * @param null|int $id
	 *
	 * @return array
	 */
	private function processFile($upload_path, $filename, $data, $id = null) {
		if(!file_exists($upload_path.'/'.$filename)) {
			return $data;
		}

		if(isset($data['s3'])) {
			$key = $data['s3']['key'];

			if($key == $filename) {
				return $data;
			}

			$this->deleteFile($key);
		}

		$bucketFilename = $filename;

		$prefix = '';
		if(!empty($this->prefixFormat)) {
			$prefix = $this->parsePrefix($this->prefixFormat, $id);
			$parts = explode('/', $filename);
			$bucketFilename = array_pop($parts);
		}

		$file = fopen($upload_path.'/'.$filename, 'r');
		try {
			$url = $this->client->upload($prefix.$bucketFilename, $file, $this->privacy, $this->cacheControl, $this->expires);

			$options = [];
			$params = [];
			if(!empty($this->cacheControl)) {
				$params['CacheControl'] = $this->cacheControl;
			}

			if(!empty($this->expires)) {
				$params['Expires'] = $this->expires;
			}

			if(!empty($params)) {
				$options['params'] = $params;
			}

			$data['s3'] = [
				'url' => $url,
				'bucket' => $this->client->bucket(),
				'privacy' => $this->privacy,
				'key' => $prefix.$bucketFilename,
				'options' => $options
			];

			if(file_exists($upload_path.'/'.$filename)) {
				$ftype = wp_check_filetype($upload_path.'/'.$filename);
				if(!empty($ftype) && isset($ftype['type'])) {
					$data['s3']['mime-type'] = $ftype['type'];
				}
			}
		}
		catch(StorageException $ex) {
			Logger::error('Upload Error', [
				'exception' => $ex->getMessage(),
				'prefix' => $prefix,
				'bucketFilename' => $bucketFilename,
				'privacy' => $this->privacy
			]);
		}
		finally {
			fclose($file);
		}

		if($this->deleteOnUpload) {
			if(file_exists($upload_path.'/'.$filename)) {
				unlink($upload_path.'/'.$filename);
			}
		}

		return $data;
	}

	/**
	 * Deletes a file from storage
	 *
	 * @param $file
	 */
	private function deleteFile($file) {
		try {
			if($this->client && $this->client->enabled()) {
				$this->client->delete($file);
			}
		}
		catch(StorageException $ex) {
			Logger::error('Delete File Error', ['exception' => $ex->getMessage(), 'Key' => $file]);
		}
	}
	//endregion

	//region WordPress UI Hooks
	/**
	 * Hooks into the WordPress UI in various ways.
	 */
	private function hookupUI() {
		$this->hookAttachmentDetails();
		$this->hookMediaList();
		$this->hookBulkActions();
		$this->hookStorageInfoMetabox();
		$this->hookMediaGrid();
	}

	/**
	 * Displays storage info in the attachment details pop up.
	 */
	private function hookAttachmentDetails() {
		add_action('wp_enqueue_media', function() {
			add_action('admin_footer', function() {
				?>
                <script>
                    jQuery(document).ready(function () {
                        var attachTemplate = jQuery('#tmpl-attachment-details-two-column');
                        if (attachTemplate) {
                            var txt = attachTemplate.text();
                            var idx = txt.indexOf('<div class="compat-meta">');
                            txt = txt.slice(0, idx) + '<# if ( data.s3 ) { #><div><strong>Bucket:</strong> {{data.s3.bucket}}</div><div><strong>Path:</strong> {{data.s3.key}}</div><div><strong>Access:</strong> {{data.s3.privacy}}</div><# if ( data.s3.options && data.s3.options.params ) { #><# if (data.s3.options.params.CacheControl) { #><div><strong>S3 Cache-Control:</strong> {{data.s3.options.params.CacheControl}}</div><# } #><# if (data.s3.options.params.Expires) { #><div><strong>S3 Expires:</strong> {{data.s3.options.params.Expires}}</div><# } #><# } #><div><a href="{{data.s3.url}}" target="_blank">Original Storage URL</a></div><# } #>' + txt.slice(idx);
                            attachTemplate.text(txt);
                        }
                    });
                </script>
				<?php

			});
		});
	}

	/**
	 * Adds a custom column to the media list.
	 */
	private function hookMediaList() {
		if(!$this->mediaListIntegration) {
			return;
		}

		add_action('admin_init', function() {
			add_filter('manage_media_columns', function($cols) {
				$cols["cloud"] = 'Cloud';

				return $cols;
			});

			add_action('manage_media_custom_column', function($column_name, $id) {
				if($column_name == "cloud") {
					$meta = wp_get_attachment_metadata($id);
					if(!empty($meta) && isset($meta['s3'])) {
						echo "<a href='".$meta['s3']['url']."' target=_blank>View</a>";
					}
				}
			}, 10, 2);
		});

		add_action('wp_enqueue_media', function() {
			add_action('admin_head', function() {
				if(get_current_screen()->base == 'upload') {
					?>
                    <style>
                        th.column-s3, td.column-s3 {
                            width: 60px !important;
                            max-width: 60px !important;
                        }
                    </style>
					<?php
				}
			});
		});
	}

	/**
	 * Adds bulk actions to the media list view.
	 */
	private function hookBulkActions() {
		if(!$this->enabled()) {
			return;
		}

		if(!$this->mediaListIntegration) {
			return;
		}

		add_action('admin_init', function() {
			add_filter('bulk_actions-upload', function($actions) {
				$actions['ilab_s3_import'] = 'Import to S3';

				return $actions;
			});

			add_filter('handle_bulk_actions-upload', function($redirect_to, $action_name, $post_ids) {
				if('ilab_s3_import' === $action_name) {
					$posts_to_import = [];
					if(count($post_ids) > 0) {
						foreach($post_ids as $post_id) {
							$meta = wp_get_attachment_metadata($post_id);
							if(!empty($meta) && isset($meta['s3'])) {
								continue;
							}

							$posts_to_import[] = $post_id;
						}
					}

					if(count($posts_to_import) > 0) {
						update_option('ilab_s3_import_status', true);
						update_option('ilab_s3_import_total_count', count($posts_to_import));
						update_option('ilab_s3_import_current', 1);
						update_option('ilab_s3_import_should_cancel', false);

						$process = new StorageImportProcess();

						for($i = 0; $i < count($posts_to_import); ++ $i) {
							$process->push_to_queue(['index' => $i, 'post' => $posts_to_import[$i]]);
						}

						$process->save();
						$process->dispatch();

						return 'admin.php?page=media-tools-s3-importer';
					}
				}

				return $redirect_to;
			}, 1000, 3);
		});
	}

	/**
	 * Displays a cloud icon on items in the media grid.
	 */
	private function hookMediaGrid() {
		if(!$this->displayBadges) {
			return;
		}

		add_action('admin_head', function() {
			?>
            <style>
                .ilab-s3-logo {
                    display: none;
                    position: absolute;
                    right: 5px;
                    bottom: 4px;
                    z-index: 5;
                }

                .has-s3 > .ilab-s3-logo {
                    display: block;
                }
            </style>
			<?php
		});
		add_action('admin_footer', function() {
			?>
            <script>
                jQuery(document).ready(function () {
                    var attachTemplate = jQuery('#tmpl-attachment');
                    if (attachTemplate) {
                        var txt = attachTemplate.text();

                        var search = '<div class="attachment-preview js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">';
                        var replace = '<div class="attachment-preview js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }} <# if (data.hasOwnProperty("s3")) {#>has-s3<#}#>"><img src="<?php echo ILAB_PUB_IMG_URL.'/ilab-cloud-icon.svg'?>" width="29" height="18" class="ilab-s3-logo">';
                        txt = txt.replace(search, replace);
                        attachTemplate.text(txt);
                    }
                });
            </script>
			<?php

		});
	}

	/**
	 * Adds the Cloud Storage metabox on attachment edit pages.
	 */
	private function hookStorageInfoMetabox() {
		add_action('admin_init', function() {
			add_meta_box('ilab-s3-info-meta', 'Cloud Storage Info', [
				$this,
				'renderStorageInfoMeta'
			], 'attachment', 'side', 'low');
		});
	}

	/**
	 * Renders the Cloud Storage metabox
	 */
	public function renderStorageInfoMeta() {
		global $post;

		$meta = wp_get_attachment_metadata($post->ID);
		if(empty($meta)) {
			return;
		}

		if(!isset($meta['s3'])) {
			$meta = get_post_meta($post->ID, 'ilab_s3_info', true);
		}

		if(empty($meta) || !isset($meta['s3'])) {
			?>
            Not uploaded.
			<?php
		} else {
			?>
            <div class="misc-pub-section">
                Bucket: <a href="https://console.aws.amazon.com/s3/buckets/<?php echo $meta['s3']['bucket'] ?>"
                           target="_blank"><?php echo $meta['s3']['bucket'] ?></a>
            </div>
            <div class="misc-pub-section">
                Path: <a
                        href="https://console.aws.amazon.com/s3/buckets/<?php echo $meta['s3']['bucket'] ?>/<?php echo $meta['s3']['key'] ?>/details"
                        target="_blank"><?php echo $meta['s3']['key'] ?></a>
            </div>
            <div class="misc-pub-section">
                <label for="s3-access-acl">Access:</label>
                <select id="s3-access-acl" name="s3-access-acl">
                    <option value="public-read" <?php echo (isset($meta['s3']['privacy']) && ($meta['s3']['privacy'] == 'public-read')) ? 'selected' : '' ?>>
                        Public
                    </option>
                    <option value="authenticated-read" <?php echo (isset($meta['s3']['privacy']) && ($meta['s3']['privacy'] == 'authenticated-read')) ? 'selected' : '' ?>>
                        Authenticated Users
                    </option>
                </select>
            </div>
            <div class="misc-pub-section">
                <label for="s3-cache-control">Cache-Control:</label>
                <input type="text" class="widefat" name="s3-cache-control" id="s3-cache-control"
                       value="<?php echo (isset($meta['s3']['options']) && isset($meta['s3']['options']['params']['CacheControl'])) ? $meta['s3']['options']['params']['CacheControl'] : '' ?>">
            </div>
            <div class="misc-pub-section">
                <label for="s3-expires">Expires:</label>
                <input type="text" class="widefat" name="s3-expires" id="s3-expires"
                       value="<?php echo (isset($meta['s3']['options']) && isset($meta['s3']['options']['params']['Expires'])) ? $meta['s3']['options']['params']['Expires'] : '' ?>">
            </div>
            <div class="misc-pub-section">
                <a href="<?php echo $meta['s3']['url'] ?>" target="_blank">View S3 URL</a></strong>
            </div>
			<?php
		}

	}
	//endregion

	//region Importer
	/**
	 * Renders the storage importer view
	 */
	public function renderImporter() {
		$shouldCancel = get_option('ilab_s3_import_should_cancel', false);
		$status = get_option('ilab_s3_import_status', false);
		$total = get_option('ilab_s3_import_total_count', 0);
		$current = get_option('ilab_s3_import_current', 1);
		$currentFile = get_option('ilab_s3_import_current_file', '');

		if($total == 0) {
			$attachments = get_posts([
				                         'post_type' => 'attachment',
				                         'posts_per_page' => - 1
			                         ]);

			$total = count($attachments);
		}

		$progress = 0;

		if($total > 0) {
			$progress = ($current / $total) * 100;
		}

		echo View::render_view('storage/ilab-storage-importer.php', [
			'status' => ($status) ? 'running' : 'idle',
			'total' => $total,
			'progress' => $progress,
			'current' => $current,
			'currentFile' => $currentFile,
			'enabled' => $this->enabled(),
			'shouldCancel' => $shouldCancel
		]);
	}

	/**
	 * Ajax callback for import progress.
	 */
	public function importProgress() {
		$shouldCancel = get_option('ilab_s3_import_should_cancel', false);
		$status = get_option('ilab_s3_import_status', false);
		$total = get_option('ilab_s3_import_total_count', 0);
		$current = get_option('ilab_s3_import_current', 0);
		$currentFile = get_option('ilab_s3_import_current_file', '');

		header('Content-type: application/json');
		echo json_encode([
			                 'status' => ($status) ? 'running' : 'idle',
			                 'total' => (int) $total,
			                 'current' => (int) $current,
			                 'currentFile' => $currentFile,
			                 'shouldCancel' => $shouldCancel
		                 ]);
		die;
	}

	/**
	 * Ajax method to cancel the import
	 */
	public function cancelImportMedia() {
		update_option('ilab_s3_import_should_cancel', 1);
		StorageImportProcess::cancelAll();

		json_response(['status' => 'ok']);
	}

	/**
	 * Ajax method to start the import.
	 */
	public function importMedia() {
		$args = [
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'nopaging' => true,
			'fields' => 'ids',
		];

		if(!$this->uploadDocs) {
			$args['post_mime_type'] = 'image';
		}

		$query = new \WP_Query($args);

		if($query->post_count > 0) {
			update_option('ilab_s3_import_status', true);
			update_option('ilab_s3_import_total_count', $query->post_count);
			update_option('ilab_s3_import_current', 1);
			update_option('ilab_s3_import_should_cancel', false);

			$process = new StorageImportProcess();

			for($i = 0; $i < $query->post_count; ++ $i) {
				$process->push_to_queue(['index' => $i, 'post' => $query->posts[$i]]);
			}

			$process->save();
			$process->dispatch();
		} else {
			delete_option('ilab_s3_import_status');
		}

		header('Content-type: application/json');
		echo '{"status":"running"}';
		die;
	}
	//endregion

	//region Direct Upload Support
	/**
	 * Gets a pre-signed URL for uploading directly to the storage backend
	 *
	 * @param string $filename
	 *
	 * @return array|null
	 */
	public function uploadUrlForFile($filename) {
		$bucketFilename = $filename;

		$prefix = '';
		if(!empty($this->prefixFormat)) {
			$prefix = $this->parsePrefix($this->prefixFormat, null);
			$parts = explode('/', $filename);
			$bucketFilename = array_pop($parts);
		}

		if($prefix == '') {
			$prefix = date('Y/m').'/';
		}

		if($this->client && $this->client->enabled()) {
			try {
				return $this->client->uploadUrl($prefix.$bucketFilename, $this->privacy, $this->cacheControl, $this->expires);
			}
			catch(StorageException $ex) {
				Logger::error('Generate File Upload URL Error', ['exception' => $ex->getMessage()]);
			}
		}

		return null;
	}

	/**
	 * Once a file has been directly uploaded, it'll need to be "imported" into WordPress
	 *
	 * @param string $key
	 *
	 * @return array|bool
	 */
	public function importImageAttachmentFromStorage($key) {
		if(!$this->client || !$this->client->enabled()) {
			return null;
		}

		$result = $this->client->info($key);

		if(!is_array($result['size'])) {
			return false;
		}

		$mimeType = 'image/'.$result['type'];

		$fileParts = explode('/', $key);
		$filename = array_pop($fileParts);
		$url = $this->client->url($key);

		$s3Info = [
			'url' => $url,
			'mime-type' => $mimeType,
			'bucket' => $this->client->bucket(),
			'privacy' => $this->privacy,
			'key' => $key,
			'options' => [
				'params' => []
			]
		];

		if(!empty($this->cacheControl)) {
			$s3Info['options']['params']['CacheControl'] = $this->cacheControl;
		}

		if(!empty($this->expires)) {
			$s3Info['options']['params']['Expires'] = $this->expires;
		}


		$meta = [
			'width' => $result['size'][0],
			'height' => $result['size'][1],
			'file' => $key,
			'image_meta' => [],
			's3' => $s3Info,
			'sizes' => []
		];

		$builtInSizes = [];
		foreach(['thumbnail', 'medium', 'medium_large', 'large'] as $size) {
			$builtInSizes[$size] = [
				'width' => get_option("{$size}_size_w"),
				'height' => get_option("{$size}_size_h"),
				'crop' => get_option("{$size}_crop", 0),
			];
		}

		$additional_sizes = wp_get_additional_image_sizes();
		$sizes = array_merge($builtInSizes, $additional_sizes);

		foreach($sizes as $sizeKey => $size) {
			$resized = image_resize_dimensions($result['size'][0], $result['size'][1], $size['width'], $size['height'], $size['crop']);
			if($resized) {
				$meta['sizes'][$sizeKey] = [
					'file' => $filename,
					'width' => $resized[4],
					'height' => $resized[5],
					'mime-type' => 'image/jpeg',
					's3' => $s3Info
				];
			}
		}

		$post = wp_insert_post([
			                       'post_author' => get_current_user_id(),
			                       'post_title' => $filename,
			                       'post_status' => 'inherit',
			                       'post_type' => 'attachment',
			                       'guid' => $url,
			                       'post_mime_type' => $mimeType
		                       ]);

		if(is_wp_error($post)) {
			return false;
		}

		$meta = apply_filters('ilab_s3_after_upload', $post, $meta);

		add_post_meta($post, '_wp_attached_file', $key);
		add_post_meta($post, '_wp_attachment_metadata', $meta);

		$thumbUrl = image_downsize($post, ['width' => 128, 'height' => 128]);

		if(is_array($thumbUrl)) {
			$thumbUrl = $thumbUrl[0];
		}


		return [
			'id' => $post,
			'url' => $url,
			'thumb' => $thumbUrl
		];

	}
	//endregion

}
