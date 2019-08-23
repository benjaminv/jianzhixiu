<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');

// $weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );


$hashid = $_GET['user_id'];//user_id=6ZgADP
$rest = file_get_contents(OSS_LOGIN."/rest/api/sso/getuserinfo?hashid=" . $hashid);
$info = json_decode($rest, true);

if(!isset($info['id'])) return;
		$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$info['openid']}'");
		if($rows)
		{
			if($rows['ecuid'] > 0)
			{

//TODO: 同步用户名，昵称，头像等

				$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
				$GLOBALS['user']->set_session($username);
				$GLOBALS['user']->set_cookie($username,1);
				update_user_info();  //更新用户信息
				recalculate_price(); //重新计算购物车中的商品价格
				exit; 
			}
		}
		else
		{
			$createtime = gmtime();
			$createymd = date('Y-m-d',gmtime());
			$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`) 
				value (0,'" . $info['openid'] . "','{$createtime}','{$createymd}',0)"); 
		}
		
		$info_user_id = 'weixin' . '_' . $info['openid'];

		if($info['nickname'])
		{
			$info['name'] = str_replace("'", "", $info['name']);
			
			// 过滤掉emoji表情
			$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);

			if($GLOBALS['user']->check_user($info['name'])) // 重名处理
			{
				$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
			}
		}
		else
		{
			$info['name'] = 'weixin_' . rand(10000, 99999); 
		}

		
		$sql = 'SELECT user_name,password,aite_id FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\' OR aite_id=\'' . $info['openid'] . '\'';
		$user_info = $GLOBALS['db']->getRow($sql);
		if($user_info)
		{
			$info['name'] = $user_info['user_name'];
			if($user_info['aite_id'] == $info['openid'])
			{
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$user_info[aite_id]'";
				$GLOBALS['db']->query($sql);
				$tag = 2;
			}
		}
		else
		{
			$user_pass = $GLOBALS['user']->compile_password(array(
				'password' => $info['openid']
			));
//var_dump($user_pass);

			$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time , is_validated,froms,headimg) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[avatar]')";

			// echo $sql;
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
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_user') . " SET ecuid = '" . $_SESSION['user_id'] . "',nickname = '" . $info['nickname'] . "',`sex` = '" . $info['sex'] . "' WHERE fake_id = '" . $info['openid'] . "'");
		if($tag == 1) //第一次注册绑定上级分销商
		{
			// $sql = "SELECT parent_id FROM " . 
			// 		$GLOBALS['ecs']->table('bind_record') . 
			// 		" WHERE wxid = '" . $info['openid'] . "'";
			$parent_id =$info['parent_id'];
			if($parent_id != 0)
			{
				//扫描分销商二维码，绑定上级分销商
				$GLOBALS['db']->query("UPDATE " . 
						$GLOBALS['ecs']->table('users') . 
						" SET parent_id = '$parent_id'" .
						" WHERE user_id = '" . $_SESSION['user_id'] . "'");
				// $GLOBALS['db']->query("DELETE FROM " . 
				// 		$GLOBALS['ecs']->table('bind_record') . 
				// 		" WHERE wxid = '" . $info['openid'] . "'");
			}
		}
?>
