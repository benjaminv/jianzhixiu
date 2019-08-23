<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * 版权所有 2005-2011 月梦网络，并保留所有权利。
 * 淘宝地址: http://dm299.taobao.com  开发QQ:124861234   dm299
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: auto_manage.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/auto_order.php';
if (file_exists($cron_lang))
{
    global $_LANG;

    include_once($cron_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'auto_order_desc';

    /* 作者 */
    $modules[$i]['author']  = 'ONLINE TEAM';

    /* 网址 */
    $modules[$i]['website'] = '#';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'auto_order_count', 'type' => 'select', 'value' => '5'),
    );

    return;
}
$time = gmtime();
$limit = !empty($cron['auto_order_count']) ? $cron['auto_order_count'] : 5;


// 自动确认收货


$okgoods_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='okgoods_time'");
$okg_time = gmtime()-$okgoods_time*24*60*60;//    $okgoods_time - (local_date('d',gmtime()) - local_date('d',$okg_id['add_time']));

//$okg = $GLOBALS['db']->getAll("select order_id, add_time from " . $GLOBALS['ecs']->table('order_info') . " where shipping_time < '$okg_time' and shipping_status = 1 and order_status = 5 and pay_status = 2  LIMIT $limit ");

$sql = 'SELECT oi.order_id, oi.add_time
			FROM '.$GLOBALS['ecs']->table('order_info'). ' oi 
			LEFT JOIN '.$GLOBALS['ecs']->table('back_order').' b ON oi.order_id=b.order_id and b.status_back < 6 AND b.status_back != 3
			WHERE '."oi.shipping_time < '$okg_time' and oi.shipping_status = 1 and oi.order_status in(1,5,6) and ( b.back_id is null or b.back_id = '') ";
$okg = $GLOBALS['db']->getAll($sql);

foreach($okg as $okg_id)
{
    $db->query("update " . $ecs->table('order_info') . " set shipping_status = 2, shipping_time_end = " . gmtime() . "  where order_id = " . $okg_id['order_id']);
}

?>