<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
function getTopDomainhuo2()
{
	$host = $_SERVER['HTTP_HOST'];
	$host = strtolower($host);

	if (strpos($host, '/') !== false) {
		$parse = @parse_url($host);
		$host = $parse['host'];
	}

	$topleveldomaindb = array('com', 'com.cn', 'cn', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'museum', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me');
	$str = '';

	foreach ($topleveldomaindb as $v) {
		$str .= ($str ? '|' : '') . $v;
	}

	$matchstr = '[^\\.]+\\.(?:(' . $str . ')|\\w{2}|((' . $str . ')\\.\\w{2}))$';

	if (preg_match('/' . $matchstr . '/ies', $host, $matchs)) {
		$domain = $matchs[0];
	}
	else {
		$domain = $host;
	}

	return $domain;
}

function lucky_buy_info($act_id, $current_num = 0)
{
	$act_id = intval($act_id);
	$sql = 'SELECT *, act_id AS lucky_buy_id, act_desc AS lucky_buy_desc, start_time AS start_date, end_time AS end_date ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . 'WHERE act_id = \'' . $act_id . '\' ' . 'AND act_type = \'' . GAT_LUCKY_BUY . '\'';
	$lucky_buy = $GLOBALS['db']->getRow($sql);

	if (empty($lucky_buy)) {
		return array();
	}

	$ext_info = unserialize($lucky_buy['ext_info']);
	$lucky_buy = array_merge($lucky_buy, $ext_info);
	$lucky_buy['formated_start_date'] = local_date('Y-m-d H:i', $lucky_buy['start_time']);
	$lucky_buy['formated_end_date'] = local_date('Y-m-d H:i', $lucky_buy['end_time']);
	$stat = lucky_buy_stat($act_id);
	$lucky_buy = array_merge($lucky_buy, $stat);
	$lucky_buy['status_no'] = lucky_buy_status($lucky_buy);

	if (isset($GLOBALS['_LANG']['gbs'][$lucky_buy['status']])) {
		$lucky_buy['status_desc'] = $GLOBALS['_LANG']['gbs'][$lucky_buy['status']];
	}

	$lucky_buy['start_time'] = $lucky_buy['formated_start_date'];
	$lucky_buy['end_time'] = $lucky_buy['formated_end_date'];
	return $lucky_buy;
}

function lucky_buy_stat($act_id)
{
	$act_id = intval($act_id);
	$sql = 'SELECT goods_id ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . 'WHERE act_id = \'' . $act_id . '\' ' . 'AND act_type = \'' . GAT_LUCKY_BUY . '\'';
	$lucky_buy_goods_id = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT COUNT(*) AS total_order, SUM(g.goods_number) AS total_goods ' . 'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' . $GLOBALS['ecs']->table('order_goods') . ' AS g ' . ' WHERE o.order_id = g.order_id ' . 'AND o.extension_code = \'lucky_buy\' ' . 'AND o.extension_id = \'' . $act_id . '\' ' . 'AND g.goods_id = \'' . $lucky_buy_goods_id . '\' ' . 'AND (order_status = \'' . OS_CONFIRMED . '\' OR order_status = \'' . OS_UNCONFIRMED . '\')';
	$stat = $GLOBALS['db']->getRow($sql);

	if ($stat['total_order'] == 0) {
		$stat['total_goods'] = 0;
	}

	$stat['valid_order'] = $stat['total_order'];
	$stat['valid_goods'] = $stat['total_goods'];
	return $stat;
}

function lucky_buy_status($lucky_buy)
{
	$now = gmtime();

	if ($lucky_buy['is_finished'] == 0) {
		if ($now < $lucky_buy['start_time']) {
			$status = GBS_PRE_START;
		}
		else if ($lucky_buy['end_time'] < $now) {
			$status = GBS_FINISHED;
		}
		else {
			$status = GBS_UNDER_WAY;
		}
	}
	else {
		if ($lucky_buy['is_finished'] == 1) {
			$status = 2;
		}
	}

	return $status;
}

function user_lucky_buy_count()
{
	$sql = 'SELECT COUNT(DISTINCT order_id) ' . 'FROM ' . $GLOBALS['ecs']->table('lucky_buy_detail') . 'WHERE user_id  = \'' . $_SESSION['user_id'] . '\' ';
	return $GLOBALS['db']->getOne($sql);
}

function get_lucky_buy_detail()
{
	$filter['act_id'] = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);
	$filter['lucky_buy_id'] = empty($_REQUEST['lucky_buy_id']) ? 0 : intval($_REQUEST['lucky_buy_id']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'used_time' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$act_id = $filter['act_id'];
	$lucky_buy_id = $filter['lucky_buy_id'];
	$order_id = ($_REQUEST['order_id'] ? intval($_REQUEST['order_id']) : 0);
	$where = (empty($filter['lucky_buy_id']) ? '' : ' WHERE lucky_buy_id=\'' . $lucky_buy_id . '\'');
	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('lucky_buy_detail') . $where;
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);
	$sql = 'SELECT s.* ' . ' FROM ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' AS s ' . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['create_time'] = local_date('Y-m-d H:i', $val['create_time']);
		$row[$key]['used_time'] = local_date('Y-m-d H:i', $val['used_time']);
	}

	$arr = array('info' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_lucky_buy($supplier_id=0)
{
	$filter['act_id'] = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'lucky_buy_id' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$act_id = $filter['act_id'];
	$where = (empty($filter['act_id']) ? '' : ' WHERE act_id=\'' . $act_id . '\' ');
	if( $supplier_id>0 ) {
		$act_id_in = $GLOBALS['db']->getOne("SELECT group_concat(act_id separator '\',\'') FROM " . $GLOBALS['ecs']->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "'") ;
		if( $act_id_in ) {
			$where = ' where act_id in(\''.$act_id_in.'\') ';
		}
	}
	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('lucky_buy') . $where;
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);
	$sql = 'SELECT * ' . ' FROM ' . $GLOBALS['ecs']->table('lucky_buy') . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['start_time'] = local_date('Y-m-d H:i', $val['start_time']);
		$row[$key]['end_time'] = local_date('Y-m-d H:i', 0 < $val['end_time'] ? $val['end_time'] : 0);
	}

	$arr = array('info' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function lucky_buy_count()
{
	$now = gmtime();
	$sql = 'SELECT COUNT(*) ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . 'WHERE act_type = \'' . GAT_LUCKY_BUY . '\' ' . 'AND start_time <= \'' . $now . '\' AND end_time >= \'' . $now . '\' AND is_finished < 1';
	return $GLOBALS['db']->getOne($sql);
}

function lucky_buy_list($size, $page)
{
	$lucky_buy_list = array();
	$lucky_buy_list['finished'] = $lucky_buy_list['finished'] = array();
	$now = gmtime();
	$sql = 'SELECT a.*,g.*, IFNULL(g.goods_thumb, \'\') AS goods_thumb ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS a ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON a.goods_id = g.goods_id ' . 'WHERE a.act_type = \'' . GAT_LUCKY_BUY . '\' ' . 'AND a.start_time <= \'' . $now . '\' AND a.end_time >= \'' . $now . '\' AND a.is_finished < 1 ORDER BY a.act_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($row['ext_info']);
		$lucky_buy = array_merge($row, $ext_info);
		$lucky_buy['status_no'] = lucky_buy_status($lucky_buy);
		$lucky_buy['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy['start_time']);
		$lucky_buy['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy['end_time']);
		$lucky_buy['formated_start_price'] = price_format($lucky_buy['start_price']);
		$lucky_buy['formated_end_price'] = price_format($lucky_buy['end_price']);
		$lucky_buy['formated_deposit'] = price_format($lucky_buy['deposit']);
		$lucky_buy['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$lucky_buy['url'] = 'lucky_buy.php?act=view&act_id=' . $row['act_id'] . '&u=' . $_SESSION['user_id'];
		$lucky_buy['shop_price'] = price_format($row['shop_price']);

		if ($lucky_buy['status_no'] < 2) {
			$lucky_buy_list['under_way'][] = $lucky_buy;
		}
		else {
			$lucky_buy_list['finished'][] = $lucky_buy;
		}

		$lucky_buy['goods_thumb'] = get_image_path($lucky_buy['goods_id'], $lucky_buy['goods_thumb'], true);
	}

	$lucky_buy_list = @array_merge($lucky_buy_list['under_way'], $lucky_buy_list['finished']);
	return $lucky_buy_list;
}

function lucky_buy_user_list($size, $page, $act_user)
{
	$lucky_buy_list = array();
	$now = gmtime();
	$sql = 'SELECT DISTINCT lbd.order_id ,lb.status AS luck_buy_status,lb.*,ga.*,g.*, IFNULL(g.goods_thumb, \'\') AS goods_thumb  ' . 'FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' AS lbd  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('lucky_buy') . ' AS lb ON lbd.lucky_buy_id   = lb.lucky_buy_id   ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_activity') . ' AS ga ON lbd.act_id  = ga.act_id  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON ga.goods_id = g.goods_id ' . 'WHERE lbd.user_id=' . $_SESSION['user_id'] . ' and lbd.user_id >0 ORDER BY lbd.lucky_buy_id DESC,lbd.order_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($row['ext_info']);
		$lucky_buy = array_merge($row, $ext_info);
		$lucky_buy['status_no'] = lucky_buy_status($lucky_buy);
		$lucky_buy['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy['start_time']);
		$lucky_buy['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy['end_time']);
		$lucky_buy['formated_start_price'] = price_format($lucky_buy['start_price']);
		$lucky_buy['formated_end_price'] = price_format($lucky_buy['end_price']);
		$lucky_buy['formated_deposit'] = price_format($lucky_buy['deposit']);
		$lucky_buy['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$lucky_buy['shop_price'] = price_format($row['shop_price']);
		$lucky_buy['lucky_user_id'] = $row['lucky_user_id'];
		$lucky_buy['status'] = $row['luck_buy_status'];
		$lucky_buy_list[] = $lucky_buy;
	}

	return $lucky_buy_list;
}

function count_lucky_buy_detail($lucky_buy_id)
{
	$sql = 'SELECT count(*) ' . '  FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . '  WHERE lucky_buy_id=' . $lucky_buy_id . ' and user_id >0 GROUP BY user_id,order_id ORDER BY used_time DESC';
	return $GLOBALS['db']->getOne($sql);
}

function lucky_buy_detail($size, $page, $lucky_buy_id)
{
	$lucky_buy_detail = array();
	$now = gmtime();
	$sql = 'SELECT user_id ,used_time, user_name,user_head,used_time,used_time_millisecond,count(code) as total ' . '  FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . '  WHERE lucky_buy_id=' . $lucky_buy_id . ' and user_id >0 GROUP BY user_id,order_id ORDER BY used_time DESC, used_time_millisecond DESC ';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$lucky_buy_detail[] = $row;
	}

	return $lucky_buy_detail;
}

function schedulelist_count($act_id)
{
	$sql = 'SELECT COUNT(*) ' . ' FROM ' . $GLOBALS['ecs']->table('lucky_buy') . ' WHERE act_id  = \'' . $act_id . '\' and status >0 ';
	return $GLOBALS['db']->getOne($sql);
}

function schedulelist_list($size, $page, $act_id)
{
	$lucky_buy_list = array();
	$now = gmtime();
	$sql = 'SELECT *  ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy') . ' WHERE act_id=' . $act_id . ' and status >0 ORDER BY lucky_buy_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$lucky_buy['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
		$lucky_buy['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
		$lucky_buy['lucky_user_head'] = $row['lucky_user_head'];
		$lucky_buy['lucky_user_name'] = $row['lucky_user_name'];
		$lucky_buy['lucky_user_id'] = $row['lucky_user_id'];
		$lucky_buy['lucky_user_order_id'] = $row['lucky_user_order_id'];
		$lucky_buy['lucky_code'] = $row['lucky_code'];
		$lucky_buy['total'] = $row['total'];
		$lucky_buy['schedule_id'] = $row['schedule_id'];
		$lucky_buy['lucky_buy_id'] = $row['lucky_buy_id'];
		$lucky_buy['status'] = $row['status'];
		$lucky_buy_list[] = $lucky_buy;
	}

	return $lucky_buy_list;
}

function lucky_buy_by_lucky_buy_id($lucky_buy_id)
{
	$sql = 'SELECT * ' . '  FROM  ' . $GLOBALS['ecs']->table('lucky_buy') . '  WHERE lucky_buy_id=' . $lucky_buy_id . ' LIMIT 1';
	$res = $GLOBALS['db']->getRow($sql);
	return $res;
}

function ship_code($lucky_buy_id)
{
	$sql = 'SELECT o.*,og.goods_number ' . ' FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('order_goods') . ' AS og   ON o.order_id  = og.order_id   ' . ' WHERE o.order_status=1 AND o.pay_status=2  ' . ' AND o.shipping_status=0 AND o.extension_code=\'lucky_buy\' AND o.extension_id>0 ';
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$sql = 'SELECT used_time,used_time_millisecond,calculate_number,count(1) as had_shipped  FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' WHERE order_id=' . $val['order_id'] . ' AND order_id>0 ' . ' GROUP BY used_time,used_time_millisecond,calculate_number ';
		$had_shipped_info = $GLOBALS['db']->getRow($sql);
		$need_number = $val['goods_number'];

		if (empty($had_shipped_info)) {
			$now = gmtime();
			$used_time_millisecond = getMillisecond();
			$the_format_time = local_date($GLOBALS['_CFG']['time_format'], $now);
			$calculate_number = substr($the_format_time, 11, 2) . substr($the_format_time, 14, 2) . substr($the_format_time, 17, 2) . $used_time_millisecond;
		}
		else {
			$now = $had_shipped_info['used_time'];
			$used_time_millisecond = $had_shipped_info['used_time_millisecond'];
			$calculate_number = $had_shipped_info['calculate_number'];
			$need_number = $val['goods_number'] - $had_shipped_info['had_shipped'];
		}

		$sql = 'SELECT lucky_buy_id ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy') . ' WHERE act_id=' . $val['extension_id'] . ' AND available >0 ';
		$lucky_buy_info = $GLOBALS['db']->getAll($sql);

		if (!empty($lucky_buy_info)) {
			foreach ($lucky_buy_info as $key => $lucky_buy_info) {
				update_available($lucky_buy_info['lucky_buy_id']);
			}
		}

		$sql = 'SELECT * ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy') . ' WHERE act_id=' . $val['extension_id'] . ' AND available >0 LIMIT 1';
		$last_lucky_buy_info = $GLOBALS['db']->getRow($sql);
		$available = ($last_lucky_buy_info['available'] ? $last_lucky_buy_info['available'] : 0);
		$lucky_buy_id = ($last_lucky_buy_info['lucky_buy_id'] ? $last_lucky_buy_info['lucky_buy_id'] : 0);

		if ($need_number <= $available) {
			if ($need_number <= 49) {
				ship_code_by_rand($val['extension_id'], $need_number, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
			}
			else {
				$not_rand_run_number = floor($need_number / 49);
				$rand_run_number = floor($need_number % 49);
				ship_code_by_rand($val['extension_id'], $rand_run_number, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
				$x = 0;

				while ($x < $not_rand_run_number) {
					echo $x;
					ship_code_notby_rand($val['extension_id'], 49, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
					++$x;
				}
			}

			update_available($lucky_buy_id);
		}
		else {
			ship_code_notby_rand($val['extension_id'], $available, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
			update_available($lucky_buy_id);
			$lucky_buy_id = goto_next_schedule($val['extension_id'], $need_number);
			$need_number = $need_number - $available;

			if ($need_number <= 49) {
				ship_code_by_rand($val['extension_id'], $need_number, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
			}
			else {
				$not_rand_run_number = floor($need_number / 49);
				$rand_run_number = floor($need_number % 49);
				echo gmtime();
				ship_code_by_rand($val['extension_id'], $rand_run_number, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
				echo gmtime();
				$x = 0;

				while ($x < $not_rand_run_number) {
					ship_code_notby_rand($val['extension_id'], 49, $val['user_id'], $now, $used_time_millisecond, $calculate_number, $val['order_id'], $val['order_sn'], $lucky_buy_id);
					++$x;
				}

				echo gmtime();
			}

			update_available($lucky_buy_id);
		}

		update_shipping_status($val['order_id'], $val['goods_number'], $now);
	}
}

function calculate_lucky_code()
{
	$sql = 'SELECT * ' . ' FROM ' . $GLOBALS['ecs']->table('lucky_buy') . ' WHERE available=0 AND status=0  ' . ' AND lucky_code<=0 ';
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$now = gmtime();
		$sql = 'SELECT * ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_calculate') . ' WHERE lucky_buy_id=\'' . $val['lucky_buy_id'] . '\' ';
		$chk_lucky_buy_calculate = $GLOBALS['db']->getRow($sql);

		if (empty($chk_lucky_buy_calculate)) {
			$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('lucky_buy_calculate') . ' (lucky_buy_id,act_id, schedule_id, code,create_time,used_time,used_time_millisecond,calculate_number)' . 'SELECT lucky_buy_id, act_id, schedule_id,code,create_time,used_time,used_time_millisecond,calculate_number ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' WHERE lucky_buy_id=' . $val['lucky_buy_id'] . ' GROUP BY order_id ORDER BY used_time DESC, used_time_millisecond DESC LIMIT 50';
			$GLOBALS['db']->query($sql);
		}

		$sql = 'SELECT SUM(calculate_number) ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_calculate') . ' WHERE lucky_buy_id=' . $val['lucky_buy_id'] . ' LIMIT 50';
		$sum_calculate_number = $GLOBALS['db']->getOne($sql);
		$mod_of_sum = $sum_calculate_number % $val['total'];
		$lucky_code = $mod_of_sum + 10000001;
		$sql = 'SELECT * ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' WHERE lucky_buy_id=' . $val['lucky_buy_id'] . ' AND code =' . $lucky_code . ' LIMIT 1';
		$lucky_code_info = $GLOBALS['db']->getRow($sql);
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' SET `is_lucky_user` =1 ' . ' WHERE lucky_buy_id=' . $val['lucky_buy_id'] . ' AND code =' . $lucky_code . ' ';
		$GLOBALS['db']->query($sql);
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('lucky_buy') . ' SET `status` =1 ' . ', end_time = \'' . $now . '\', sum_of_calculate_number = \'' . $sum_calculate_number . '\', lucky_code = ' . $lucky_code . ', lucky_user_id = \'' . $lucky_code_info['user_id'] . '\', lucky_user_name = \'' . $lucky_code_info['user_name'] . '\', lucky_user_head = \'' . $lucky_code_info['user_head'] . '\', lucky_user_order_id = \'' . $lucky_code_info['order_id'] . '\', lucky_user_order_sn = \'' . $lucky_code_info['order_sn'] . '\' WHERE lucky_buy_id=\'' . $val['lucky_buy_id'] . '\'  ';
		$GLOBALS['db']->query($sql);
		$lucky_buy_id = $val['lucky_buy_id'];
		include_once ROOT_PATH . 'wxm_lucky_buy.php';
	}
}

function get_calculate_info($lucky_buy_id)
{
	$sql = 'SELECT count(*) ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_calculate') . ' WHERE lucky_buy_id=\'' . $lucky_buy_id . '\' LIMIT 50';
	$count_all = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT * ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_calculate') . ' WHERE lucky_buy_id=\'' . $lucky_buy_id . '\' ORDER BY used_time DESC, used_time_millisecond DESC LIMIT 50';
	$row = $GLOBALS['db']->getAll($sql);
	$i = 0;

	foreach ($row as $key => $val) {
		$row[$key]['used_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['used_time']);
		$row[$key]['this_index'] = $i + 1;
		$i = $i + 1;
		$row[$key]['count_all'] = $count_all;
	}

	return $row;
}

function goto_next_schedule($act_id, $need_number = 1)
{
	$lucky_buy = lucky_buy_info($act_id);
	$sql = 'select * from ' . $GLOBALS['ecs']->table('lucky_buy') . ' where  act_id=\'' . $act_id . '\' order by schedule_id desc limit 1';
	$chk_info = $GLOBALS['db']->getRow($sql);

	if (empty($chk_info)) {
		$schedule_id = '8' . $act_id . '80001';
	}
	else {
		if ($chk_info['available'] < $need_number) {
			$schedule_id = $chk_info['schedule_id'] + 1;
		}
	}

	if ((empty($chk_info) || ($chk_info['available'] < $need_number)) && $schedule_id) {
		$nowtime = gmtime();
		$total = $lucky_buy['number'];
		$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('lucky_buy') . ' (act_id, schedule_id, total,available,start_time)' . 'VALUES (\'' . $act_id . '\', \'' . $schedule_id . '\', \'' . $total . '\',\'' . $total . '\',\'' . $nowtime . '\')';
		$GLOBALS['db']->query($sql);
		$lucky_buy_id = $GLOBALS['db']->insert_id();
		$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' (lucky_buy_id,act_id, schedule_id, code,create_time)' . 'SELECT \'' . $lucky_buy_id . '\', \'' . $act_id . '\', \'' . $schedule_id . '\',code,\'' . $nowtime . '\' FROM ' . $GLOBALS['ecs']->table('lucky_buy_code') . ' ORDER BY code ASC LIMIT ' . $total . ' ';
		$GLOBALS['db']->query($sql);
		return $lucky_buy_id;
	}
}

function getMillisecond()
{
	list($usec, $usec) = explode(' ', microtime());
	$msec = round($usec * 1000);
	return $msec;
}

function get_lucky_buy_by_id($lucky_buy_id)
{
	$sql = 'SELECT lb.* ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy') . ' AS lb  ' . ' WHERE lb.lucky_buy_id=' . $lucky_buy_id . '  ';
	return $GLOBALS['db']->getRow($sql);
}

function lucky_buy_list_adm($supplier_id=0)
{
	$result = get_filter();

	if ($result === false) {
		$filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
		if (isset($_REQUEST['is_ajax']) && ($_REQUEST['is_ajax'] == 1)) {
			$filter['keyword'] = json_str_iconv($filter['keyword']);
		}

		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		$where = (!empty($filter['keyword']) ? ' AND goods_name LIKE \'%' . mysql_like_quote($filter['keyword']) . '%\'' : '');
		if( $supplier_id>0 ) {
			$act_id_in = $GLOBALS['db']->getOne("SELECT group_concat(act_id separator '\',\'') FROM " . $GLOBALS['ecs']->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "'") ;
			if( $act_id_in ) {
				$where = '  AND act_id in(\''.$act_id_in.'\') ';
			}
		}
		$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_type = \'' . GAT_LUCKY_BUY . '\' ' . $where;
		$filter['record_count'] = $GLOBALS['db']->getOne($sql);
		$filter = page_and_size($filter);
		$sql = 'SELECT * ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_type = \'' . GAT_LUCKY_BUY . '\' ' . $where . ' ' . ' ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' ' . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
		$filter['keyword'] = stripslashes($filter['keyword']);
		set_filter($filter, $sql);
	}
	else {
		$sql = $result['sql'];
		$filter = $result['filter'];
	}

	$res = $GLOBALS['db']->query($sql);
	$list = array();

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($row['ext_info']);
		$arr = array_merge($row, $ext_info);
		$stat = lucky_buy_stat($arr['act_id']);
		$arr['valid_order'] = $stat['valid_order'];
		$arr['start_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['start_time']);
		$arr['end_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['end_time']);
		$list[] = $arr;
	}

	$arr = array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function goods_lucky_buy($goods_id)
{
	$sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE goods_id = \'' . $goods_id . '\' ' . ' AND act_type = \'' . GAT_LUCKY_BUY . '\'' . ' AND start_time <= ' . gmtime() . ' AND end_time >= ' . gmtime();
	return $GLOBALS['db']->getRow($sql);
}

function list_link($is_add = true)
{
	$href = 'lucky_buy.php?act=list';

	if (!$is_add) {
		$href .= '&' . list_link_postfix();
	}

	return array('href' => $href, 'text' => $GLOBALS['_LANG']['lucky_buy_list']);
}

function ship_code_by_rand($act_id, $number, $user_id, $now, $used_time_millisecond, $calculate_number, $order_id, $order_sn, $lucky_buy_id)
{
	$sql = 'SELECT * ' . ' FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' WHERE act_id=' . $act_id . ' AND user_id=0 and used_time=0' . ' ORDER BY RAND() LIMIT ' . $number;
	$codes = $GLOBALS['db']->getAll($sql);
	$sql = 'SELECT u.*,w.nickname,w.headimgurl FROM  ' . $GLOBALS['ecs']->table('users') . ' u ' . 'left join  ' . $GLOBALS['ecs']->table('weixin_user') . '  w on u.user_id=w.ecuid ' . 'WHERE  u.user_id=' . $user_id;
	$user_info = $GLOBALS['db']->getRow($sql);
	$user_info['user_name'] = $user_info['nickname'] ? $user_info['nickname'] : $user_info['user_name'];
	$user_info['headimgurl'] = $user_info['headimgurl'];

	foreach ($codes as $key => $code) {
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' SET `used_time` =' . $now . ', used_time_millisecond = ' . $used_time_millisecond . ', calculate_number = ' . $calculate_number . ', user_id = ' . $user_id . ', user_name = \'' . $user_info['user_name'] . '\', user_head = \'' . $user_info['headimgurl'] . '\', order_id = ' . $order_id . ', order_sn = \'' . $order_sn . '\' WHERE id = ' . $code['id'];
		$GLOBALS['db']->query($sql);
	}
}

function ship_code_notby_rand($act_id, $number, $user_id, $now, $used_time_millisecond, $calculate_number, $order_id, $order_sn, $lucky_buy_id)
{
	$sql = 'SELECT u.*,w.nickname,w.headimgurl FROM  ' . $GLOBALS['ecs']->table('users') . ' u ' . ' left join  ' . $GLOBALS['ecs']->table('weixin_user') . '  w on u.user_id=w.ecuid ' . ' WHERE  u.user_id=' . $user_id;
	$user_info = $GLOBALS['db']->getRow($sql);
	$user_info['user_name'] = $user_info['nickname'] ? $user_info['nickname'] : $user_info['user_name'];
	$user_info['headimgurl'] = $user_info['headimgurl'];
	$sql = 'UPDATE ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' SET `used_time` =' . $now . ', used_time_millisecond = ' . $used_time_millisecond . ', calculate_number = ' . $calculate_number . ', user_id = ' . $user_id . ', user_name = \'' . $user_info['user_name'] . '\', user_head = \'' . $user_info['headimgurl'] . '\', order_id = ' . $order_id . ', order_sn = \'' . $order_sn . '\' WHERE act_id<=\'' . $act_id . '\' and order_id<=0 AND user_id <=0 LIMIT ' . $number;
	$GLOBALS['db']->query($sql);
}

function update_available($lucky_buy_id)
{
	$sql = 'SELECT count(*)  FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' WHERE lucky_buy_id = ' . $lucky_buy_id . ' AND order_id>0  ';
	$had_used = $GLOBALS['db']->getOne($sql);
	$sql = 'UPDATE ' . $GLOBALS['ecs']->table('lucky_buy') . ' SET `available` =`total`-' . $had_used . ' WHERE lucky_buy_id = ' . $lucky_buy_id;
	$GLOBALS['db']->query($sql);
}

function update_shipping_status($order_id, $number, $now)
{
	$sql = 'SELECT count(*)  FROM  ' . $GLOBALS['ecs']->table('lucky_buy_detail') . ' WHERE order_id=' . $order_id . ' AND order_id>0  ';
	$had_shipped_number = $GLOBALS['db']->getOne($sql);

	if ($number <= $had_shipped_number) {
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET `shipping_time` =' . $now . ', shipping_status = 2 ' . ' WHERE order_id = ' . $order_id;
		$GLOBALS['db']->query($sql);
	}
}

function is_wechat_browser_for_lucky_buy()
{
	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	if (strpos($user_agent, 'MicroMessenger') === false) {
		return false;
	}

	return true;
}

?>
