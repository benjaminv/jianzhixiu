<?php

/**
 * ECSHOP 支付接口函数库
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: lib_payment.php 17218 2011-01-24 04:10:41Z derek $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 取得返回信息地址
 * @param   string  $code   支付方式代码
 */
function return_url($code)
{
    return $GLOBALS['ecs']->url() . 'respond.php?code=' . $code;
}

/**
 *  取得某支付方式信息
 *  @param  string  $code   支付方式代码
 */
function get_payment($code)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment').
           " WHERE pay_code = '$code' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

/**
 *  通过订单sn取得订单ID
 *  @param  string  $order_sn   订单sn
 *  @param  blob    $voucher    是否为会员充值
 */
function get_order_id_by_sn($order_sn, $voucher = 'false')
{
    if ($voucher == 'true')
    {
        if(is_numeric($order_sn))
        {
              return $GLOBALS['db']->getOne("SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id=" . $order_sn . ' AND order_type=1');
        }
        else
        {
            return "";
        }
    }
    else
    {
        if(is_numeric($order_sn))
        {
            $sql = 'SELECT order_id FROM ' . $GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '$order_sn'";
            $order_id = $GLOBALS['db']->getOne($sql);
        }
        if (!empty($order_id))
        {
            $pay_log_id = $GLOBALS['db']->getOne("SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id='" . $order_id . "'");
            return $pay_log_id;
        }
        else
        {
            return "";
        }
    }
}

/**
 *  通过订单ID取得订单商品名称
 *  @param  string  $order_id   订单ID
 */
function get_goods_name_by_id($order_id)
{
    $sql = 'SELECT goods_name FROM ' . $GLOBALS['ecs']->table('order_goods'). " WHERE order_id = '$order_id'";
    $goods_name = $GLOBALS['db']->getCol($sql);
    return implode(',', $goods_name);
}

/**
 * 检查支付的金额是否与订单相符
 *
 * @access  public
 * @param   string   $log_id      支付编号
 * @param   float    $money       支付接口返回的金额
 * @return  true
 */
function check_money($log_id, $money)
{
    if(is_numeric($log_id))
    {
        $sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table('pay_log') .
              " WHERE log_id = '$log_id'";
        $amount = $GLOBALS['db']->getOne($sql);
    }
    else
    {
        return false;
    }
    if ($money == $amount)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 修改订单的支付状态
 *
 * @access  public
 * @param   string  $log_id     支付编号
 * @param   integer $pay_status 状态
 * @param   string  $note       备注
 * @return  void
 */
function order_paid($log_id, $pay_status = PS_PAYED, $note = '',$out_trade_no='',$transaction_id= '')
{
	
    /* 取得支付编号 */
    $log_id = intval($log_id);
    if ($log_id > 0)
    {
        /* 取得要修改的支付记录信息 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pay_log') .
                " WHERE log_id = '$log_id'";
        $pay_log = $GLOBALS['db']->getRow($sql);
		
        if ($pay_log && $pay_log['is_paid'] == 0)
        {
            /* 修改此次支付操作的状态为已付款 */
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('pay_log') . " SET is_paid = '1' WHERE log_id = '$log_id'";
            $GLOBALS['db']->query($sql);
            /* 根据记录类型做相应处理 */
            if ($pay_log['order_type'] == PAY_ORDER)
            {
                /* 取得订单信息 */
                $sql = 'SELECT order_id, user_id,supplier_id, order_sn, consignee, address, tel, shipping_id, extension_code, extension_id, goods_amount ' .
                        'FROM ' . $GLOBALS['ecs']->table('order_info') .
                       " WHERE order_id = '$pay_log[order_id]' OR parent_order_id = '$pay_log[order_id]' ";
                $orderinfo    = $GLOBALS['db']->getAll($sql);
				
				
					require_once(ROOT_PATH . '/includes/lib_order.php');
					   foreach($orderinfo as $key => $order)
					   {
							$order_id = $order['order_id'];
							$order_sn = $order['order_sn'];
							$suppid = $order['supplier_id'];
							$supplier[$suppid]	 = $order_sn;
					
							/* 修改订单状态为已付款 */
							$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
										" SET order_status = '" . OS_CONFIRMED . "', " .
										" confirm_time = '" . gmtime() . "', " .
										" pay_status = '$pay_status', " .
										" out_trade_no = '$out_trade_no', " .    //PRINCE 120029121
										" transaction_id = '$transaction_id', " . //PRINCE 120029121
										" pay_time = '".gmtime()."', " .
										" money_paid = order_amount," .
										" order_amount = 0 ".
										"WHERE order_id = '$order_id'";
							$GLOBALS['db']->query($sql);

							/*yhy 2019/5/16 支付成功发放订单红包*/
							
							send_order_bonus($order_id,$suppid);
							/*yhy 2019/5/16 支付成功发放订单红包 end*/

							/* 记录订单操作记录 */
							order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
							//微信消息 PRINCE 120029121
							if($order['extension_code']=='extpintuan'){
								include_once(ROOT_PATH.'wxm_extpintuan.php');
								send_order_message($order_id);
							}
							 //palenggege修改  付款给客户发短信
							 include_once(ROOT_PATH. 'sms/sms.php');
							 send_sms($supplier,2,$mobile,$order_sn);
									

						   //palenggege修改结束
						  /* 如果需要，发微信 */  
							$wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
							$wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);//手机端网址
							file_get_contents_new($wap_url."weixin/weixin_remind.php?notice=2&is_one_user=".$order['user_id']."&order_id=".$order['order_id']);
						  
							/* 对虚拟商品的支持 */
							$virtual_goods = get_virtual_goods($order_id);
							if (!empty($virtual_goods))
							{
								$msg = '';
								//palenggege修改开始
								 if (virtual_goods_ship($virtual_goods, $msg, $order_sn, true))
								{
									foreach($virtual_goods['virtual_good'] as $key=>$val){
										if($val['supplier_id']){
											$supplier_name = $GLOBALS['db']->getOne("select supplier_name from ".$GLOBALS['ecs']->table('supplier')." where supplier_id = $val[supplier_id]");
										}else{
											 $supplier_name = '网站自营';
										}
										$card = $GLOBALS['db']->getAll("select card_sn from ".$GLOBALS['ecs']->table('virtual_goods_card')." where order_sn='$order_sn'");
										include_once(ROOT_PATH .  'languages/' .$GLOBALS['_CFG']['lang']. '/user.php');
										foreach($card as $k=>$v){  
											$card_sn .= $v['card_sn'].", ";
										}   
										$sj=local_date('Y-m-d',$val['valid_date']);
										$sj=(string)$sj;
										$card_sn=substr($card_sn, 0, -2);
									    $aa=array(
										   'supplier_name'=>$supplier_name,
											'goods_name'=>$val['goods_name'],
											'card_sn'=>$card_sn,
											'vali_date'=>$sj
									    );
  									    $moban=trim($GLOBALS['_CFG']['dayu_xuni_tpl']);
										$result = sendSMS($mobile_phone,$aa,$moban); 
									}
								   
							    }else{
									$GLOBALS['_LANG']['pay_success'] .= '<div style="color:red;">'.$msg.'</div>'.$GLOBALS['_LANG']['virtual_goods_ship_fail'];
							    }  
								//palenggege修改结束
								/* 如果订单没有配送方式，自动完成发货操作 */
								if ($order['shipping_id'] == -1)
								{
									/* 将订单标识为已发货状态，并记录发货记录 */
									$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
										   " SET shipping_status = '" . SS_SHIPPED . "', shipping_time = '" . gmtime() . "'" .
										   " WHERE order_id = '$order_id'";
									$GLOBALS['db']->query($sql);
									 /* 记录订单操作记录 */
									order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
									$integral = integral_to_give($order);
									log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));
								}
							}
					   }

				//edit by yhy  代码只能放在最后面，如果同步失败会报错
				$ordersns = array_column($orderinfo,'order_sn');
				if(ODOO_ERP){
					$odooErpObj = OdooErp::getInstance();
					//edit yhy 同步erp中的会员
					$res = $odooErpObj->syncOrderByOrdersns($ordersns);	
				}
				//end

			}
			elseif ($pay_log['order_type'] == PAY_SURPLUS)
			{
						$sql = 'SELECT `id` FROM ' . $GLOBALS['ecs']->table('user_account') .  " WHERE `id` = '$pay_log[order_id]' AND `is_paid` = 1  LIMIT 1";
						$res_id=$GLOBALS['db']->getOne($sql);
						if(empty($res_id))
						{
							/* 更新会员预付款的到款状态 */
							$sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_account') .
								   " SET paid_time = '" .gmtime(). "', is_paid = 1" .
								   " WHERE id = '$pay_log[order_id]' LIMIT 1";
							$GLOBALS['db']->query($sql);

							/* 取得添加预付款的用户以及金额 */
							$sql = "SELECT user_id, amount FROM " . $GLOBALS['ecs']->table('user_account') .
									" WHERE id = '$pay_log[order_id]'";
							$arr = $GLOBALS['db']->getRow($sql);

							/* 修改会员帐户金额 */
							$_LANG = array();
							include_once(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');
							log_account_change($arr['user_id'], $arr['amount'], 0, 0, 0, $_LANG['surplus_type_0'], ACT_SAVING);
						}
			}
		}
        else
        {
            /* 取得已发货的虚拟商品信息 */
            $post_virtual_goods = get_virtual_goods($pay_log['order_id'], true);

            /* 有已发货的虚拟商品 */
            if (!empty($post_virtual_goods))
            {
                $msg = '';
                /* 检查两次刷新时间有无超过12小时 */
                $sql = 'SELECT pay_time, order_sn FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$pay_log[order_id]'";
                $row = $GLOBALS['db']->getRow($sql);
                $intval_time = gmtime() - $row['pay_time'];
                if ($intval_time >= 0 && $intval_time < 3600 * 12)
                {
                    $virtual_card = array();
                    foreach ($post_virtual_goods as $code => $goods_list)
                    {
                        /* 只处理虚拟卡 */
                        if ($code == 'virtual_card')
                        {
                            foreach ($goods_list as $goods)
                            {
                                if ($info = virtual_card_result($row['order_sn'], $goods))
                                {
                                    $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>$info);
                                }
                            }

                            $GLOBALS['smarty']->assign('virtual_card',      $virtual_card);
                        }
                    }
                }
                else
                {
                    $msg = '<div>' .  $GLOBALS['_LANG']['please_view_order_detail'] . '</div>';
                }

                $GLOBALS['_LANG']['pay_success'] .= $msg;
            }

           /* 取得未发货虚拟商品 */
           $virtual_goods = get_virtual_goods($pay_log['order_id'], false);
           if (!empty($virtual_goods))
           {
               $GLOBALS['_LANG']['pay_success'] .= '<br />' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
           }
        }
    }
}

?>