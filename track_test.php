<?php
define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');

track_order(array('2019010535280'));

function track_order($ordersns){
	$track_url = LIANMEI_EDUCATION."/web/index.php?c=promoter&a=track&do=order";
	$orderstr = "";
	foreach($ordersns as $v){
		$orderstr .= "'".$v."',";
	}
	$orderstr = substr($orderstr,0,-1);
	$orderInfosql = "SELECT a.order_sn as ordersn,a.order_id,b.portal_id as unionid,a.order_amount as price,a.goods_amount,a.shipping_fee,a.pay_name,b.user_name as nickname,b.mobile_phone as mobile FROM ".$GLOBALS['ecs']->table('order_info')." a LEFT JOIN ".$GLOBALS['ecs']->table('users')." b ON a.user_id = b.user_id WHERE a.order_sn IN (".$orderstr.")";
	$orderInfos = $GLOBALS['db']->getAll($orderInfosql);
	foreach($orderInfos as $k=>$v){
		$orderInfos[$k]['ordertype'] = 2;
		$orderInfos[$k]['name'] = '商城商品';
		$orderInfos[$k]['paytime'] = time();
		$orderGoodsSql = "SELECT goods_name,goods_number,goods_price,goods_attr FROM ".$GLOBALS['ecs']->table('order_goods')." WHERE order_id = ".$v['order_id'];
		$orderGoods = $GLOBALS['db']->getAll($orderInfosql);
		$orderInfos[$k]['order_goods'] = $orderGoods;
	}
				
	http_track_request($track_url,array('orderInfo'=>$orderInfos));

}

function http_track_request($url, $data = null){
	$headers = array("Content-type: application/json;charset=utf-8","Accept: application/json","Cache-Control: no-cache","Pragma: no-cache");
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
	$rtn = curl_exec($ch);//CURLOPT_RETURNTRANSFER 不设置  curl_exec返回TRUE 设置  curl_exec返回json(此处) 失败都返回FALSE
	curl_close($ch);
	//echo "<pre>";print_R($rtn);exit;	
	return $rtn;
}
