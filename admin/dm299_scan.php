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
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');
define('IN_ECS', true);
define('DM299_SCAN_VERSION', '1.0.0');
@set_time_limit(0);
@ini_set("output_buffering","On");


require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/scan/cls_scan.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/scan/lib_scan.php');

$act = (!empty($_REQUEST['act']) ? $_REQUEST['act'] : 'scanset');
$scan = new scan();

// 设置显示页面
if ( $act == 'setting' || $act == 'scanset' ) 
{
	assign_query_info();
	$smarty->assign('config', $scan->config);
	$smarty->assign('act', $act);
	$smarty->display('dm299_scan.dwt');
}

// 保存配置
elseif( $act == 'setting_do' ) 
{
	$config = $scan->config;
	$config['authcode']  = trim($_REQUEST['authcode']) ? trim($_REQUEST['authcode']) : '';
	$scan->set_config($config);
	$link[] = array('href' => 'dm299_scan.php?act=setting', 'text' => '扫描插件');
	sys_msg ( '配置更新成功', 0, $link );
}

// 开始扫描
elseif ($act == 'scan') 
{
	$config = $scan->config;
	if( $_POST['uptime'] ) {
		$config['uptime']  = intval($_REQUEST['uptime']) ? intval($_REQUEST['uptime']) : 0;
		$config['ext']    = trim($_REQUEST['ext']) ? trim($_REQUEST['ext']) : '';
		$scan->set_config($config);
	}

	$stime=time()+microtime();
	$heads = file_get_contents(ROOT_PATH . '/' . ADMIN_PATH . '/templates/dm299_scan_php.dwt');
	$scan->flush_echo($heads);				//打印网页头
	$scan->read($stime);
}

// 检查版本
elseif ($act == 'chk_dm299_version')
{
	$version = $scan->version();
	echo ("当前版本：".DM299_SCAN_VERSION."<br/>");
	echo ($version['content']);
	exit;
}

// 信任文件
elseif( $act=='set_security_file' ) 
{
	$dirfile = trim($_GET['file']);
	if(  $dirfile ) {
		echo($scan->set_security_file($dirfile));	
	}
	else {
		die(json_encode(array("code"=>0,'data'=>"文件不存在")));
	}
}

// 查看文件内容
elseif( $act=='get_file_content' ) 
{
	$dirfile = trim($_GET['file']);
    if (file_exists($dirfile)) {
		highlight_file($dirfile);
    }else{
		die("no");
    }
}


?>
