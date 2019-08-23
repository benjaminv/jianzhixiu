<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/28
 * Time: 15:51
 */



define('INIT_NO_USERS', true);
define('IN_ECS', true);
require('../../includes/init.php');



require_once(ROOT_PATH.'includes/lib_order.php');
require_once(ROOT_PATH.'includes/lib_payment.php');

define("WX_KEY","dZ29ODCpgdxHDVEAn08HM2qUJ5oUE1Vq");



/*
$p=$GLOBALS['db']->getRow( 'SELECT * FROM ' . $GLOBALS['ecs']->table('touch_payment') .		" WHERE pay_code = 'wxpay'");

$payment = unserialize_config($payment['pay_config']);
logResultWx("log::notify::p",$p);*/
logResultWx("log::notify::WX_KEY",WX_KEY);

$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
logResultWx("log::notify::xml",$xml);
if (! empty($xml)) {

	$postdata =xmlToArray($xml);
	/* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
	//todo 部署后删除
logResultWx("log::notify::postdata",$postdata);
	// 微信端签名
	$wxsign = $postdata['sign'];

	unset($postdata['sign']);

	//todo 部署后删除
logResultWx("log::notify::wxsign",$wxsign);

	$sign=getSign($postdata);


	//todo 部署后删除
logResultWx("log::notify::sign:",$sign);

	if ($wxsign == $sign) {
		// 交易成功
		if ($postdata['result_code'] == 'SUCCESS') {
			// 获取log_id
			$out_trade_no_array = explode('_',$postdata['out_trade_no']);
			$log_id =$out_trade_no_array[1];

	/*		$sql="SELECT p.log_id from ".$GLOBALS['ecs']->table('pay_log').
					" as p LEFT JOIN ".$GLOBALS['ecs']->table('order_info').
					" as o on o.order_id = p.order_id  where o.order_sn = '".$out_trade_no."'";


			$log_id=$GLOBALS['db']->getOne($sql);*/
			// 改变订单状态


			//todo 部署后删除
			logResultWx("log::notify::out_trade_no:",$postdata['out_trade_no']);
			order_paid($log_id, 2);

		}
		$returndata['return_code'] = 'SUCCESS';
	} else {
		$returndata['return_code'] = 'FAIL';
		$returndata['return_msg'] = '签名失败';
	}

} else {
	$returndata['return_code'] = 'FAIL';
	$returndata['return_msg'] = '无数据返回';
}

//todo 部署后删除
logResultWx("log::notify::returndata",$returndata['return_code']);
$xml=arrayToXml($returndata);
//todo 部署后删除
logResultWx("log::notify::returnxml",$xml);

echo $xml;
exit();




/**
 * 作用：生成签名
 */
 function getSign($Obj)
{
	foreach ($Obj as $k => $v) {
		$Parameters[$k] = $v;
	}
	// 签名步骤一：按字典序排序参数
	ksort($Parameters);

	$buff = "";
	foreach ($Parameters as $k => $v) {
		$buff .= $k . "=" . $v . "&";
	}
	$String="";
	if (strlen($buff) > 0) {
		$String = substr($buff, 0, strlen($buff) - 1);
	}
	// echo '【string1】'.$String.'</br>';
	// 签名步骤二：在string后加入KEY
	$String = $String . "&key=" . WX_KEY;
	// echo "【string2】".$String."</br>";
	// 签名步骤三：MD5加密
	$String = md5($String);
	// echo "【string3】 ".$String."</br>";
	// 签名步骤四：所有字符转为大写
	$result_ = strtoupper($String);
	// echo "【result】 ".$result_."</br>";
	return $result_;
}



/**
 * 	作用：将xml转为array
 */
 function xmlToArray($xml)
{
	//将XML转为array
	$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $array_data;
}
/**
 * 	作用：array转xml
 */
function arrayToXml($arr)
{
	$xml = "<xml>";
	foreach ($arr as $key=>$val)
	{
		if (is_numeric($val))
		{
			$xml.="<".$key.">".$val."</".$key.">";

		}
		else
			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
	}
	$xml.="</xml>";
	return $xml;
}

function logResultWx($word = '',$var=array()) {

	$output= strftime("%Y%m%d %H:%M:%S", time()) . "\n" ;
	$output .= $word."\n" ;
	if(!empty($var)){
		$output .= print_r($var, true)."\n";
	}
	$output.="\n";

	$log_path=ROOT_PATH . "/notify/";
	if(!is_dir($log_path)){
		@mkdir($log_path, 0777, true);
	}

	file_put_contents($log_path.date("Ymd")."wx_log.txt", $output, FILE_APPEND | LOCK_EX);
}
