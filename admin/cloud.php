<?php

/**
 * DM299  云服务接口
 * ============================================================================
 * 版权所有 2005-2010 苏州点迈软件系统有限告诉，并保留所有权利。
 * 网站地址: http://www.dm299.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: markzhou $
 * $Id: cloud.php 17063 2011-07-25 06:35:46Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/cls_transport.php');
require(ROOT_PATH . 'includes/cls_json.php');

require(ROOT_PATH . 'includes/shopex_json.php');
require(ROOT_PATH . 'data/cloud.php');

$data['api_ver'] = API_VERSION;

if( UPDATE_VERSION>API_VERSION ) {
	$f=ROOT_PATH . 'data/config.php'; 
	file_put_contents($f,str_replace("'API_VERSION', '".API_VERSION."'","'API_VERSION', '".UPDATE_VERSION."'",file_get_contents($f)));
	$data['api_ver'] = UPDATE_VERSION;
}

$data['version'] = VERSION;
$data['patch'] = file_get_contents(ROOT_PATH.ADMIN_PATH."/patch_num");
$data['ecs_lang'] = $_CFG['lang'];
$data['release'] = RELEASE;
$data['charset'] = strtoupper(EC_CHARSET);
$data['certificate_id'] = $_CFG['certificate_id'];
$data['token'] = md5($_CFG['token']);
$data['certi'] = $_CFG['certi'];
$data['php_ver'] = PHP_VERSION;
$data['mysql_ver'] = $db->version();
$data['shop_url'] = urlencode($ecs->url());
$data['admin_url'] = urlencode($ecs->url().ADMIN_PATH);
$data['sess_id'] = $GLOBALS['sess']->get_session_id();
$data['stamp'] = time();
$data['ent_id'] = $_CFG['ent_id'];
$data['ent_ac'] = $_CFG['ent_ac'];
$data['ent_sign'] = $_CFG['ent_sign'];
$data['ent_email'] = $_CFG['ent_email'];
$act = !empty($_REQUEST['act']) ? $_REQUEST['act'] :  'index';

$must = array('version','ecs_lang','charset','patch','stamp','api_ver');

$api_data = read_static_cache('cloud_remind');
if($api_data === false || API_TIME < date('Y-m-d H:i:s',time()-43200))
{
	$t = new transport('-1',5);

	$apiget = "ver=$data[api_ver]&charset=$data[charset]&url=$data[shop_url]&admin_url=$data[admin_url]";
	if($act == 'close_remind'){
		$remind_id=$_REQUEST['remind_id'];
		$apiget = "ver=$data[version]&charset=$data[charset]&url=$data[shop_url]&admin_url=$data[admin_url]&remind_id=$remind_id";
	}
	$api_comment = $t->request('http://www.dm299.com/cloud.php', $apiget);
	$api_str=    $api_comment["body"];
	$json = new Services_JSON;
	$api_arr = @$json->decode($api_str,1);
	if(!empty($api_str))
	{
		if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash'])
		{

			$api_arr['content'] = urldecode($api_arr['content']);
			$message =explode('|',$api_arr['content']);
			$count_message = count($message);
			if( $count_message >0 and $data['charset'] == 'UTF-8') {
				$api_arr['content'] ='';
			}

			for($i=0; $i<$count_message; $i++) {
				if($message[$i]{0}.$message[$i]{1}==='no' ) {
					$f=ROOT_PATH . 'data/config.php'; 
					file_put_contents($f,str_replace("'API_TIME', '".API_TIME."'","'API_TIME', '".$message[$i]."'",file_get_contents($f)));
					break;
				}
				 $api_arr['content'] .='<li  class="cloud_close">'.$message[$i].'<img onclick="cloud_close('.$message[$i+1].')" src="images/no.gif"></li>';
				$i++;
			}	
			if ($data['charset'] != 'UTF-8')
			{
				$api_arr['content'] = ecs_iconv('UTF-8',$data['charset'],$api_arr['content']);
			}
			$f=ROOT_PATH . 'data/config.php'; 
			file_put_contents($f,str_replace("'API_TIME', '".API_TIME."'","'API_TIME', '".date('Y-m-d H:i:s',time())."'",file_get_contents($f)));
			write_static_cache('cloud_remind', $api_arr);
			make_json_result($api_arr['content']);
		}
		else
		{
			make_json_result('0');
		}
	}
	else
	{
	  make_json_result('0');
	}
}
?>