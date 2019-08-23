<?php
/**
 * 微信退款插件
 * $Author: PRINCE $
 * 2016-03-25 09:29:08Z palenggege
 */
require_once ROOT_PATH ."vender/wxpay/lib/WxPay.Api.php";
require_once ROOT_PATH ."vender/wxpay/lib/WxPay.Data.php";
require_once ROOT_PATH ."vender/wxpay/lib/WxPay.Config.php";
require_once ROOT_PATH ."data/config.php";
function do_wx_refund($order_id,$order_sn,$money_paid,$money_refund){	

    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('weixin_config').
           " WHERE id = '1'";
    $payment = $GLOBALS['db']->getRow($sql);
    if(BORROW == 1){
        define("PRINCE_WXPAY_APPID", BOR_APPID);
        define("PRINCE_WXPAY_MCHID", BOR_MCHID);
        define("PRINCE_WXPAY_KEY", BOR_APIKEY);
        define("PRINCE_WXPAY_APPSECRET", BOR_APPSECRET);
        define("PRINCE_WXPAY_SSLCERT_PATH", __DIR__.'/cert/apiclient_cert.pem');
        define("PRINCE_WXPAY_SSLKEY_PATH", __DIR__.'/cert/apiclient_key.pem');

    }else{
        define("PRINCE_WXPAY_APPID", $payment['appid']);
        define("PRINCE_WXPAY_MCHID", $payment['partnerId']);
        define("PRINCE_WXPAY_KEY", $payment['partnerKey']);
        define("PRINCE_WXPAY_APPSECRET", $payment['appsecret']);
        define("PRINCE_WXPAY_SSLCERT_PATH", __DIR__.'/cert/apiclient_cert.pem');
        define("PRINCE_WXPAY_SSLKEY_PATH", __DIR__.'/cert/apiclient_key.pem');
    }
	if(isset($order_sn) && $order_sn != ""){
		$out_trade_no = $order_sn;
		$total_fee = $money_paid*100;
		$refund_fee = $money_refund*100;
        $input = new WxPayRefund();
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($refund_fee);

        $config = new WxPayConfig();
        $input->SetOut_refund_no("sdkphp".date("YmdHis"));
        $input->SetOp_user_id($config->GetMerchantId());
        $return = WxPayApi::refund($config, $input);
		if(is_array($return) && $return['result_code'] == 'SUCCESS'){
			return true;
		}elseif(is_array($return) && $return['result_code'] == 'FAIL'){
			echo '订单:'.$order_sn.' 处理失败<br />';
			echo '订单金额:'.$money_paid.'<br />';
			echo '退款金额:'.$money_refund.'<br />';
			echo '返回状态码:'.$return['return_code'].'<br />';
			echo '返回信息:'.$return['return_msg'].'<br />';
			echo '业务结果:'.$return['result_code'].'<br />';
			echo '错误代码:'.$return['err_code'].'<br />';
			echo '错误代码描述:'.$return['err_code_des'].'<br />';
			return false;
		}
	}
}

//获取毫秒
function wx_getMillisecond() {
	list($usec, $usec) = explode(' ', microtime());
	   $msec=round($usec*1000);
	   return $msec;
}


function prince_get_payment_by_code_pc($code)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment').
           " WHERE pay_code = '$code' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

function prince_get_payment_by_id_pc($id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment').
           " WHERE pay_id  = '$id' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}


function prince_get_payment_by_code_mobile($code)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('ecsmart_payment').
           " WHERE pay_code = '$code' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

function prince_get_payment_by_id_mobile($id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('ecsmart_payment').
           " WHERE pay_id = '$id' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

/**
 * 微信退款插件
 * $Author: PRINCE $
 * 2016-03-25 09:29:08Z PRINCE QQ 120029121 
 */
?>
