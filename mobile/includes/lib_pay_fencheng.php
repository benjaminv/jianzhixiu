<?php

/**
 * ECSHOP 付款自动分成文件
 * ===========================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://www.dm299.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务；  寒冰   QQ   paleng 
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: derek $
 * $Id: affiliate_ck.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);

function get_pay_fencheng($order_id)
{
	$row = $GLOBALS['db']->getRow("SELECT o.order_sn,u.parent_id, o.is_separate,(o.goods_amount - o.discount) AS goods_amount, o.user_id,o.bonus_id,o.bonus FROM " . $GLOBALS['ecs']->table('order_info') . " o"." LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id"." WHERE order_id = '$order_id'");
	
	//edit yhy 2019/5/17 使用红包的订单不参与任何分成
		if($row['bonus_id'] > 0 && $row['bonus']>0){
			return false;
		}else{
	//end
			$sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'affiliate'";
			$affiliatearr = $GLOBALS['db']->getOne($sql);
			$affiliate = unserialize($affiliatearr);
			empty($affiliate) && $affiliate = array();
			$separate_by = $affiliate['config']['separate_by'];
			$oid = $order_id;
			//获取订单分成金额
			$split_money = get_split_money_by_orderid($oid);
			
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
				$row = $GLOBALS['db']->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
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
					//push_user_msg($up_uid,$order_sn,$setmoney);
					
					write_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $separate_by,$_LANG['order_separate']);
				}
				$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
					   " SET is_separate = 1" .
					   " WHERE order_id = '$oid'";
				$GLOBALS['db']->query($sql);
			}
			  //个人购买增加分成
			  
				$separate_personal = $affiliate['config']['ex_fenxiao_personal'];
				$personal_lever_money = $affiliate['config']['personal_lever_money'];	
			   if ($separate_personal > 0){
						$personal_data = $GLOBALS['db']->getRow("SELECT o.user_id,u.user_name,u.rank_points,u.is_fenxiao FROM " . $GLOBALS['ecs']->table('order_info') . " o".
								" LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id".
								" WHERE order_id = '$oid'");
						$personal_pay_money = $GLOBALS['db']->getOne("SELECT sum(goods_amount) FROM " . $GLOBALS['ecs']->table('order_info')." where user_id = ".$personal_data['user_id']);
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
								$personal_up_id = $GLOBALS['db']->getOne("SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') .
								" WHERE user_id = '$personal_id'");
								$personal_up_name = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') .
								" WHERE user_id = '$personal_up_id'");
								if(!empty($personal_up_id)){
									log_account_change($personal_up_id, $personalMoney, 0, $personalPoint, 0, $info);
									push_user_msg($personal_up_id,$order_sn,$personalMoney);
									write_affiliate_log($oid,$personal_up_id,$personal_up_name,$personalMoney, $personalPoint, $separate_by,$separate_personal);
								}
								 
								
						   }
						
						  $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
					   " SET is_separate = 1" .
					   " WHERE order_id = '$oid'";
				$GLOBALS['db']->query($sql); 
						   
					}
			
		   
			
			 $wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
			$wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);//手机端网址
			@file_get_contents($wap_url."/weixin/auto_do.php?type=1&is_affiliate=1");
		}	
}

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

//获取某一个订单的分成金额
function get_split_money_by_orderid($order_id)
{
   $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'distrib_type'";
    $distrib_type = $GLOBALS['db']->getOne($sql);

	 if($distrib_type == 0)
	 {
		 $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_money";
		 //按订单分成
		 $sql = "SELECT " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'";
		 $total_fee = $GLOBALS['db']->getOne($sql);
		 $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'distrib_percent'";
         $distrib_percent = $GLOBALS['db']->getOne($sql);
		 $split_money = $total_fee*($distrib_percent/100);
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


?>