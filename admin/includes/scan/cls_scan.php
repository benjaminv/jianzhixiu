<?php
// +----------------------------------------------------------------------
// | 点迈软件系统 [ DM299 ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2018 苏州点迈软件系统有限公司 [ http://www.dm299.com ]
// +----------------------------------------------------------------------
// | 官方网站：http://dm299.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 作者: 周志华 <124861234@qq.com>
// +----------------------------------------------------------------------


class scan
{

	public $config    = array(); 
	 
	public $api_domain = 'http://scan.dm299.com';
	
	public function __construct()
	{
		$this->config  =require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/scan/config.php');
		$this->domain = $GLOBALS['ecs']->get_domain();
	}

	//  更新配置信息
	public function set_config($config)
	{
		$str ='';
		foreach($config as $key=>$val) 
		{
			$str .= "	'".$key."'=>'".$val."',\r\n";
		}
		$str = '<?php '."\r\n".'return array('."\r\n".$str.');'."\r\n".'?>';
		file_put_contents(ROOT_PATH . '/' . ADMIN_PATH . '/includes/scan/config.php',$str);
		$this->config = $config;
	}


	//  获得最新版本信息
	public function version()
	{
		$json = $this->file_get_contents_curl($this->api_domain."/api.php?act=version&authcode=".$this->config['authcode']);
		return json_decode($json,1);
	}

	//  设置信任文件
	public function set_security_file($filename)
	{
		$json = $this->file_get_contents_curl($this->api_domain."/api.php?act=set_security_file&filename=".$filename."&filetime=".filectime($filename)."&authcode=".$this->config['authcode']);
		return $json;
	}



	// 开始扫描
	public function read($stime)
	{
		dm299_fun_scan(ROOT_PATH,$this->get_ext());
		$etime=time()+microtime();
		$pass_time=sprintf("%.2f", $etime-$stime);//消耗时间
		$this->flush_search(true);
		echo('<script type="text/javascript">$("#endMsg").html("扫描完成！用时：'.$pass_time.'秒");</script>'."\r\n");
		ob_flush();
		flush();
	}

	// 设置扫描文件类型
	public function get_ext()
	{
		$ext = trim($this->config['ext']);
		if( $ext and  $ext!=".*" ) {
			$is_e_ext= explode("|",$ext);
			foreach($is_e_ext as $key=>$value)
					$is_e_ext[$key]=trim(str_replace("?","(.)",$value));
			$is_ext = "(\.".implode("($|\.))|(\.",$is_e_ext)."($|\.))";
		}
		else {
			$is_ext="(.+)";
		}
		return $is_ext;
	}


	// 开始输出调用
	public function flush_echo($data) 
	{
		ob_start();
		ob_implicit_flush(1);
		echo $data;
		ob_end_flush();
	}

	// 实时输出调用 
	public function flush_echos_msg($data) 
	{
		echo('<script type="text/javascript">msg(\''.addslashes($data).'\');</script>'."\r\n");
		ob_flush();
		flush();
	}

	// 文件输出调用
	public function flush_files($data) 
	{
		global $dm299_dubious_num;
		echo('<script type="text/javascript">showmessage("'.$data[0].$data[1].'","'.date('Y-m-d H:i:s',filemtime($data[0].$data[1])).'","'.$data[3].'",'.$data[4].');</script>'."\r\n");
		echo('<script type="text/javascript">$("#mumamun").html("'.$dm299_dubious_num.'");</script>'."\r\n");
		ob_flush();
		flush();
	}

	// 文件搜索数量输出
	public function flush_search($qz=false) 
	{
		global $dm299_search_num;
		$dm299_search_num ++;
		if( $dm299_search_num % 10 ==0 or $qz==true) {
			echo('<script type="text/javascript">$("#filenum").html("'.$dm299_search_num.'");</script>'."\r\n");
			ob_flush();
			flush();
		}
	}


	// 运程连接
	public function file_get_contents_curl($url,$method='GET',$params=''){
		$url .= "&referer_domain=".urlencode($this->domain);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10); 
		//curl_setopt($ch, CURLOPT_REFERER,$this->domain); 
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11'); 
		if( $method=='POST' ) 
		{
			curl_setopt($ch, CURLOPT_POST,true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
		}
		$dxycontent = curl_exec($ch); 
		curl_close($ch);
		return $dxycontent;
	}




}
?>
