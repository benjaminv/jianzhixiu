<?php
//echo $_GET['echostr'];exit;
require(dirname(__FILE__) . '/api.class.php');
require(dirname(__FILE__) . '/wechat.class.php');
$weixinconfig = $db->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
//多微信帐号支持
$id = intval($_GET['id']);
if($id > 0){
	$otherconfig = $db->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = $id" );
	if($otherconfig){
		$weixinconfig['token'] = $otherconfig['token'];
		$weixinconfig['appid'] = $otherconfig['appid'];
		$weixinconfig['appsecret'] = $otherconfig['appsecret'];
	}
}
$baseurl = $weburl = $_SERVER['SERVER_NAME'] ? "http://".$_SERVER['SERVER_NAME']."/" : "http://".$_SERVER['HTTP_HOST']."/";
//$weburl .= $weixinconfig['wap_url'] ? $weixinconfig['wap_url'] : "";
$weixin = new core_lib_wechat($weixinconfig);
$weixin->valid();
$api = new weixinapi();
$weburl .= $api->dir;
$type = $weixin->getRev()->getRevType();
$wxid = $weixin->getRev()->getRevFrom();
$data = $weixin->getRevData();
//上报地理信息
 $loc = $weixin->getRev()->getUserLocation();
 if($loc){
 	$api->updatelocation($wxid, $loc);
 }
$reMsg = "";
switch($type) {
	case 'text':
		$content = $weixin->getRev()->getRevContent();
		break;
	case 'event':
		$event = $weixin->getRev()->getRevEvent();
		$content =  json_encode($event);
		break;
	case 'image':
		$content = json_encode($weixin->getRev()->getRevPic());
		$reMsg = "图片很美！";
		break;
	case 'location':
		$content = json_encode($weixin->getRev()->getRevGeo());
		$reMsg = "您所在的位置很安全！";
		break;
	default:
		$reMsg = $weixinconfig['help'];
}
$api->saveMsg($content,$wxid,$type);
if($reMsg){
	echo $weixin->text($reMsg)->reply();exit;
}
$followInfo = $api->getFollowUserInfo($wxid);
if(!$followInfo or $followInfo['expire_in']-86400<time()){
	$info = $weixin->getUserInfo($wxid);
	if($info) $api->followUser($wxid,$info);
}
	
if ($event['event'] == "subscribe") { //用户关注
	if(intval($followInfo['ecuid']) == 0 && $weixinconfig['reg_type'] == 1){
	    $autoreg = $db->getRow("SELECT * FROM `ecs_weixin_autoreg` WHERE `autoreg_id` = 1");//获取微信自动注册配置
		$username = $username ? $username :"wx_".date('md').mt_rand(1, 99999);
		$userpwd = $autoreg['userpwd'];//密码前缀
		$autoreg_rand = $autoreg['autoreg_rand'];//随机密码长度
		$s_mima = random_pwdkeys($autoreg_rand);
		$pwd = $userpwd.$s_mima;
		$rs = $api->bindUser($wxid,$username.'@163.com',$pwd,$username);
		if($rs === false){
			echo $weixin->text("系统繁忙,请稍后再试")->reply();exit;
		}
		//$weixinconfig['followmsg'] .= "\r\n系统分配帐号：$username 密码：$pwd\r\n";
		$aite_id = 'weixin_'.$wxid;
		$ec_name = $db -> getOne("SELECT `user_name` FROM `ecs_users` WHERE `aite_id`= '$aite_id'"); //获取用户名
		$weixinconfig['followmsg'] .= "\r\n您已经注册过并绑定了帐号：$ec_name 密码：$pwd \r\n请你及时修改完善您的用户资料，此账号密码可用于您在PC端登陆使用。";
		if(!empty($data['Ticket']) || $scene_id )
		{
			$sql = "select content from ".$GLOBALS['ecs']->table('weixin_qcode')." where qcode = '".$data['Ticket']."'";
			$uid = $GLOBALS['db']->getOne($sql);

			if(intval($uid) > 0)
			{
				 $r = $api->bind_distrib($wxid,$uid);
				 if($r === false)
				 {
					 echo $weixin->text("系统繁忙,请稍后再试")->reply();exit; 
				 }
				 $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$uid'";
				 $distribor = $GLOBALS['db']->getOne($sql);
				 if(!empty($distribor))
				 {
				 	$weixinconfig['followmsg'] .= "\r\n您已成为[".$distribor."]的一级会员 \r\n";
					
					$wx_id = "weixin_".$wxid;
					 
					$parent_id_sql = "SELECT `parent_id` FROM `ecs_users` WHERE `aite_id` = '$wx_id'";
		
					$parent_id = $db -> getOne($parent_id_sql);//上级uid
		
					$my_name_sql = "SELECT `nickname` FROM `ecs_weixin_user` WHERE `fake_id` = '$wxid'";
		
					$my_name = $db -> getOne($my_name_sql);//登录会员微信昵称
					
					$wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
		
					$wap_url = $db -> getOne($wap_url_sql);//手机端网址
					
                    @file_get_contents($wap_url."weixin/weixin_remind.php?notice=5&up_uid=".$parent_id."&my_name=".$my_name);
				 }
			}
		}
		if($weixinconfig['bonustype'] > 0){
			$api->sendBonus($wxid,$weixinconfig['bonustype']);
		}
	}
	
	//prince 120029121
	$scene_id_arr=explode("qrscene_", $event['key']);
	$scene_id = $scene_id_arr[1];
	if($scene_id){
		$ecuid = $GLOBALS['db']->getOne ( "SELECT ecuid FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE `fake_id` = '$wxid'" );
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_login') . " SET `uid`='$ecuid' WHERE `value` = '$scene_id'");
	}
	//prince 120029121

	//取消关注再重新关注不送红包
	if(!$followInfo && $weixinconfig['bonustype2'] > 0){
		$bonus_sn = $api->sendBonus('',$weixinconfig['bonustype2']);
	}
	$bonus_msg =  $bonus_sn ? "\r\n恭喜您获得红包一个：{$bonus_sn}(可在购买商品时使用)" : "";
	
	$continue_url = $db -> getOne("SELECT `continue_url` FROM `ecs_weixin_user` WHERE `fake_id`= '$wxid'"); //返回地址
	if(!empty($continue_url)){
		$continue_url= "\r\n\n".'<a href="' . $continue_url . '">点击继续浏览>></a>';
		$db -> query("UPDATE `ecs_weixin_user` SET `continue_url` = '' WHERE `fake_id` ='$wxid'");
	}
	
	echo $weixin->text($weixinconfig['followmsg'].$bonus_msg.$continue_url)->reply();//发送欢迎信息
	exit;
}
if ($event['event'] == "unsubscribe"){ // 用户主动删除
	$api->unFollowUser($wxid);	//设置关注状态
	//$api->unBindUser($wxid);	//寒冰解绑会员(一般情况下,取消关注是不解绑会员的.除非用户主动点击解绑.)
	exit;
}
//查询用户输入是否为指定命令
if($type == "text"){
	$userKey = $api->keywordsToKey($content,&$diy_type);
	if($userKey) $event = array('event'=>'CLICK','key'=>$userKey);
	if($content=='qrcode') $event = array('event'=>'CLICK','key'=>$content);
	if($content=='cxsc') $event = array('event'=>'CLICK','key'=>$content);
	if($content=='jcbd') $event = array('event'=>'CLICK','key'=>$content);

}
$weburl .= $api->createTokenLoginUrl($wxid,$api->dir);
//判断用户是否点击的菜单
if ($event['event'] == "CLICK"){
	$content = $event['key'];
	if(count($event) == 5)
	{
		$userKey = $api->keywordsToKey($content,&$diy_type);
	}
	$api->sendIntegral($wxid,$num=0,$content);
	switch($content){
		case "best":
		case "new":
		case "hot":
			$newsData = array();
			$reMsg = $api->getGoods($content);
			if($reMsg){
				foreach($reMsg as $k=>$v){
					$newsData[$k]['Title'] = $v['name'];
					$newsData[$k]['Description'] = strip_tags($v['name']);
					$newsData[$k]['PicUrl'] = (strpos($v['thumb'],'http://') !== false ? $v['thumb'] : $baseurl.$v['thumb']);
					$newsData[$k]['Url'] = $weburl."mobile/".$v['url'];
					if($k == 9)
					{
						break; 
					}
				}
			}
			echo $weixin->news($newsData)->reply();exit;
		break;
		case "wdzh":
			if($api->isBindUser($wxid)){
				$user = $api->getUserInfo($wxid);
				if($user['mobile_phone'])
				{
					$account = $user['mobile_phone']; 
				}
				else
				{
					if($user['email'])
					{
						$account = $user['email']; 
					}
					else
					{
						$account = $user['user_name']; 
					}
				}
				$msg = sprintf($weixinconfig['bindmsg'],$account);
				$msg .= "<a href='{$weburl}mobile/weixin_account.php?wxid={$wxid}'>帐号管理</a>";
				echo $weixin->text($msg)->reply();exit;
			}
			$msg = "<a href='{$weburl}mobile/user.php?wxid={$wxid}'>点击绑定</a>，";
			$msg .= $weixinconfig['reg_notice'];
			echo $weixin->text($msg)->reply();exit;
		break;
		case "ddcx":
			$reMsg = $api->getOrder($wxid);
			if($reMsg === false){
				echo $weixin->text("您还没有绑定帐号！")->reply();exit;
			}else{
				$os = array(0=>'未确认',1=>'已确认',2=>'取消',3=>'无效',4=>'退款',5=>'已分单');
				$ps = array(0=>'未付款',1=>'部分支付',2=>'已付款');
				$ss = array(0=>'未发货',1=>'已发货',2=>'确认收货',3=>'配货中',4=>'已发货(部分商品)');
				foreach ($reMsg as $v){
					$text .= "订单编号：<a href='{$weburl}mobile/user.php?act=order_detail"."%26"."order_id={$v[order_id]}'>{$v['order_sn']}</a>\r\n";
					$text .= "订单金额：{$v['order_amount']}\r\n";
					$text .= "订单状态：{$os[$v['order_status']]}\r\n";
					$text .= "付款状态：{$ps[$v['pay_status']]}\r\n";
					$text .= "发货状态：{$ss[$v['shipping_status']]}\r\n";
				}
			}
			$text = $text ? $text : "您还没有购买任何商品！";
			echo $weixin->text($text)->reply();exit;
		break;
		case "jcbd":
			//解除绑定直接是否已经绑定判断
			if($followInfo['ecuid'] == 0){
				echo $weixin->text("您还没有绑定账号!")->reply();exit;
			}
			$api->unBindUser($wxid);
			echo $weixin->text("帐号绑定解除！")->reply();exit;
		break;
		case "bdhy":
			if($api->isBindUser($wxid)){
				echo $weixin->text("您已经绑定帐号，无需重复绑定！如需解绑请回复：jcbd")->reply();exit;
			}
			echo $weixin->text($weixinconfig['reg_notice'])->reply();exit;
		break;
		 case "mima":
			  $user = $api->getUserInfo($wxid);
		      $username = $user['user_name'];
	          $passwd_weixin = $db -> getOne("SELECT `passwd_weixin` FROM `ecs_users` WHERE `user_name`= '$username'"); //获取用户密码
			echo $weixin->text("您的账号是：".$username ."\r\n账号密码是：".$passwd_weixin)->reply();exit;
		break;
		case "info":
			$reMsg = $api->getUserInfo($wxid);
			if($reMsg === false){
				echo $weixin->text("您还没有绑定帐号！")->reply();exit;
			}else{
				//$text = "会员编号：{$reMsg['user_id']}\r\n";
				$text= "会员账号：\r\n{$reMsg['user_name']}\r\n";

				if(!empty($reMsg['email']))
				{
					$text .= "邮箱地址：\r\n{$reMsg['email']}\r\n";
				}
				if(!empty($reMsg['mobile_phone']))
				{
					$text .= "手机号码：\r\n{$reMsg['mobile_phone']}\r\n"; 
				}
				$text .= "余额：{$reMsg['user_money']}\r\n";
				$text .= "积分：{$reMsg['pay_points']}\r\n";
				$text .="<a href='{$weburl}mobile/user.php'>查看详情</a>";
			}
			echo $weixin->text($text)->reply();exit;
			break;
			case "gn"://特色功能
			    $text = "特色功能展示\r\n";
				$text .= "手机预售：\r\n<a href='{$weburl}mobile/pre_sale.php'>》》点击查看详情</a>\r\n";
				$text .="手机拍卖：\r\n<a href='{$weburl}mobile/auction.php'>》》点击查看详情</a>\r\n";
				$text .= "单品推广：\r\n<a href='{$weburl}mobile/tuiguang.php?id=142'>》》点击查看详情</a>\r\n";
				$text .="附近店铺：\r\n<a href='{$weburl}mobile/supplier_near.php'>》》点击查看详情</a>\r\n";
				$text .="新版拼团：\r\n<a href='{$weburl}mobile/extpintuan.php'>》》点击查看详情</a>\r\n";
				$text .="新版砍价：\r\n<a href='{$weburl}mobile/cut.php'>》》点击查看详情</a>\r\n";
				$text .="\r\n<a href='{$weburl}mobile/index.php'>浏览手机商城</a>".$_SESSION['location_suppId'];
			
			echo $weixin->text($text)->reply();exit;
			break;
		case "qd":
			if(($num = $api->userSign($wxid)) === false){
				$text = join('', (array)$GLOBALS['err']->last_message());
			}else{
				$text = "签到成功！获取积分{$num}。";
			}
			echo $weixin->text($text)->reply();exit;
			break;
		case "kf":
			echo $weixin->kefu()->reply();exit;
			break;
		case 'qdcx':
			$order = $api->queryKuaidi($wxid);
			if($order === false){
				$text = join('', (array)$GLOBALS['err']->last_message());
			}else{
				$text = '';
				foreach ($order as $o){
					$text .= "订单：{$o['order_sn']}\r\n";
					$text .= "快递名称：{$o['shipping_name']}\r\n";
					$text .= "快递单号：{$o['invoice_no']}\r\n";
					$text .= "最新状态：{$o['kuaidi']['context']}\r\n";
				}
			}
			echo $weixin->text($text)->reply();exit;
			break;
		case 'qrcode':
			$user = $api->getUserInfo($wxid);
		    $user_id = $user['user_id'];
			prince_get_qrcode($db,$wxid,$weixin,$user_id);
			break;
		case 'cxsc':
			$user = $api->getUserInfo($wxid);
		    $user_id = $user['user_id'];
			$db->query("DELETE FROM `ecs_weixin_qcode` WHERE `content`='$user_id' or `user_id`='$user_id' ");
			prince_get_qrcode($db,$wxid,$weixin,$user_id);
			break;
	}
	if(strpos($content,"article_") !== false){
		$articleId = str_replace('article_','',$content);
		$artInfo = $db->getRow("select * from ".$GLOBALS['ecs']->table('article')." where article_id='{$articleId}'");
		if($diy_type == 1){
			echo $weixin->text($artInfo['description'])->reply();exit;
		}
		$newsData[0]['Title'] = $artInfo['title'];
		$newsData[0]['Description'] = $artInfo['description'];
		$newsData[0]['PicUrl'] = (strpos($artInfo['file_url'], 'http://') !== false ? $artInfo['file_url'] : $baseurl."mobile/".$artInfo['file_url']);
		$newsData[0]['Url'] = $weburl.$artInfo['link'];
		echo $weixin->news($newsData)->reply();exit;
	}
	echo $weixin->text("未定义菜单事件{$content}")->reply();exit;
}
//$content = $api->getstr($content);
//处理用户扫一扫
if ($event['event'] == "SCAN"){
	$content = intval($event['key']);//场景值ID，临时二维码时为32位非0整型，永久二维码时最大值为100000
	$res = $db->getRow("select * from " . $GLOBALS['ecs']->table('weixin_qcode') . " where id='$content'");
	if($res){
		if($res['type'] == 1){
			$goodsInfo = $db->getRow("select * from ".$GLOBALS['ecs']->table('goods')." where goods_id='{$res['content']}'");
			$newsData[0]['Title'] = $goodsInfo['goods_name'];
			$newsData[0]['Description'] = strip_tags($goodsInfo['goods_desc']);
			$newsData[0]['PicUrl'] = (strpos($goodsInfo['goods_thumb'], 'http://') !== false ? $goodsInfo['goods_thumb'] :$baseurl.$goodsInfo['goods_thumb']);
			$newsData[0]['Url'] = $weburl."mobile/goods.php?id=".$goodsInfo['goods_id'];
			echo $weixin->news($newsData)->reply();exit;
		}elseif($res['type'] == 2){
			$artInfo = $db->getRow("select * from ".$GLOBALS['ecs']->table('article')." where article_id='{$res['content']}'");
			$newsData[0]['Title'] = $artInfo['title'];
			$newsData[0]['Description'] = strip_tags($artInfo['description']);
			$newsData[0]['PicUrl'] = (strpos($artInfo['file_url'], 'http://') !== false ? $artInfo['file_url'] : $baseurl.'mobile/'.$artInfo['file_url']);
			$newsData[0]['Url'] = $weburl."mobile/article.php?id=".$artInfo['article_id'];
			echo $weixin->news($newsData)->reply();exit;
		}elseif($res['type'] == 3){
			echo $weixin->text($res['content'])->reply();exit;
		}elseif($res['type'] == 4){
			if($api->isBindUser($wxid) === false)
			{
				$api->bind_record($wxid,$res['content']);
			}
		}elseif($res['type'] == 6)
		{
			$sql = "SELECT value FROM " . 
				 	$GLOBALS['ecs']->table('supplier_shop_config') . 
					" WHERE code = 'shop_name'" . 
					" AND supplier_id = '" . $res['content'] . "'";
			 $shop_name = $GLOBALS['db']->getOne($sql);
			 $sql = "SELECT value FROM " . 
				 	$GLOBALS['ecs']->table('supplier_shop_config') . 
					" WHERE code = 'shop_desc'" . 
					" AND supplier_id = '" . $res['content'] . "'";;
			$shop_desc = $GLOBALS['db']->getOne($sql);
			$sql = "SELECT value FROM " . 
				 	$GLOBALS['ecs']->table('supplier_shop_config') . 
					" WHERE code = 'shop_logo'" . 
					" AND supplier_id = '" . $res['content'] . "'";;
			$shop_logo = $GLOBALS['db']->getOne($sql);
			$newsData[0]['Title'] = $shop_name;
			$newsData[0]['Description'] = $shop_desc;
			$newsData[0]['PicUrl'] = $baseurl.$shop_logo;
			$newsData[0]['Url'] = $baseurl."mobile/supplier.php?suppId=" . $res['content'];
			echo $weixin->news($newsData)->reply();exit;
		}elseif($res['type'] == 99){
			echo $weixin->text("欢迎您的回来")->reply();exit;
		}else{
			echo $weixin->text($res['content'])->reply();exit;
		}
	}
	if($api->scanLogin($content,$wxid) === true){
		echo $weixin->text("您使用扫一扫功能登陆网站成功！如网页没有跳转请点击底部按钮，谢谢！")->reply();exit;
	}
}
//处理用户的输入
if($content){

	//寒冰 判断是否为绑定内容
	$content = str_replace('+',' ',$content);
	$bindInfo = explode(' ',$content);
	$RegExp='/^[a-z0-9_]{6,12}$/';
	$username = '';
	if(preg_match($RegExp,$bindInfo[0]) && $weixinconfig['reg_type'] == 3){
		$username = $bindInfo[0];
		$bindInfo[0] .= "@163.com";
	}
	if(is_email($bindInfo[0]) && $api->isBindUser($wxid)===false){
		if(strlen($bindInfo[1])<6){
			echo $weixin->text("密码长度必须大于6！")->reply();exit;
		}
		$rs = $api->bindUser($wxid,$bindInfo[0],$bindInfo[1],$username);
		if($rs === false){
			$err = $GLOBALS['err']->last_message();
			echo $weixin->text("绑定出错！原因：".$err[0])->reply();exit;
		}
		if($weixinconfig['bonustype'] > 0){
			$bonus_sn = $api->sendBonus($wxid,$weixinconfig['bonustype']);
		}
		$bonus_msg =  $bonus_sn ? "\r\n恭喜您获得红包一个可登录网站查看！" : "";
		echo $weixin->text($weixinconfig['bindmsg'].$bonus_msg)->reply();//发送欢迎信息
	}

	if($content == '客服'){
		echo $weixin->kefu()->reply();exit;
	}
}
$reMsg = $api->getGoodsByKey($content);
if($reMsg){
	$k = 0;
	foreach($reMsg as $v){
		$newsData[$k]['Title'] = $v['goods_name'];
		$newsData[$k]['Description'] = strip_tags($v['goods_name']);
		$newsData[$k]['PicUrl'] = (strpos($v['thumb'],'http://') !== false ? $v['thumb'] : $baseurl.$v['thumb']);
		$newsData[$k]['Url'] = $weburl.$v['url'];
		$k++;
	}
	echo $weixin->news($newsData)->reply();exit;
}/*else{
	if($content){
		$xhyData = $weixinconfig['auto_reply'];
		//$xhyData = $weixin->http_post("http://www.niurenqushi.com/app/simsimi/ajax.aspx",array('txt'=>$content));
		echo $weixin->text("自动回复：".$xhyData)->reply();exit;
	}
}*/

function prince_access_token($db) {
	$ret = $GLOBALS['db']->getRow("SELECT * FROM `ecs_weixin_config` WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$access_token = $ret['access_token'];
	$dateline = $ret['expire_in'];
	$time = time();

	//if(($time - $dateline) >= 7200) {
	if(1) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$ret_json = prince_curl_get_contents($url);
		$ret = json_decode($ret_json);
		if($ret->access_token){
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
		}
	}elseif(empty($access_token)) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$ret_json = prince_curl_get_contents($url);
		$ret = json_decode($ret_json);
		if($ret->access_token){
			$GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
			return $ret->access_token;
		}
	}else {
		return $access_token;
	}
}
function prince_curl_get_contents($url) 
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
function prince_curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') {
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

function prince_get_qrcode($db,$wxid,$weixin,$user_id){
				$access_token = prince_access_token($db);	
				echo customText($access_token, $wxid);
				
				if(empty($user_id)){
					$text = "您还未绑定会员账号，请先绑定后进行操作";
					echo $weixin->text($text)->reply();exit;
				}
				$user_name = $db->getOne("SELECT `user_name` FROM `ecs_users` WHERE `user_id`='$user_id'");				
				$qr_path = $db->getOne("SELECT `qr_path` FROM `ecs_weixin_qcode` WHERE `user_id`='$user_id'");
				$exists_file=fopen(dirname(__FILE__)."/qrcode/".$qr_path,'r');	
					
				if(!empty($qr_path) && $exists_file){
					$data=dirname(__FILE__)."/qrcode/".$qr_path;
				}else{
				    $db->query("DELETE FROM `ecs_weixin_qcode` WHERE `user_id`='$user_id'");
					$insert_sql = "INSERT INTO `ecs_weixin_qcode` (`type`,`content`,`user_name`,`user_id`) VALUES
					('99','$user_id','$user_name', '$user_id')";
					$db->query($insert_sql);
					$id = $db -> insert_id();
					
					$action_name="QR_LIMIT_SCENE";
					$json_arr = array('action_name'=>$action_name,'action_info'=>array('scene'=>array('scene_id'=>$id)));
					$data = json_encode($json_arr);
					$access_token = prince_access_token($db);	
					if(strlen($access_token) >= 64) {
						$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
						$res_json =prince_curl_grab_page($url, $data);
						$json = json_decode($res_json);	
					}
					$ticket = $json->ticket;
					
					if($ticket){
						$ticket_url = urlencode($ticket);
						$ticket_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket_url;
						$imageinfo=downloadimageformweixin($ticket_url);
						if(empty($imageinfo)){
								$text = "下载二维码失败，请检查服务器环境后重试";
								echo $weixin->text($text)->reply();exit;
						}
						$time = time();	
						$url=$_SERVER['HTTP_HOST'].'/mobile/weixin/';			
						$path = 'images/qrcode/'.$time.'.jpg';
						$surl="http://".$url.'/images/qrcode/'.$time.'.jpg';
						$local_file=fopen($path,'a');
						$h_path='images/head/'.$time.'.jpg';
						$h_local_file=fopen($h_path,'a');
						$headimgurl = $db->getOne("SELECT `headimgurl` FROM `ecs_weixin_user` WHERE `fake_id`='$wxid'");
						$h_imageinfo=downloadimageformweixin($headimgurl);
						if(false !==$local_file){	
							 fwrite($local_file,$imageinfo);
							 fwrite($h_local_file,$h_imageinfo);
							 fclose($local_file);
							 fclose($h_local_file);
						}else{		
								$text = "保存二维码图片的路径mobile/weixin/images/qrcode或mobile/weixin/images/qrcode/head则没可写权限，请修改！";
								echo $weixin->text($text)->reply();exit;
						}
					}else{
						$text = "获取ticket失败请检查appid和appsecret是否正确";
						echo $weixin->text($text)->reply();exit;
					}
					
					$imgsrc = "images/qrcode/".$time.".jpg";
					$h_imgsrc="images/head/".$time.".jpg";
					$width = 200; 
					$height = 200;
					$time=time();
					$name=resizejpg($imgsrc,$width,$height,$time); 
					$imgs = $name;				
					//处理头像
					$width = 60; 
					$height = 60;
					$h_time=$time."_h";
					$h_name=resizejpg($h_imgsrc,$width,$height,$h_time); 
					$h_imgs = $h_name;				
					$target = 'images/prince-qq-120029121.jpg';
					$target_img = Imagecreatefromjpeg($target);
					$source = Imagecreatefromjpeg($imgs);
					$h_source = Imagecreatefromjpeg($h_imgs);
					imagecopy($target_img,$source,165,392,0,0,200,200);
					imagecopy($target_img,$h_source,60,28,0,0,60,60);
					$fontfile = "simsun.ttf";
					#水印文字
					$nickname = $db->getOne("SELECT `nickname` FROM `ecs_weixin_user` WHERE `fake_id`='$wxid'");
					
					#打水印
					$textcolor = imagecolorallocate($target_img, 0, 0, 255);
					imagettftext($target_img,18,0,268,59,$textcolor,$fontfile,$nickname);				
					Imagejpeg($target_img,'qrcode/'.$time.'.jpg');
					$data=dirname(__FILE__)."/qrcode/".$time.".jpg";
					$s_data=$time.".jpg";
					$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_qcode') . " SET `qr_path`='$s_data' ,`nickname`='$nickname',`qcode`='$ticket' WHERE `id` = '$id'");
					
					
				}
				
				$filedata=array("media"=>"@".$data);
				$access_token = prince_access_token($db);	
				if(strlen($access_token) >= 64) {
					$url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type=image';
					$res_json =https_request($url, $filedata);
					$json = json_decode($res_json);	
				}
				
				if($json->media_id){
					$media_id=$json->media_id;
					echo $weixin->image($media_id)->reply();exit;
				}else{
					$text = "请联系PRINCE QQ 120029121修改代码";
					echo $weixin->text($text)->reply();exit;
				}
}
function downloadimageformweixin($url) {  
        $ch = curl_init ();  
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );  
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );  
        curl_setopt ( $ch, CURLOPT_URL, $url );  
        ob_start ();  
        curl_exec ( $ch );  
        $return_content = ob_get_contents ();  
        ob_end_clean ();  
        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );  
        return $return_content;  
}

function resizejpg($imgsrc,$imgwidth,$imgheight,$time) { 
		//$imgsrc jpg格式图像路径  $imgwidth要改变的宽度 $imgheight要改变的高度   $time jpg格式图像名字
		$arr = getimagesize($imgsrc); 
		header("Content-type: image/jpg"); 
		$imgWidth = $imgwidth; 
		$imgHeight = $imgheight; 
		$imgsrc = imagecreatefromjpeg($imgsrc); 
		$image = imagecreatetruecolor($imgWidth, $imgHeight);
		imagecopyresampled($image, $imgsrc, 0, 0, 0, 0,$imgWidth,$imgHeight,$arr[0], $arr[1]);
		$name="images/resizejpg/".$time.".jpg";  
		Imagejpeg($image,$name);
		return $name;
}
function https_request($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
function customText($access_token, $openid){
    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
     $data = '{
						"touser":"'.$openid.'",
						"msgtype":"text",
						"text":
						{
							 "content":"正在为您生成您的专属推广二维码，如果生成失败请重试，需要重置请回复:cxsc"
						}
			}';
    $result = https_request($url, $data);
}

