<?php
/**
 * JS_API支付demo
 * ====================================================
 * 在微信浏览器里面打开H5网页中执行JS调起支付。接口输入输出数据格式为JSON。
 * 成功调起支付需要三个步骤：
 * 步骤1：网页授权获取用户openid
 * 步骤2：使用统一支付接口，获取prepay_id
 * 步骤3：使用jsapi调起支付
*/
include_once("WxPayPubHelper_js_api.php");
    $weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );	
	$appid = $weixinconfig['appid'];  //	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	$appkey = $weixinconfig['appsecret'];//	//JSAPI接口中获取appid，审核后在公众平台开启开发模式后可查看
	$mchid = $weixinconfig['partnerId'];//受理商ID，身份标识
	$partnerkey = $weixinconfig['partnerKey'];//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	//=========步骤2：使用统一支付接口，获取prepay_id============
	//内部类无需修改   	
	//使用统一支付接口
	$unifiedOrder = new WxPayJsapi($appid,$appkey,$mchid,$partnerkey);
	//=========步骤1：网页授权获取用户openid============
	//通过code获得openid
/**	if (!isset($_GET['code']))
	{
		//触发微信返回code码
		$url = $unifiedOrder->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL);
		Header("Location: $url"); 
	}else
	{
		//获取code码，以获取openid
	    $code = $_GET['code'];
		$unifiedOrder->setCode($code);
		$openid = $unifiedOrder->getOpenId();
	}*/
	//$openid ='o8k1bt_lmDdR_a4g7P47poLsymFI';
	//设置统一支付接口参数
	

	
	
	
	//设置必填参数
	$unifiedOrder->setParameter("openid","$openid");//openid。
	$unifiedOrder->setParameter("body","$title");//商品描述
	//自定义订单号，此处仅作举例
	
	$unifiedOrder->setParameter("out_trade_no","$order_sn");//商户订单号 
	$unifiedOrder->setParameter("total_fee","$reward_money");//总金额
	$unifiedOrder->setParameter("notify_url",'WxPayConf_pub::NOTIFY_URL');//通知地址 
	$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
	//非必填参数，商户可根据实际情况选填
	//$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号  
	//$unifiedOrder->setParameter("device_info","XXXX");//设备号 
	//$unifiedOrder->setParameter("attach","XXXX");//附加数据 
	//$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
	//$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间 
	//$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记 
	//$unifiedOrder->setParameter("openid","XXXX");//用户标识
	//$unifiedOrder->setParameter("product_id","XXXX");//商品ID

	$prepay_id = $unifiedOrder->getPrepayId();
	//=========步骤3：使用jsapi调起支付============
	$unifiedOrder->setPrepayId($prepay_id);

	$jsApiParameters = $unifiedOrder->getParameters();
	//echo $jsApiParameters;


?>