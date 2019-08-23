<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$begin = (isset($_REQUEST['date_begin'])&&$_REQUEST['date_begin'])?strtotime($_REQUEST['date_begin']):0;

$end = (isset($_REQUEST['date_end'])&&$_REQUEST['date_end'])?strtotime($_REQUEST['date_end']):time();


// 商城的总二维码数量
$total_code_count = $db->getOne('select count(id) from ecs_security_codes'); 

$where = ' where user_id > 0 ';

if($begin){

	$where .= ' and first_scantime >'.$begin;
}

$where .= ' and first_scantime <='.$end;

$info = $db->getRow('select count(id) as first,sum(scan_num) as total from ecs_security_codes'.$where);


$smarty->assign('total_code',$total_code_count);
$smarty->assign('info',$info);
$smarty->assign('date_begin',$_REQUEST['date_begin']);
$smarty->assign('date_end',$_REQUEST['date_end']);

$smarty->display('total_code_number.htm');
