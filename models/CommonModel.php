<?php

namespace RsApi;

class CommonModel extends DatabaseModel {
	
	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	public function userLogin ($userName, $hashedPassword, $isAdmin = false) {
		$groupId = $isAdmin ? $this->_getUserGroupId('Super Admin') : 
			$this->_getUserGroupId('General Users');
		
		$this->_prepare("select ref from user where username=? and password=? and usergroup>=?");
		$this->_fetch(['ssi' => [$userName, $hashedPassword, $groupId]]);
		
		if (!empty($this->_res)) {
			$this->_prepare("update user set last_active=now() where ref=?");
			$this->_update(['i' => [$this->_res]]);
			return $this->_res;
		}
		return false;
	}

	public function userExists ($userName) {
		$this->_prepare("select count(*) from user where username = ?");
		$this->_fetch(['s' => [$userName]]);
		return $this->_res == 1;
	}
	
	public function collectionExists ($collectionId) {
		$this->_prepare("select name from collection where ref=?");
		$this->_fetch(['i' => [$collectionId]]);
		return !empty($this->_res);
	}
	
	protected function _getUserGroupId ($groupName = false) {
		$this->_prepare("select ref from usergroup where `name`=?");
		$this->_fetch(['s' => [$groupName]]);
		return $this->_res;
	}
	
	protected function _getUserDashes () {
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
	
	
}