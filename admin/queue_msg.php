<?php

/**
 * queue_msg 队列 
 * ============================================================================
 */

define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 三方客服列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 模板赋值 */
    $filter = array();
    $smarty->assign('ur_here', $_LANG['queue_msg']);
    $smarty->assign('full_page', 1);
    $smarty->assign('filter', $filter);
    $result = get_queue_list();
	
    $smarty->assign('list', $result['item']);
    $smarty->assign('filter', $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count', $result['page_count']);
    $sort_flag = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示客服列表页面 */
    assign_query_info();
    $smarty->display('queue_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    //check_authz_json('third_customer');

    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
	$smarty->assign('ur_here', $_LANG['queue_msg']);
    $result = get_queue_list();

    $smarty->assign('list', $result['item']);
    $smarty->assign('filter', $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count', $result['page_count']);

    $sort_flag = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('queue_list.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}
/*------------------------------------------------------ */
//-- 删除三方客服信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
	//check_authz_json('third_customer');
    $id = intval($_GET['id']);
    $db->query("DELETE FROM " . $ecs->table('queue') . " WHERE id = $id");
    $url = 'queue_msg.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
	
    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除三方客服信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_drop')
{
	//check_authz_json('third_customer');

    if (isset($_POST['checkboxes']))
    {
        $count = 0;
        foreach ($_POST['checkboxes'] AS $key => $id)
        {
            $sql = "DELETE FROM " .$ecs->table('queue'). " WHERE id = ".intval($id);
            $db->query($sql);
            $count++;
        }
        /* 提示信息 */
        $link[] = array('text' => $_LANG['back_queue_list'], 'href'=>'queue_msg.php?act=list');
        sys_msg(sprintf($_LANG['drop_success'], $count), 0, $link);
    }
    else
    {
        $link[] = array('text' => $_LANG['back_queue_list'], 'href'=>'queue_msg.php?act=list');
        sys_msg($_LANG['no_select_tag'], 0, $link);
    }
}
elseif($_REQUEST['act'] == 'done')
{
	$id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
	//查询最近的一个未执行任务
	if(ODOO_ERP){
		$sql = 'SELECT * FROM '. $ecs->table('queue') .' WHERE operate_status=0 AND id='.intval($id);
		$info = $db->getRow($sql);
		if(!isset($info) || empty($info)){
			$link[] = array('text' => $_LANG['back_queue_list'], 'href'=>'queue_msg.php?act=list');
			sys_msg($_LANG['no_exist_or_disabled'], 0, $link);
		}else{
			$odooErpObj = OdooErp::getInstance();
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
				$queue_update_data['operate_result'] = $res['faultString'];
			}
			$db->autoExecute($ecs->table('queue'), $queue_update_data, 'UPDATE', 'id = '.$info['id']);
			$link[] = array('text' => $_LANG['back_queue_list'], 'href'=>'queue_msg.php?act=list');
			sys_msg($queue_update_data['operate_result'], 0, $link);
		}
	}else{
		$link[] = array('text' => $_LANG['back_queue_list'], 'href'=>'queue_msg.php?act=list');
			sys_msg($_LANG['odoo_erp_off'], 0, $link);
	}
}

/**
 * 分页获取三方客服列表
 *
 * @return array
 */
function get_queue_list ()
{
    $result = get_filter();
    if($result === false)
    {
        $filter = array();
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //$filter['id'] = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		$where = "";
        
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('queue') . " WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('queue') . 
               " WHERE 1 $where " . " ORDER BY $filter[sort_by] $filter[sort_order] " .
               " LIMIT " . $filter['start'] . ", $filter[page_size]";
        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $list = $GLOBALS['db']->getAll($sql);

    foreach($list as $k=>$v)
    {
		$list[$k]['formated_create_time'] = local_date('Y-m-d H:i:s', $v['create_time']);
		$list[$k]['queue_type_name'] = queue_type_name($v['queue_type']);
        
		if($v['operate_status'] == 1){
			$list[$k]['operator_status_name'] = '已执行';
		}else{
			$list[$k]['operator_status_name'] = '未执行';
		}
		
		if($v['operate_status'] == 0){
			$list[$k]['result_status_name'] = '未执行';
		}elseif($v['result_status'] == 0){
			$list[$k]['result_status_name'] = '失败';
		}else{
			$list[$k]['result_status_name'] = '成功';
		}
    }
    $arr = array(
        'item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
    );

    return $arr;
}


function queue_type_name($type){
	switch($type){
		case "0":
			return '会员';
			break;
		case "1":
			return '订单';
			break;
		case "2":
			return '退款单';
			break;
		default:
			return '未知';
	}
}
?>
