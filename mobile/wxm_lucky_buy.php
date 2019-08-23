<?php
// PRINCE QQ 120029121
$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('weixin_remind'). " WHERE id = 1";
$ret_re = $GLOBALS['db']->getRow($sql);
	
if($lucky_buy_id && $ret_re['lucky_buy_lucky_user_notice']==1){
	$access_token = lb_access_token($db);
	$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
	
	$sql = "SELECT * FROM  " . $GLOBALS['ecs']->table('lucky_buy') . " l ".
							"left join  " . $GLOBALS['ecs']->table('goods_activity') . "  g on l.act_id=g.act_id ".
							"WHERE  l.lucky_buy_id=".$lucky_buy_id;
	$lucky_buy_info =$GLOBALS['db']->getRow($sql);
	$schedule_id=$lucky_buy_info['schedule_id'];
	$time=date("Y-m-d",time());
	$goods_name=$lucky_buy_info['goods_name'];
	$lucky_user_id=$lucky_buy_info['lucky_user_id'];

	$sql= "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$lucky_user_id'";
	$ret = $GLOBALS['db']->getRow($sql);
	$wxid = $ret['fake_id'];
		

	
	$wap_url = $GLOBALS['db'] -> getOne("SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1");
	$w_url = $wap_url.'lucky_buy.php?act=schedule_view&lucky_buy_id='.$lucky_buy_id;

	$w_title="恭喜您获得云购奖品";
	$remark="我们将会为您尽快寄送奖品";
	
		$post_msg = '{
		   "touser":"'.$wxid.'",
		   "template_id":"'.$ret_re['lucky_buy_lucky_user_msg'].'",
		   "url":"'.$w_url.'",
		   "topcolor":"#FF0000",
			   "data":{
					   "first": {
						   "value":"'.$w_title.'",
						   "color":"#0000FF"
					   },
					   "keyword1":{
						   "value":"'.$schedule_id.'",
						   "color":"#0000FF"
					   },
					   "keyword2": {
						   "value":"'.$goods_name.'",
						   "color":"#0000FF"
					   },
					   "keyword3": {
						   "value":"'.$time.'",
						   "color":"#FF0000"
					   },
					   "remark":{
						   "value":"'.$remark.'",
						   "color":"#0000FF"
					   }
			   }
		 }';

	$ret_json = lb_curl_grab_page($url, $post_msg);
	$ret = json_decode($ret_json);
	if($ret->errmsg != 'ok' || empty($ret->errmsg)) {
		$access_token = lb_new_access_token($db); 
		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
		$ret_json = lb_curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
	}


	
}




function lb_new_access_token($db) 
{
	$ret = $GLOBALS['db']->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$access_token = $ret['access_token'];
	$dateline = $ret['expire_in'];
	$time = time();
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
	$ret_json = lb_curl_get_contents($url);
	$ret = json_decode($ret_json);
	if($ret->access_token){
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
	}
	
}
function lb_access_token($db) 
{

	

	$ret = $GLOBALS['db']->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$access_token = $ret['access_token'];
	$dateline = $ret['expire_in'];
	$time = time();

	if(($time - $dateline) >= 7200) 
	{
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$ret_json = lb_curl_get_contents($url);
		$ret = json_decode($ret_json);
		if($ret->access_token)
		{
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
		}
	}
	elseif(empty($access_token)) 
	{
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$ret_json = lb_curl_get_contents($url);
		$ret = json_decode($ret_json);
		if($ret->access_token)
		{
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
		}
	}
	else 
	{
		return $access_token;
	}
}
function lb_curl_get_contents($url) 
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$r = curl_exec($ch);
	curl_close($ch);
	return $r;
}
function lb_curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') 
{    
    $header = array('Expect:');  
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($proxystatus == 'true') {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	if(!empty($ref_url)){
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_REFERER, $ref_url);
	}
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	ob_start();
	return curl_exec ($ch);
	ob_end_clean();
	curl_close ($ch);
	unset($ch);
}

//ALTER TABLE `ecs_weixin_config` CHANGE `access_token` `access_token` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
// PRINCE QQ 120029121
?>