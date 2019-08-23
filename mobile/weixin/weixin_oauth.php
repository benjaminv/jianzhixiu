<?php



$code = !empty($_GET['code']) ? $_GET['code'] : '';

$up_uid = trim($_REQUEST['u'])?trim($_REQUEST['u']):$_GET['u'] ;

/*if(strpos($_SERVER['REQUEST_URI'],"u%3D")){
	echo 1;
	$chk_REQUEST_URI_01=strpos($_SERVER['REQUEST_URI'],"u%3D");
	echo $chk_REQUEST_URI_01[1];
	$chk_REQUEST_URI_02=strpos($chk_REQUEST_URI_01[1],"%26from");
    echo $chk_REQUEST_URI_02[0];
}else{
	echo 2;
}*/


$is_wechat_browser=oauth_is_wechat_browser();


if(1){ 
		$testurl=$_SESSION['user_id'].'-'.$_SESSION['user_name'].'-'.$_COOKIE["openid"].'-'.$code.' http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $output= strftime("%Y%m%d %H:%M:%S", time()) . "\n" ;
        $output .= $testurl."\n" ;
        $output.="\n";
        $log_path=ROOT_PATH . "/data/log/";
        if(!is_dir($log_path)){
            @mkdir($log_path, 0777, true);
        }
        $output_date= strftime("%Y%m%d", time());
        file_put_contents($log_path.$output_date."_oauth.txt", $output, FILE_APPEND | LOCK_EX);
}

//$cookie_openid=1;
/*if(empty($_SESSION['user_id']) && !empty($code) && $is_wechat_browser){
    if(isset($_COOKIE["openid"]) && !empty($_COOKIE["openid"])){
		$cookie_openid=$_COOKIE["openid"];
	}else{
		$cookie_openid=0;
	}
}*/
//echo $_COOKIE["openid"].'-'.$code;
//setcookie("openid",'' ,-1);
//echo $_COOKIE["openid"];
//if(empty($_COOKIE["openid"])) echo 'yes';exit;
 $db = $GLOBALS['db'];

if(empty($_SESSION['user_id'])&& (empty($code) )&& $is_wechat_browser){
	$appid = $db -> getOne("SELECT appid FROM `ecs_weixin_config` WHERE `id` = 1");
	$backurl='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	
	
	$redirect_uri = urlencode($backurl);
	$state = 1;
	$scope = 'snsapi_userinfo';
	$oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
	header("Location: $oauth_url");
	exit;
} 







if (empty($_SESSION['user_id'])){
    if(!empty($code)){
		$wxch_config = $db->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
		$appid = $wxch_config['appid'];
		$appsecret = $wxch_config['appsecret'];
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';
	
		
		$ret_json = wx_curl_get_contents($url);
		$ret = json_decode($ret_json);
		$openid = $ret->openid;
		
		
        $output .= 'code:'.$code.' openid:'.$openid.' setcookie:'.$_COOKIE["openid"]."\n" ;
        $log_path=ROOT_PATH . "/data/log/";
        file_put_contents($log_path."first.txt", $output, FILE_APPEND | LOCK_EX);
		
        setcookie("openid",$openid ,time()+3600*24*7);
		if(empty($_COOKIE["openid"])){
			$backurl='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$redirect_uri = urlencode($backurl);
			$state = 1;
			$scope = 'snsapi_userinfo';
			$oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
			header("Location: $oauth_url");
			exit;
		}
		

		$access_token = !empty($ret->access_token) ? $ret->access_token : '';
		$url='https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid;
		$ret_json = wx_curl_get_contents($url);
		$ret = json_decode($ret_json);
		$fromUsername=$ret->openid;
		$nickname=$ret->nickname;
		$headimgurl=$ret->headimgurl;
		$openid=$fromUsername;
		$ret = $db -> getRow("SELECT `fake_id` FROM `ecs_weixin_user` WHERE `fake_id` = '$fromUsername'");
		$createtime = time();
		$createymd = date('Y-m-d');;
	   // $expire_in = time()+48*3600;
		if (empty($ret)) {
			if (!empty($fromUsername)) {
				$sql = "insert into ".$GLOBALS['ecs']->table('weixin_user')." 				(`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`,`nickname`,`access_token`,`expire_in`,`headimgurl`,`from_id`) 
					value (0,'{$fromUsername}','{$createtime}','{$createymd}',0,'$nickname','','0','$headimgurl',0)";
					$GLOBALS['db']->query($sql);//注册粉丝
			} 
		}
	}



    $wx_id = "weixin_".$fromUsername;
	$ec_name_sql = "SELECT `user_name` FROM `ecs_users` WHERE `aite_id` = '$wx_id'";
	$ec_name = $db -> getOne($ec_name_sql);
	$ec_wxid_sql = "SELECT `aite_id` FROM `ecs_users` WHERE `aite_id` = '$wx_id'";
	$ec_wxid = $db -> getOne($ec_wxid_sql);

	if (empty($ec_wxid)) {
		if (empty($ec_name)) {
		    $data = $db->getRow("SELECT * FROM `ecs_weixin_autoreg` WHERE `autoreg_id` = 1");//获取微信自动注册配置
            $username = $username ? $username :"wx_".date('md').mt_rand(1, 99999);
			$email =$username.'@163.com';//	默认分配@163邮箱
			$userpwd = $data['userpwd'];//密码前缀
			$autoreg_rand = $data['autoreg_rand'];//随机密码长度
			$s_mima = randomkeys($autoreg_rand);
		    $pwd = $userpwd.$s_mima;
		    $ec_pwd = md5($pwd);
			$time=time();
			$ec_user_sql = "INSERT INTO `ecs_users` ( `user_name`,`password`,`aite_id`,`email`,`user_rank`,`passwd_weixin`,`reg_time`,`is_fenxiao`,`parent_id`,`headimg`,`froms`) VALUES ('$username','$ec_pwd','$wx_id','$email','0','$pwd','$time','1','$up_uid','$headimgurl','mobile')";
			$db -> query($ec_user_sql);
			$ecs_user_id = $db -> insert_id();
		    $ec_name = $data['autoreg_name'].$ecs_user_id;
			if ($data['open_email'] == 1) {
			$email =$ec_name.$data['email'];
			}else{
			$email = '';
			}
			$db->query("UPDATE `ecs_users` SET `email`='$email' ,`user_name`='$ec_name' WHERE `user_id`= '$ecs_user_id'");//更新会员资料
			$db->query("UPDATE `ecs_weixin_user` SET `ecuid`='$ecs_user_id' ,`ec_name`='$ec_name' WHERE `fake_id`= '$fromUsername'");//注册后默认绑定

			/********分享链接上级通知*******/

		    $parent_id_sql = "SELECT `parent_id` FROM `ecs_users` WHERE `aite_id` = '$wx_id'";
            $parent_id = $db -> getOne($parent_id_sql);//上级uid
			$my_name_sql = "SELECT `nickname` FROM `ecs_weixin_user` WHERE `fake_id` = '$fromUsername'";
            $my_name = $db -> getOne($my_name_sql);//登录会员微信昵称
			$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : "/mobile/";
            $autoUrl = str_replace($_SERVER['REQUEST_URI'],"",$GLOBALS['ecs']->url());
            @file_get_contents($autoUrl."weixin/weixin_remind.php?notice=5&up_uid=".$parent_id."&my_name=".$my_name);
		  }	
	}
}







if(!empty($openid) && strlen($openid) == 28){		
		$w_res = $db->getRow("SELECT * FROM  `ecs_users` WHERE  `aite_id` = '$wx_id'");
		$_SESSION['wxid'] = $openid;	
		if ($user->login($w_res['user_name'], null, true)) {
			update_user_info();
			recalculate_price();
		}
}

function wx_curl_get_contents($url) 
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10); 
	$dxycontent = curl_exec($ch); 
	return $dxycontent;
}

/* 检查是否是微信浏览器访问 */
function oauth_is_wechat_browser(){
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'MicroMessenger') === false){
      return false;
    } else {
      return true;
    }
}

function randomkeys($length)//随机密码
	{
		$pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
		for($i=0;$i<$length;$i++)
		{
			$key .= $pattern{mt_rand(0,35)};    //生成php随机数
		}
		return $key;
	}	
?>



