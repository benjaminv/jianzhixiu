<?php
/**
 * 微信退款插件
 * $Author: PRINCE $
 * 2016-03-25 09:29:08Z PRINCE QQ 120029121 
 */
class WxPayConfig
{
	
	const APPID = PRINCE_WXPAY_APPID;            //微信公众号AppId 
	const MCHID = PRINCE_WXPAY_MCHID;            //微信支付商户ID 
	const KEY = PRINCE_WXPAY_KEY;                //微信支付商户密钥Key 
	const APPSECRET = PRINCE_WXPAY_APPSECRET;    //微信公众号AppSecret 

	const SSLCERT_PATH = PRINCE_WXPAY_SSLCERT_PATH;
	const SSLKEY_PATH = PRINCE_WXPAY_SSLKEY_PATH;
	

	const CURL_PROXY_HOST = "0.0.0.0";
	const CURL_PROXY_PORT = 0;
	

	const REPORT_LEVENL = 1;
}
