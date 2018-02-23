<?php

namespace RsApi;

final class UserModel extends CommonModel {
	
	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	public function userExists ($userName) {
		$this->_prepare("select count(*) from user where username = ?");
		$this->_fetch(['s' => [$userName]]);
		return $this->_res == 1;
	}
	
	public function createUser ($userName) {
		$this->_prepare("insert into user(username) values (?)");
		$this->_userId = $this->_insert(['s' => [$userName]]);
		return $this->_userId;
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
		$groupId = $this->_getGeneralUsersGroupId();
		$this->_prepare("
			update user set username=?, password=?, password_last_change=now(), fullname=?, email='', 
				usergroup=?, ip_restrict='', search_filter_override='', comments='', approved=1
			where ref=?");
		$this->_insert(['sssii' => [$userName, $hashedUserPassword, $userName, 
			$groupId, $userId]]);
	}
		
}