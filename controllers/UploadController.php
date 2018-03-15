<?php

namespace RsApi;

require_once 'AbstractController.php';

final class UploadController extends AbstractController {
	
	// Data pertaining to uploaded file
	private $_file;
	// RS database id 
	private $_resourceId;
	// Key used to scramble path (set from config)
	private $_scrambleKey;
	// RS filestore location (set from config)
	private $_storageDir;
	// Base url (set from config)
	private $_baseUrl;
	// Available preview types (set from RS database)
	private $_previewSizes;
	// Codes translating into thumbnails
	private $_thumbnailCodes = [
		'small' => 'col', 
		'medium' =>'thm', 
		'large' => 'pre'
	];
	// Codes translating into previews
	private $_previewCodes = ['scr', 'hpr'];
	// Original image and preview data stored here before returned in response
	private $_files;
	// Thumbnail data stored here before returned in response
	private $_thumbnails;

	public function __construct ($configPath = false) {
		// AbstractController creates Config object from file path
		parent::__construct($configPath);
		// Initialise model with Config object
		$this->_dbh = new UploadModel($this->_config);
		$this->_storageDir = $this->_config->getStorageDir();
		$this->_scrambleKey = $this->_config->getScrambleKey();
		$this->_baseUrl = $this->_config->getRsBaseUrl();
		// Create file object with posted data; includes simple bootstrap
		try {
			$this->_initFile();
		} catch (\Exception $e) {
			$this->_setResponseError($e->getMessage());
			return $this->getResponse();		
		}
	}
	
	// Controller specific: user must be superadmin to add another user!
	public function checkApiCredentials () {
		if (!isset($_GET['key'])) {
			$this->_setResponseError('Error! No api key provided');
			return $this->getResponse();
		// No admin credentials needed here
		} else if (!$this->_checkApiCredentials($_GET['key'])) {
			$this->_setResponseError('Error! Incorrect api key provided');
			return $this->getResponse();
		}
		return $this;
	}
	
	public function createResource () {
		$this->checkApiCredentials();
		if (!$this->_loginSucccessful || empty($this->_apiUserId)) {
			$this->_setResponseError('Error! Login failed');
			return $this->getResponse();
		}
		if (empty($this->_file)) {
			$this->_setResponseError('Error! No file to process');
			return $this->getResponse();
		}
		$this->_file->resourceId = $this->_dbh->createResource($this->_apiUserId);
        $pathInfo = pathinfo($_FILES['userfile']['name']);
        $this->_file->extension = strtolower($pathInfo['extension']);
		
        try {
        	// Set RS path and dimensions; these are used for thumbnail creation
        	$this->_file->rsPath = $this->_getResourcePath();
        	
// for testing purposes!
// copy($_FILES['userfile']['tmp_name'], $this->_file->rsPath);
 
        	// Must use $_FILES['userfile']['tmp_name'] here
        	move_uploaded_file($_FILES['userfile']['tmp_name'], $this->_file->rsPath);
        	list($this->_file->width, $this->_file->height) = 
        		$this->_getImageDimensions($this->_file->rsPath);
	        $this->_dbh->updateResource(
	        	$this->_file->resourceId,
	        	$this->_file->name ?? null,
	        	$this->_file->title ?? 'No title',
	        	$this->_file->extension
	        );
	        $this->_dbh->addResourceToCollection(
	        	$this->_file->resourceId, 
	        	$this->_file->collectionId
	        );
        	// Add original file data to response
	        $this->_addFileToResponse($this->_file->rsPath);

        } catch (\RuntimeException $e) {
        	$this->_setResponseError('Error! Could not upload file (' . $e->getMessage() . ')');
        	return $this->getResponse();;
        }
        $this->_createPreviews();
        return $this->getResponse();
	}
	
	// Formatted response
	public function getResponse () {
		if (!empty($this->_response->error)) {
			// Make sure no data is returned but the error itself;
			// re-initalise response with current error
			return $this->_initResponse($this->_response->error);
		}
		$this->_response->ref = $this->_file->resourceId;
		$this->_response->field8 = $this->_file->title;
		$this->_response->files = $this->_files;
		$this->_response->thumbnails = $this->_thumbnails;		
		// Modify response as per RS original
		$output = [
			'error' => null,
			'collection' => $this->_file->collectionId,
			'resource' => $this->_response
		];
		return $output;
	}
	
	// Append data of original image, preview or thumbnail to appropriate container;
	// both containers are appended to response at the final stage
	private function _addFileToResponse ($filePath, $sizeCode = false) {
		$file = new \stdClass();
		// Original image; add to files container
		if (!$sizeCode) {
			$file->name = 'Original';
			$file->width= $this->_file->width;
			$file->height = $this->_file->height;
			$file->extension = $this->_file->extension;
			$file->src = $this->_filePathToUrl($filePath);
			$this->_files[0] = $file;
		}
		// Preview; add to files container
		else if (in_array($sizeCode, $this->_previewCodes)) {
			$preview = $this->_getPreviewData($sizeCode);
			$file->name = $preview['name'];
			$file->width= $preview['width'];
			$file->height = $preview['height'];
			$file->extension = $this->_file->extension;
			$file->src = $this->_filePathToUrl($filePath);
			$key = isset($this->_files) ? count($this->_files) : 0;
			$this->_files[$key] = $file;
		}
		// Thumbnail; treat differently in response
		else if (in_array($sizeCode, $this->_thumbnailCodes)) {
			$preview = $this->_getPreviewData($sizeCode);
			$key = array_search($preview['id'], $this->_thumbnailCodes);
			$this->_thumbnails->{$key} = $this->_filePathToUrl($filePath);
		}
	}
	
	// Converts base path to base url;
	// assumes url is base url plus last directory in base path
	private function _filePathToUrl ($filePath) {
		return $this->_baseUrl . substr($filePath, strpos($filePath, basename($this->_storageDir)) - 1);
	}
	
	// functional clone of RS method (but minus the incomprehensible juggling of function parameters)
	private function _createPreviews () {
		foreach ($this->_getPreviewSizes() as $preview) {
			$previewPath = $this->_getResourcePath($preview['id'], $this->_file->extension);
			$cmd = $this->_getImageMagickPath('convert') . ' ' . 
				escapeshellarg($this->_file->rsPath) . ' quality -90 ' .
				'-resize ' . $preview['width'] . 'x' . $preview['width'] . "\">\" " .
				escapeshellarg($previewPath);
			$this->_runCommand($cmd);
			$this->_addFileToResponse($previewPath, $preview['id']);
		}
	}
	
	// Basically data from RS database table, but any sizeCode not in 
	// previewCodes or thumbnailCodes is discarded
	private function _getPreviewSizes () {
		if (!$this->_previewSizes) {
			$this->_previewSizes = $this->_dbh->getPreviewSizes();
			// Only create the previews specified in previews and thumbnails arrays
			foreach ($this->_previewSizes as $i => $preview) {
				if (!in_array($preview['id'], array_merge($this->_previewCodes, 
					array_values($this->_thumbnailCodes)))) {
					unset($this->_previewSizes[$i]);
				}
			}
		}
		return $this->_previewSizes;
	}
	
	// Returns parameters for preview file creation
	private function _getPreviewData ($sizeCode) {
		foreach ($this->_getPreviewSizes() as $i => $preview) {
			if ($preview['id'] == $sizeCode) {
				return $this->_getPreviewSizes()[$i];
			}
		}
		return false;
	}
	
	// CLone of RS method
	private function _setScramblePath () {
		if ($this->_scrambleKey && $this->_file->resourceId) {
			return substr(md5($this->_file->resourceId . "_" . $this->_scrambleKey), 0, 15);
		}
		return '';
	}
	
	/*
	 * Functional clone of RS method: create scrambled file path to image based on resource id.
	 * 
	 * sizeCode is abbreviation of derivate image (medium size, thumbnail etc);
	 * should be empty for original image.
	 */ 
	private function _getResourcePath ($sizeCode = '', $extension = false) {
		$extension = !$extension ? $this->_file->extension : $extension;
		$folder = '';
	    for ($n = 0, $nMax = strlen($this->_file->resourceId); $n < $nMax; $n++) {
	        $folder .= substr($this->_file->resourceId, $n, 1);
	        if ($this->_scrambleKey && $n == (strlen($this->_file->resourceId) - 1)) {
	            $folder .= "_" . $this->_setScramblePath();
	        }
	        $folder .= "/";
	        if ((!(file_exists($this->_storageDir . '/' . $folder)))) {
	            if (!mkdir($this->_storageDir . '/' . $folder, 0777) && 
	            	!is_dir($this->_storageDir . '/' . $folder)) {
	                throw new \RuntimeException(sprintf('Directory "%s" was not created', 
	                	$this->_storageDir . '/' . $folder));
	            }
	            chmod($this->_storageDir . '/' . $folder, 0777);
	        }
	    }
		return $this->_storageDir . '/' . $folder . $this->_file->resourceId . $sizeCode . "_" . 
			substr(md5($this->_file->resourceId . $sizeCode . $this->_scrambleKey), 0, 15) . 
			"." . $extension;
	}
	
	// Initialises file object with data pertaining to uploaded file; 
	// added bonus: some basic bootstrapping
	private function _initFile () {
		if (empty($_FILES['userfile'])) {
			throw new \Exception ('Error! No file to process');
		}
		$this->_file = (object)$_FILES['userfile'];
		$this->_file->title = $_GET['field8'] ?? 'No title';
		$this->_file->collectionId = $_GET['collection'] ?? false;
		// Collection id must be present!...
		if (!$this->_file->collectionId) {
			throw new \Exception ('Error! Collection is not set');
		}
		// ... and collection must exist
		if (!$this->_dbh->collectionExists($this->_file->collectionId)) {
			throw new \Exception ('Error! Collection with id ' . 
				$this->_file->collectionId . ' does not exist');
		}
		// Empty containers to store image data
		$this->_files = [];
		$this->_thumbnails = new \stdClass();
		return $this->_file;
	}
	
	// CLone of RS method
	private function _getImageMagickPath ($utility = false) {
		if (!$utility || !$this->_config->getImageMagickPath()) {
			throw new \Exception('Error! Cannot find path to ImageMagick');
		}
		return $this->_config->getImageMagickPath() . '/' . $utility;
	}
	
	// Clone of RS method; maybe native PHP function would have sufficed?
	private function _getImageDimensions ($filePath) {
	    $command = $this->_getImageMagickPath('identify') . ' -format %wx%h ' . 
	    	escapeshellarg($filePath) . '[0]';
        $output = $this->_runCommand($command);
        preg_match('/^([0-9]+)x([0-9]+)$/ims', $output, $matches);
        if ((@list(, $sw, $sh) = $matches) === false) {
            return false;
        }
		return [$sw, $sh];
	}
	

	
}