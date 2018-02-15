<?php

namespace RsApi;

require_once 'DatabaseModel.php';

final class UserModel extends DatabaseModel {
	
	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	public function userExists ($userName) {
		$this->_prepare("select count(*) value from user where username = ?");
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
		$this->_prepare("
			update user set username=?, password=?, password_last_change=now(), fullname=?, email='', 
				usergroup=?, ip_restrict='', search_filter_override='', comments='', approved=1
			where ref=?");
		$this->_update(['sssii' => [$userName, $hashedUserPassword, $userName, 
			$this->_getGeneralUsersGroupId(), $userId]]);
	}
	
	private function _getGeneralUsersGroupId () {
		$res = $this->_mysqli()->query("select ref as value from usergroup where `name`='General Users'");
		$row = $res->fetch_array(MYSQLI_NUM);
		return $row[0];
	}
	
	private function _getUserDashes () {
		$res = $this->_mysqli()->query("
			SELECT dash_tile.ref as 'tile',dash_tile.title,dash_tile.url,
				dash_tile.reload_interval_secs,dash_tile.link,dash_tile.default_order_by as 'order' 
			FROM dash_tile WHERE dash_tile.all_users = 1 AND ref NOT IN 
				(SELECT dash_tile FROM usergroup_dash_tile) AND (dash_tile.allow_delete=1 OR 
					(dash_tile.allow_delete=0 AND dash_tile.ref IN 
					(SELECT DISTINCT user_dash_tile.dash_tile FROM user_dash_tile))) 
			ORDER BY default_order_by");
		return $res ? $res->fetch_all(MYSQLI_ASSOC) : null;
	}
	
	private function _adduserTile ($userId, $tileId, $order) {
		$this->_prepare("INSERT IGNORE INTO user_dash_tile (user,dash_tile,order_by) VALUES (?,?,?)");
		return $this->_insert(['iii' => [$userId, $tileId, $order]]);
	}
	
	
}