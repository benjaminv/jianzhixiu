<?php
/****************************************************************************
*** 计划任务通道
*** 
***
****************************************************************************/

define('IN_ECS', true);
require('./init.php');
error_reporting(0);
if(ODOO_ERP){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$odooErpObj = OdooErp::getInstance();
	//查询最近的一个未执行任务
	$sql = 'SELECT * FROM '. $ecs->table('queue') .' WHERE operate_status=0 ORDER BY create_time ASC,id ASC ';
	$info = $db->getRow($sql);
	if(!isset($info) || empty($info)){
		echo "<br/>";
		echo "<br/>";
		echo "no result";
		echo "<br/>";
		echo "<br/>";
	}else{
		
		$queue_update_data = array('operate_status'=>1,'operate_time'=>time());
		$param = unserialize($info['queue_param']);
		switch($info['queue_type']){
			case "0"://0会员
				$res = $odooErpObj->syncUserByUseridFromQueue($param['userid']);
				break;
			case "1"://1订单
				$res = $odooErpObj->syncOrderByOrdersnsFromQueue($param['ordersns']);
				break;
			case "2"://2退款单
				$res = $odooErpObj->syncRefundOrderByOrdersnFromQueue($param['back_id'],$param['mode'],$param['back_type']);
				break;
		}
		if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
			$queue_update_data['result_status'] = 1;
			$queue_update_data['operate_result'] = '同步成功';
		}else{
			$queue_update_data['operate_result'] = get_magic_quotes_gpc($res['faultString']);
		}
		$db->autoExecute($ecs->table('queue'), $queue_update_data, 'UPDATE', 'id = '.$info['id']);
		echo "end";
	}
}
