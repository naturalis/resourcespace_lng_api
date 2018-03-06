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
	private $_files = new \stdClass();
	// Thumbnail data stored here before returned in response
	private $_thumbnails = new \stdClass();

	public function __construct ($configPath = false) {
		// AbstractController creates Config object from file path
		parent::__construct($configPath);
		// Initialise model with Config object
		$this->_dbh = new UploadModel($this->_config);
		$this->_storageDir = $this->_config->getStorageDir();
		$this->_scrambleKey = $this->_config->getScrambleKey();
		// Includes bootstrap, checking for required fields
		$this->_createFile();
	}
	
	public function checkApiCredentials ($apiKey = false) {
		if (!$apiKey) {
			$this->_setUserDataError('Error! No api key provided');
		} else if (!$this->_checkApiCredentials($apiKey)) {
			$this->_setUserDataError('Error! Incorrect api key provided');
		}
		return $this;
	}
	
	public function createResource () {
		if (!$this->_loginSucccessful || empty($this->_apiUserId)) {
			$this->_setResponseError('Error! Login failed');
			return false;
		}
		if (empty($this->_file)) {
			$this->_setResponseError('Error! No file to process');
			return false;
		}
		$this->_resourceId = $this->_dbh->createResource($this->_apiUserId);
        $this->_setExtension();
        
        try {
        	// Set RS path and dimensions; these are used for thumbnail creation
        	$this->_file->rsPath = $this->_getResourcePath();
        	
// for testing purposes
copy($_FILES['userfile']['tmp_name'], $this->_file->rsPath);
 
        	// Must use $_FILES['userfile']['tmp_name'] here
        	//move_uploaded_file($_FILES['userfile']['tmp_name'], $this->_file->rsPath);
        	list($this->_file->width, $this->_file->height) = 
        		$this->_getImageDimensions($this->_file->rsPath);
        	$this->_response->field8 = 
        	$this->_addFileToResponse($this->_file->rsPath);

        } catch (\RuntimeException $e) {
        	$this->_setResponseError('Error! Could not upload file (' . $e->getMessage() . ')');
        	return false;
        }
        $this->_dbh->updateResource(
        	$this->_resourceId,
        	$this->_file->name ?? null,
        	$this->_file->title ?? 'No title',
        	$this->_file->extension
        );
        $this->_createPreviews();
        
        print_r($this->getResponse()); die();
        
        
        return $this->getResponse();
	}
	
	public function getResponse () {
		if (!empty($this->_response->error)) {
			// Make sure no data is returned but the error itself;
			// re-initalise response with current error
			return $this->_initResponse($this->_response->error);
		}
		$this->_response->ref = $this->_resourceId;
		$this->_response->field8 = $this->_file->title;
		$this->_response->files = $this->_files;
		$this->_response->thumbnails = $this->_thumbnails;
		return $this->_response;
	}
	
	private function _addFileToResponse ($filePath, $sizeCode = false) {
		$file = new \stdClass();
		// Original image
		if (!$sizeCode) {
			$file->name = 'Original';
			$file->width= $this->_file->width;
			$file->height = $this->_file->height;
			$file->extension = $this->_file->extension;
			$file->src = $filePath;
			$this->_files[0] = $file;
		}
		// Preview
		else if (in_array($sizeCode, $this->_previewCodes)) {
			$preview = $this->_getPreviewData($sizeCode);
			$file->name = $preview['name'];
			$file->width= $preview['width'];
			$file->height = $preview['height'];
			$file->extension = $this->_file->extension;
			$file->src = $filePath;
			$key = isset($this->_files) ? count($this->_files) : 0;
			$this->_files[$key] = $file;
		}
		// Preview
		else if (in_array($sizeCode, $this->_thumbnailCodes)) {
			$preview = $this->_getPreviewData($sizeCode);
			$key = array_search($preview['id'], $this->_thumbnailCodes);
			$this->_thumbnails->{$key} = $filePath;
		}
	}
		
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
	
	private function _getPreviewSizes () {
		if (!$this->_previewSizes) {
			$this->_previewSizes = $this->_dbh->getPreviewSizes();
			// Only create the previews specified in previews and thumbnails arrays
			foreach ($this->_previewSizes as $i => $preview) {
				if (!in_array($preview['id'], array_merge($this->_previewCodes, array_values($this->_thumbnailCodes)))) {
					unset($this->_previewSizes[$i]);
				}
			}
		}
		return $this->_previewSizes;
	}
	
	private function _getPreviewData ($sizeCode) {
		foreach ($this->_getPreviewSizes() as $i => $preview) {
			if ($preview['id'] == $sizeCode) {
				return $this->_getPreviewSizes()[$i];
			}
		}
		return false;
	}
	
	private function _setScramblePath () {
		if ($this->_scrambleKey && $this->_resourceId) {
			return substr(md5($this->_resourceId . "_" . $this->_scrambleKey), 0, 15);
		}
		return '';
	}
	
	private function _setExtension () {
        $pathInfo = pathinfo($_FILES['userfile']['name']);
        $this->_file->extension = strtolower($pathInfo['extension']);
	}
	
	/*
	 * sizeCode is abbreviation of derivate image (medium size, thumbnail etc);
	 * should be empty for original image.
	 */ 
	private function _getResourcePath ($sizeCode = '', $extension = false) {
		$extension = !$extension ? $this->_file->extension : $extension;
		$folder = '';
	    for ($n = 0, $nMax = strlen($this->_resourceId); $n < $nMax; $n++) {
	        $folder .= substr($this->_resourceId, $n, 1);
	        if ($this->_scrambleKey && $n == (strlen($this->_resourceId) - 1)) {
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
		return $this->_storageDir . '/' . $folder . $this->_resourceId . $sizeCode . "_" . 
			substr(md5($this->_resourceId . $sizeCode . $this->_scrambleKey), 0, 15) . "." . $extension;
	}
	
	private function _createFile () {
		if (empty($_FILES['userfile'])) {
			throw new \Exception ('Error! No file to process');
		}
		$this->_file = (object)$_FILES['userfile'];
		$this->_file->title = $_GET['field8'] ?? null;
		$this->_file->collectionId = $_GET['collection'] ?? false;
		// Collection id must be present\
		if (!$this->_file->collectionId) {
			throw new \Exception ('Error! Collection is not set');
		}
		return $this->_file;
	}
	
	private function _getImageMagickPath ($utility = false) {
		if (!$utility || !$this->_config->getImageMagickPath()) {
			throw new \Exception('Error! Cannot find path to ImageMagick');
		}
		return $this->_config->getImageMagickPath() . '/' . $utility;
	}
	
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