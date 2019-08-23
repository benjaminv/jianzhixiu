<?php
	define('IN_ECS', true);
	require('./init.php');
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	set_time_limit(0);  //设置程序执行时间
	ignore_user_abort(true);    //设置断开连接继续执行
	header('X-Accel-Buffering: no');    //关闭buffer
	header('Content-type: text/html;charset=utf-8');    //设置网页编码
	ob_start(); //打开输出缓冲控制

	if(ODOO_ERP){
		//查询最近的一个未执行任务
		$sql = 'SELECT * FROM '. $ecs->table('queue') .' WHERE operate_status=0 ORDER BY create_time ASC,id ASC LIMIT 0,20';
		$list = $db->getAll($sql);
		
		$odooErpObj = OdooErp::getInstance();

		foreach($list as $k=>$info){
			$queue_update_data = array('operate_status'=>1,'operate_time'=>time());
			$param = unserialize($info['queue_param']);
			switch($info['queue_type']){
				case "0"://0会员
					if(!$param['userid']){
                    	$res = array('SuccessCode'=>0,'faultString'=>'param error');
                    }else{
						$res = $odooErpObj->syncUserByUseridFromQueue($param['userid']);
                    }
					break;
				case "1"://1订单
					if(!$param['ordersns']){
                    	$res = array('SuccessCode'=>0,'faultString'=>'param error');
                    }else{
						$res = $odooErpObj->syncOrderByOrdersnsFromQueue($param['ordersns']);
                    }
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

			echo $info['id']."执行完成<br/>";
			echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
			flush();   //刷新缓冲区的内容，输出
		}
			
	}
