<?php
/**
 * 众筹管理
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: markzhou $
 * $Id: pre_sell.php  2018-05-5  $
*/

define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');
require_once (ROOT_PATH . 'includes/lib_goods.php');
require_once (ROOT_PATH . 'includes/lib_order.php');

/* 检查权限 */
admin_priv('pre_sell');

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_list";
}

call_user_func($function_name);

/* 路由 */

/* ------------------------------------------------------ */
// -- 众筹活动列表
/* ------------------------------------------------------ */
function action_list ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 模板赋值 */
	$smarty->assign('full_page', 1);
	$smarty->assign('ur_here', $_LANG['pre_sale_list']);
	$smarty->assign('action_link', array(
		'href' => 'pre_sell.php?act=add', 'text' => $_LANG['add_pre_sale']
	));
	
	$list = pre_sale_list();

//	print_r($list);exit();

	$smarty->assign('pre_sale_list', $list['item']);
	$smarty->assign('filter', $list['filter']);
	$smarty->assign('record_count', $list['record_count']);
	$smarty->assign('page_count', $list['page_count']);
	
	$sort_flag = sort_flag($list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
	/* 显示商品列表页面 */
	assign_query_info();
//	print_r($list);exit();
	$smarty->display('pre_sell_list.htm');
}

/* ------------------------------------------------------ */
// -- 退款
/* ------------------------------------------------------ */
function action_backMoney()
{

//	print_r($_REQUEST);exit();
	$sql = 'SELECT user_id,order_price,order_id FROM'.$GLOBALS['ecs']->table('zhongchou_order').'WHERE act_id = '.$_REQUEST['act_id'].' AND back_status = 1';
	$user_info = $GLOBALS['db']->getAll($sql);
	if (empty($user_info)){
        $links = array(
            array(
                'href' => 'pre_sell.php?act=list'
            )
        );
        sys_msg($GLOBALS['_LANG']['back_money_fail'],0,$links);
	}else{
        foreach ($user_info as $values){
            log_account_change($values['user_id'],$values['order_price'],0,0,0,'众筹失败退款');
            $sql = 'UPDATE '.$GLOBALS['ecs']->table('order_info').'SET ORDER_STATUS = 2 , SHIPPING_STATUS = 0 ,PAY_STATUS = 2 WHERE order_id ='.$values['order_id'];
            $GLOBALS['db']->query($sql);
        }
        $sql = ' UPDATE '.$GLOBALS['ecs']->table('zhongchou_order').' SET back_status = 0 WHERE act_id = '.$_REQUEST['act_id'];
        $GLOBALS['db']->query($sql);
        $links = array(
            array(
                'href' => 'pre_sell.php?act=list'
            )
        );
        sys_msg($GLOBALS['_LANG']['back_money_success'],1,$links);
	}
}
/* ------------------------------------------------------ */
// -- 翻页、排序
/* ------------------------------------------------------ */
function action_query ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	$list = pre_sale_list();
	
	$smarty->assign('pre_sale_list', $list['item']);
	$smarty->assign('filter', $list['filter']);
	$smarty->assign('record_count', $list['record_count']);
	$smarty->assign('page_count', $list['page_count']);
	
	$sort_flag = sort_flag($list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
	make_json_result($smarty->fetch('pre_sell_list.htm'), '', array(
		'filter' => $list['filter'], 'page_count' => $list['page_count']
	));
}

/**
 * 添加众筹活动
 */
function action_add ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	$pre_sale = array(
		'act_id' => 0, 'start_time' => date('Y-m-d 00:00', time() + 86400), 'end_time' => date('Y-m-d 00:00', time() + 4 * 86400), 'price_ladder' => array(
			array(
				'amount' => 0,'point' => 0, 'price' => 0
			)
		)
	);
	
	$smarty->assign('pre_sale', $pre_sale);
	
	/* 模板赋值 */
	$smarty->assign('ur_here', $_LANG['add_pre_sale']);
    $smarty->assign('action_link', list_link($action == 'add'));
    $smarty->assign('cat_list', cat_list());
    $smarty->assign('brand_list', get_brand_list());
    $smarty->assign('is_pre_sale', 1);
	
	
	/* 显示模板 */
	assign_query_info();
	$smarty->display('pre_sell_info.htm');
}

/**
 * 编辑众筹活动
 */
function action_edit ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	$pre_sale_id = intval($_REQUEST['id']);
	if($pre_sale_id <= 0)
	{
		die('invalid param');
	}
	$pre_sale = pre_sell_info($pre_sale_id);

	$smarty->assign('pre_sale', $pre_sale);
	
	/* 模板赋值 */
	$smarty->assign('ur_here', $_LANG['edit_pre_sale']);
	$smarty->assign('action_link', list_link($action == 'add'));
	$smarty->assign('cat_list', cat_list());
	$smarty->assign('brand_list', get_brand_list());
	
	/* 显示模板 */
	assign_query_info();
	
	$smarty->display('pre_sell_info.htm');
}

/**
 * 添加/编辑众筹活动的提交
 */


function action_insert_update ()
{

	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	/* 取得众筹活动id */
	$pre_sale_id = intval($_POST['act_id']);
	if(isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail']))
	{
		if($pre_sale_id <= 0)
		{
			sys_msg($_LANG['error_pre_sale'], 1);
		}
		$pre_sale = pre_sell_info($pre_sale_id);
		if(empty($pre_sale))
		{
			sys_msg($_LANG['error_pre_sale'], 1);
		}
	}

	if(isset($_POST['finish']))
	{
		/* 设置活动结束 */
		
		/* 判断活动状态 */
		if($pre_sale['status'] != ZC_UNDER_WAY)
		{
			sys_msg($_LANG['error_status'], 1);
			// 此处怀疑是如果活动进行中突然要结束掉，应该抛出禁止的页面，貌似去掉了，所以程序继续执行
		}
		
		/* 结束众筹活动，修改结束时间为当前时间 */
		$sql = "UPDATE " . $ecs->table('goods_activity') . " SET end_time = '" . gmtime() . "' " . "WHERE act_id = '$pre_sale_id' LIMIT 1";
		$db->query($sql);
		
		/* 清除缓存 */
		clear_cache_files();
		
		/* 提示信息 */
		$links = array(
			array(
				'href' => 'pre_sell.php?act=list', 'text' => $_LANG['back_list']
			)
		);
		sys_msg($_LANG['edit_success'], 0, $links);
	}

	elseif(isset($_POST['succeed']))
	{
		/* 设置活动成功 */
		
		/* 判断订单状态 */
		if($pre_sale['status'] != ZC_FINISHED)
		{
			sys_msg($_LANG['error_status'], 1);
		}
		
		/* 如果有订单，更新订单信息 */
		if($pre_sale['total_order'] > 0)
		{
			/* 查找该众筹活动的已确认或未确认订单（已取消的就不管了） */
			$sql = "SELECT order_id " . "FROM " . $ecs->table('order_info') . " WHERE extension_code = '" . PRE_SELL_CODE . "' " . "AND extension_id = '$pre_sale_id' " . "AND (order_status = '" . OS_CONFIRMED . "' or order_status = '" . OS_UNCONFIRMED . "')";
			$order_id_list = $db->getCol($sql);
			
			/* 更新订单商品价 */
			$final_price = $pre_sale['trans_price'];
			$sql = "UPDATE " . $ecs->table('order_goods') . " SET goods_price = '$final_price' " . "WHERE order_id " . db_create_in($order_id_list);
			$db->query($sql);
			
			/* 查询订单商品总额 */
			$sql = "SELECT o.order_id,SUM(o.goods_number * p.pro_price) AS goods_amount " . "FROM " . $ecs->table('order_goods') . " as o  left join ". $GLOBALS['ecs']->table('products') ." as p ON o.product_id = p.product_id   WHERE o.order_id " . db_create_in($order_id_list) . " GROUP BY o.order_id";
			$res = $db->query($sql);
			while($row = $db->fetchRow($res))
			{
				$order_id = $row['order_id'];
				$goods_amount = floatval($row['goods_amount']);
				
				/* 取得订单信息 */
				$order = order_info($order_id);
				
				/* 判断订单是否有效：余额支付金额 + 已付款金额 >= 保证金 */
				if($order['surplus'] + $order['money_paid'] >= $pre_sale['deposit'])
				{
					/* 有效，设为已确认，更新订单 */
					
					// 更新商品总额
					$order['goods_amount'] = $goods_amount;
					
					// 如果保价，重新计算保价费用
					if($order['insure_fee'] > 0)
					{
						$shipping = shipping_info($order['shipping_id']);
						$order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], $goods_amount, $shipping['insure']);
					}
					
					// 重算支付费用
					$order['order_amount'] = $order['goods_amount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['pack_fee'] + $order['card_fee'] - $order['money_paid'] - $order['surplus'];
					if($order['order_amount'] > 0)
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
					if($order['order_amount'] > 0)
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
					if($order['order_amount'] < 0)
					{
						// todo （现在手工退款）
						// 退款到帐户余额
						order_refund($order, 1, $_LANG['pre_sale_order_refund'] . ':' . $order['order_sn'], abs($order['order_amount']));
						// 应付款赋值为0
						$order['order_amount'] = 0;
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
					if($money > 0)
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
		
		/* 修改众筹活动状态为成功 */
		$sql = "UPDATE " . $ecs->table('goods_activity') . " SET is_finished = '" . ZC_SUCCEED . "' " . "WHERE act_id = '$pre_sale_id' LIMIT 1";
		$db->query($sql);
		
		/* 清除缓存 */
		clear_cache_files();
		
		/* 提示信息 */
		$links = array(
			array(
				'href' => 'pre_sell.php?act=list', 'text' => $_LANG['back_list']
			)
		);
		sys_msg($_LANG['edit_success'], 0, $links);
	}

	elseif(isset($_POST['fail']))
	{
		/* 设置活动失败 */
		
		/* 判断订单状态 */
		if($pre_sale['status'] != ZC_FINISHED)
		{
			sys_msg($_LANG['error_status'], 1);
		}
		print_r($pre_sale);
		exit;
		/* 如果有有效订单，取消订单 */
		if($pre_sale['valid_order'] > 0)
		{
			/* 查找未确认或已确认的订单 */
			$sql = "SELECT * " . "FROM " . $ecs->table('order_info') . " WHERE extension_code = '" . PRE_SALE_CODE . "' " . "AND extension_id = '$pre_sale_id' " . "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "') ";
			$res = $db->query($sql);
			while($order = $db->fetchRow($res))
			{
				// 修改订单状态为已取消，付款状态为未付款
				$order['order_status'] = OS_CANCELED;
				$order['to_buyer'] = $_LANG['cancel_order_reason'];
				$order['pay_status'] = PS_UNPAYED;
				$order['pay_time'] = 0;
				
				/* 如果使用余额或有已付款金额，退回帐户余额 */
				$money = $order['surplus'] + $order['money_paid'];
				if($money > 0)
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
		
		/* 修改众筹活动状态为失败，记录失败原因（活动说明） */
		$sql = "UPDATE " . $ecs->table('goods_activity') . " SET is_finished = '" . ZC_FAIL . "', " . "act_desc = '$_POST[detail_desc]' " . "WHERE act_id = '$pre_sale_id' LIMIT 1";
		$db->query($sql);

		/* 清除缓存 */
		clear_cache_files();

		/* 提示信息 */
		$links = array(
			array(
				'href' => 'pre_sell.php?act=list', 'text' => $_LANG['back_list']
			)
		);
		sys_msg($_LANG['edit_success'], 0, $links);
	}
	elseif(isset($_POST['mail']))
	{
		/* 发送通知邮件 */

		/* 判断订单状态 */
		if($pre_sale['status'] != ZC_SUCCEED)
		{
			sys_msg($_LANG['error_status'], 1);
		}

		/* 取得邮件模板 */
		$tpl = get_mail_template('pre_sale');

		/* 初始化订单数和成功发送邮件数 */
		$count = 0;
		$send_count = 0;

		/* 取得有效订单 */
		$sql = "SELECT o.consignee, o.add_time, g.goods_number, o.order_sn, " . "o.order_amount, o.order_id, o.email " . "FROM " . $ecs->table('order_info') . " AS o, " . $ecs->table('order_goods') . " AS g " . "WHERE o.order_id = g.order_id " . "AND o.extension_code = '" . PRE_SALE_CODE . "' " . "AND o.extension_id = '$pre_sale_id' " . "AND o.order_status = '" . OS_CONFIRMED . "'";
		$res = $db->query($sql);
		while($order = $db->fetchRow($res))
		{
			/* 邮件模板赋值 */
			$smarty->assign('consignee', $order['consignee']);
			$smarty->assign('add_time', local_date($_CFG['time_format'], $order['add_time']));
			$smarty->assign('goods_name', $pre_sale['goods_name']);
			$smarty->assign('goods_number', $order['goods_number']);
			$smarty->assign('order_sn', $order['order_sn']);
			$smarty->assign('order_amount', price_format($order['order_amount']));
			$smarty->assign('shop_url', $ecs->url() . 'user.php?act=order_detail&order_id=' . $order['order_id']);
			$smarty->assign('shop_name', $_CFG['shop_name']);
			$smarty->assign('send_date', local_date($_CFG['date_format']));

			/* 取得模板内容，发邮件 */
			$content = $smarty->fetch('str:' . $tpl['template_content']);
			if(send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
			{
				$send_count ++;
			}
			$count ++;
		}

		/* 提示信息 */
		sys_msg(sprintf($_LANG['mail_result'], $count, $send_count));
	}
	else
	{

		/* 保存众筹信息 */
		$goods_id = intval($_POST['goods_id']);
		if($goods_id <= 0)
		{
			sys_msg($_LANG['error_goods_null']);
		}
		$info = goods_pre_sale($goods_id);

		if($info && $info['act_id'] != $pre_sale_id)
		{
			sys_msg($_LANG['error_goods_exist']);
		}

		// 判断商品是否已经参与了众筹活动
		$_pre_sale_id = is_pre_sELL_goods($goods_id);
		if(! empty($_pre_sale_id) && $_pre_sale_id != $pre_sale_id)
		{
			sys_msg($_LANG['error_goods_exist']);
		}

		$goods_name = $db->getOne("SELECT goods_name FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'");

		$act_name = empty($_POST['act_name']) ? $goods_name : sub_str($_POST['act_name'], 0, 255, false);

		// 众筹价格
        $total_price = intval($_POST['total_price']);
		if( $total_price < 0)
		{
            $total_price = 0;
		}


		$price_ladder = array();		
		$count = count($_POST['ladder_amount']);

		
		for($i = $count - 1; $i >= 0; $i --)
		{
			/* 如果数量小于等于0，不要 */
			$amount = intval($_POST['ladder_amount'][$i]);


			/* 如果价格小于等于0，不要 */
			$price = round(floatval($_POST['ladder_price'][$i]), 2);
			if($price <= 0)
			{
				continue;
			}
			/* 加入价格阶梯 */
			$price_ladder[$amount] = array(
				'amount'    => $amount,
				'price'     => $price,
				'desc'      => $_POST['ladder_desc'][$i],
				'limit_num' => $_POST['limit_num'][$i],
				'point'     => $_POST['point'][$i]
			);
		}
		if(count($price_ladder) < 1)
		{
			sys_msg($_LANG['error_price_ladder']);
		}
		ksort($price_ladder);
		$price_ladder = array_values($price_ladder);

		/* 检查开始时间和结束时间是否合理 */
		$start_time = local_strtotime($_POST['start_time']);
		$end_time = local_strtotime($_POST['end_time']);
		if($start_time >= $end_time)
		{
			// $_LANG['invalid_time']
			sys_msg('您输入了一个无效的时间，活动结束时间不能早于活动开始时间！');
		}

		// 预计发货时间描述
		$deliver_goods = $_POST['deliver_goods'];

		$sql = 'SELECT shop_price FROM '.$GLOBALS['ecs']->table('goods').'WHERE goods_name="'.$goods_name.'"';
		$shop_price = $GLOBALS['db']->getOne($sql);
		$pre_sale = array(
			// 活动名称
			'act_name' => $act_name,
			// 活动描述
			'act_desc' => $_POST['act_desc'],
			// 活动类型
			'act_type' => GAT_PRE_SELL,
			// 商品编号
			'goods_id' => $goods_id, 
			// 商品名称
			'goods_name' => $goods_name, 
			// 活动开始时间
			'start_time' => $start_time, 
			// 活动结束时间
			'end_time' => $end_time,
			//其它功能
			'ext_info' => serialize(array(
				// 众筹目标金额
				'sell_price'      =>  $total_price,
                // 已经筹总价格
                'get_price'       => 0,
                // 商品原始价格
                'initial_price'   => $shop_price,
				// 价格阶梯
				'price_ladder'    => $price_ladder,
                // 筹款达成率
                'price_rate'      => 0,
                // 积分
                'point'      => 0,
                // 发货描述
                'deliver_goods'   => $deliver_goods
			))
		);




		// 开始发货时间描述
		
		/* 清除缓存 */
		clear_cache_files();

		/* 保存数据 */
		if($pre_sale_id > 0)
		{
			/* update */
			$db->autoExecute($ecs->table('goods_activity'), $pre_sale, 'UPDATE', "act_id = '$pre_sale_id'");
			
			//修改之前去掉旧的数据，在插入新的数据 青 2017-10-11 09:37:13
			$sql = " DELETE FROM ".$GLOBALS['ecs']->table('zhongchou_price')." WHERE act_id = ".$info['act_id'];
			$GLOBALS['db']->query($sql);

			//添加众筹活动商品后  把价格阶梯插入到价格阶梯表中
            $sql = 'SELECT act_id FROM '.$GLOBALS['ecs']->table('goods_activity').'WHERE act_type = '.GAT_PRE_SELL.' ORDER BY act_id DESC LIMIT 1';
            $act_id = $GLOBALS['db']->getOne($sql);
            foreach ($price_ladder as $value)
            {
            	$zhongchou_price = array(
            		'act_id'   =>   $act_id,
					'price_id' =>   $value['amount'],
					'point' =>   $value['point'],
					'price'    =>   $value['price']
				);
                $db->autoExecute($ecs->table('zhongchou_price'), $zhongchou_price, 'INSERT');
            }
			
			/* log */
			admin_log(addslashes($goods_name) . '[' . $pre_sale_id . ']', 'edit', 'pre_sale');
			
			/* todo 更新活动表 */
			
			/* 提示信息 */
			$links = array(
				array(
					'href' => 'pre_sell.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_list']
				)
			);
			sys_msg($_LANG['edit_success'], 0, $links);
		}
		else
		{

			//一个商品只能众筹一次 青 2017-12-1 09:37:51
			$sql = "SELECT goods_id FROM ".$GLOBALS['ecs']->table('goods_activity')." WHERE goods_id = $goods_id";
			$num = $GLOBALS['db']->getOne($sql);
			if ($num) {
				sys_msg("该商品已参加过众筹活动");
			}
			/* insert */
			$db->autoExecute($ecs->table('goods_activity'), $pre_sale, 'INSERT');

			//添加众筹活动商品后  把价格阶梯插入到价格阶梯表中
            $sql = 'SELECT act_id FROM '.$GLOBALS['ecs']->table('goods_activity').'WHERE act_type = '.GAT_PRE_SELL.' ORDER BY act_id DESC LIMIT 1';
            $act_id = $GLOBALS['db']->getOne($sql);
            foreach ($price_ladder as $value)
            {
            	$zhongchou_price = array(
            		'act_id'   =>   $act_id,
					'price_id' =>   $value['amount'],
					'point' =>   $value['point'],
					'price'    =>   $value['price']
				);
                $db->autoExecute($ecs->table('zhongchou_price'), $zhongchou_price, 'INSERT');
            }

			/* log */
			admin_log(addslashes($goods_name), 'add', 'pre_sale');
			
			/* 提示信息 */
			$links = array(
				array(
					'href' => 'pre_sell.php?act=add', 'text' => $_LANG['continue_add']
				), array(
					'href' => 'pre_sell.php?act=list', 'text' => $_LANG['back_list']
				)
			);
			sys_msg($_LANG['add_success'], 0, $links);
		}
	}
}

/* ------------------------------------------------------ */
// -- 批量删除众筹活动
/* ------------------------------------------------------ */
function action_batch_drop ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	if(isset($_POST['checkboxes']))
	{
		$del_count = 0; // 初始化删除数量
		foreach($_POST['checkboxes'] as $key => $id)
		{
			/* 取得众筹活动信息 */
			$pre_sale = pre_sell_info($id);
			
			/* 如果众筹活动已经有订单，不能删除 */
			if($pre_sale['order_all'] <= 0)
			{
				/* 删除众筹活动 */
				$sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_id = '$id' LIMIT 1";
				$GLOBALS['db']->query($sql, 'SILENT');
				
				admin_log(addslashes($pre_sale['goods_name']) . '[' . $id . ']', 'remove', 'pre_sell');
				$del_count ++;
			}
		}
		
		/* 如果删除了众筹活动，清除缓存 */
		if($del_count > 0)
		{
			clear_cache_files();
		}
		
		$links[] = array(
			'text' => $_LANG['back_list'], 'href' => 'pre_sell.php?act=list'
		);
		sys_msg(sprintf($_LANG['batch_drop_success'], $del_count), 0, $links);
	}
	else
	{
		$links[] = array(
			'text' => $_LANG['back_list'], 'href' => 'pre_sell.php?act=list'
		);
		sys_msg($_LANG['no_select_pre_sale'], 0, $links);
	}
}

/**
 * 删除众筹活动
 */
function action_remove ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	if(isset($_REQUEST['act_id']))
	{
		
		$del_count = 0;
		
		$id = $_REQUEST['act_id'];
		/* 取得众筹活动信息 */
		$pre_sale = pre_sell_info($id);
		
		/* 如果众筹活动已经有订单，不能删除 */
		if($pre_sale['order_all'] <= 0)
		{
			/* 删除众筹活动 */
			$sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_id = '$id' LIMIT 1";
			$GLOBALS['db']->query($sql, 'SILENT');

			/* 删除该活动的价格表 */
			$sql = 'DELETE FROM'  . $GLOBALS['ecs']->table('zhongchou_price'). 'WHERE act_id = '.$id.'LIMIT 1';
            $GLOBALS['db']->query($sql, 'SILENT');

			admin_log(addslashes($pre_sale['goods_name']) . '[' . $id . ']', 'remove', 'pre_sell');
			$del_count ++;
		}
		
		/* 如果删除了众筹活动，清除缓存 */
		if($del_count > 0)
		{
			clear_cache_files();
		}
		
		$links[] = array(
			'text' => $_LANG['back_list'], 'href' => 'pre_sell.php?act=list'
		);
		sys_msg(sprintf($_LANG['batch_drop_success'], $del_count), 0, $links);
	}
	else
	{
		$links[] = array(
			'text' => $_LANG['back_list'], 'href' => 'pre_sell.php?act=list'
		);
		sys_msg($_LANG['no_select_pre_sale'], 0, $links);
	}
}

/**
 * 搜索商品
 */
function action_search_goods ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	
	check_authz_json('pre_sale');
	
	include_once (ROOT_PATH . 'includes/cls_json.php');
	
	$json = new JSON();
	// 非虚拟商品
	$filter = $json->decode($_GET['JSON']);
	$filter->is_virtual = 0;
	$arr = get_goods_list($filter);
	
	make_json_result($arr);
}

/*
 * 取得众筹活动列表
 * @return array
 */
function pre_sale_list ()
{
	$result = get_filter();
	if($result === false)
	{
		/* 过滤条件 */
		$filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
		{
			$filter['keyword'] = json_str_iconv($filter['keyword']);
		}
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		
		$where = (! empty($filter['keyword'])) ? " AND goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" : '';
		
		$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_type = '" . GAT_PRE_SELL . "' $where";
		$filter['record_count'] = $GLOBALS['db']->getOne($sql);
		
		/* 分页大小 */
		$filter = page_and_size($filter);
		
		/* 查询 */
		$sql = "SELECT * " . "FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE act_type = '" . GAT_PRE_SELL . "' $where " . " ORDER BY $filter[sort_by] $filter[sort_order] " . " LIMIT " . $filter['start'] . ", $filter[page_size]";

		$filter['keyword'] = stripslashes($filter['keyword']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}
	$res = $GLOBALS['db']->query($sql);

	$list = array();
	while($row = $GLOBALS['db']->fetchRow($res))
	{
		$ext_info = unserialize($row['ext_info']);
		$stat = pre_sale_stat($row['act_id'], $ext_info['deposit']);
		$arr = array_merge($row, $stat, $ext_info);
		
		/* 处理价格阶梯 */
		$price_ladder = $arr['price_ladder'];
		if(! is_array($price_ladder) || empty($price_ladder))
		{
			$price_ladder = array(
				array(
					'amount' => 0,'point' => 0, 'price' => 0
				)
			);
		}
		else
		{
			foreach($price_ladder as $key => $amount_price)
			{
				$price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
			}
		}
		
		/* 计算当前价 */
		$cur_price = $price_ladder[0]['price']; // 初始化
		$cur_amount = $stat['valid_goods']; // 当前数量
		foreach($price_ladder as $amount_price)
		{
			if($cur_amount >= $amount_price['amount'])
			{
				$cur_price = $amount_price['price'];
			}
			else
			{
				break;
			}
		}
		
		$arr['cur_price'] = $cur_price;

//		print_r($arr);exit();
		//获取状态和已筹天数
        $state = pre_sell_status($arr);


        $info =  pre_sell_info($arr['act_id']);


//        var_dump($info['back_status']);exit();


//		//定义天数变量
//		$days ='';

		//判断是否为数组 若为数组则 为
		if (is_array($state)){
			$status = $state['status'];
			$days = empty($state['days'])? 0 : $state['days'];
		}else{
			$status = $state;
			$days = 0;
		}


		//判断众筹活动是否失败
//        if ($status == ZC_FAIL){
//        $sql = 'SELECT user_id,order_price FROM'.$GLOBALS['ecs']->table('zhongchou_order').'WHERE act_id = '.$arr['act_id'];
//        $user_info = $GLOBALS['db']->getAll($sql);
//			foreach ($user_info as $values){
//				log_account_change($values['user_id'],$values['order_price'],0,0,0,'众筹失败退款');
//			}
//        }

//		print_r($info);exit();
		$arr['start_time']  = local_date($GLOBALS['_CFG']['date_format'], $arr['start_time']);
		$arr['end_time']    = local_date($GLOBALS['_CFG']['date_format'], $arr['end_time']);
		$arr['days']        = $days;
		$arr['now_price']   = $info['now_price'];
		$arr['get_rate']    = $info['get_rate'];
		$arr['order_all']   = $info['order_all'];
		$arr['goods_num']   = $info['goods_num'];
		

        if ($status == ZC_FINISHED){

            if ($info['now_price'] >=  $info['sell_price']){
                $arr['cur_status']  = $GLOBALS['_LANG']['zc'][$status].','.$GLOBALS['_LANG']['zc'][ZC_SUCCEED].','.$GLOBALS['_LANG']['bm'][$info['back_status']];
            }else{
            	if ($info['back_status'] === null ){
                    $arr['cur_status']  = $GLOBALS['_LANG']['zc'][$status].','.$GLOBALS['_LANG']['zc'][ZC_FAIL].','.$GLOBALS['_LANG']['bm'][0];
				}else{
                    $arr['cur_status']  = $GLOBALS['_LANG']['zc'][$status].','.$GLOBALS['_LANG']['zc'][ZC_FAIL].','.$GLOBALS['_LANG']['bm'][$info['back_status']];
                    $arr['back_money']  = 1;
				}
			}
        }else{

        	$arr['cur_status'] = $GLOBALS['_LANG']['zc'][$status];
		}
		$list[] = $arr;
	}
	$arr = array(
		'item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);

	return $arr;
}

/**
 * 取得某商品的众筹活动
 *
 * @param int $goods_id
 *        	商品id
 * @return array
 */
function goods_pre_sale ($goods_id)
{
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('goods_activity') . " WHERE goods_id = '$goods_id' " . " AND act_type = '" . GAT_PRE_SELL . "'";
	
	return $GLOBALS['db']->getRow($sql);
}

/**
 * 列表链接
 *
 * @param bool $is_add
 *        	是否添加（插入）
 * @return array('href' => $href, 'text' => $text)
 */
function list_link ($is_add = true)
{
	$href = 'pre_sell.php?act=list';
	if(! $is_add)
	{
		$href .= '&' . list_link_postfix();
	}
	
	return array(
		'href' => $href, 'text' => $GLOBALS['_LANG']['pre_sale_list']
	);
}
?>
