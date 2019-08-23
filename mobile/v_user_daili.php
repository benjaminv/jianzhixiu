<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if($_CFG['is_distrib'] == 0)
{
	show_message('没有开启微信分销服务！','返回首页','index.php'); 
}

if($_SESSION['user_id'] == 0)
{
	ecs_header("Location: ./\n");
    exit;	 
}

$is_distribor = is_distribor($_SESSION['user_id']);
if($is_distribor != 1)
{
	show_message('您还不是分销商！','去首页','index.php');
	exit;
}
if(isset($_REQUEST['act']) && $_REQUEST['act']=='ajax'){
    include('includes/cls_json.php');
    $json   = new JSON;
    $time_start = empty($_REQUEST['time_start'])?0:  ($_REQUEST['time_start']);
    $time_end = empty($_REQUEST['time_end'])?0:  ($_REQUEST['time_end']);

    $day_start=strtotime($time_start."-01 00:00:00");

    $day_end=strtotime($time_end."-29 23:59:59");
    $sql = 'SELECT sum(money) as total FROM ' . $GLOBALS['ecs']->table('affiliate_fenxiao_log') . " WHERE time > '$day_start' and  time < '$day_end' and  user_id = '" . $_SESSION['user_id'] ."'";
    $next_total     = $GLOBALS['db']->getOne($sql);

    $next_total = $next_total > 0 ? $next_total : 0;
    $result['success'] = 1;
    $result['conent'] = "￥".$next_total;
    die($json->encode($result));
}

if (!$smarty->is_cached('v_user_daili.dwt', $cache_id))
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
	$smarty->assign('user_info',get_user_info_by_user_id($_SESSION['user_id'])); 
	$smarty->assign('one_level_user_count',get_user_count($_SESSION['user_id'],1));  //一级会员数量
	$smarty->assign('two_level_user_count',get_user_count($_SESSION['user_id'],2));  //二级会员数量
	$smarty->assign('three_level_user_count',get_user_count($_SESSION['user_id'],3));  //三级会员数量
	$smarty->assign('four_level_user_count',get_user_count($_SESSION['user_id'],4));  //四级会员数量
	$smarty->assign('five_level_user_count',get_user_count($_SESSION['user_id'],5));  //五级会员数量
	$smarty->assign('six_level_user_count',get_user_count($_SESSION['user_id'],6));  //六级会员数量
	
	$smarty->assign('one_user_list',get_distrib_user_daili_info($_SESSION['user_id'],1)); //一级会员信息

	$smarty->assign('user_id',$_SESSION['user_id']);

    $sql = 'SELECT sum(money) as total FROM ' . $GLOBALS['ecs']->table('affiliate_fenxiao_log') . " WHERE user_id = '" . $_SESSION['user_id'] ."'";
    $total     = $GLOBALS['db']->getOne($sql);
    $total = $total > 0 ? $total : 0;

    $day_start=strtotime(date('Y-m-d 00:00:00', strtotime(date('Y-m-01') . ' -1 month'))); // 计算出本月第一天再减一个月
    $day_end=strtotime(date('Y-m-d 23:59:59', strtotime(date('Y-m-01') . ' -1 day'))); // 计算出本月第一天再减一天
    $sql = 'SELECT sum(money) as total FROM ' . $GLOBALS['ecs']->table('affiliate_fenxiao_log') . " WHERE time > '$day_start' and  time < '$day_end' and  user_id = '" . $_SESSION['user_id'] ."'";
    $next_total     = $GLOBALS['db']->getOne($sql);
    $next_total = $next_total > 0 ? $next_total : 0;

    $smarty->assign('total',$total);
    $smarty->assign('next_total',$next_total);
    /* 页面中的动态内容 */
    assign_dynamic('v_user_huiyuan');
}


$smarty->display('v_user_daili.dwt', $cache_id);


?>