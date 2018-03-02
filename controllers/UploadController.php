<?php

namespace RsApi;

require_once 'AbstractController.php';

final class UploadController extends AbstractController {
	
	private $_file;
	private $_resourceId;
	private $_extension;
	private $_scrambleKey;
	private $_scramblePath;
	private $_storageDir;
	private $_pathSuffix = '/';	

	public function __construct ($configPath = false) {
		// AbstractController creates Config object from file path
		parent::__construct($configPath);
		// Initialise model with Config object
		$this->_dbh = new UploadModel($this->_config);
		$this->_storageDir = $this->_config->getStorageDir();
		$this->_scrambleKey = $this->_config->getScrambleKey();
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
        	list($this->_file->width, $this->_file->height) = 
        		$this->_getImageDimensions($this->_file->rsPath);
        	move_uploaded_file($this->_file->tmp_name, $this->_file->rsPath);
        } catch (\RuntimeException $e) {
        	$this->_setResponseError('Error! Could not upload file (' . $e->getMessage() . ')');
        	return false;
        }
        $this->_dbh->updateResource(
        	$this->_resourceId,
        	$this->_file->name ?? null,
        	$this->_file->title ?? 'No title',
        	$this->_extension
        );
        $this->_createPreviews();
		
	}
	
	public function createPreviews () {
		foreach ($this->_dbh->getPreviewSizes() as $i => $preview) {
			// Makes no sense to create the same file twice or more, does it?
			if ($this->_file->width <= $preview['width'] && 
				$this->_file->height <= $preview['height']) {
				continue;
			}
			$cmd = $this->_getImageMagickPath('convert') . ' ' . 
				escapeshellarg($this->_file->rsPath) . ' quality -90 ' .
				'-resize ' . $preview['width'] . 'x' . $preview['width'] . "\">\" " .
				escapeshellarg($this->_getResourcePath($preview['id'], $this->_extension));
		}
	}
	
	public function getResponse () {
		if (!empty($this->_response->error)) {
			// Make sure no data is returned but the error itself;
			// re-initalise response with current error
			return $this->_initResponse($this->_response->error);
		}
		$this->_response->ref = $this->_resourceId;
		$this->_response->field8 = $this->_field8;
		$this->_response->files = [];	
		return $this->_response;
	}
		
	private function _setScramblePath () {
		if ($this->_scrambleKey && $this->_resourceId) {
			return substr(md5($this->_resourceId . "_" . $this->_scrambleKey), 0, 15);
		}
		return '';
	}
	
	private function _setExtension () {
        $pathInfo = pathinfo($_FILES['userfile']['name']);
        $this->_extension = strtolower($pathInfo['extension']);
	}
	
	/*
	 * sizeCode is abbreviation of derivate image (medium size, thumbnail etc);
	 * should be empty for original image.
	 */ 
	private function _getResourcePath ($sizeCode = '', $extension = false) {
		$extension = !$extension ? $this->_extension : $extension;
		$folder = '';
	    for ($n = 0, $nMax = strlen($this->_resourceId); $n < $nMax; $n++) {
	        $folder .= substr($this->_resourceId, $n, 1);
	        if ($this->_scrambleKey && $n == (strlen($this->_resourceId) - 1)) {
	            $folder .= "_" . $this->_setScramblePath();
	        }
	        $folder .= "/";
	        if ((!(file_exists($this->_storageDir . $this->_pathSuffix . $folder)))) {
	            if (!mkdir($this->_storageDir . $this->_pathSuffix . $folder, 0777) && 
	            	!is_dir($this->_storageDir . $this->_pathSuffix . $folder)) {
	                throw new \RuntimeException(sprintf('Directory "%s" was not created', 
	                	$this->_storageDir . $this->_pathSuffix . $folder));
	            }
	            chmod($this->_storageDir . $this->_pathSuffix . $folder, 0777);
	        }
	    }
		return $this->_storageDir . $this->_pathSuffix . $folder . $this->_resourceId . $sizeCode . "_" . 
			substr(md5($this->_resourceId . $sizeCode . $this->_scrambleKey), 0, 15) . "." . $extension;
	}
	
	private function _createFile () {
		if (empty($_FILES['userfile'])) {
			return false;
		}
		$this->_file = (object)$_FILES['userfile'];
		$this->_file->title = $_GET['field8'] ?? 'No title';
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