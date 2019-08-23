<?php
$time = time();

$user_id = $act_user;
$cut_id = $cut_id;

if($user_id > 0) {

	$access_token = cut_access_token($db);
	$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
	$query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$user_id'";
	$ret_w = $GLOBALS['db']->getRow($query_sql);
	$fake_id = $ret_w['fake_id'];
	$wap_url = $GLOBALS['db'] -> getOne("SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1");


	$w_title="有新朋友帮您砍价了，赶紧看看吧";
	$w_description="当前砍后价格为：".$after_cut_price."\n分享给更多朋友可砍获更低价格";
	$w_url = $wap_url.'cut.php?act=cut_view&cut_id='.$cut_id.'&u='.$user_id;
	$w_picurl = $wap_url."images/weixin/wxch_cut.jpg";


	$post_msg = '{
       "touser":"'.$fake_id.'",
       "msgtype":"news",
       "news":{
           "articles": [
            {
                "title":"'.$w_title.'",
                "description":"'.$w_description.'",
                "url":"'.$w_url.'",
                "picurl":"'.$w_picurl.'"
            }
            ]
       }
   }';
	$ret_json = cut_curl_grab_page($url, $post_msg);
	$ret = json_decode($ret_json);
	if($ret->errmsg != 'ok' || empty($ret->errmsg)) {
		$access_token = new_cut_access_token($db);
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
		$ret_json = cut_curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
	}
	

	
	
}
function new_cut_access_token($db) 
{
	$ret = $GLOBALS['db']->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$access_token = $ret['access_token'];
	$dateline = $ret['expire_in'];
	$time = time();
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
	$ret_json = cut_curl_get_contents($url);
	$ret = json_decode($ret_json);
	if($ret->access_token){
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
	}
	
}
function cut_access_token($db) {
	$ret = $GLOBALS['db']->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$access_token = $ret['access_token'];
	$dateline = $ret['expire_in'];
	$time = time();

	if(($time - $dateline) >= 7200) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$ret_json = cut_curl_get_contents($url);
		$ret = json_decode($ret_json);
		if($ret->access_token)
		{
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
		}
	}
	elseif(empty($access_token)) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$ret_json = cut_curl_get_contents($url);
		$ret = json_decode($ret_json);
		if($ret->access_token)
		{
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
		}
	}
	else {
		return $access_token;
	}
}
function cut_curl_get_contents($url) {
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
function cut_curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($proxystatus == 'true') 
	{
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	if(!empty($ref_url))
	{
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_REFERER, $ref_url);
	}
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	ob_start();
	return curl_exec ($ch);
	ob_end_clean();
	curl_close ($ch);
	unset($ch);
}
?>