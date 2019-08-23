<?php
/**
 *  红包/企业转账提现
 * ============================================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: wxhongbao.php 17217 2011-01-19 06:29:08Z liubo $
*/


	
class Common_util_pub
{
	
	
	
public function sendhongbaoto($arr){
 $weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );	

if ($arr['present_manner'] == 'hongbao'){
//$comm = new Common_util_pub();
$data['mch_id'] = $weixinconfig['partnerId'];
$data['mch_billno'] = 'jzx1225425402'.date("Ymd",time()).date("His",time()).rand(1111,9999);
$data['nonce_str'] = self::createNoncestr();
$data['re_openid'] = $arr['openid'];
$data['wxappid'] = $weixinconfig['appid'] ;
$data['nick_name'] = $arr['hbname'];
$data['send_name'] = $arr['hbname'];
$data['total_amount'] = $arr['fee']*100;
$data['min_value'] = $arr['fee']*100;
$data['max_value'] = $arr['fee']*100;
$data['total_num'] = 1;
$data['client_ip'] = $_SERVER['REMOTE_ADDR'];
$data['act_name'] = '余额提现';
$data['remark'] = '如有疑问，联系在线客服';
$data['wishing'] = $arr['body'];
if(!$data['re_openid']) {	
	 $rearr['return_msg']='缺少用户openid';
	 return $rearr;
}
$data['sign'] = self::getSign($data);
$xml = self::arrayToXml($data);
//var_dump($xml);
$url ="https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
$re = self::wxHttpsRequestPem($xml,$url);
$rearr = self::xmlToArray($re);

return  $rearr;

}
if($arr['present_manner'] == 'lingqian'){

//$comm = new Common_util_pub();
$data['mchid'] = $weixinconfig['partnerId'];//商户号
$data['partner_trade_no'] = 'QYTX'.$weixinconfig['partnerId'].date("Ymd",time()).date("His",time()).rand(1111,9999);//商户订单号
$data['nonce_str'] = self::createNoncestr();//随机字符
$data['openid'] = $arr['openid'];//用户openid
$data['mch_appid'] = $weixinconfig['appid'];//公众号appid
$data['check_name'] = 'NO_CHECK';//校验用户姓名选项，NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账）OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
$data['re_user_name'] = $arr['name'];//收款人姓名
$data['amount'] = $arr['fee']*100;//金额
$data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];//ip
$data['desc'] ='【'.$arr['hbname'].'】提醒您：'. $arr['body'];//描述
if(!$data['openid']) {	
	 $rearr['return_msg']='缺少用户openid';
	 return $rearr;
}



$data['sign'] = self::getSign($data);
$xml = self::arrayToXml($data);
//var_dump($xml);
$url ="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
$re = self::wxHttpsRequestPem($xml,$url);
$rearr = self::xmlToArray($re);


return  $rearr;
}
	
}	

	function trimString($value)
	{
		$ret = null;
		if (null != $value) 
		{
			$ret = $value;
			if (strlen($ret) == 0) 
			{
				$ret = null;
			}
		}
		return $ret;
	}
	
	/**
	 * 	作用：产生随机字符串，不长于32位
	 */
	public function createNoncestr( $length = 32 ) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		}  
		return $str;
	}
	
	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
		    if($urlencode)
		    {
			   $v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0) 
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	
	/**
	 * 	作用：生成签名
	 */
	public function getSign($Obj)
	{
	 $weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );	
		foreach ($Obj as $k => $v)
		{
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String."&key=".$weixinconfig['partnerKey'];
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}
	
	/**
	 * 	作用：array转xml
	 */
public	function arrayToXml($arr)
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
	
	/**
	 * 	作用：将xml转为array
	 */
	public function xmlToArray($xml)
	{		
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $array_data;
	}



	
	
	
	 public function wxHttpsRequestPem( $vars,$url, $second=30,$aHeader=array()){
	 	
                $ch = curl_init();
                //超时时间
                curl_setopt($ch,CURLOPT_TIMEOUT,$second);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                //这里设置代理，如果有的话
                //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
                //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
 
                //以下两种方式需选择一种
 
                //第一种方法，cert 与 key 分别属于两个.pem文件
                //默认格式为PEM，可以注释
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLCERT,dirname(__FILE__).'/hongbao/apiclient_cert.pem');
                //默认格式为PEM，可以注释
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLKEY,dirname(__FILE__).'/hongbao/apiclient_key.pem');
 
                curl_setopt($ch,CURLOPT_CAINFO,'PEM');
                curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__).'/hongbao/rootca.pem');
 
                //第二种方式，两个文件合成一个.pem文件
                //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
 
                if( count($aHeader) >= 1 ){
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
                }
 
                curl_setopt($ch,CURLOPT_POST, 1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
                $data = curl_exec($ch);
                if($data){
                        curl_close($ch);
                        return $data;
                }
                else { 
                        $error = curl_errno($ch);
                        echo "call faild, errorCode:$error\n"; 
                        curl_close($ch);
                        return false;
                }
        }
	
	
	
	
	
	

}


?>