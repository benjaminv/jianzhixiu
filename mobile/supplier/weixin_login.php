<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require('../weixin/wechat.class.php');


  
  $weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );

  $weixin = new core_lib_wechat($weixinconfig);

 
  
  if($_GET['code']){

  $json = $weixin->getOauthAccessToken();

    if($json['openid']){
		$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$json['openid']}'");
 
        if($rows)
		{
			if($rows['ecuid'] > 0)
			{
				$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");


             $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,supplier_id,ec_salt".
            " FROM " . $ecs->table('supplier_admin_user') .
            " WHERE user_name = '$username'" ;
            
			  $row = $db->getRow($sql);



     if ($row)
    {
        // 登录成功
    	$_SESSION['supplier_id'] = $row['supplier_id'];//店铺的id
		$_SESSION['supplier_user_id'] = $row['user_id'];//管理员id
		$_SESSION['supplier_name']  = $row['user_name'];//管理员名称
		$_SESSION['supplier_action_list'] = $row['action_list'];//管理员权限
    	$_SESSION['supplier_last_check']  = $row['last_login']; // 用于保存最后一次检查订单的时间
        set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_login']);
		

        if($row['action_list'] == 'all')
        {
        	$_SESSION['supplier_admin_id'] = $row['user_id'];//超级管理员的标识管理员id
            $_SESSION['supplier_shop_guide'] = true;//超级管理员标识
        }

        // 更新最后登录时间和IP
        $db->query("UPDATE " .$ecs->table('supplier_admin_user').
                 " SET last_login='" . gmtime() . "', last_ip='" . real_ip() . "'".
                 " WHERE user_id='$_SESSION[supplier_user_id]'");

        if (isset($_REQUEST['remember']))
        {
            $time = gmtime() + 3600 * 24 * 365;
            setcookie('ECSCP[supplier_id]',   $row['supplier_id'],                            $time);
			setcookie('ECSCP[supplier_user_id]',   $row['user_id'],                            $time);
            setcookie('ECSCP[supplier_pass]', md5($md5_password.$_CFG['hash_code']), $time);
        }

        ecs_header("Location: ./index.php\n");
        exit;
    }




	}



		}
	}

  }


  


    $url = $GLOBALS['ecs']->url()."weixin_login.php";
    $url = $weixin->getOauthRedirect($url,1,'snsapi_userinfo');
    header("Location:$url");exit;





?>