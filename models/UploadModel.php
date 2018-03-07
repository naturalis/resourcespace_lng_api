<?php

namespace RsApi;

final class UploadModel extends CommonModel {
	
	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	// Returns id of created resource
	public function createResource ($apiUserId = false) {
		$this->_prepare("insert into resource(resource_type,creation_date,archive,created_by) 
			values (1, now(), 0, ?)");
		return $this->_insert(['i' => [$apiUserId]]);
	}
	
	public function updateResource ($resourceId, $fileName, $title, $extension) {
		$this->_prepare("update resource set file_extension=?,preview_extension='jpg',
			file_modified=now(),has_image=0,field8=?,field51=?,preview_tweaks='0|1' where ref=?");
		return $this->_update(['sssi' => [$extension, $title, $fileName, $resourceId]]);
	}
	
	public function addResourceToCollection ($resourceId, $collectionId) {
		$this->_prepare("insert into collection_resource(resource,collection) values (?,?)");
		return $this->_insert(['ii' => [$resourceId, $collectionId]]);
	}
	
	public function getPreviewSizes () {
		$res = $this->_mysqli()->query("select * from preview_size 
			order by width desc, height desc");
		return $res ? $res->fetch_all(MYSQLI_ASSOC) : null;
	}
	
}