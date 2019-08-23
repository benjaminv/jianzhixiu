<?php
/**
 * 微信退款插件
 * $Author: PRINCE $
 * 2016-03-25 09:29:08Z PRINCE QQ 120029121 
 */

class WxPayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
