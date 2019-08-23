<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
require_once(ROOT_PATH . 'weixin/weixin_notice.php');
//订单分销
$get_affiliate_ck=get_affiliate_ck();  //获取分成订单
if(!empty($get_affiliate_ck)){
    foreach ($get_affiliate_ck as $tempkey => $value){
        $order_goods_list=array();
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
        $info = sprintf($_LANG['separate_info'],$value['order_sn'], $personalMoney, 0);
        log_account_change($order_goods_list[0]['parent_id'], $personalMoney, 0, 0, 0, $value['order_sn']."获取提成");
        //push_user_msg($personal_up_id,$order_sn,$personalMoney);
        write_affiliate_log($value['order_id'],$order_goods_list[0]['parent_id'],$order_goods_list[0]['personal_up_name'],$personalMoney, 0, $value['order_sn']."获取提成",0);
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') ." SET is_separate = 1" ." WHERE order_id = '$value[order_id]'";

        $db->query($sql);

        yongjin($order_goods_list[0]['parent_id'],$personalMoney);
    }
}

//分销提成
affiliate_order_fenxiao();

//分销订单日志
function write_affiliate_log($oid, $uid, $username, $money, $separate_by,$change_desc)
{
    $time = gmtime();
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('affiliate_log') . "( order_id, user_id, user_name, time, money, separate_type,change_desc)".
        " VALUES ( '$oid', '$uid', '$username', '$time', '$money', '$separate_by','$change_desc')";
    if ($oid)
    {
        $GLOBALS['db']->query($sql);
    }
}

//获取分销订单
function get_affiliate_ck()
{
    $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'fx_day_num'";
    $fx_day_num = $GLOBALS['db']->GetOne($sql);

	//$now_time=gmtime();
    $now_time=gmtime()-$fx_day_num*24*60*60;

    $sqladd = '';
    if (isset($_REQUEST['status']))
    {
        $sqladd = ' AND o.is_separate = ' . (int)$_REQUEST['status'];
        $filter['status'] = (int)$_REQUEST['status'];
    }
	//edit yhy未使用红包的订单和使用红包的订单并且红包可分销 2019/5/29 
    $sqladd = ' AND o.supplier_id = 0 AND ((o.bonus=0 AND o.bonus_id=0) OR (o.bonus>0 AND o.bonus_id>0 AND t.is_distribute=1)) ';//过滤掉使用红包的订单
    $sql = "select special_rank,shipping_status,pay_status,shipping_time_end,goods_amount,order_sn,is_separate,order_id,user_id,add_time,
    order_status,supplier_id,user_name from " .
        "(select r.special_rank,o.shipping_status,o.pay_status,o.shipping_time_end,o.goods_amount,o.order_id,o.order_sn,o.user_id,o.add_time,o.order_status,".
        "o.supplier_id,sum(split_money*goods_number) as total_money," .
        "o.is_separate,u.user_name from " .
        $GLOBALS['ecs']->table('order_info') . " as o LEFT JOIN ".$GLOBALS['ecs']->table('user_bonus')." as ut ON o.bonus_id=ut.bonus_id LEFT JOIN ".$GLOBALS['ecs']->table('bonus_type')." as t ON t.type_id=ut.bonus_type_id ," .
        $GLOBALS['ecs']->table('order_goods') . " as b," .
        $GLOBALS['ecs']->table('users') . " as u ".
        'LEFT JOIN ' .  $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
        "ON u.user_rank =  r.rank_id " .
        " where o.pay_status = 2 and o.order_id = b.order_id " .
        "and o.user_id = u.user_id $sqladd group by o.order_id ) as ab " .
        " where total_money > 0 and (special_rank = 0 or special_rank is null) and is_separate = 0 and '$now_time' > shipping_time_end and order_status = 5 and shipping_status = 2 and pay_status = 2 ORDER BY order_id DESC" .
        " LIMIT 10";
    $info_lists = $GLOBALS['db']->GetAll($sql);

    return $info_lists;
}

function get_user_rank_prices($goods_id, $shop_price)
{
    $sql = "SELECT rank_id, IFNULL(mp.user_price, r.discount * $shop_price / 100) AS price, r.rank_name, r.discount " .
        'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
        "ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .
        "WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
    $res = $GLOBALS['db']->query($sql);
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {

        $arr[$row['rank_id']] = array(
            'rank_name' => htmlspecialchars($row['rank_name']),
            'price'     => price_format($row['price']));
    }
    return $arr;
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
//分成订单日志
function write_affiliate_order_fenxiao_log($uid, $money)
{
    $time = strtotime(date('Y-m-d 00:00:00', strtotime(date('Y-m-05') . ' -1 month')));
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('affiliate_fenxiao_log') . "( time, user_id, money)".
        " VALUES ( '$time', '$uid', '$money')";
    $GLOBALS['db']->query($sql);
}
function affiliate_order_fenxiao()
{
    $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'fc_rate'";
    $fc_rate = $GLOBALS['db']->GetOne($sql);
    $sql = "SELECT value FROM " .$GLOBALS['ecs']->table('shop_config')." where code = 'sl_time_start'";
    $sl_time = $GLOBALS['db']->GetOne($sql);
    $sql="select rank_id from".$GLOBALS['ecs']->table('user_rank')."where special_rank = 1".' ORDER BY discount DESC';
    $fenxiao_rank_id= $GLOBALS['db']->getOne($sql);
    $sql="select rank_id from".$GLOBALS['ecs']->table('user_rank')."where special_rank = 1".' ORDER BY discount ASC';
    $daili_rank_id= $GLOBALS['db']->getOne($sql);
    $day_start=strtotime(date('Y-m-d 00:00:00', strtotime(date('Y-m-01') . ' -1 month'))); // 计算出本月第一天再减一个月
    $day_end=strtotime(date('Y-m-d 23:59:59', strtotime(date('Y-m-01') . ' -1 day'))); // 计算出本月第一天再减一天
    $next_day_start=strtotime(date('Y-m-d 00:00:00', strtotime(date('Y-m-05') . ' -1 month')));
    $time_d=date("d");
    if($sl_time==$time_d)
    {
        $sql = "SELECT u.user_id " .
            'FROM ' . $GLOBALS['ecs']->table('users') . ' AS u ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('user_rank') . " AS ur ".
            "ON  u.user_rank = ur.rank_id " .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('affiliate_fenxiao_log') . " AS af ".
            "ON  af.user_id = u.user_id and af.time > '$day_start' and  af.time < '$day_end' " .
            "WHERE  ur.rank_id = '$fenxiao_rank_id' and af.log_id IS NULL ". " LIMIT 10";;
        $user_list = $GLOBALS['db']->GetAll($sql);
        foreach ($user_list AS $key => $val)
        {
            $sql = "SELECT sum(money) as total_money " .
                'FROM ' . $GLOBALS['ecs']->table('affiliate_log') . ' AS ag ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . " AS oi ".
                "ON  ag.order_id = oi.order_id " .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('users') . " AS u ".
                "ON  u.user_id = ag.user_id " .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('user_rank') . " AS ur ".
                "ON  u.user_rank = ur.rank_id " .
                "WHERE ag.time > '$day_start' and  ag.time < '$day_end' and  ur.rank_id = '$daili_rank_id' and u.parent_id = '$val[user_id]' and ag.separate_type = 0 and oi.is_separate = 1  ";
            $total_money = $GLOBALS['db']->GetOne($sql);
            if(empty($total_money))
            {
                $total_money=0;
            }
            log_account_change($val['user_id'], $total_money*$fc_rate, 0, 0, 0, "分校月初提成到账");
            write_affiliate_order_fenxiao_log($val['user_id'],$total_money*$fc_rate);
            fenxiao_tic($val['user_id'],$total_money*$fc_rate);
        }
    }
}
?>