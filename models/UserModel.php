<?php

namespace RsApi;

final class UserModel extends CommonModel {
	
	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	// Returns id of created user
	public function createUser ($userName) {
		$this->_prepare("insert into user(username) values (?)");
		return $this->_insert(['s' => [$userName]]);
	}
	
	public function createNewUserDash ($userId) {
		foreach ($this->_getUserDashes() as $tile) {
			 $this->_adduserTile($userId, $tile['tile'], $tile['order']);
		}
	}
	
	public function createCollection ($userId) {
		$this->_prepare("insert into collection (name,user,created,allow_changes,cant_delete,session_id) 
			values ('My Collection',?,now(),0,1,NULL)");
		$collectionId = $this->_insert(['i' => [$userId]]);
		if (!empty($collectionId)) {
			// Indexing a collection, as per original function doesn't seem necessary 
			// for a newly created collection, so this is skipped
			$this->_prepare("update user set current_collection=? where ref=?");
			$this->_update(['ii' => [$collectionId, $userId]]);
			return $collectionId;
		}
		return false;
	}
	
	public function saveUserData ($userName, $userId, $hashedUserPassword) {
		$groupId = $this->_getUserGroupId('General Users');
		$this->_prepare("
			update user set username=?, password=?, password_last_change=now(), fullname=?, email='', 
				usergroup=?, ip_restrict='', search_filter_override='', comments='', approved=1
			where ref=?");
		$this->_insert(['sssii' => [$userName, $hashedUserPassword, $userName, 
			$groupId, $userId]]);
	}

	private function _adduserTile ($userId, $tileId, $order) {
		$this->_prepare("INSERT IGNORE INTO user_dash_tile (user,dash_tile,order_by) VALUES (?,?,?)");
		return $this->_insert(['iii' => [$userId, $tileId, $order]]);
	}
	
	
}