<?php
include(ROOT_PATH ."vender/alipay/AopSdk.php");
include(ROOT_PATH."data/config.php");
require_once ROOT_PATH.'vender/alipay/pagepay/service/AlipayTradeService.php';
require_once ROOT_PATH.'vender/alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php';
function do_alipay_refund($order_id,$order_sn,$refund_amount){
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment')." WHERE pay_code = 'alipay'";
    $payment = $GLOBALS['db']->getRow($sql);
    $payment = unserialize($payment['pay_config']);
    $payment_config = array();
    foreach($payment as $k => $v){
        $payment_config[$v['name']] = $v['value'];
    }
    $config = [
        'app_id' => ALIPAY_APPID,
        'merchant_private_key' => ALIPAY_PRIVATE_KEY,
        'alipay_public_key' => ALIPAY_PUBLIC_KEY,
        'charset' => "UTF-8",
        'sign_type'=>"RSA2",
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    ];
    $aop = new AlipayTradeService($config);
    $RequestBuilder=new AlipayTradeRefundContentBuilder();
    $RequestBuilder->setOutTradeNo($order_sn);
    $RequestBuilder->setRefundAmount($refund_amount);
	$requestNo = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	$RequestBuilder->setOutRequestNo($requestNo);
    $RequestBuilder->setRefundReason("正常退款");
    $response = $aop->Refund($RequestBuilder);
    if($response->code == "10000"){
        return true;
    }else{
        return false;
    }
}