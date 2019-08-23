<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');

$hashid = $_GET['user_id'];//user_id=vVzq5D
$rest = file_get_contents(OSS_LOGIN."/rest/api/sso/getuserinfo?hashid=" . $hashid);
$info = json_decode($rest, true);

if(!isset($info['id'])) return;
$sql = "SELECT user_id FROM " .$GLOBALS['ecs']->table('users') ." WHERE portal_id = '" . $info['id'] . "'";
$user_id = $GLOBALS['db']->getOne($sql);
if(isset($user_id) && !empty($user_id)){
	if(!isset($info['openid']) || empty($info['openid'])){
		$info['openid'] = 'register'.$info['id'];
	}

	$info_user_id = 'weixin' . '_' . $info['openid'];
	//更新openid和头像
	$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_user') . " SET fake_id = '" . $info['openid'] . "',headimgurl='".$info['avatar']."' WHERE ecuid = '" . $user_id . "'");

	$rsync_idcard = $_GET['rsync_idcard'];

	if ($rsync_idcard == 1) {
        $birthday = date("Y-m-d", strtotime($info['birthday']));
        $setSql = ",real_name='" . $info['name'] . "',card='" . $info['code'] . "',face_card='" . $info['id_card_front_img'] . "',back_card='" . $info['id_card_back_img'] . "',issue='" . $info['issue'] . "',issue_address='" . $info['address'] . "',sex=" . $info['sex'] . ",birthday='" . $birthday . "'";
	} else {
        $setSql = '';
	}

	$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('users') . " SET aite_id = '" . $info_user_id . "',headimg='".$info['avatar']."'" . $setSql . " WHERE user_id = '" . $user_id . "'");

	$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $user_id . "'");
	$GLOBALS['user']->set_session($username);
	$GLOBALS['user']->set_cookie($username,1);
	update_user_info();  //更新用户信息
	recalculate_price(); //重新计算购物车中的商品价格

}else{
	if(!isset($info['openid']) || empty($info['openid'])){
		$info['openid'] = 'register'.$info['id'];
	}	


		$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$info['openid']}'");
		
		if($rows)
		{
			if($rows['ecuid'] > 0)
			{
				$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
				$GLOBALS['user']->set_session($username);
				$GLOBALS['user']->set_cookie($username,1);
				update_user_info();  //更新用户信息
				recalculate_price(); //重新计算购物车中的商品价格
				
				if(ODOO_ERP){
					$odooErpObj = OdooErp::getInstance();
					//edit yhy 同步erp中的会员
					$res = $odooErpObj->syncUserByUserid($_SESSION['user_id']);	
				}
				exit; 
			}
		}else{
			$createtime = gmtime();
			$createymd = date('Y-m-d',gmtime());
			$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`,`headimgurl`,`nickname`) 
				value (0,'" . $info['openid'] . "','{$createtime}','{$createymd}',0,'". $info['avatar'] ."','" . $info['nickname'] ."')"); 
		}
		
		$info_user_id = 'weixin' . '_' . $info['openid'];

		if($info['name']){
			$info['name'] = str_replace("'", "", $info['name']);
			// 过滤掉emoji表情
			$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);

			if($GLOBALS['user']->check_user($info['name'])) // 重名处理
			{
				$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
			}
		}else{
			$info['name'] = 'weixin_' . rand(10000, 99999); 
		}
		
		$sql = 'SELECT user_name,password,aite_id FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\' OR aite_id=\'' . $info['openid'] . '\'';
		$user_info = $GLOBALS['db']->getRow($sql);
		if($user_info){
			$info['name'] = $user_info['user_name'];
			if($user_info['aite_id'] == $info['openid']){
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$user_info[aite_id]'";
				$GLOBALS['db']->query($sql);
				$tag = 2;
			}
			
		}else{
			$user_pass = $GLOBALS['user']->compile_password(array(
				'password' => $info['openid']
			));
			$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time, is_validated, froms, headimg, portal_id, real_name) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[avatar]', '$info[id]', '$info[nickname]')";
			$GLOBALS['db']->query($sql);
			$tag = 1; //第一次注册标记
		}

		$GLOBALS['user']->set_session($info['name']);
		$GLOBALS['user']->set_cookie($info['name']);
		update_user_info();
		recalculate_price();
		//修改新注册的用户成为普通分销商
		$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET is_fenxiao = 2 WHERE user_id = '" . $_SESSION['user_id'] . "'");

		//微信和新生成会员绑定
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_user') . " SET ecuid = '" . $_SESSION['user_id'] . "' WHERE fake_id = '" . $info['openid'] . "'");
		
		if($info['parent_id'] != 0){
			$sql = "SELECT user_id FROM " . 
					$GLOBALS['ecs']->table('users') . 
					" WHERE portal_id = '" . $info['parent_id'] . "'";
			$parent_id = $GLOBALS['db']->getOne($sql);
			if($parent_id != 0)
			{
				//扫描分销商二维码，绑定上级分销商
				$GLOBALS['db']->query("UPDATE " . 
						$GLOBALS['ecs']->table('users') . 
						" SET parent_id = '" . $parent_id . "'" .
						" WHERE user_id = '" . $_SESSION['user_id'] . "'");
			}
		}
	
	if(ODOO_ERP){
		$odooErpObj = OdooErp::getInstance();
		//edit yhy 同步erp中的会员
		$res = $odooErpObj->syncUserByUserid($_SESSION['user_id']);	
	}
}
?>
