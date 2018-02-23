<?php

namespace RsApi;

class CommonModel extends DatabaseModel {
	
	public function __construct ($config = false) {
		parent::__construct($config);
	}
	
	public function userLogin ($userName, $hashedPassword) {
		$this->_prepare("select ref,usergroup,account_expires from user where username=? and password=?");
		$this->_fetch(['ss' => [$userName, $hashedPassword]]);
		return $this->_res[0];
	}
	
	protected function _getGeneralUsersGroupId () {
		$res = $this->_mysqli()->query("select ref as value from usergroup where `name`='General Users'");
		$row = $res->fetch_array(MYSQLI_NUM);
		return $row[0];
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
	
	protected function _adduserTile ($userId, $tileId, $order) {
		$this->_prepare("INSERT IGNORE INTO user_dash_tile (user,dash_tile,order_by) VALUES (?,?,?)");
		return $this->_insert(['iii' => [$userId, $tileId, $order]]);
	}
	
	
}