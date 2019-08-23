<?php
function cut_info($act_id, $config = false)
{
	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_id = \'' . $act_id . '\'';
	$cut = $GLOBALS['db']->getRow($sql);

	if ($cut['act_type'] != GAT_CUT) {
		return array();
	}

	$cut['status_no'] = cut_status($cut);

	if ($config == true) {
		$cut['start_time'] = local_date('Y-m-d H:i', $cut['start_time']);
		$cut['end_time'] = local_date('Y-m-d H:i', $cut['end_time']);
	}
	else {
		$cut['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $cut['start_time']);
		$cut['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $cut['end_time']);
	}

	$ext_info = unserialize($cut['ext_info']);
	$cut = array_merge($cut, $ext_info);
	$cut['formated_start_price'] = price_format($cut['start_price']);
	$cut['formated_end_price'] = price_format($cut['end_price']);
	$cut['formated_max_price'] = price_format($cut['max_price']);
	$cut['formated_deposit'] = price_format($cut['deposit']);
	$cut['goods_name'] = $cut['act_name'] ? $cut['act_name'] : $cut['goods_name'];
	return $cut;
}

function cut_log($act_id)
{
	$log = array();
	$sql = 'SELECT a.* ' . 'FROM ' . $GLOBALS['ecs']->table('cut') . ' AS a ' . 'WHERE act_id = \'' . $act_id . '\' ' . 'ORDER BY a.new_price ASC LIMIT 10';
	$res = $GLOBALS['db']->query($sql);
	$rownum = 1;

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$row['cut_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['cut_time']);
		$row['user_nickname'] = $row['user_nickname'];
		$row['shop_price'] = price_format($row['shop_price'], false);
		$row['new_price'] = price_format($row['new_price'], false);
		$row['rownum'] = $rownum;
		$rownum = $rownum + 1;
		$log[] = $row;
	}

	return $log;
}

function user_cut_log($user_id, $act_id, $page = 1)
{
	$count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('cut_log') . ' WHERE act_user = \'' . $user_id . '\' AND act_id = \'' . $act_id . '\' ');
	$size = 10;
	$page_count = (0 < $count ? intval(ceil($count / $size)) : 1);
	$log = array();
	$sql = 'SELECT c.* ' . 'FROM ' . $GLOBALS['ecs']->table('cut_log') . ' AS c  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('cut') . ' AS u ON (u.user_id = c.act_user and u.act_id=c.act_id) ' . 'WHERE u.user_id = \'' . $user_id . '\' ' . 'AND u.act_id = \'' . $act_id . '\' ' . 'ORDER BY c.log_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$row['cut_user_nickname'] = $row['cut_user_nickname'];
		$row['formated_cut_price'] = price_format($row['cut_price'], false);
		$row['formated_cut_price'] = price_format($row['cut_price'], false);
		$row['formated_after_cut_price'] = price_format($row['after_cut_price'], false);
		$log[] = $row;
	}

	$pager['page'] = $page;
	$pager['size'] = $size;
	$pager['record_count'] = $count;
	$pager['page_count'] = $page_count;
	$pager['page_first'] = 'javascript:gotoPage(1,' . $id . ',' . $type . ')';
	$pager['page_prev'] = 1 < $page ? 'cut.php?act=logpage&id=' . $act_id . '&actuid=' . $user_id . '&page=' . ($page - 1) : false;
	$pager['page_next'] = $page < $page_count ? 'cut.php?act=logpage&id=' . $act_id . '&actuid=' . $user_id . '&page=' . ($page + 1) : false;
	$pager['page_last'] = $page < $page_count ? 'javascript:gotoPage(' . $page_count . ',' . $id . ',' . $type . ')' : 'javascript:;';
	$log = array('log' => $log, 'pager' => $pager);
	return $log;
}

function cut_status($cut)
{
	$now = gmtime();

	if ($cut['is_finished'] == 0) {
		if ($now < $cut['start_time']) {
			return PRE_START;
		}

		if ($cut['end_time'] < $now) {
			return FINISHED;
		}

		return UNDER_WAY;
	}

	if ($cut['is_finished'] == 1) {
		return FINISHED;
	}

	return SETTLED;
}

function cut_count()
{
	$now = gmtime();
	$sql = 'SELECT COUNT(*) ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . 'WHERE act_type = \'' . GAT_CUT . '\' ' . 'AND start_time <= \'' . $now . '\' AND end_time >= \'' . $now . '\' AND is_finished < 2';
	return $GLOBALS['db']->getOne($sql);
}

function cut_list($size, $page)
{
	$cut_list = array();
	$cut_list['finished'] = $cut_list['finished'] = array();
	$now = gmtime();
	$sql = 'SELECT a.*,g.*, IFNULL(g.goods_thumb, \'\') AS goods_thumb ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS a ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON a.goods_id = g.goods_id ' . 'WHERE a.act_type = \'' . GAT_CUT . '\' ' . 'AND a.start_time <= \'' . $now . '\' AND a.end_time >= \'' . $now . '\' AND a.is_finished < 2 ORDER BY a.act_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, 100000, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($row['ext_info']);
		$cut = array_merge($row, $ext_info);
		$cut['status_no'] = cut_status($cut);
		$cut['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $cut['start_time']);
		$cut['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $cut['end_time']);
		$cut['formated_start_price'] = price_format($cut['start_price']);
		$cut['formated_end_price'] = price_format($cut['end_price']);
		$cut['formated_deposit'] = price_format($cut['deposit']);
		$cut['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$cut['url'] = build_uri('cut', array('auid' => $cut['act_id']));
		$cut['shop_price'] = price_format($row['shop_price']);
		$cut['goods_name'] = $row['act_name'] ? $row['act_name'] : $row['goods_name'];

		if ($cut['status_no'] < 2) {
			$cut_list['under_way'][] = $cut;
		}
		else {
			$cut_list['finished'][] = $cut;
		}
	}

	$cut_list = @array_merge($cut_list['under_way'], $cut_list['finished']);
	return $cut_list;
}

function cut_user_list($size, $page, $act_user)
{
	$cut_list = array();
	$cut_list['finished'] = $cut_list['finished'] = array();
	$now = gmtime();
	$sql = 'SELECT u.*,a.*,g.*, IFNULL(g.goods_thumb, \'\') AS goods_thumb ' . 'FROM ' . $GLOBALS['ecs']->table('cut') . ' AS u ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_activity') . ' AS a ON u.act_id  = a.act_id  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON a.goods_id = g.goods_id ' . 'WHERE a.act_type = \'' . GAT_CUT . '\' ' . 'AND u.user_id=\'' . $act_user . '\'  ORDER BY u.cut_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, 100000, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($row['ext_info']);
		$cut = array_merge($row, $ext_info);
		$cut['status_no'] = cut_status($cut);
		$cut['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $cut['start_time']);
		$cut['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $cut['end_time']);
		$cut['formated_start_price'] = price_format($cut['start_price']);
		$cut['formated_end_price'] = price_format($cut['end_price']);
		$cut['formated_deposit'] = price_format($cut['deposit']);
		$cut['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$cut['url'] = build_uri('cut', array('auid' => $cut['act_id']));
		$cut['shop_price'] = price_format($row['shop_price']);
		$cut['goods_name'] = $row['act_name'] ? $row['act_name'] : $row['goods_name'];

		if ($cut['status_no'] < 2) {
			$cut_list['under_way'][] = $cut;
		}
		else {
			$cut_list['finished'][] = $cut;
		}
	}

	$cut_list = @array_merge($cut_list['under_way'], $cut_list['finished']);
	return $cut_list;
}

function get_cutlist($supplier_id=0)
{
	$result = get_filter();

	if ($result === false) {
		$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
		if (isset($_REQUEST['is_ajax']) && ($_REQUEST['is_ajax'] == 1)) {
			$filter['keywords'] = json_str_iconv($filter['keywords']);
		}

		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		$where = (!empty($filter['keywords']) ? ' AND act_name like \'%' . mysql_like_quote($filter['keywords']) . '%\'' : '');
		$where .= ($supplier_id>0) ? ' AND supplier_id =\''.$supplier_id.'\' ' : '';
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_type =' . GAT_CUT . $where;
		$filter['record_count'] = $GLOBALS['db']->getOne($sql);
		$filter = page_and_size($filter);
		$sql = 'SELECT act_id, act_name AS cut_name, goods_name, start_time, end_time, is_finished, ext_info, product_id ' . ' FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_type = ' . GAT_CUT . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else {
		$sql = $result['sql'];
		$filter = $result['filter'];
	}
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['start_time']);
		$row[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['end_time']);
		$info = unserialize($row[$key]['ext_info']);
		unset($row[$key]['ext_info']);

		if ($info) {
			foreach ($info as $info_key => $info_val) {
				$row[$key][$info_key] = $info_val;
			}
		}
	}

	$arr = array('cuts' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_cut_info($id)
{
	global $ecs;
	global $db;
	global $_CFG;
	$sql = 'SELECT act_id, act_name AS cut_name, goods_id, product_id, goods_name, start_time, end_time, act_desc, ext_info' . ' FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_id=\'' . $id . '\' AND act_type = ' . GAT_CUT;
	$cut = $db->GetRow($sql);
	$cut['start_time'] = local_date('Y-m-d H:i', $cut['start_time']);
	$cut['end_time'] = local_date('Y-m-d H:i', $cut['end_time']);
	$row = unserialize($cut['ext_info']);
	unset($cut['ext_info']);

	if ($row) {
		foreach ($row as $key => $val) {
			$cut[$key] = $val;
		}
	}

	return $cut;
}

function get_cut_log_detail()
{
	$filter['cut_id'] = empty($_REQUEST['cut_id']) ? 0 : intval($_REQUEST['cut_id']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'cut_time' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$cut_id = $filter['cut_id'];
	$where = (empty($filter['cut_id']) ? '' : ' WHERE  cut_id=\'' . $cut_id . '\' ');
	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cut_log') . $where;
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);
	$sql = 'SELECT s.* ' . ' FROM ' . $GLOBALS['ecs']->table('cut_log') . ' AS s ' . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['cut_time'] = local_date('Y-m-d H:i', $val['cut_time']);
		$row[$key]['end_cut_time'] = local_date('Y-m-d H:i', $val['end_cut_time']);
		$row[$key]['end_buy_time'] = local_date('Y-m-d H:i', $val['end_buy_time']);
	}

	$arr = array('cut' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_cut_detail($supplier_id=0)
{

	$filter['act_id'] = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'create_time' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$act_id = $filter['act_id'];
	$where = (empty($filter['act_id']) ? '' : ' WHERE act_id=\'' . $act_id . '\' ');

	if( $supplier_id>0 ) {
		$act_id_in = $GLOBALS['db']->getOne("SELECT group_concat(act_id separator '\',\'') FROM " . $GLOBALS['ecs']->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "'") ;
		if( $act_id_in ) {
			$where = ' where act_id in(\''.$act_id_in.'\') ';
		}
	}

	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('cut') . $where;
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);


	$sql = 'SELECT * ' . ' FROM ' . $GLOBALS['ecs']->table('cut') . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['create_time'] = local_date('Y-m-d H:i', $val['create_time']);
		$row[$key]['end_cut_time'] = local_date('Y-m-d H:i', $val['end_cut_time']);
		$row[$key]['end_buy_time'] = local_date('Y-m-d H:i', $val['end_buy_time']);
	}

	$arr = array('cut' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function cut_detail_info($cut_id)
{
	$sql = 'SELECT ga.*,IFNULL(g.goods_thumb, \'\') AS goods_thumb, c.*,g.* ' . 'FROM  ' . $GLOBALS['ecs']->table('cut') . ' AS c  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_activity') . ' AS ga ON c.act_id  = ga.act_id  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON ga.goods_id = g.goods_id ' . 'WHERE c.cut_id=' . $cut_id . '  ';
	$cutinfo = $GLOBALS['db']->getRow($sql);
	$ext_info = unserialize($cutinfo['ext_info']);
	$cutinfo = array_merge($cutinfo, $ext_info);
	$cutinfo['now_time'] = gmtime();

	if (empty($cutinfo['goods_thumb'])) {
		$cutinfo['goods_thumb'] = get_image_path($cutinfo['goods_id'], $cutinfo['goods_thumb'], true);
	}

	$cutinfo['url'] = 'extpintuan.php?act=view&act_id=' . $cutinfo['act_id'] . '&u=' . $_SESSION['user_id'];
	return $cutinfo;
}

function is_wechat_browser_for_cut()
{
	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	if (strpos($user_agent, 'MicroMessenger') === false) {
		return false;
	}

	return true;
}
?>
