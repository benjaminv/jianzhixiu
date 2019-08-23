<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

if($_REQUEST['act'] ==  'check_code' ){

   $rel = $db->getAll('select * from ecs_security_codes where code_number="'.$_REQUEST['code'].'"');
   if($rel[0]){
    
        header('location:sao_ma.php?bianhao='.$_REQUEST['code'].'&jifen='.$rel[0]['points']);
 
   }else{

      header('location:sao_yi_sao.php?msg=该防伪码不存在，请更换防伪码');
   }

}

$weixin_info = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('weixin_config'));

if($weixin_info['title'] && $weixin_info['appid'] && $weixin_info['appsecret']){

	require_once "wxjs/jssdk.php";

	$jssdk = new JSSDK($weixin_info['appid'], $weixin_info['appsecret']);
	$signPackage = $jssdk->GetSignPackage();
}


 $smarty->assign('signPackage', $signPackage);

 $smarty->display('sao_yi_sao.dwt');






