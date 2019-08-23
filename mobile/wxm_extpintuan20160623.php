<?php
// PRINCE QQ 120029121

function send_order_message($order_id){
	$access_token = access_token($db);
	$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
	
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('extpintuan_orders') . " WHERE order_id = '$order_id' ";
	$extpintuan = $GLOBALS['db']->getRow($sql);
	$pt_id=$extpintuan['pt_id'];
	$act_user=$extpintuan['act_user'];
	$follow_user=$extpintuan['follow_user'];
	
	$query_sql_1 = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$act_user'";
	$ret_w_1 = $GLOBALS['db']->getRow($query_sql_1);
	$act_wxid = $ret_w_1['fake_id'];
		
	$query_sql_2 = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$follow_user'";
	$ret_w_2 = $GLOBALS['db']->getRow($query_sql_2);
	$follow_wxid = $ret_w_2['fake_id'];
	
	$wap_url = $GLOBALS['db'] -> getOne("SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1");
	$w1_url = $wap_url.'extpintuan.php?act=pt_view&pt_id='.$pt_id.'&u='.$act_user;
	$w2_url = $wap_url.'extpintuan.php?act=pt_view&pt_id='.$pt_id.'&u='.$follow_user;
	$w_picurl = $wap_url."images/weixin/wxch_pt.jpg";
	$w_description="快快点击进入分享给朋友们参团吧";

	if($act_user==$follow_user){
		$w_title="成功发起拼团";
	}else{
		$w_title="成功参与拼团";
	}
	

	$post_msg = '{
			   "touser":"'.$follow_wxid.'",
			   "msgtype":"news",
			   "news":{
				   "articles": [
					{
						"title":"'.$w_title.'",
						"description":"'.$w_description.'",
						"url":"'.$w2_url.'",
						"picurl":"'.$w_picurl.'"
					}
					]
			   }
		    }';
	$ret_json = pt_curl_grab_page($url, $post_msg);
	$ret = json_decode($ret_json);
	if($ret->errmsg != 'ok' || empty($ret->errmsg)) 
	{
		$access_token = new_access_token($db); 
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
		$ret_json = pt_curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
	}	
			
	if($act_user!=$follow_user){
				$w_title="有新朋友参加您的拼团啦";
				$w_description="快快点击进入分享更多朋友参团吧";
				$post_msg = '{
				   "touser":"'.$act_wxid.'",
				   "msgtype":"news",
				   "news":{
					   "articles": [
						{
							"title":"'.$w_title.'",
							"description":"'.$w_description.'",
							"url":"'.$w1_url.'",
							"picurl":"'.$w_picurl.'"
						}
						]
				   }
				}';
				$ret_json = pt_curl_grab_page($url, $post_msg);
				$ret = json_decode($ret_json);
				if($ret->errmsg != 'ok' || empty($ret->errmsg)) 
				{
					$access_token = new_access_token($db); 
					$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
					$ret_json = pt_curl_grab_page($url, $post_msg);
					$ret = json_decode($ret_json);
				}	
	}
	
}



function send_status_message($user_id,$order_id,$order_sn,$pt_id,$status){
	$access_token = access_token($db);
	$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
	$query_sql = "SELECT * FROM `ecs_weixin_user` WHERE ecuid = '$user_id'";
	$ret_w = $GLOBALS['db']->getRow($query_sql);
	$fake_id = $ret_w['fake_id'];

    if($fake_id ){	
		$wap_url = $GLOBALS['db'] -> getOne("SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1");
	
		if($status==1){
			$w_title = '拼团成功,我们将尽快为您发货!';
		}elseif($status==3){
			$w_title = '拼团成功,请等待抽奖!';
		}
		else{
			$w_title = '拼团失败,我们将尽快为您退款!';
		}
	
	
		$w_description = '订单号：'.$order_sn;
		$w_url = $wap_url.'extpintuan.php?act=pt_view&pt_id='.$pt_id.'&u='.$user_id;
		$w_picurl = $wap_url."images/weixin/wxch_pt.jpg";
	
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
		$ret_json = pt_curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
		if($ret->errmsg != 'ok' || empty($ret->errmsg)) 
		{
			$access_token = new_access_token($db); 
			$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
			$ret_json = pt_curl_grab_page($url, $post_msg);
			$ret = json_decode($ret_json);
		}	
	}
}




function send_lucky_message($user_id,$order_id,$order_sn,$pt_id,$lucky_order){
	$access_token = access_token($db);
	$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
	$query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$user_id'";
	$ret_w = $GLOBALS['db']->getRow($query_sql);
	$fake_id = $ret_w['fake_id'];
	
	if($fake_id){	
		$wap_url = $GLOBALS['db'] -> getOne("SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1");
	
		if($lucky_order==1){
			$w_title = '拼团中奖啦,我们将尽快为您发货!';
		}else{
			$w_title = '拼团未中奖,我们将尽快为您退款!';
		}
	
	
		$w_description = '订单号：'.$order_sn;
		$w_url = $wap_url.'extpintuan.php?act=pt_view&pt_id='.$pt_id.'&u='.$user_id;
		$w_picurl = $wap_url."images/weixin/wxch_pt.jpg";
	
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
		$ret_json = pt_curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
		if($ret->errmsg != 'ok' || empty($ret->errmsg)) 
		{
			$access_token = new_access_token($db); 
			$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
			$ret_json = pt_curl_grab_page($url, $post_msg);
			$ret = json_decode($ret_json);
		}
	}
}

function new_access_token($db) 
{
	//return access_token($db);
	$ret = $GLOBALS['db']->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$access_token = $ret['access_token'];
	$dateline = $ret['expire_in'];
	$time = time();
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
	$ret_json = pt_curl_get_contents($url);
	$ret = json_decode($ret_json);
	if($ret->access_token){
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
	}
	
}
function access_token($db) 
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
		$ret_json = pt_curl_get_contents($url);
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
		$ret_json = pt_curl_get_contents($url);
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
function pt_curl_get_contents($url) 
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
function pt_curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') 
{
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

//ALTER TABLE `ecs_weixin_config` CHANGE `access_token` `access_token` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL 
// PRINCE QQ 120029121
?>