<?php


/**
 * 新版拼团文件
 * $Author: RINCE 120029121  $
 * $Id: extpintuan.php 17217 2016-04-20 09:29:08Z RINCE 120029121  $
 */



define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_goods.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'mobile/includes/prince/lib_extpintuan.php');
require_once(ROOT_PATH . 'mobile/wxm_extpintuan.php');  
update_extpintuan_info();
create_lucky_orders();
send_lucky_extpintuan_wxm();



if( !$_SESSION['dm299com_supplier_extpintuan'] ) {
	sys_msg($_LANG['priv_error'], 0, $link);
}


/* 检查权限 */
//admin_priv('extpintuan');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 拼团活动列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{  
    /* 模板赋值 */
    $smarty->assign('full_page',    1);
    $smarty->assign('ur_here',      $_LANG['extpintuan_list']);
    $smarty->assign('action_link',  array('href' => 'extpintuan.php?act=add', 'text' => $_LANG['add_extpintuan']));
    $smarty->assign('action_link2',  array('href' => 'extpintuan.php?act=view', 'text' => '查看所有拼团'));

    $list = extpintuan_list_adm();

    $smarty->assign('extpintuan_list',   $list['item']);
    $smarty->assign('filter',           $list['filter']);
    $smarty->assign('record_count',     $list['record_count']);
    $smarty->assign('page_count',       $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('extpintuan_list.htm');
}

elseif ($_REQUEST['act'] == 'query')
{
    $list = extpintuan_list_adm();

    $smarty->assign('extpintuan_list', $list['item']);
    $smarty->assign('filter',         $list['filter']);
    $smarty->assign('record_count',   $list['record_count']);
    $smarty->assign('page_count',     $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('extpintuan_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加/编辑拼团活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 初始化/取得拼团活动信息 */
    if ($_REQUEST['act'] == 'add')
    {
        $extpintuan = array(
            'act_id'  => 0,
            'start_time'    => date('Y-m-d', time() ),
            'end_time'      => date('Y-m-d', time() + 30 * 86400),
            'time_limit'    =>24,
            'single_buy'    => 1,
            'choose_number' => 0,
            'discount' => 3.5,
            'virtual_sold' => 1000,
            'notify_header' => 1,
            'lucky_extpintuan' => 0,
            'need_follow'    => 0,
            'price_ladder'  => array(array('amount' => 3, 'minprice' => 0,'maxprice' => 0,'orderlimit'  => 0 ,'tuanzhangdis'  => 10,'fencheng'  => 0 ))
        );
    }
    else
    {
        $extpintuan_id = intval($_REQUEST['id']);
        if ($extpintuan_id <= 0)
        {
            die('invalid param');
        }
        $extpintuan = extpintuan_info($extpintuan_id);
	
		$extpintuan['start_time'] = local_date('Y-m-d H:i', $extpintuan['start_time']);
	
		$extpintuan['end_time'] = local_date('Y-m-d H:i', $extpintuan['end_time']);
	
    }
    $smarty->assign('extpintuan', $extpintuan);

    create_html_editor('act_desc', htmlspecialchars($extpintuan['act_desc']));


    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['add_extpintuan']);
    $smarty->assign('action_link', list_link($_REQUEST['act'] == 'add'));
    $smarty->assign('cat_list', cat_list());
    $smarty->assign('brand_list', get_brand_list());

    /* 显示模板 */
    assign_query_info();
    $smarty->display('extpintuan_info.htm');
}

/*------------------------------------------------------ */
//-- 添加/编辑拼团活动的提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] =='insert_update')
{
    /* 取得拼团活动id */
    $extpintuan_id = intval($_POST['act_id']);
    if (isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail']))
    {
        if ($extpintuan_id <= 0)
        {
            sys_msg($_LANG['error_extpintuan'], 1);
        }
        $extpintuan = extpintuan_info($extpintuan_id);
		
        if (empty($extpintuan))
        {
            sys_msg($_LANG['error_extpintuan'], 1);
        }
    }

    if (isset($_POST['finish']))
    {
        /* 判断订单状态 */
        if ($extpintuan['status'] != GBS_UNDER_WAY)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 结束拼团活动，修改结束时间为当前时间 */
        $sql = "UPDATE " . $ecs->table('goods_activity') .
                " SET end_time = '" . gmtime() . "' " .
                "WHERE act_id = '$extpintuan_id' LIMIT 1";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'extpintuan.php?act=list', 'text' => $_LANG['back_list'])
        );
        sys_msg($_LANG['edit_success'], 0, $links);
    }
    elseif (isset($_POST['succeed']))
    {
        /* 设置活动成功 */

        /* 判断订单状态 */
        if ($extpintuan['status'] != GBS_FINISHED)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 如果有订单，更新订单信息 */
        if ($extpintuan['total_order'] > 0)
        {
            /* 查找该拼团活动的已确认或未确认订单（已取消的就不管了） */
            $sql = "SELECT order_id " .
                    "FROM " . $ecs->table('order_info') .
                    " WHERE extension_code = 'extpintuan' " .
                    "AND extension_id = '$extpintuan_id' " .
                    "AND (order_status = '" . OS_CONFIRMED . "' or order_status = '" . OS_UNCONFIRMED . "')";
            $order_id_list = $db->getCol($sql);

            /* 更新订单商品价 */
            $final_price = $extpintuan['trans_price'];
            $sql = "UPDATE " . $ecs->table('order_goods') .
                    " SET goods_price = '$final_price' " .
                    "WHERE order_id " . db_create_in($order_id_list);
            $db->query($sql);

            /* 查询订单商品总额 */
            $sql = "SELECT order_id, SUM(goods_number * goods_price) AS goods_amount " .
                    "FROM " . $ecs->table('order_goods') .
                    " WHERE order_id " . db_create_in($order_id_list) .
                    " GROUP BY order_id";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $order_id = $row['order_id'];
                $goods_amount = floatval($row['goods_amount']);

                /* 取得订单信息 */
                $order = order_info($order_id);

                /* 判断订单是否有效：余额支付金额 + 已付款金额 >= 保证金 */
                if ($order['surplus'] + $order['money_paid'] >= $extpintuan['deposit'])
                {
                    /* 有效，设为已确认，更新订单 */

                    // 更新商品总额
                    $order['goods_amount'] = $goods_amount;

                    // 如果保价，重新计算保价费用
                    if ($order['insure_fee'] > 0)
                    {
                        $shipping = shipping_info($order['shipping_id']);
                        $order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], $goods_amount, $shipping['insure']);
                    }

                    // 重算支付费用
                    $order['order_amount'] = $order['goods_amount'] + $order['shipping_fee']
                        + $order['insure_fee'] + $order['pack_fee'] + $order['card_fee']
                        - $order['money_paid'] - $order['surplus'];
                    if ($order['order_amount'] > 0)
                    {
                        $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount']);
                    }
                    else
                    {
                        $order['pay_fee'] = 0;
                    }

                    // 计算应付款金额
                    $order['order_amount'] += $order['pay_fee'];

                    // 计算付款状态
                    if ($order['order_amount'] > 0)
                    {
                        $order['pay_status'] = PS_UNPAYED;
                        $order['pay_time'] = 0;
                    }
                    else
                    {
                        $order['pay_status'] = PS_PAYED;
                        $order['pay_time'] = gmtime();
                    }

                    // 如果需要退款，退到帐户余额
                    if ($order['order_amount'] < 0)
                    {
                        // todo （现在手工退款）
                    }

                    // 订单状态
                    $order['order_status'] = OS_CONFIRMED;
                    $order['confirm_time'] = gmtime();

                    // 更新订单
                    $order = addslashes_deep($order);
                    update_order($order_id, $order);
                }
                else
                {
                    /* 无效，取消订单，退回已付款 */

                    // 修改订单状态为已取消，付款状态为未付款
                    $order['order_status'] = OS_CANCELED;
                    $order['to_buyer'] = $_LANG['cancel_order_reason'];
                    $order['pay_status'] = PS_UNPAYED;
                    $order['pay_time'] = 0;

                    /* 如果使用余额或有已付款金额，退回帐户余额 */
                    $money = $order['surplus'] + $order['money_paid'];
                    if ($money > 0)
                    {
                        $order['surplus'] = 0;
                        $order['money_paid'] = 0;
                        $order['order_amount'] = $money;

                        // 退款到帐户余额
                        order_refund($order, 1, $_LANG['cancel_order_reason'] . ':' . $order['order_sn']);
                    }

                    /* 更新订单 */
                    $order = addslashes_deep($order);
                    update_order($order['order_id'], $order);
                }
            }
        }

        /* 修改拼团活动状态为成功 */
        $sql = "UPDATE " . $ecs->table('goods_activity') .
                " SET is_finished = '" . GBS_SUCCEED . "' " .
                "WHERE act_id = '$extpintuan_id' LIMIT 1";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'extpintuan.php?act=list', 'text' => $_LANG['back_list'])
        );
        sys_msg($_LANG['edit_success'], 0, $links);
    }
    elseif (isset($_POST['fail']))
    {
        /* 设置活动失败 */

        /* 判断订单状态 */
        if ($extpintuan['status'] != GBS_FINISHED)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 如果有有效订单，取消订单 */
        if ($extpintuan['valid_order'] > 0)
        {
            /* 查找未确认或已确认的订单 */
            $sql = "SELECT * " .
                    "FROM " . $ecs->table('order_info') .
                    " WHERE extension_code = 'extpintuan' " .
                    "AND extension_id = '$extpintuan_id' " .
                    "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "') ";
            $res = $db->query($sql);
            while ($order = $db->fetchRow($res))
            {
                // 修改订单状态为已取消，付款状态为未付款
                $order['order_status'] = OS_CANCELED;
                $order['to_buyer'] = $_LANG['cancel_order_reason'];
                $order['pay_status'] = PS_UNPAYED;
                $order['pay_time'] = 0;

                /* 如果使用余额或有已付款金额，退回帐户余额 */
                $money = $order['surplus'] + $order['money_paid'];
                if ($money > 0)
                {
                    $order['surplus'] = 0;
                    $order['money_paid'] = 0;
                    $order['order_amount'] = $money;

                    // 退款到帐户余额
                    order_refund($order, 1, $_LANG['cancel_order_reason'] . ':' . $order['order_sn'], $money);
                }

                /* 更新订单 */
                $order = addslashes_deep($order);
                update_order($order['order_id'], $order);
            }
        }

        /* 修改拼团活动状态为失败，记录失败原因（活动说明） */
        $sql = "UPDATE " . $ecs->table('goods_activity') .
                " SET is_finished = '" . GBS_FAIL . "', " .
                    "act_desc = '$_POST[act_desc]' " .
                "WHERE act_id = '$extpintuan_id' LIMIT 1";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'extpintuan.php?act=list', 'text' => $_LANG['back_list'])
        );
        sys_msg($_LANG['edit_success'], 0, $links);
    }
    elseif (isset($_POST['mail']))
    {
        /* 发送通知邮件 */

        /* 判断订单状态 */
        if ($extpintuan['status'] != GBS_SUCCEED)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 取得邮件模板 */
        $tpl = get_mail_template('extpintuan');

        /* 初始化订单数和成功发送邮件数 */
        $count = 0;
        $send_count = 0;

        /* 取得有效订单 */
        $sql = "SELECT o.consignee, o.add_time, g.goods_number, o.order_sn, " .
                    "o.order_amount, o.order_id, o.email " .
                "FROM " . $ecs->table('order_info') . " AS o, " .
                    $ecs->table('order_goods') . " AS g " .
                "WHERE o.order_id = g.order_id " .
                "AND o.extension_code = 'extpintuan' " .
                "AND o.extension_id = '$extpintuan_id' " .
                "AND o.order_status = '" . OS_CONFIRMED . "'";
        $res = $db->query($sql);
        while ($order = $db->fetchRow($res))
        {
            /* 邮件模板赋值 */
            $smarty->assign('consignee',    $order['consignee']);
            $smarty->assign('add_time',     local_date($_CFG['time_format'], $order['add_time']));
            $smarty->assign('goods_name',   $extpintuan['goods_name']);
            $smarty->assign('goods_number', $order['goods_number']);
            $smarty->assign('order_sn',     $order['order_sn']);
            $smarty->assign('order_amount', price_format($order['order_amount']));
            $smarty->assign('shop_url',     $ecs->url() . 'user.php?act=order_detail&order_id='.$order['order_id']);
            $smarty->assign('shop_name',    $_CFG['shop_name']);
            $smarty->assign('send_date',    local_date($_CFG['date_format']));

            /* 取得模板内容，发邮件 */
            $content = $smarty->fetch('str:' . $tpl['template_content']);
            if (send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $send_count++;
            }
            $count++;
        }

        /* 提示信息 */
        sys_msg(sprintf($_LANG['mail_result'], $count, $send_count));
    }
    else
    {
        /* 保存拼团信息 */
        $goods_id = intval($_POST['goods_id']);
        if ($goods_id <= 0)
        {
            sys_msg($_LANG['error_goods_null']);
        }
        $info = goods_extpintuan($goods_id);
        /*if ($info && $info['act_id'] != $extpintuan_id)
        {
            sys_msg($_LANG['error_goods_exist']);
        }*/
		
        $had_finished = $db->getOne("SELECT count(*) FROM " . $ecs->table('extpintuan') . " WHERE status = 4 and act_id = '$extpintuan_id' ");
        if ($had_finished)
        {
            sys_msg('活动已开奖，禁止编辑');
        }

        $goods_name = $db->getOne("SELECT goods_name FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'");

        $act_name = empty($_POST['act_name']) ? $goods_name : sub_str($_POST['act_name'], 0, 255, false);

        $deposit = floatval($_POST['deposit']);
        if ($deposit < 0)
        {
            $deposit = 0;
        }

        $restrict_amount = intval($_POST['restrict_amount']);
        if ($restrict_amount < 0)
        {
            $restrict_amount = 0;
        }

        $gift_integral = intval($_POST['gift_integral']);
        if ($gift_integral < 0)
        {
            $gift_integral = 0;
        }

        $price_ladder = array();
        $count = count($_POST['ladder_amount']);
        for ($i = $count - 1; $i >= 0; $i--)
        {
            $amount = intval($_POST['ladder_amount'][$i]);
            if ($amount <= 0)
            {
                continue;
            }

            $minprice = round(floatval($_POST['ladder_minprice'][$i]), 2);
            if ($minprice < 0)
            {
                continue;
            }
			
            /*$maxprice = round(floatval($_POST['ladder_maxprice'][$i]), 2);
            if ($maxprice < 0)
            {
                continue;
            }*/
			
            $orderlimit = intval($_POST['ladder_orderlimit'][$i]);
            if ($orderlimit <= 0)
            {
                $orderlimit = 0;
            }
			
            $tuanzhangdis = round(floatval($_POST['ladder_tuanzhangdis'][$i]), 2);
            if ($tuanzhangdis < 0)
            {
                continue;
            }
			
            $fencheng = round(floatval($_POST['ladder_fencheng'][$i]), 2);
            if ($fencheng < 0)
            {
                continue;
            }
			

            $price_ladder[$amount] = array('amount' => $amount,'price' => $minprice, 'minprice' => $minprice, 'maxprice' => $minprice, 'orderlimit' => $orderlimit, 'tuanzhangdis' => $tuanzhangdis,'fencheng'  => $fencheng ); //PRINCE  
        }
        if (count($price_ladder) < 1)
        {
            sys_msg($_LANG['error_price_ladder']);
        }

        /* 限购数量不能小于价格阶梯中的最大数量 */
        $amount_list = array_keys($price_ladder);
        if ($restrict_amount > 0 && max($amount_list) > $restrict_amount)
        {
            sys_msg($_LANG['error_restrict_amount']);
        }
		
        $single_buy_price = floatval($_POST['single_buy_price']);
        if ($single_buy_price < 0)
        {
            $single_buy_price = 0;
        }
        $market_price = floatval($_POST['market_price']);
        if ($market_price < 0)
        {
            $market_price = 0;
        }
        $discount = floatval($_POST['discount']);
        if ($discount < 0)
        {
            $discount = 0;
        }
        $virtual_sold = intval($_POST['virtual_sold']);
        if ($virtual_sold < 0)
        {
            $virtual_sold = 0;
        }
        $time_limit = floatval($_POST['time_limit']);
        if ($time_limit < 0)
        {
            $time_limit = 0;
        }
		
        $lucky_extpintuan = intval($_POST['lucky_extpintuan']);
        if ($lucky_extpintuan < 0)
        {
            $lucky_extpintuan = 0;
        }
		
        $lucky_limit = intval($_POST['lucky_limit']);
        if ($lucky_limit < 0)
        {
            $lucky_limit = 0;
        }
		
        $open_limit = floatval($_POST['open_limit']);
        if ($open_limit < 0)
        {
            $open_limit = 0;
        }
		
        $need_people = intval($_POST['need_people']);
        if ($need_people < 0)
        {
            $need_people = 0;
        }
        $min_price = floatval($_POST['min_price']);
        if ($min_price < 0)
        {
            $min_price = 0;
        }
		
        $max_price = floatval($_POST['max_price']);
        if ($max_price < 0)
        {
            $max_price = 0;
        }
		
        ksort($price_ladder);
        $price_ladder = array_values($price_ladder);

        /* 检查开始时间和结束时间是否合理 */
        $start_time = local_strtotime($_POST['start_time']);
        $end_time = local_strtotime($_POST['end_time']);
        if ($start_time >= $end_time)
        {
            sys_msg($_LANG['invalid_time']);
        }

        $extpintuan = array(
            'act_name'   => $act_name,
            'act_desc'   => $_POST['act_desc'],
            'act_type'   => GAT_EXTPINTUAN,
            'ext_act_type'  => $lucky_extpintuan>0?2:1,
            'goods_id'   => $goods_id,
            'goods_name' => $goods_name,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'ext_info'   => serialize(array(
                    'price_ladder'      => $price_ladder,
                    'restrict_amount'   => $restrict_amount,
                    'gift_integral'     => $gift_integral,
                    'single_buy'        => $_POST['single_buy'],
                    'need_people'       => $need_people,
                    'min_price' 	    => $min_price,
                    'max_price'    		=> $max_price,
                    'single_buy_price'  => $single_buy_price,
                    'market_price'      => $market_price,
                    'discount'          => $discount,
                    'virtual_sold'      => $virtual_sold,
                    'time_limit'        => $time_limit,
                    'open_limit'        => $open_limit,
                    'lucky_extpintuan'  => $lucky_extpintuan,
                    'lucky_limit'       => $lucky_limit,
                    'choose_number'     => intval($_POST['choose_number'])?intval($_POST['choose_number']):0,
                    'notify_header'     => $_POST['notify_header'],
                    'need_follow'        => $_POST['need_follow'],
                    'qrcode_img'        => $_POST['qrcode_img'],
                    'share_title'       => $_POST['share_title'],
                    'share_brief'       => $_POST['share_brief'],
                    'share_img'         => $_POST['share_img'],
                    'goods_brief'       => $_POST['goods_brief'],

                    'deposit'           => $deposit
                    ))
        );

        /* 清除缓存 */
        clear_cache_files();

        /* 保存数据 */
        if ($extpintuan_id > 0)
        {
            /* update */
            $db->autoExecute($ecs->table('goods_activity'), $extpintuan, 'UPDATE', "act_id = '$extpintuan_id'");

            /* log */
            admin_log(addslashes($goods_name) . '[' . $extpintuan_id . ']', 'edit', 'extpintuan');

            /* todo 更新活动表 */

            /* 提示信息 */
            $links = array(
                array('href' => 'extpintuan.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_list'])
            );
            sys_msg($_LANG['edit_success'], 0, $links);
        }
        else
        {
			$extpintuan['supplier_id'] = $_SESSION['supplier_id'];

            /* insert */
            $db->autoExecute($ecs->table('goods_activity'), $extpintuan, 'INSERT');

            /* log */
            admin_log(addslashes($goods_name), 'add', 'extpintuan');

            /* 提示信息 */
            $links = array(
                array('href' => 'extpintuan.php?act=add', 'text' => $_LANG['continue_add']),
                array('href' => 'extpintuan.php?act=list', 'text' => $_LANG['back_list'])
            );
            sys_msg($_LANG['add_success'], 0, $links);
        }
    }
}


/*------------------------------------------------------ */
//-- 查看活动详情
/*------------------------------------------------------ */


elseif ($_REQUEST['act'] == 'view')
{

        $act_id = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);
		$extpintuan_list = get_extpintuan($_SESSION['supplier_id']);
	
		$smarty->assign('extpintuan_list',     $extpintuan_list['extpintuan']);
		$smarty->assign('filter',       $extpintuan_list['filter']);
		$smarty->assign('record_count', $extpintuan_list['record_count']);
		$smarty->assign('page_count',   $extpintuan_list['page_count']);
	
		$sort_flag  = sort_flag($extpintuan_list['filter']);
		$smarty->assign($sort_flag['tag'], $sort_flag['img']);
		/* 赋值 */
		$smarty->assign('full_page',    1);
		$smarty->assign('ur_here',      '活动详情' );
		$smarty->assign('action_link',  array('text' => '拼团活动列表', 'href'=>'extpintuan.php?act=list'));
		$smarty->display('extpintuan_view.htm');
}

elseif ($_REQUEST['act'] == 'detail_view')
{
        $pt_id = empty($_REQUEST['pt_id']) ? 0 : intval($_REQUEST['pt_id']);
        $act_id = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);

		$extpintuan_list = get_extpintuan_detail();
		
        
		//订单状态 Start
		$orders=$extpintuan_list['extpintuan'];
		foreach ($orders as $key => $order_id){
			   $order = order_info($orders[$key]['order_id']);
			   $orders[$key]['order_status'] =$order['order_status'];
			   $orders[$key]['pay_status'] =$order['pay_status'];
			   $orders[$key]['shipping_status'] =$order['shipping_status'];

		}
		$extpintuan_list['extpintuan']=$orders;
		//订单状态 End
		
		
		$extpintuan_info=get_extpintuan_by_ptid($pt_id );
        $extpintuan_info['create_time']   = local_date($GLOBALS['_CFG']['time_format'], $extpintuan_info['create_time']);
        $extpintuan_info['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $extpintuan_info['end_time']);
		$smarty->assign('extpintuan_info',     $extpintuan_info);

		if($act_id==0){
        $act_id = $pintuan_info['act_id'];
		}
		
		$smarty->assign('extpintuan_list',     $extpintuan_list['extpintuan']);
		$smarty->assign('filter',       $extpintuan_list['filter']);
		$smarty->assign('record_count', $extpintuan_list['record_count']);
		$smarty->assign('page_count',   $extpintuan_list['page_count']);
	
		$sort_flag  = sort_flag($extpintuan_list['filter']);
		$smarty->assign($sort_flag['tag'], $sort_flag['img']);
		/* 赋值 */
		$smarty->assign('info',         get_extpintuan_info($id));
		$smarty->assign('full_page',    1);
		$smarty->assign('ur_here',      '活动详情' );
		$smarty->assign('action_link',  array('text' => '团长列表', 'href'=>'extpintuan.php?act=view&act_id='.$act_id ));
		$smarty->display('extpintuan_detail_view.htm');

}
/*------------------------------------------------------ */
//-- 批量删除拼团活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_drop')
{
    if (isset($_POST['checkboxes']))
    {
        $del_count = 0; //初始化删除数量
        foreach ($_POST['checkboxes'] AS $key => $id)
        {
            /* 取得拼团活动信息 */
            $extpintuan = extpintuan_info($id);

            /* 如果拼团活动已经有订单，不能删除 */
            if ($extpintuan['valid_order'] <= 0)
            {
                /* 删除拼团活动 */
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_activity') .
                        " WHERE act_id = '$id' and supplier_id='".$_SESSION['supplier_id']."' LIMIT 1";

                $GLOBALS['db']->query($sql, 'SILENT');

                admin_log(addslashes($extpintuan['goods_name']) . '[' . $id . ']', 'remove', 'extpintuan');
                $del_count++;
            }
        }

        /* 如果删除了拼团活动，清除缓存 */
        if ($del_count > 0)
        {
            clear_cache_files();
        }

        $links[] = array('text' => $_LANG['back_list'], 'href'=>'extpintuan.php?act=list');
        sys_msg(sprintf($_LANG['batch_drop_success'], $del_count), 0, $links);
    }
    else
    {
        $links[] = array('text' => $_LANG['back_list'], 'href'=>'extpintuan.php?act=list');
        sys_msg($_LANG['no_select_extpintuan'], 0, $links);
    }
}


/*------------------------------------------------------ */
//-- 排序、翻页活动详情
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query_extpintuan')
{
    $extpintuan_list = get_extpintuan($_SESSION['supplier_id']);

    $smarty->assign('extpintuan_list',     $extpintuan_list['extpintuan']);
    $smarty->assign('filter',       $extpintuan_list['filter']);
    $smarty->assign('record_count', $extpintuan_list['record_count']);
    $smarty->assign('page_count',   $extpintuan_list['page_count']);

    $sort_flag  = sort_flag($extpintuan_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('extpintuan_view.htm'), '',
        array('filter' => $extpintuan_list['filter'], 'page_count' => $extpintuan_list['page_count']));
}

/*------------------------------------------------------ */
//-- 排序、翻页活动详情
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query_extpintuan_detail')
{
    $extpintuan_list = get_extpintuan_detail();
	
		//订单状态 Start
		$orders=$extpintuan_list['extpintuan'];
		foreach ($orders as $key => $order_id){
			   $order = order_info($orders[$key]['order_id']);
			   $orders[$key]['order_status'] =$order['order_status'];
			   $orders[$key]['pay_status'] =$order['pay_status'];
			   $orders[$key]['shipping_status'] =$order['shipping_status'];

		}
		$extpintuan_list['extpintuan']=$orders;
		//订单状态 End
        
		/*$extpintuan_info=get_extpintuan_by_ptid($pt_id);
        $extpintuan_info['create_time']   = local_date($GLOBALS['_CFG']['time_format'], $extpintuan_info['create_time']);
        $extpintuan_info['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $extpintuan_info['end_time']);
		$smarty->assign('extpintuan_info',     $extpintuan_info);*/
		
    $smarty->assign('extpintuan_list',     $extpintuan_list['extpintuan']);
    $smarty->assign('filter',       $extpintuan_list['filter']);
    $smarty->assign('record_count', $extpintuan_list['record_count']);
    $smarty->assign('page_count',   $extpintuan_list['page_count']);

    $sort_flag  = sort_flag($extpintuan_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('extpintuan_detail_view.htm'), '',
        array('filter' => $extpintuan_list['filter'], 'page_count' => $extpintuan_list['page_count']));
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    //check_authz_json('extpintuan');

    include_once(ROOT_PATH . 'includes/cls_json.php');



    $json   = new JSON;

    $filter = $json->decode($_GET['JSON']);

    $arr    = get_goods_list($filter);



    make_json_result($arr);
}

/*------------------------------------------------------ */
//-- 编辑保证金
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_deposit')
{

    $id = intval($_POST['id']);
    $val = floatval($_POST['val']);

    $sql = "SELECT ext_info FROM " . $ecs->table('goods_activity') .
            " WHERE act_id = '$id' AND act_type = '" . GAT_EXTPINTUAN . "'";
    $ext_info = unserialize($db->getOne($sql));
    $ext_info['deposit'] = $val;

    $sql = "UPDATE " . $ecs->table('goods_activity') .
            " SET ext_info = '" . serialize($ext_info) . "'" .
            " WHERE act_id = '$id'";
    $db->query($sql);

    clear_cache_files();

    make_json_result(number_format($val, 2));
}

/*------------------------------------------------------ */
//-- 编辑保证金
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_restrict_amount')
{

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $sql = "SELECT ext_info FROM " . $ecs->table('goods_activity') .
            " WHERE act_id = '$id' AND act_type = '" . GAT_EXTPINTUAN . "'";
    $ext_info = unserialize($db->getOne($sql));
    $ext_info['restrict_amount'] = $val;

    $sql = "UPDATE " . $ecs->table('goods_activity') .
            " SET ext_info = '" . serialize($ext_info) . "'" .
            " WHERE act_id = '$id'";
    $db->query($sql);

    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除拼团活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('extpintuan');

    $id = intval($_GET['id']);

    /* 取得拼团活动信息 */
    $extpintuan = extpintuan_info($id);

    /* 如果拼团活动已经有订单，不能删除 */
    if ($extpintuan['valid_order'] > 0)
    {
        make_json_error($_LANG['error_exist_order']);
    }

    /* 删除拼团活动 */
    $sql = "DELETE FROM " . $ecs->table('goods_activity') . " WHERE act_id = '$id' and supplier_id='".$_SESSION['supplier_id']."' LIMIT 1";
    $db->query($sql);

    admin_log(addslashes($extpintuan['goods_name']) . '[' . $id . ']', 'remove', 'extpintuan');

    clear_cache_files();

    $url = 'extpintuan.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*
 * 取得拼团活动列表
 * @return   array
 */
function extpintuan_list_adm()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keyword']      = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']      = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = (!empty($filter['keyword'])) ? " AND goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" : '';

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE supplier_id='".$_SESSION['supplier_id']."' and act_type = '" . GAT_EXTPINTUAN . "' $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table('goods_activity') .
                " WHERE supplier_id='".$_SESSION['supplier_id']."' and act_type = '" . GAT_EXTPINTUAN . "' $where ".
                " ORDER BY $filter[sort_by] $filter[sort_order] ".
                " LIMIT ". $filter['start'] .", $filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $ext_info = unserialize($row['ext_info']);
        $stat = extpintuan_stat($row['act_id'], $ext_info['deposit']);
        $arr = array_merge($row, $stat, $ext_info);

        /* 处理价格阶梯 */
        $price_ladder = $arr['price_ladder'];
        if (!is_array($price_ladder) || empty($price_ladder))
        {
            $price_ladder = array(array('amount' => 0, 'price' => 0));
        }
        else
        {
            foreach ($price_ladder AS $key => $amount_price)
            {
                $price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
            }
        }

        /* 计算当前价 */
        $cur_price  = $price_ladder[0]['price'];    // 初始化
        $cur_amount = $stat['valid_goods'];         // 当前数量
        foreach ($price_ladder AS $amount_price)
        {
            if ($cur_amount >= $amount_price['amount'])
            {
                $cur_price = $amount_price['price'];
            }
            else
            {
                break;
            }
        }

        $arr['cur_price']   = $cur_price;

        $status = extpintuan_status($arr);

        $arr['start_time']  = local_date($GLOBALS['_CFG']['date_format'], $arr['start_time']);
        $arr['end_time']    = local_date($GLOBALS['_CFG']['date_format'], $arr['end_time']);
        $arr['cur_status']  = $GLOBALS['_LANG']['gbs'][$status];

        $list[] = $arr;
    }
    $arr = array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 取得某商品的拼团活动
 * @param   int     $goods_id   商品id
 * @return  array
 */
function goods_extpintuan($goods_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('goods_activity') .
            " WHERE goods_id = '$goods_id' " .
            " AND act_type = '" . GAT_EXTPINTUAN . "'" .
            " AND start_time <= " . gmtime() .
            " AND end_time >= " . gmtime();

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 列表链接
 * @param   bool    $is_add         是否添加（插入）
 * @return  array('href' => $href, 'text' => $text)
 */
function list_link($is_add = true)
{
    $href = 'extpintuan.php?act=list';
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }

    return array('href' => $href, 'text' => $GLOBALS['_LANG']['extpintuan_list']);
}


?>