<?php
/**
 *代金券使用商品专栏
*/
define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');
/* 载入语言文件 */
//$_REQUEST['act'] = !empty($_REQUEST['act']) ? trim($_REQUEST['act']) : '';
$type_id = !empty($_REQUEST['type_id']) ? intval($_REQUEST['type_id']) : '';
//根据红包类型id获取该红包可以抵扣的商品
$list = get_bonus_goods($type_id);
$smarty->assign('list', $list);
$smarty->assign('page_title', '代金券抵扣专栏');
$smarty->display('bonus_goods.dwt');
?>