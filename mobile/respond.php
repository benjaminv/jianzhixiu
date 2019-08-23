<?php

/**
 * ECSHOP 支付响应页面
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');
/* 支付方式代码 */
$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : 'weixin';

//获取首信支付方式
if (empty($pay_code) && !empty($_REQUEST['v_pmode']) && !empty($_REQUEST['v_pstring']))
{
    $pay_code = 'cappay';
}

//获取快钱神州行支付方式
if (empty($pay_code) && ($_REQUEST['ext1'] == 'shenzhou') && ($_REQUEST['ext2'] == 'ecshop'))
{
    $pay_code = 'shenzhou';
}

/* 参数是否为空 */
if (empty($pay_code))
{
    $msg = $_LANG['pay_not_exist'];
}
else
{
    /* 检查code里面有没有问号 */
    if (strpos($pay_code, '?') !== false)
    {
        $arr1 = explode('?', $pay_code);
        $arr2 = explode('=', $arr1[1]);

        $_REQUEST['code']   = $arr1[0];
        $_REQUEST[$arr2[0]] = $arr2[1];
        $_GET['code']       = $arr1[0];
        $_GET[$arr2[0]]     = $arr2[1];
        $pay_code           = $arr1[0];
    }

    /* 判断是否启用 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
    if ($db->getOne($sql) == 0)
    {
        $msg = $_LANG['pay_disabled'];
    }
    else
    {
        $plugin_file = 'includes/modules/payment/' . $pay_code . '.php';

        /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
        if (file_exists($plugin_file))
        {
            /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
            include_once($plugin_file);
            $payment = new $pay_code();
            $msg     = ($payment->respond()) ? $_LANG['pay_success'] : $_LANG['pay_fail'];
            if($_GET['code'] == 'weixin' && $_GET['from'] == 'notify'){
            	echo 'success';exit;
            }
        }
        else
        {
            $msg = $_LANG['pay_not_exist'];
        }
    }
}

//新版拼团新增 S PRINCE
$user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;
$order = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE `user_id` = '$user_id' and pay_time>unix_timestamp(now())-3600*8-120 ORDER BY order_id DESC LIMIT 1");
if($order &&  $order['extension_code']=='extpintuan'){
	$order_id=$order['order_id'];
	$sql = "SELECT * FROM " . $ecs->table('extpintuan_orders') . " WHERE order_id = '$order_id' ";
	$extpintuan = $db->getRow($sql);
	if($extpintuan){
	$pt_id=$extpintuan['pt_id'];
	$follow_user=$extpintuan['follow_user'];
	$url = 'extpintuan.php?act=pt_view&pt_id='.$pt_id.'&u='.$follow_user;
	ecs_header("Location: $url\n");
    }
}
//新版拼团新增 E PRINCE


//云购新增 S PRINCE
$user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;
$order = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE `user_id` = '$user_id' and pay_time>unix_timestamp(now())-3600*8-120 ORDER BY order_id DESC LIMIT 1");
if($order &&  $order['extension_code']=='lucky_buy'){
	$url = 'lucky_buy.php?act=userlist';
	ecs_header("Location: $url\n");
}
//云购新增 E PRINCE

assign_template();
$position = assign_ur_here();
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('helps',      get_shop_help());      // 网店帮助

$smarty->assign('message',    $msg);
$smarty->assign('shop_url',   $ecs->url());

$smarty->display('respond.dwt');

?>