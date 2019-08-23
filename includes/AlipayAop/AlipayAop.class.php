<?php
require_once 'AopClient.php';
require_once 'AlipayFundTransToaccountTransferRequest.php';
class AlipayAop{
  private $appId; 
  private $rsaPrivateKey; 
  private $alipayrsaPublicKey; 
  function __construct(){ 
      $this->appId = '2018011901970468';
	  $this->rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDZmvFlRb8fW606aCz+UhiSphsHs4mEZXp/sLtJZC10NZ8UjDGWSzIJxS6GUHTBiwpXOm/6NMjvkxvbUEQSH5mclsR3OvHjDlzKiPUDG8/Ype0hScMJYcX2B197M4RA/Q3JYQ+KVN9h0tNqr4z2ngLHxGjn+te90sQw4T90go1X+2AkWitlOoazVxyvp0McxOBPRFf4hSC9S+NhsDy2Rc3Hr1IfMtch+OnalflXr8sWEV19fWkKs5XJ9+KlcOPMLGiFSFEMq8lov0frqICamX7Wt3nn+xSLvzKHtpMorHmNA5t96oWZ1vCgMyseBGL0hb/WjaXZkc0ETzAYag6f95MDAgMBAAECggEAU2gwXsD9IOfi3iBQHqsZABzq/2ixrS24Znk3UEo1ofVrpFSYLSNlaplJ2/G6zvScYhLkGONioXGhm86ISOoT1xFy/MB7NqyqpHcacraWVFRFMB01xMLVPhhVYMO+TaqxPh8V9c/ST4yfvKTNQzoNlsSR8VkUmI3Q5WtxBxeDVdUvdN2iWQQEjf8o6e2j/JPPpIlgmq5rTWG0+u8QX998X4E7o6Vt9VnjJI3HcwqJE46zQLfuoC07Fhf2JQG91kaPtlhPnoNg+0qpVNuiFbV+Nfm/pkH67Vovu3z2Dp+aAfa7NuhfJ29Dwnoh08tX2wtSrD4vyPPWn6i+edDNO+IWAQKBgQDs631nYy6c1dHtfkZmVpnuIhl1BaC1NWhxHSpMIiiWB7Hg1zEqV/0IbW7r2hroq+DvHiVsUzexNgpehb6dQhrzNVZHFeUMf3G3crHo1IMX+CcwxD3P8bAujCCeJIyDI4nqkKrNZ1MdExFw81BT6GId7LHiMvAxoLlcQeHKE6sIOQKBgQDrIT+q/hF7dHk18txRa0Puqsr01/9tNEVBtfhKbxn+sK/gb8gZcDjwxQfzESDTwlLuDV/70Z2H7WpLFatl7X0D2x1Z9KBC9UAlgwWvusJjKk3dMhJfL9sxavzUnNoWB3hqWNSHcfXaOAmNBWReOmpCcqegW5aF/iuIer0GyHldGwKBgQDT3hesEC8MA86SspzkQcewBAB9/MVlp1g551n+8YEYAdOZfPczpbHbCnnqIoZz0dj6HRxcTeL875XAR5xZZ1dQbT81nKfTUFjyM3hT/U8qbTkmzCd2wOzMA3Xb1lVtpKdeA3cq7p6N3pJ3Tq9kCelMV3IQFXtk9hUtIqF3I7WMSQKBgHFWN4BOs1KU1BBjHjvIvpf+j5Hxw9d5yKBh/Gq0nw0bUcuXVhac93VnI+vQJ8iq9Jp2q/uQEKUClafXrCSXkxkWt1EzD0T3PpJWU5lfJm/yZlHm3uAvCzMI5RH/AUh5FVv9sYQQNHeZZ1EodjbNZYbeCVrMiwPPfmBs+UyZuZZdAoGAUAmtiTO5Ru160GFVz4pkItTIzJUSMaw4+PMieMqkrARF75yLrbGa8pptVjBGvj7Ku8HxbTN+W1LNrV72cUlH9Tog11okvleY9BPoyzpZ6NoblgG4MSuuMtBVpCdeXzzqCMEnkGTqOrPRQjj9CzTL0zPBcH7eCjbv11jhMN55sJQ=';
	  $this->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2ZrxZUW/H1utOmgs/lIYkqYbB7OJhGV6f7C7SWQtdDWfFIwxlksyCcUuhlB0wYsKVzpv+jTI75Mb21BEEh+ZnJbEdzrx4w5cyoj1AxvP2KXtIUnDCWHF9gdfezOEQP0NyWEPilTfYdLTaq+M9p4Cx8Ro5/rXvdLEMOE/dIKNV/tgJForZTqGs1ccr6dDHMTgT0RX+IUgvUvjYbA8tkXNx69SHzLXIfjp2pX5V6/LFhFdfX1pCrOVyffipXDjzCxohUhRDKvJaL9H66iAmpl+1rd55/sUi78yh7aTKKx5jQObfeqFmdbwoDMrHgRi9IW/1o2l2ZHNBE8wGGoOn/eTAwIDAQAB';
  }
  /*
  *该方法为支付宝转账方法
  *osn 交易单号
  *payee_account 收款人帐号
  *amount 转账金额
  *payee_real_name 收款方真实姓名
  */
  public function AlipayFundTransToaccountTransfer($osn,$payee_account,$amount,$payee_real_name){
	    $msg = array();
	    if(!$osn){
			$msg['success'] = 2;
			$msg['text']    = '交易单号为空';
			return $msg;
		}elseif(!$payee_account){
			$msg['success'] = 2;
			$msg['text']    = '收款人帐号为空';
			return $msg;
		}elseif(!$amount || $amount<0.1){
			$msg['success'] = 2;
			$msg['text']    = '转账金额不能小于0.1';
			return $msg;
		}elseif(!$payee_real_name){
			$msg['success'] = 2;
			$msg['text']    = '收款人姓名为空';
			return $msg;
		}else{
			$aop = new AopClient();
			$aop->gatewayUrl         = 'https://openapi.alipay.com/gateway.do';
			$aop->appId              = $this->appId;
			$aop->rsaPrivateKey      = $this->rsaPrivateKey;
			$aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
			$aop->apiVersion         = '1.0';
			$aop->signType           = 'RSA2';
			$aop->postCharset        = 'UTF-8';
			$aop->format             = 'json';
			$request = new AlipayFundTransToaccountTransferRequest();
			$request->setBizContent("{" .
			"    \"out_biz_no\":\"".$osn."\"," .
			"    \"payee_type\":\"ALIPAY_LOGONID\"," .
			"    \"payee_account\":\"".$payee_account."\"," .
			"    \"amount\":\"".$amount."\"," .
			"    \"payer_show_name\":\"支付方名称\"," .
			"    \"payee_real_name\":\"".$payee_real_name."\"," .
			"    \"remark\":\"转账备注\"," .
			"    \"ext_param\":\"{\\\"order_title\\\":\\\"用户提现\\\"}\"" .
			"  }");
			$result = $aop->execute ( $request); 
			$resultCode = $result->alipay_fund_trans_toaccount_transfer_response->code;
			$resultMsg  = $result->alipay_fund_trans_toaccount_transfer_response->sub_msg;
		    if(!empty($resultCode)&&$resultCode == 10000){
				$msg['success'] = 1;
				$msg['text']    = '支付宝转账成功';
				return $msg;
			}else{
				$msg['success'] = 2;
				$msg['text']    = $resultMsg;
				return $msg;
			}
		}
  }
}
?>