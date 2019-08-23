<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');


if($_SESSION['user_id'])
{
    $ip = $_SERVER["REMOTE_ADDR"];
    $ip_url='http://api.map.baidu.com/location/ip?ak=2TGbi6zzFm5rjYKqPPomh9GBwcgLW5sS&ip='. $ip .'&coor=bd09ll';
	$code_json = erm_curl_get($ip_url);
    //$code_json=file_get_contents($ip_url);
    $json = json_decode($code_json,true);
    if($json['status'] != 0){
        show_message("系统暂未获取您当前位置", '返回主页', 'index.php', '');
    }
    $str = "";
    $str.="&shengfen=".$json['content']['address_detail']['province']; //省
    $str.="&shi=".$json['content']['address_detail']['city'];    //市
    $bianhao = $_GET['bianhao'];
    //$rest = @file_get_contents(CODE_URL."/wap.php?act=get&bianhao=".$bianhao.$str);
	$rest = erm_curl_get(CODE_URL."/wap.php?act=get&bianhao=".$bianhao.$str);
    $info = json_decode($rest, true);
    //第一次扫码加积分
    if($info['hits'] == 1){
    	$points = $info['points'] < 99 ? $info['points'] : 0;
    	//$sql = "update " . $GLOBALS['ecs']->table('users') . " set pay_points = pay_points+" . $points . " WHERE user_id=".$_SESSION['user_id'];
		log_account_change($_SESSION['user_id'],  0,  0, 0, $pay_points, '扫描赠送积分');

		if($GLOBALS['db']->query($sql)){
			show_message("您好！您所购买的商品是正品！添加". $points ."积分！", '返回主页', 'user.php', 'info');
		}
    }else{
    	show_message("您好！您所购买的商品是正品！该防伪码已被查询过，本次是第" .$info['hits']. "次查询，如不是亲自查询，请谨慎购买！", '返回主页', 'user.php', 'info');
    }
}else{
		show_message("尚未登录，登录之后再扫二维码加积分！", '返回主页', 'index.php', '');
}


function erm_curl_get($url){ 
	$ch=curl_init($url); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
	$content=curl_exec($ch); 
	curl_close($ch); 
	return($content); 
}


?>
