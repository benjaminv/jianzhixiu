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


$trojan_features=$filter_file_code=$trusted_file= array();

$dm299_dubious_num = $dm299_search_num = 0;

function dm299_fun_scan($path = './',$is_ext) 
{
	global $trojan_features,$filter_file_code,$trusted_file,$pathtime;

	$json = $GLOBALS['scan']->file_get_contents_curl($GLOBALS['scan']->api_domain."/api.php?act=info&authcode=".$GLOBALS['scan']->config['authcode']);
	$data = json_decode($json,1);
	if( $data['code']== 1 ) {
		$trojan_features  = $data['data']['trojan_features'];
		$filter_file_code = $data['data']['filter_file_code'];
		$trusted_file	  = $data['data']['trusted_file'];
		$pathtime		  = require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/scan/pathtime.php');
		//$path             .= "includes/";
		dm299_fun_scan_dir($path,$is_ext);
	}
	else {
		$GLOBALS['scan']->flush_echos_msg($data['data']);
	}
	return false;
}

/**
	* 扫描
	*
	* @param   $php_code 特征文件库
	* @param   $path     扫描目录
	* @param   $is_ext   文件类型 正则表达式
	* @param   $count    可疑文件数
	* @param   $scanned  已扫描文件数
	* @return  void
*/
function dm299_fun_scan_dir($path,$is_ext)
{
	global $dm299_dubious_num;

	$ignore = array('.', '..' );
	$dh = @opendir( $path );
    while(false!==($file=readdir($dh)))
	{
		if( in_array( $file, $ignore ) ) continue;
		if( is_dir( "$path$file" ) )
		{
			dm299_fun_scan_dir("$path$file/",$is_ext);
			continue;
		} 
		$data = dm299_chk_file($path,$file,$is_ext);
		$GLOBALS['scan']->flush_search();

		if( $data!==true ) {
			$dm299_dubious_num ++;
			$GLOBALS['scan']->flush_files($data);
		}
	}
    closedir( $dh );
}


function dm299_chk_file($path,$file,$is_ext) 
{
	global $trojan_features,$filter_file_code,$trusted_file,$pathtime;

	$current = $path.$file;
	if( isset($trusted_file[filectime($current).md5($current)]) ) return true;

	$zipext = array('rar', 'zip', '7-zip', 'jar', 'tr', 'z', 'cab', 'iso');
	$ext = dm299_scan_ext($file);

	// 压缩文件
	if( in_array( $ext, $zipext ) ) {
		if( filesize($current)>1024*1024*20 ) { // 20M
			return array($path,$file,$ext,'服务器端压缩文件',0);
		}
	}

	// 文件格式
	if(!preg_match("/$is_ext/i",$file)) return true;

	# 木马扫描
	if(is_readable($current))
	{
		$replace=array(" ","\n","\r","\t");

		$content=file_get_contents($current);
		$content= str_replace($replace,"",$content);
		$content= str_replace('eval()',"",$content);

		// 安全文件特殊代码
		if( isset($filter_file_code[$file]) ) {
			foreach($filter_file_code[$file] as $val){
				$content =preg_replace("/$val/i","",$content);
			}
		}

		foreach($trojan_features as $key => $value)
		{
			if(preg_match("/$value/i",$content))
			{
				return array($path,$file,$ext,$key,1);
			}
		}
	}
	# 指定修改时间
	if( $GLOBALS['scan']->config['uptime']>0 ) {
		$tdm =  true;
		foreach($pathtime as $sval) {
			if( stripos($path,$sval)!==false ) {
				$tdm =  false;
				break;
			}
		}
		if( $tdm ) {
			if( filemtime($current) > time()-$GLOBALS['scan']->config['uptime']*3600*24) {
				return array($path,$file,$ext,'指定时间修改',1);
			}
		}
	}
	return true;
}

// 获得文件类型
function dm299_scan_ext($file) {
	$arr = explode('.',$file);
	return end($arr);
}

?>
