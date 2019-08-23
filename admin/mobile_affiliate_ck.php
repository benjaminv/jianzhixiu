<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://www.dm299.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: derek $
 * $Id: mobile_affiliate_ck.php 17217 2011-01-19 06:29:08Z derek $
 */
  
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

admin_priv('affiliate_ck');
$timestamp = time();

$affiliate = get_affiliate();
$separate_on = $affiliate['on'];



/*------------------------------------------------------ */
//-- 分成页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
   
    $logdb = get_affiliate_ck_new($_LANG['separate_info']);
    
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['affiliate_ck']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    if (!empty($_GET['auid']))
    {
        $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$_GET[auid]"));
    }
    $smarty->assign('supplier_list',get_supplier_list());

    if(isset($_REQUEST['status'])){

        switch ($_REQUEST['status']) {
            case '0':
                $status = '1';
                break;
            case '1':
                $status = '2';
                break;
            case '2':
                $status = '3';
                break;
            
        }
    }else{
        $status =0;
    }
    $smarty->assign('status',$status);
    //echo "<pre>";print_r($logdb);exit;
    assign_query_info();
    $smarty->display('mobile_affiliate_ck_list.htm');
}
/*------------------------------------------------------ */
//-- 导出订单
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'excel_shuju')
{
   
    $logdb = get_affiliate_ck_new1($_LANG['separate_info']);
     // 导出订单
    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=orderexcel.xls");

    $data = "<table border='1' style='center'>
            <tr>
                 <th>".iconv('utf-8', 'gbk','订单号')."</th>
                 <th>".iconv('utf-8', 'gbk','供货商家')."</th>
                 <th>".iconv('utf-8', 'gbk','下单人')."</th>
                 <th>".iconv('utf-8', 'gbk','下单时间')."</th>
                 <th>".iconv('utf-8', 'gbk','订单状态')."</th>
                 <th>".iconv('utf-8', 'gbk','操作状态')."</th>
                 <th>".iconv('utf-8', 'gbk','操作信息')."</th>
            </tr>";
   
    foreach ($logdb as  $val) {
        
        if($val['is_separate']){
            $state = '确认收货';
            $separate = '已分成';
        }else{
            $state = '已确认';
            $separate = '未分成';
        }
        $data .="
            <tr>
                <td>".iconv('utf-8', 'gbk',$val['order_sn'])."&nbsp</td>
                <td>".iconv('utf-8', 'gbk','网站自营')."</td>
                <td>".iconv('utf-8', 'gbk',$val['user_name'])."</td>
                <td>".iconv('utf-8', 'gbk',$val['add_time'])."</td>
                <td>".iconv('utf-8', 'gbk',$state)."</td>
                <td>".iconv('utf-8', 'gbk',$separate)."</td>
                <td>".iconv('utf-8', 'gbk',$val['info'])."</td>
            </tr>";
    
    }
    $data .='</table>';

    echo $data;
   
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_affiliate_ck_new($_LANG['separate_info']);
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mobile_affiliate_ck_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
/*
    取消分成，不再能对该订单进行分成
*/
elseif ($_REQUEST['act'] == 'del')
{
    $oid = (int)$_REQUEST['oid'];
    $stat = $db->getOne("SELECT is_separate FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$oid'");
    if (empty($stat))
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
               " SET is_separate = 2" .
               " WHERE order_id = '$oid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'mobile_affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*
    撤销某次分成，将已分成的收回来
*/
elseif ($_REQUEST['act'] == 'rollback')
{
    $oid = (int)$_REQUEST['oid'];
    $log = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') . " WHERE order_id = '$oid'");

    foreach($log as $stat) 
    {

        $flag = $stat['separate_type'] == 1 ? -2 : -1;  //推荐订单分成  //推荐注册分成
        $sql = "UPDATE " . $GLOBALS['ecs']->table('affiliate_log') ." SET separate_type = '$flag' WHERE log_id = '".$stat['log_id']."'";
        $db->query($sql);
        //撤销分成，记录日志
        write_affiliate_log($stat['order_id'], $stat['user_id'], $stat['user_name'], -$stat['money'], $flag,$_LANG['order_cancel_separate'],0,0);
        //撤销分成扣除余额 yhy2019/5/18
        log_account_change($stat['user_id'], -$stat['money'], 0, 0, 0, '订单id'.$stat['order_id']."分成撤销");
    }
    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET is_separate = 2 WHERE order_id = '" . $oid . "'";
    $db->query($sql);
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'mobile_affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*
    分成
*/
elseif ($_REQUEST['act'] == 'separate')
{
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $affiliate = unserialize($GLOBALS['_CFG_MOBILE']['affiliate']);
   
    empty($affiliate) && $affiliate = array();

    $separate_by = $affiliate['config']['separate_by'];
    $oid = (int)$_REQUEST['oid'];
    //获取订单分成金额
    /*  $split_money = get_split_money_by_orderid($oid);

        $row = $db->getRow("SELECT o.order_sn,u.parent_id, o.is_separate,(o.goods_amount - o.discount) AS goods_amount, o.user_id FROM " . $GLOBALS['ecs']->table('order_info') . " o"." LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id"." WHERE order_id = '$oid'");


        $order_sn = $row['order_sn'];
        $num = count($affiliate['item']);
        for ($i=0; $i < $num; $i++)
        {
            $affiliate['item'][$i]['level_money'] = (float)$affiliate['item'][$i]['level_money'];
            if($affiliate['config']['level_money_all']==100 )
            {
                $setmoney = $split_money;
            }
            else 
            {
                if ($affiliate['item'][$i]['level_money'])
                {
                    $affiliate['item'][$i]['level_money'] /= 100;
                }
                $setmoney = round($split_money * $affiliate['item'][$i]['level_money'], 2);
            }
            $row = $db->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
                            " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                            " WHERE o.user_id = '$row[user_id]'"
                    );
            $up_uid = $row['user_id'];
            if (empty($up_uid) || empty($row['user_name']))
            {
                break;
            }
            else
            {
                $info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, 0);
                push_user_msg($up_uid,$order_sn,$setmoney);
                $level = $i+1;
                write_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $separate_by,$_LANG['order_separate'],$level);
            }
            $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') ." SET is_separate = 1" ." WHERE order_id = '$oid'";
            $db->query($sql);
        }
      //个人购买增加分成
      
       $separate_personal = $affiliate['config']['ex_fenxiao_personal'];
        $personal_lever_money = $affiliate['config']['personal_lever_money'];
        $level_register_up = (float)$affiliate['config']['level_register_up'];
        if ($separate_personal > 0){
                $personal_data = $db->getRow("SELECT o.user_id,u.user_name,u.rank_points,u.is_fenxiao FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                        " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id".
                        " WHERE order_id = '$oid'");
                $personal_pay_money = $db->getOne("SELECT sum(goods_amount) FROM " . $GLOBALS['ecs']->table('order_info')." where user_id = ".$personal_data['user_id']);
                //消费金额小于设置的最少消费金额时，个人分成 0
                if ($personal_pay_money < $personal_lever_money){
                    $affiliate['config']['level_money_personal'] = 0;
                    $affiliate['config']['level_point_personal'] = 0;
                }
                if($personal_data['is_fenxiao'] == 1){
                    $personalMoney = round($split_money * $affiliate['config']['level_money_personal']*0.01, 2);
                    $personalPoint = round($point * $affiliate['config']['level_point_personal']*0.01, 0);
                    $info = sprintf($_LANG['separate_info'], $order_sn, $personalMoney, $personalPoint);
                    log_account_change($personal_data['user_id'], $personalMoney, 0, $personalPoint, 0, $info);
                    push_user_msg($personal_data['user_id'],$order_sn,$personalMoney);
                    write_affiliate_log($oid, $personal_data['user_id'] , $personal_data['user_name'], $personalMoney, $personalPoint, $separate_by,$separate_personal);
                }else{
                        //如果不是分销商，自己的分成给自己的上级
                        $personalMoney = round($split_money * $affiliate['config']['level_money_personal']*0.01, 2);
                        $personalPoint = round($point * $affiliate['config']['level_point_personal']*0.01, 0);                      
                        $info = sprintf($_LANG['separate_info'], $order_sn, $personalMoney, $personalPoint);
                        $personal_id=$personal_data['user_id'];
                        $personal_up_id = $db->getOne("SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') .
                        " WHERE user_id = '$personal_id'");
                        $personal_up_name = $db->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') .
                        " WHERE user_id = '$personal_up_id'");
                        if(!empty($personal_up_id)){
                            log_account_change($personal_up_id, $personalMoney, 0, $personalPoint, 0, $info);
                            push_user_msg($personal_up_id,$order_sn,$personalMoney);
                            write_affiliate_log($oid,$personal_up_id,$personal_up_name,$personalMoney, $personalPoint, $separate_by,$separate_personal);
                        }
                         
                        
                   }
                  $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') ." SET is_separate = 1" ." WHERE order_id = '$oid'";
                 $db->query($sql); 
                   
            }*/
    $sql = "select * from " .
        $GLOBALS['ecs']->table('order_info') .
        " where order_id = '$oid' AND bonus=0 AND bonus_id=0 ";//使用红包的订单不参与分成
    $value = $GLOBALS['db']->GetRow($sql);
    if(!isset($value) || empty($value)){
        $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'mobile_affiliate_ck.php?act=list');
        sys_msg('订单不存在或者不能分成', 0 ,$links);
        exit;
    }
    //获取不同商品不同会员等级价格
    $sql = "SELECT og.*,u.parent_id,pu.user_name as personal_up_name , pu.user_rank, ur.rank_id, IFNULL(mp.user_price * og.goods_number, ur.discount * og.goods_number * og.goods_price / 100) AS price, ur.rank_name, ur.discount 
            FROM " .$GLOBALS['ecs']->table('order_goods'). " AS og ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('order_info').
        " AS oi ON og.order_id = oi.order_id ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('users').
        " AS u ON u.user_id = oi.user_id ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('users').
        " AS pu ON u.parent_id = pu.user_id ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('user_rank').
        " AS ur ON ur.rank_id = pu.user_rank ".
        'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
        "ON mp.goods_id = og.goods_id AND mp.user_rank = ur.rank_id " .
        " WHERE  oi.order_id = '$value[order_id]' ".$sqladd;
    $order_goods_list = $GLOBALS['db']->GetAll($sql);
    $order_goods_total=0;
    foreach ($order_goods_list as $key => $val){
        $order_goods_total +=$val['price'];
    }
    $sl_rate=sl_rate();
    //分成金额
    $personalMoney=($value['goods_amount']-$order_goods_total)*(1-$sl_rate);
    $info = sprintf($_LANG['separate_info'],$value['order_sn'], $personalMoney, 0);

    log_account_change($order_goods_list[0]['parent_id'], $personalMoney, 0, 0, 0, $value['order_sn']."获取提成");
    push_user_msg($order_goods_list[0]['parent_id'],$value['order_sn'],$personalMoney);
    write_affiliate_log($value['order_id'],$order_goods_list[0]['parent_id'],$order_goods_list[0]['personal_up_name'],$personalMoney, 0,  $value['order_sn']."获取提成",0);
    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') ." SET is_separate = 1" ." WHERE order_id = '$oid'";
    $db->query($sql);
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'mobile_affiliate_ck.php?act=list');
    
     $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : "/mobile/";
    $autoUrl = str_replace($_SERVER['REQUEST_URI'],"",$GLOBALS['ecs']->url());
    @file_get_contents($autoUrl."/weixin/auto_do.php?type=1&is_affiliate=1");
    
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*
 *  批量分成
 */
elseif ($_REQUEST['act'] == 'pi_liang_fen_cheng')
{
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $affiliate = unserialize($GLOBALS['_CFG_MOBILE']['affiliate']);
   
    
    $orderids = $_REQUEST['order_id'];


    empty($affiliate) && $affiliate = array();

    $separate_by = $affiliate['config']['separate_by'];

    foreach ( $orderids  as $key => $oid) {



        $sql = "select * from " .
        $GLOBALS['ecs']->table('order_info') .
        " where order_id = '$oid' AND bonus=0 AND bonus_id=0 ";//使用红包的订单不参与分成
        $value = $GLOBALS['db']->GetRow($sql);

        $is_separate =  $GLOBALS['db']->getOne('select is_separate from ecs_order_info where order_id="'.$oid.'"');  // 判断是否已经分销过
        if($value && ($is_separate == 0) ){
           
            //获取不同商品不同会员等级价格
            $sql = "SELECT og.*,u.parent_id,pu.user_name as personal_up_name , pu.user_rank, ur.rank_id, IFNULL(mp.user_price * og.goods_number, ur.discount * og.goods_number * og.goods_price / 100) AS price, ur.rank_name, ur.discount 
                    FROM " .$GLOBALS['ecs']->table('order_goods'). " AS og ".
                " LEFT JOIN " .$GLOBALS['ecs']->table('order_info').
                " AS oi ON og.order_id = oi.order_id ".
                " LEFT JOIN " .$GLOBALS['ecs']->table('users').
                " AS u ON u.user_id = oi.user_id ".
                " LEFT JOIN " .$GLOBALS['ecs']->table('users').
                " AS pu ON u.parent_id = pu.user_id ".
                " LEFT JOIN " .$GLOBALS['ecs']->table('user_rank').
                " AS ur ON ur.rank_id = pu.user_rank ".
                'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = og.goods_id AND mp.user_rank = ur.rank_id " .
                " WHERE  oi.order_id = '$value[order_id]' ".$sqladd;
            $order_goods_list = $GLOBALS['db']->GetAll($sql);
            $order_goods_total=0;
            foreach ($order_goods_list as $key => $val){
                $order_goods_total +=$val['price'];
            }
            $sl_rate=sl_rate();
            //分成金额
            $personalMoney=($value['goods_amount']-$order_goods_total)*(1-$sl_rate);
            $info = sprintf($_LANG['separate_info'],$value['order_sn'], $personalMoney, 0);

            log_account_change($order_goods_list[0]['parent_id'], $personalMoney, 0, 0, 0, $value['order_sn']."获取提成");
            push_user_msg($order_goods_list[0]['parent_id'],$value['order_sn'],$personalMoney);
            write_affiliate_log($value['order_id'],$order_goods_list[0]['parent_id'],$order_goods_list[0]['personal_up_name'],$personalMoney, 0,  $value['order_sn']."获取提成",0);
            $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') ." SET is_separate = 1" ." WHERE order_id = '$oid'";
            $db->query($sql);
           
             $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : "/mobile/";
            $autoUrl = str_replace($_SERVER['REQUEST_URI'],"",$GLOBALS['ecs']->url());
            @file_get_contents($autoUrl."/weixin/auto_do.php?type=1&is_affiliate=1");
            
        }
    } 
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'mobile_affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
    
}



//获取税率
function sl_rate()
{
    $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'sl_status'";
    $sl_status = $GLOBALS['db']->GetOne($sql);
    $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'sl_rate'";
    $sl_rate = $GLOBALS['db']->GetOne($sql);
    $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'sl_time_start'";
    $sl_time_start = $GLOBALS['db']->GetOne($sql);
    /* $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'sl_time_end'";
     $sl_time_end = $GLOBALS['db']->GetOne($sql);*/
    //   $start_time=strtotime($sl_time_start." 00:00:00");
    // $end_time=strtotime($sl_time_end." 23:59:59");
    $now_time=time();
    if($sl_status==1){
        return $sl_rate;
    }
    else{
        return 0;
    }
}
function get_affiliate_ck()
{

    $affiliate = unserialize($GLOBALS['_CFG_MOBILE']['affiliate']);
    empty($affiliate) && $affiliate = array();
    $separate_by = $affiliate['config']['separate_by'];

    $sqladd = '';
    if (isset($_REQUEST['status']))
    {
        $sqladd = ' AND o.is_separate = ' . (int)$_REQUEST['status'];
        $filter['status'] = (int)$_REQUEST['status'];
    }
    if (isset($_REQUEST['order_sn']))
    {
        $sqladd = ' AND o.order_sn LIKE \'%' . trim($_REQUEST['order_sn']) . '%\'';
        $filter['order_sn'] = $_REQUEST['order_sn'];
    }
    if (isset($_GET['auid']))
    {
        $sqladd = ' AND o.user_id=' . $_GET['auid'];
    }

    if($GLOBALS['_CFG_MOBILE']['is_add_distrib'] == 0)
    {
        $sqladd = ' AND o.supplier_id = 0 ';
    }
    else
    {
        if(isset($_REQUEST['supplier_id']))
        {
            $sqladd = ' AND o.supplier_id = ' . $_REQUEST['supplier_id'];
        }
    }


    if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
    {
        //按订单分成
        $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('order_info')." AS o," . $GLOBALS['ecs']->table('users') . " as u WHERE o.pay_status = 2 AND o.user_id = u.user_id $sqladd ";
    }
    else
    {
        //按商品分成
        $sql = "select count(*) from ".
            "(select o.order_id,o.user_id,o.add_time,o.order_status,".
            "sum(split_money*goods_number) as total_money,u.user_name,o.is_separate ".
            "from " . $GLOBALS['ecs']->table('order_info') . " as o ," .
            $GLOBALS['ecs']->table('order_goods') . " as b," .
            $GLOBALS['ecs']->table('users') .
            " as u where o.pay_status = 2 and o.order_id = b.order_id ".
            "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab" .
            " where total_money > 0";
    }

    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
    {
        $sql = "SELECT order_sn,is_separate,order_id,o.user_id,add_time,order_status,supplier_id,u.user_name FROM ".$GLOBALS['ecs']->table('order_info')." AS o," . $GLOBALS['ecs']->table('users') . " as u WHERE o.pay_status = 2 AND o.user_id = u.user_id $sqladd ORDER BY order_id DESC LIMIT " . $filter['start'] . ",$filter[page_size]";
    }
    else
    {
        $sql = "select special_rank,order_sn,is_separate,order_id,user_id,add_time,
        order_status,supplier_id,user_name from " .
            "(select r.special_rank,o.order_id,o.order_sn,o.user_id,o.add_time,o.order_status,".
            "o.supplier_id,sum(split_money*goods_number) as total_money," .
            "o.is_separate,u.user_name from " .
            $GLOBALS['ecs']->table('order_info') . " as o ," .
            $GLOBALS['ecs']->table('order_goods') . " as b," .
            $GLOBALS['ecs']->table('users') . " as u ".
            'LEFT JOIN ' .  $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
            "ON u.user_rank =  r.rank_id " .
            " where o.pay_status = 2 and o.order_id = b.order_id  " .
            "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab " .
            " where total_money > 0 and (special_rank = 0 or special_rank is null) ORDER BY order_id DESC" .
            " LIMIT " . $filter['start'] . ",$filter[page_size]";
    }
    $query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
        $info = get_all_affiliate_log($rt['order_id']);
        $rt['add_time'] = local_date("Y-m-d",$rt['add_time']);
        $rt['info'] = $info['info'];
        $rt['log_id'] = $info['log_id'];
        if($info['separate_type'] == -1 || $info['separate_type'] == -2)
        {
            //已被撤销
            $rt['is_separate'] = 3;
            $rt['info'] = "<s>" . $rt['info'] . "</s>";
        }
        $rt['supplier'] = get_supplier($rt['supplier_id']);
        $logdb[] = $rt;
    }
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
function get_affiliate_ck_new($separate_info)
{
    $affiliate = unserialize($GLOBALS['_CFG_MOBILE']['affiliate']);
    empty($affiliate) && $affiliate = array();
    $separate_by = $affiliate['config']['separate_by'];
    
    //订单如果未使用红包，或者使用的红包支持分销 edit yhy 2019/5/29
    $sqladd = ' AND ((o.bonus=0 AND o.bonus_id=0) OR (o.bonus>0 AND o.bonus_id>0 AND t.is_distribute=1)) ';//使用红包的订单不参与分成
    //$sqladd = '';
    if (isset($_REQUEST['status']))
    {
        $sqladd .= ' AND o.is_separate = ' . (int)$_REQUEST['status'];
        $filter['status'] = (int)$_REQUEST['status'];
    }
    if (isset($_REQUEST['order_sn']) && !empty($_REQUEST['order_sn']))
    {
        $sqladd .= ' AND o.order_sn LIKE \'%' . trim($_REQUEST['order_sn']) . '%\'';
        $filter['order_sn'] = $_REQUEST['order_sn'];
    }
    //if (isset($_GET['auid']))
    //{
        //$sqladd = ' AND o.user_id=' . $_GET['auid'];
    //}

    $sqladd .= ' AND u.parent_id > 0 ';

    //if($GLOBALS['_CFG_MOBILE']['is_add_distrib'] == 0)
    //{
        //$sqladd = ' AND o.supplier_id = 0 '; 
    //}
    //else
    //{
        //if(isset($_REQUEST['supplier_id']))
        //{
            //$sqladd = ' AND o.supplier_id = ' . $_REQUEST['supplier_id']; 
        //}  
    //}
    
    
    if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
    {
        //按订单分成
        $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('order_info')." AS o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . $GLOBALS['ecs']->table('users') . " as u WHERE o.pay_status = 2 AND o.user_id = u.user_id  $sqladd ";
    }
    else
    {
        //按商品分成
        $sql = "select count(*) from ".
        "(select o.order_id,o.user_id,o.add_time,o.order_status,".
        "sum(split_money*goods_number) as total_money,u.user_name,o.is_separate ".
        "from " . $GLOBALS['ecs']->table('order_info') . " as o  LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . 
        $GLOBALS['ecs']->table('order_goods') . " as b," . 
        $GLOBALS['ecs']->table('users') ." as u".
        " where o.pay_status = 2 and o.order_id = b.order_id ".
        "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab" .
        " where total_money > 0 ";
    }
    
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);
    
    if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
    {
        $sql = "SELECT order_sn,is_separate,order_id,o.user_id,add_time,order_status,supplier_id,u.user_name FROM ".$GLOBALS['ecs']->table('order_info')." AS o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . $GLOBALS['ecs']->table('users') . " as u WHERE o.pay_status = 2 AND o.user_id = u.user_id  $sqladd ORDER BY order_id DESC LIMIT " . $filter['start'] . ",$filter[page_size]";
    }
    else
    {
        $sql = "select special_rank,order_sn,is_separate,order_id,user_id,add_time,
        order_status,supplier_id,user_name from " .
        "(select r.special_rank,o.order_id,o.order_sn,o.user_id,o.add_time,o.order_status,".
        "o.supplier_id,sum(split_money*goods_number) as total_money," .
        "o.is_separate,u.user_name from " . 
        $GLOBALS['ecs']->table('order_info') . " as o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . 
        $GLOBALS['ecs']->table('order_goods') . " as b," .
        $GLOBALS['ecs']->table('users') . " as u LEFT JOIN ".$GLOBALS['ecs']->table('user_rank') ." AS r ON u.user_rank =  r.rank_id ".
        " where o.pay_status = 2 and o.order_id = b.order_id  " .
        "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab " . 
        " where total_money > 0 and (special_rank = 0 or special_rank is null) ORDER BY order_id DESC" .
        " LIMIT " . $filter['start'] . ",$filter[page_size]";
    }
    //echo $sql;
    $query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
        $info = get_all_affiliate_log($rt['order_id']);
        $rt['add_time'] = local_date("Y-m-d",$rt['add_time']);
        $rt['info'] = $info['info'];
        $rt['log_id'] = $info['log_id'];
        if($info['separate_type'] == -1 || $info['separate_type'] == -2)
        {
            //已被撤销
            $rt['is_separate'] = 3;
            $rt['info'] = "<s>" . $rt['info'] . "</s>";
        }
        $rt['supplier'] = get_supplier($rt['supplier_id']);

        if(empty($rt['info']))
        {
            $rt['info']=affiliate_order_money($rt['order_id'],$separate_info);

        }
        $logdb[] = $rt;
    }
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
function affiliate_order_money($oid,$separate_info)
{
    $sql = "select * from " .
        $GLOBALS['ecs']->table('order_info') .
        " where order_id = '$oid' ";
    $value = $GLOBALS['db']->GetRow($sql);

    //获取不同商品不同会员等级价格
    $sql = "SELECT og.*,u.parent_id,pu.user_name as personal_up_name , pu.user_rank, ur.rank_id, IFNULL(mp.user_price * og.goods_number, ur.discount * og.goods_number * og.goods_price / 100) AS price, ur.rank_name, ur.discount 
            FROM " .$GLOBALS['ecs']->table('order_goods'). " AS og ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('order_info').
        " AS oi ON og.order_id = oi.order_id ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('users').
        " AS u ON u.user_id = oi.user_id ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('users').
        " AS pu ON u.parent_id = pu.user_id ".
        " LEFT JOIN " .$GLOBALS['ecs']->table('user_rank').
        " AS ur ON ur.rank_id = pu.user_rank ".
        'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
        "ON mp.goods_id = og.goods_id AND mp.user_rank = ur.rank_id " .
        " WHERE  oi.order_id = '$value[order_id]'";
    $order_goods_list = $GLOBALS['db']->GetAll($sql);
    $order_goods_total=0;
    foreach ($order_goods_list as $key => $val){
        $order_goods_total +=$val['price'];
    }
    $sl_rate=sl_rate();
    //分成金额
    $personalMoney=($value['goods_amount']-$order_goods_total)*(1-$sl_rate);
    //$info = sprintf($separate_info,$value['order_sn'], $personalMoney, 0);
    $info .= sprintf($GLOBALS['_LANG']['separate_info2'], $order_goods_list[0]['parent_id'], $order_goods_list[0]['personal_up_name'], $personalMoney);
    return $info;
}
//separate_type 0表示撤销分成 1表示分成
function write_affiliate_log($oid, $uid, $username, $money, $separate_by,$change_desc,$grade = 0,$separate_type = 1)
{
    //error_reporting(E_ALL);
    $time = gmtime();
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('affiliate_log') . "( order_id, user_id, user_name, time, money, separate_type,change_desc)".
                                                              " VALUES ( '$oid', '$uid', '$username', '$time', '$money', '$separate_by','$change_desc')";
    if ($oid)
    {
        $GLOBALS['db']->query($sql);

        if(SSO_USERCENTER){
            //将分销同步订单点登录系统 yhy edit 
            require_once($_SERVER['DOCUMENT_ROOT']. '/includes/lib_sso.php');
            $portal_id = $GLOBALS['db']->getOne("SELECT portal_id FROM ".$GLOBALS['ecs']->table('users') ." WHERE user_id=".intval($uid));
            $order = $GLOBALS['db']->getRow("SELECT order_sn,goods_amount,user_id FROM ".$GLOBALS['ecs']->table('order_info') ." WHERE order_id=".intval($oid));
            $commission_user_id = $GLOBALS['db']->getOne("SELECT portal_id FROM ".$GLOBALS['ecs']->table('users') ." WHERE user_id=".intval($order['user_id']));
            $updateData = array(
                'userid'=>$portal_id,
                'log_type'=>$separate_type?0:1,  //0表示分成，1表示分销撤回
                'order_sn'=>$order['order_sn'],
                'user_name'=>$username,
                'order_type' => 0,
                'log_desc' => $separate_type?"商城订单分销":"商城订单分销撤回",
                'level'=>$grade,//默认为0表示下单自己拿到的提成
                'price'=>$order['goods_amount'],
                'commission_user_id'=>$commission_user_id,
                'nopay_commission'=>$money
            );
            $GLOBALS['ssoObj']->cashUpdate($updateData);
        }
        //将分销同步订单点登录系统 yhy end
    }
    
}

//获取某一个订单的分成金额
function get_split_money_by_orderid($order_id)
{
     if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
     {
         $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_money";
         //按订单分成
         $sql = "SELECT " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'";
         $total_fee = $GLOBALS['db']->getOne($sql);
         $split_money = $total_fee*($GLOBALS['_CFG_MOBILE']['distrib_percent']/100);
     }
     else
     {
        //按商品分成
        $sql = "SELECT sum(split_money*goods_number) FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '$order_id'";
        $split_money = $GLOBALS['db']->getOne($sql);
     }
     if($split_money > 0)
     {
         return $split_money; 
     }
     else
     {
         return 0; 
     }
}

//分成后，推送到各个上级分销商微信
function push_user_msg($ecuid,$order_sn,$split_money){
    $type = 1;
    $text = "订单".$order_sn."分成，您得到的分成金额为".$split_money;
    $user = $GLOBALS['db']->getRow("select * from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid='{$ecuid}'");
    if($user && $user['fake_id']){
        $content = array(
            'touser'=>$user['fake_id'],
            'msgtype'=>'text',
            'text'=>array('content'=>$text)
        );
        $content = serialize($content);
        $sendtime = $sendtime ? $sendtime : time();
        $createtime = time();
        $sql = "insert into ".$GLOBALS['ecs']->table('weixin_corn')." 

(`ecuid`,`content`,`createtime`,`sendtime`,`issend`,`sendtype`) 
            value ({$ecuid},'{$content}','{$createtime}','{$sendtime}','0',

{$type})";
        $GLOBALS['db']->query($sql);
        return true;
    }else{
        return false;
    }
}


function get_affiliate()
{

    $sql = "select value from " . $GLOBALS['ecs']->table('ecsmart_shop_config') ."  WHERE code = 'affiliate'";
    $config = $GLOBALS['db']->getOne($sql);
    $config = unserialize($config);
    empty($config) && $config = array();
    return $config;
}

//根据订单号获取分成日志信息
function get_all_affiliate_log($order_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') . " WHERE order_id = '$order_id'";
    $list = $GLOBALS['db']->getAll($sql);
    $arr = array();
    $str = '';
    foreach($list as $val)
    {
         $str .= sprintf($GLOBALS['_LANG']['separate_info2'], $val['user_id'], $val['user_name'], $val['money'])."<br />";
         $arr['log_id'] = $val['log_id'];
         $arr['separate_type'] = $val['separate_type'];
    }
    $arr['info'] = $str;
    return $arr;
}

//获取供货商名称
function get_supplier($supplier_id)
{
    $sql = "SELECT supplier_name FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE supplier_id = '$supplier_id'";
    return $GLOBALS['db']->getOne($sql); 
}

//获取供货商列表
function get_supplier_list()
{
    $sql = 'SELECT supplier_id,supplier_name 
            FROM ' . $GLOBALS['ecs']->table('supplier') . '
            WHERE status=1 
            ORDER BY supplier_name ASC';
    $res = $GLOBALS['db']->getAll($sql);

    if (!is_array($res))
    {
        $res = array();
    }

    return $res;
}


/**
 * 功能：导出订单的方法
 * 参数：
 * 返回： array
 *
 */
function get_affiliate_ck_new1($separate_info)
{
    $affiliate = unserialize($GLOBALS['_CFG_MOBILE']['affiliate']);
    empty($affiliate) && $affiliate = array();
    $separate_by = $affiliate['config']['separate_by'];
    
    //订单如果未使用红包，或者使用的红包支持分销 edit yhy 2019/5/29
    $sqladd = ' AND ((o.bonus=0 AND o.bonus_id=0) OR (o.bonus>0 AND o.bonus_id>0 AND t.is_distribute=1)) ';//使用红包的订单不参与分成
    //$sqladd = '';
    if (isset($_REQUEST['status']))
    {
        if($_REQUEST['status']){
            $status = $_REQUEST['status']-1;
            $sqladd .= ' AND o.is_separate = ' . (int)$status;
            $filter['status'] = (int)$status;
        }
        
    }
    
    //if (isset($_GET['auid']))
    //{
        //$sqladd = ' AND o.user_id=' . $_GET['auid'];
    //}

    $sqladd .= ' AND u.parent_id > 0 ';

    //if($GLOBALS['_CFG_MOBILE']['is_add_distrib'] == 0)
    //{
        //$sqladd = ' AND o.supplier_id = 0 '; 
    //}
    //else
    //{
        //if(isset($_REQUEST['supplier_id']))
        //{
            //$sqladd = ' AND o.supplier_id = ' . $_REQUEST['supplier_id']; 
        //}  
    //}
    
    
    if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
    {
        //按订单分成
        $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('order_info')." AS o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . $GLOBALS['ecs']->table('users') . " as u WHERE o.pay_status = 2 AND o.user_id = u.user_id  $sqladd ";
    }
    else
    {
        //按商品分成
        $sql = "select count(*) from ".
        "(select o.order_id,o.user_id,o.add_time,o.order_status,".
        "sum(split_money*goods_number) as total_money,u.user_name,o.is_separate ".
        "from " . $GLOBALS['ecs']->table('order_info') . " as o  LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . 
        $GLOBALS['ecs']->table('order_goods') . " as b," . 
        $GLOBALS['ecs']->table('users') ." as u".
        " where o.pay_status = 2 and o.order_id = b.order_id ".
        "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab" .
        " where total_money > 0 ";
    }
    
    //$filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    //$filter = page_and_size($filter);
    
    if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
    {
        $sql = "SELECT order_sn,is_separate,order_id,o.user_id,add_time,order_status,supplier_id,u.user_name FROM ".$GLOBALS['ecs']->table('order_info')." AS o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . $GLOBALS['ecs']->table('users') . " as u WHERE o.pay_status = 2 AND o.user_id = u.user_id  $sqladd ORDER BY order_id DESC ";
    }
    else
    {
        $sql = "select special_rank,order_sn,is_separate,order_id,user_id,add_time,
        order_status,supplier_id,user_name from " .
        "(select r.special_rank,o.order_id,o.order_sn,o.user_id,o.add_time,o.order_status,".
        "o.supplier_id,sum(split_money*goods_number) as total_money," .
        "o.is_separate,u.user_name from " . 
        $GLOBALS['ecs']->table('order_info') . " as o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," . 
        $GLOBALS['ecs']->table('order_goods') . " as b," .
        $GLOBALS['ecs']->table('users') . " as u LEFT JOIN ".$GLOBALS['ecs']->table('user_rank') ." AS r ON u.user_rank =  r.rank_id ".
        " where o.pay_status = 2 and o.order_id = b.order_id  " .
        "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab " . 
        " where total_money > 0 and (special_rank = 0 or special_rank is null) ORDER BY order_id DESC";
    }
    //echo $sql;
    $query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
        $info = get_all_affiliate_log($rt['order_id']);
        $rt['add_time'] = local_date("Y-m-d",$rt['add_time']);
        $rt['info'] = $info['info'];
        $rt['log_id'] = $info['log_id'];
        if($info['separate_type'] == -1 || $info['separate_type'] == -2)
        {
            //已被撤销
            $rt['is_separate'] = 3;
            $rt['info'] = "<s>" . $rt['info'] . "</s>";
        }
        $rt['supplier'] = get_supplier($rt['supplier_id']);

        if(empty($rt['info']))
        {
            $rt['info']=affiliate_order_money($rt['order_id'],$separate_info);

        }
        $logdb[] = $rt;
    }
    //$arr = array('logdb' => $logdb);

    return $logdb;
}