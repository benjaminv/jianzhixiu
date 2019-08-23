<?php
//error_reporting(E_ALL);
class Sso{
    //保存例实例在此属性中 
	private static $_instance;
	private  $odooUid;
	private  $erpObj;
    //构造函数声明为private,防止直接创建对象 
	private function __construct(){
        
    }
    //单例方法 
	public static function getInstance(){
        if(!isset(self::$_instance)){
            self::$_instance=new self();
        }
        return self::$_instance;
    }
    //阻止用户复制对象实例 
	private function __clone(){
        trigger_error('禁止克隆' ,E_USER_ERROR);
    }

	public function cashUpdate($updateData){
		$url = OSS_LOGIN."/rest/api/sso/commission/cashupdate";
		$result = $this->curl_post($url, $updateData);
		return json_decode($result,1);
	}

	public function curl_post($url,$data){
		// 初始化curl
		$ch = curl_init();
		// 抓取指定网页
		curl_setopt($ch, CURLOPT_URL, $url);
		// 设置header
		curl_setopt($ch, CURLOPT_HEADER, 0);
		// 要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// post提交方式
		curl_setopt($ch, CURLOPT_POST, 1);
		// 提交的数据
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		// 运行curl
		$data = curl_exec($ch);
		// 关闭
		curl_close($ch);
		return $data;
	}

	public function creditUpdate($updateData){
		$url = OSS_LOGIN."/rest/api/sso/credit/sync";
		$result = $this->curl_post($url, $updateData);
		return json_decode($result,1);
	}

	public function creditGetByUserid($user_id){
		
		$url = OSS_LOGIN."/rest/api/sso/credit/getbyuser";
		$portal_id = $GLOBALS['db']->getOne("SELECT portal_id FROM ".$GLOBALS['ecs']->table('users') ." WHERE user_id=".intval($user_id));
		
		$result = $this->curl_post($url, array('userid'=>$portal_id));
		return json_decode($result,1);
	}
}

$ssoObj = Sso::getInstance();
$GLOBALS['ssoObj'] = $ssoObj;		
