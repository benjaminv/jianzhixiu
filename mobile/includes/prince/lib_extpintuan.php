<?php
function extpintuan_info($extpintuan_id, $current_num = 0)
{
	$extpintuan_id = intval($extpintuan_id);
	$sql = 'SELECT *, act_id AS extpintuan_id, act_desc AS extpintuan_desc, start_time AS start_date, end_time AS end_date ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . 'WHERE act_id = \'' . $extpintuan_id . '\' ' . 'AND act_type = \'' . GAT_EXTPINTUAN . '\'';
	$extpintuan = $GLOBALS['db']->getRow($sql);

	if (empty($extpintuan)) {
		return array();
	}

	$ext_info = unserialize($extpintuan['ext_info']);
	$extpintuan = array_merge($extpintuan, $ext_info);
	$extpintuan['formated_start_date'] = local_date('Y-m-d H:i', $extpintuan['start_time']);
	$extpintuan['formated_end_date'] = local_date('Y-m-d H:i', $extpintuan['end_time']);
	$extpintuan['formated_deposit'] = price_format($extpintuan['deposit'], false);
	$extpintuan['org_price_ladder'] = $extpintuan['price_ladder'];
	$price_ladder = $extpintuan['price_ladder'];
	$i = 0;
	if (!is_array($price_ladder) || empty($price_ladder)) {
		$price_ladder = array(
			array('amount' => 0, 'minprice' => 0, 'maxprice' => 0)
			);
	}
	else {
		foreach ($price_ladder as $key => $amount_price) {
			$i = $i + 1;
		}
	}

	$extpintuan['price_ladder'] = $extpintuan['price_ladder'];
	$extpintuan['ladder_amount'] = $i;
	$stat = extpintuan_stat($extpintuan_id, $extpintuan['deposit']);
	$extpintuan = array_merge($extpintuan, $stat);
	$cur_price = $price_ladder[0]['minprice'];
	$cur_amount = $stat['valid_goods'] + $current_num;

	foreach ($price_ladder as $amount_price) {
		if ($amount_price['amount'] <= $cur_amount) {
			$cur_price = $amount_price['minprice'];
		}
		else {
			break;
		}
	}

	$extpintuan['cur_price'] = $cur_price;
	$extpintuan['formated_cur_price'] = price_format($cur_price, false);
	$extpintuan['trans_price'] = $extpintuan['cur_price'];
	$extpintuan['formated_trans_price'] = $extpintuan['formated_cur_price'];
	$extpintuan['trans_amount'] = $extpintuan['valid_goods'];
	$extpintuan['status'] = extpintuan_status($extpintuan);

	if (isset($GLOBALS['_LANG']['gbs'][$extpintuan['status']])) {
		$extpintuan['status_desc'] = $GLOBALS['_LANG']['gbs'][$extpintuan['status']];
	}

	return $extpintuan;
}

function extpintuan_stat($extpintuan_id, $deposit)
{
	$extpintuan_id = intval($extpintuan_id);
	$sql = 'SELECT goods_id ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . 'WHERE act_id = \'' . $extpintuan_id . '\' ' . 'AND act_type = \'' . GAT_EXTPINTUAN . '\'';
	$extpintuan_goods_id = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT COUNT(*) AS total_order, SUM(g.goods_number) AS total_goods ' . 'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' . $GLOBALS['ecs']->table('order_goods') . ' AS g ' . ' WHERE o.order_id = g.order_id ' . 'AND o.extension_code = \'extpintuan\' ' . 'AND o.extension_id = \'' . $extpintuan_id . '\' ' . 'AND g.goods_id = \'' . $extpintuan_goods_id . '\' ' . 'AND (order_status = \'' . OS_CONFIRMED . '\' OR order_status = \'' . OS_UNCONFIRMED . '\')';
	$stat = $GLOBALS['db']->getRow($sql);

	if ($stat['total_order'] == 0) {
		$stat['total_goods'] = 0;
	}

	$deposit = floatval($deposit);
	if ((0 < $deposit) && (0 < $stat['total_order'])) {
		$sql .= ' AND (o.money_paid + o.surplus) >= \'' . $deposit . '\'';
		$row = $GLOBALS['db']->getRow($sql);
		$stat['valid_order'] = $row['total_order'];

		if ($stat['valid_order'] == 0) {
			$stat['valid_goods'] = 0;
		}
		else {
			$stat['valid_goods'] = $row['total_goods'];
		}
	}
	else {
		$stat['valid_order'] = $stat['total_order'];
		$stat['valid_goods'] = $stat['total_goods'];
	}

	return $stat;
}

function extpintuan_status($extpintuan)
{
	$now = gmtime();

	if ($extpintuan['is_finished'] == 0) {
		if ($now < $extpintuan['start_time']) {
			$status = GBS_PRE_START;
		}
		else if ($extpintuan['end_time'] < $now) {
			$status = GBS_FINISHED;
		}
		else {
			if (($extpintuan['restrict_amount'] == 0) || ($extpintuan['valid_goods'] < $extpintuan['restrict_amount'])) {
				$status = GBS_UNDER_WAY;
			}
			else {
				$status = GBS_FINISHED;
			}
		}
	}
	else if ($extpintuan['is_finished'] == GBS_SUCCEED) {
		$status = GBS_SUCCEED;
	}
	else {
		if ($extpintuan['is_finished'] == GBS_FAIL) {
			$status = GBS_FAIL;
		}
	}

	return $status;
}

function update_extpintuan_info()
{
	$now = gmtime();
	$sql = 'SELECT a.* ' . 'FROM ' . $GLOBALS['ecs']->table('extpintuan') . ' AS a ' . 'WHERE status=0  ' . 'ORDER BY a.create_time asc ';
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		if ($val['create_succeed'] == 1) {
			if ($val['available_people'] == 0) {
				if ($val['is_lucky_extpintuan']) {
					$status = 3;
				}
				else {
					$status = 1;
				}

				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . ' SET status =' . $status . ' ' . 'WHERE pt_id = \'' . $val['pt_id'] . '\'';
				$GLOBALS['db']->query($sql);
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =' . $status . ' ' . 'WHERE exists (SELECT 1 FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto WHERE  o.order_id=pto.order_id and pto.pt_id= \'' . $val['pt_id'] . '\')';
				$GLOBALS['db']->query($sql);
				send_extpintuan_wxm($val['pt_id'], $status);
			}
			else {
				$sql = 'SELECT count(*) ' . 'FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' AS o ON pto.order_id    = o.order_id    ' . 'WHERE pto.pt_id=' . $val['pt_id'] . '  and o.pay_status =2 ';
				$valid_orders = $GLOBALS['db']->getOne($sql);
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . ' SET `available_people` =`need_people`-' . $valid_orders . ' WHERE pt_id = \'' . $val['pt_id'] . '\'';
				$GLOBALS['db']->query($sql);

				if ($val['need_people'] <= $valid_orders) {
					if ($val['is_lucky_extpintuan']) {
						$status = 3;
					}
					else {
						$status = 1;
					}

					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . ' SET status =' . $status . ' ' . 'WHERE pt_id = \'' . $val['pt_id'] . '\'';
					$GLOBALS['db']->query($sql);
					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =' . $status . ' ' . 'WHERE exists (SELECT 1 FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto WHERE  o.order_id=pto.order_id and pto.pt_id= \'' . $val['pt_id'] . '\')';
					$GLOBALS['db']->query($sql);
					send_extpintuan_wxm($val['pt_id'], $status);
				}
				else {
					if ($val['end_time'] < $now) {
						$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . ' SET status =2 ' . 'WHERE pt_id = \'' . $val['pt_id'] . '\' and end_time<' . $now . ' ';
						$GLOBALS['db']->query($sql);
						$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =2 ' . 'WHERE exists (SELECT 1 FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto WHERE  o.order_id=pto.order_id and pto.pt_id= \'' . $val['pt_id'] . '\')';
						$GLOBALS['db']->query($sql);
						send_extpintuan_wxm($val['pt_id'], 2);
					}
				}
			}
		}
		else if ($now < $val['end_time']) {
			$sql = 'SELECT pto.*,o.order_status,o.shipping_status,o.pay_status ' . 'FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' AS o ON pto.order_id    = o.order_id    ' . 'WHERE pto.pt_id=' . $val['pt_id'] . ' and pto.follow_user=pto.act_user and o.pay_status =2';
			$act_user_order = $GLOBALS['db']->getRow($sql);

			if ($act_user_order) {
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . ' SET create_succeed =1 ' . 'WHERE pt_id = \'' . $val['pt_id'] . '\'';
				$GLOBALS['db']->query($sql);
			}
		}
		else {
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . ' SET status =2 ' . 'WHERE pt_id = \'' . $val['pt_id'] . '\'';
			$GLOBALS['db']->query($sql);
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =2 ' . 'WHERE exists (SELECT 1 FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto WHERE  o.order_id=pto.order_id and pto.pt_id= \'' . $val['pt_id'] . '\')';
			$GLOBALS['db']->query($sql);
			send_extpintuan_wxm($val['pt_id'], 2);
		}
	}

	$sql = 'SELECT pto.order_id ' . 'FROM  ' . $GLOBALS['ecs']->table('extpintuan') . ' AS pt  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto ON pto.pt_id    = pt.pt_id    ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' AS o ON pto.order_id    = o.order_id    ' . 'WHERE pt.status!=0 AND o.pay_status <2 and order_status<2 ';
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET order_status =2 ' . 'WHERE order_id = \'' . $val['order_id'] . '\'';
		$GLOBALS['db']->query($sql);
	}

	$sql = 'SELECT * ' . ' FROM  ' . $GLOBALS['ecs']->table('order_info') . ' WHERE extension_code=\'extpintuan\' AND pay_status =2 and pt_price_status=0 ';
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan_price') . ' SET status =1 ' . ' WHERE act_id = ' . $val['extension_id'] . ' AND level = ' . $val['pt_level'] . ' AND status =0 ';
		$GLOBALS['db']->query($sql);
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' SET pt_price_status =1 ' . ' WHERE order_id = ' . $val['order_id'];
		$GLOBALS['db']->query($sql);
	}
}

function send_extpintuan_sms($pt_id, $status)
{
	$sql = 'SELECT o.order_sn,o.mobile FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto  ' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' AS o ON pto.order_id    = o.order_id    ' . ' WHERE pto.pt_id=' . $pt_id . '  AND o.pay_status =2 ';
	$res = $GLOBALS['db']->query($sql);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$order_sn = $row['order_sn'];
		$mobile = $row['mobile'];
		include_once ROOT_PATH . 'sms/sms.php';
		if ($order_sn && $mobile && $status) {
			if ($status == 1) {
				$content = '{\'OrderNo\':\'' . $order_sn . '\'}，SMS_7496235';
				sendSMS($mobile, $content);
			}
			else {
				if ($status == 2) {
					$content = '{\'OrderNo\':\'' . $order_sn . '\'}，SMS_7451118';
					sendSMS($mobile, $content);
				}
			}
		}
	}
}

function send_extpintuan_wxm($pt_id, $status)
{
	$sql = 'SELECT pto.* FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto  ' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('order_info') . ' AS o ON pto.order_id    = o.order_id    ' . ' WHERE pto.pt_id=' . $pt_id . '  AND o.pay_status =2 ';
	$res = $GLOBALS['db']->query($sql);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		send_status_message($row['follow_user'], $row['order_id'], $row['order_sn'], $pt_id, $status);
	}
}

function send_lucky_extpintuan_wxm()
{
	$sql = 'SELECT po.* FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS po  ' . ' LEFT JOIN ' . $GLOBALS['ecs']->table('extpintuan') . ' AS p ON p.pt_id    = po.pt_id    ' . ' WHERE p.is_lucky_extpintuan=1 and p.status=4 ' . ' AND po.notify =0 ';
	$res = $GLOBALS['db']->query($sql);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$notify = $GLOBALS['db']->getOne('SELECT `notify` FROM ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' WHERE order_id = ' . $row['order_id']);
		send_lucky_message($row['follow_user'], $row['order_id'], $row['order_sn'], $row['pt_id'], $row['lucky_order']);
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' SET notify =1 WHERE order_id = ' . $row['order_id'];
		$GLOBALS['db']->query($sql);
	}
}

function extpintuan_count()
{
	$type = (isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0);
	$now = gmtime();
	$where = ' AND b.start_time <= \'' . $now . '\' AND b.end_time > \'' . $now . '\' ';

	if ($type == 1) {
		$where = ' AND ext_act_type=\'' . $type . '\' AND b.start_time <= \'' . $now . '\' AND b.end_time > \'' . $now . '\' ';
	}
	else {
		if ($type == 2) {
			$where = ' AND ext_act_type=\'' . $type . '\'  ';
		}
	}

	$sql = 'SELECT COUNT(*) ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' b WHERE act_type = \'' . GAT_EXTPINTUAN . '\' ' . $where . ' ';
	return $GLOBALS['db']->getOne($sql);
}

function user_extpintuan_count()
{
	$sql = 'SELECT COUNT(*) ' . 'FROM ' . $GLOBALS['ecs']->table('extpintuan_orders') . 'WHERE follow_user  = \'' . $_SESSION['user_id'] . '\' ';
	return $GLOBALS['db']->getOne($sql);
}

function extpintuan_list($size, $page)
{
	$type = (isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0);
	$now = gmtime();
	$where = ' AND b.start_time <= \'' . $now . '\' AND b.end_time > \'' . $now . '\' ';

	if ($type == 1) {
		$where = ' AND ext_act_type=\'' . $type . '\' AND b.start_time <= \'' . $now . '\' AND b.end_time > \'' . $now . '\' ';
	}
	else {
		if ($type == 2) {
			$where = ' AND ext_act_type=\'' . $type . '\'  ';
		}
	}

	$pt_list = array();
	$sql = 'SELECT b.*, IFNULL(g.goods_thumb, \'\') AS goods_thumb, g.*,b.act_id AS extpintuan_id, ' . 'b.start_time AS start_date, b.end_time AS end_date ' . 'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS b ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON b.goods_id = g.goods_id ' . 'WHERE b.act_type = \'' . GAT_EXTPINTUAN . '\' ' . $where . ' ORDER BY b.act_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($extpintuan = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($extpintuan['ext_info']);
		$extpintuan = array_merge($extpintuan, $ext_info);
		$extpintuan['formated_start_date'] = local_date($GLOBALS['_CFG']['time_format'], $extpintuan['start_date']);
		$extpintuan['formated_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $extpintuan['end_date']);
		$extpintuan['formated_deposit'] = price_format($extpintuan['deposit'], false);
		$extpintuan['org_price_ladder'] = $extpintuan['price_ladder'];
		$price_ladder = $extpintuan['price_ladder'];
		$i = 0;
		if (!is_array($price_ladder) || empty($price_ladder)) {
			$price_ladder = array(
				array('amount' => 0, 'minprice' => 0, 'maxprice' => 0)
				);
		}
		else {
			foreach ($price_ladder as $key => $amount_price) {
				$i = $i + 1;
			}
		}

		$extpintuan['ladder_amount'] = $i;
		$extpintuan['price_ladder'] = $extpintuan['price_ladder'];
		$extpintuan['lowest_amount'] = get_lowest_amount($price_ladder);
		$extpintuan['min_price'] = get_min_price($price_ladder);
		$extpintuan['max_price'] = get_max_price($price_ladder);
		$extpintuan['single_buy'] = $extpintuan['single_buy'];
		$extpintuan['single_buy_price'] = $extpintuan['single_buy_price'];
		$extpintuan['act_id'] = $extpintuan['act_id'];

		if (empty($extpintuan['goods_thumb'])) {
			$extpintuan['goods_thumb'] = get_image_path($extpintuan['goods_id'], $extpintuan['goods_thumb'], true);
		}

		$extpintuan['url'] = 'extpintuan.php?act=view&act_id=' . $extpintuan['extpintuan_id'] . '&u=' . $_SESSION['user_id'];
		$pt_list[] = $extpintuan;
	}

	return $pt_list;
}

function extpintuan_user_list($size, $page)
{
	$pt_list = array();
	$now = gmtime();
	$sql = 'SELECT ga.*,g.*, IFNULL(g.goods_thumb, \'\') AS goods_thumb, pto.* ,pt.status,pt.need_people,pt.need_people AS this_need_people,pt.pt_id,pt.price as pt_price ' . 'FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('extpintuan') . ' AS pt ON pto.pt_id   = pt.pt_id   ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_activity') . ' AS ga ON pt.act_id  = ga.act_id  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON ga.goods_id = g.goods_id ' . 'WHERE pto.follow_user=' . $_SESSION['user_id'] . '  ORDER BY pto.order_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($extpintuan = $GLOBALS['db']->fetchRow($res)) {
		$ext_info = unserialize($extpintuan['ext_info']);
		$extpintuan = array_merge($extpintuan, $ext_info);
		$extpintuan['formated_start_date'] = local_date($GLOBALS['_CFG']['time_format'], $extpintuan['start_date']);
		$extpintuan['formated_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $extpintuan['end_date']);
		$extpintuan['price'] = price_format($extpintuan['pt_price'], false);
		$extpintuan['formated_deposit'] = price_format($extpintuan['deposit'], false);
		$price_ladder = $extpintuan['price_ladder'];
		$i = 0;
		if (!is_array($price_ladder) || empty($price_ladder)) {
			$price_ladder = array(
				array('amount' => 0, 'minprice' => 0, 'maxprice' => 0)
				);
		}
		else {
			foreach ($price_ladder as $key => $amount_price) {
				$i = $i + 1;
			}
		}

		$extpintuan['price_ladder'] = $extpintuan['price_ladder'];
		$extpintuan['lowest_price'] = price_format(get_min_price($price_ladder));
		$extpintuan['lowest_amount'] = get_lowest_amount($price_ladder);
		$extpintuan['ladder_amount'] = $i;

		if (empty($extpintuan['goods_thumb'])) {
			$extpintuan['goods_thumb'] = get_image_path($extpintuan['goods_id'], $extpintuan['goods_thumb'], true);
		}

		$extpintuan['url'] = 'extpintuan.php?act=view&act_id=' . $extpintuan['extpintuan_id'] . '&u=' . $_SESSION['user_id'];
		$pt_list[] = $extpintuan;
	}

	return $pt_list;
}

function extpintuan_detail_info($extpintuan_id)
{
	$sql = 'SELECT ga.*,IFNULL(g.goods_thumb, \'\') AS goods_thumb, pt.need_people AS this_need_people, pt.*,g.* ' . 'FROM  ' . $GLOBALS['ecs']->table('extpintuan') . ' AS pt  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_activity') . ' AS ga ON pt.act_id  = ga.act_id  ' . 'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON ga.goods_id = g.goods_id ' . 'WHERE pt.pt_id=' . $extpintuan_id . '  ';
	$extpintuan = $GLOBALS['db']->getRow($sql);
	$ext_info = unserialize($extpintuan['ext_info']);
	$extpintuan = array_merge($extpintuan, $ext_info);
	$extpintuan['create_time'] = local_date($GLOBALS['_CFG']['time_format'], $extpintuan['create_time']);

	if (empty($extpintuan['goods_thumb'])) {
		$extpintuan['goods_thumb'] = get_image_path($extpintuan['goods_id'], $extpintuan['goods_thumb'], true);
	}

	$extpintuan['url'] = 'extpintuan.php?act=view&act_id=' . $extpintuan['act_id'] . '&u=' . $_SESSION['user_id'];
	return $extpintuan;
}

function get_min_price($price_ladder)
{
	if (is_array($price_ladder)) {
		$aa = array();

		foreach ($price_ladder as $key => $value) {
			$aa[] = $value['minprice'];
		}

		sort($aa);
		return $aa[0];
	}
}

function get_max_price($price_ladder)
{
	if (is_array($price_ladder)) {
		$aa = array();

		foreach ($price_ladder as $key => $value) {
			$aa[] = $value['maxprice'];
		}

		sort($aa);
		return $aa[0];
	}
}

function get_lowest_amount($price_ladder)
{
	if (is_array($price_ladder)) {
		$aa = array();

		foreach ($price_ladder as $key => $value) {
			$aa[] = $value['amount'];
		}

		sort($aa);
		return $aa[0];
	}
}

function get_new_extpintuan($act_id, $level)
{
	$new_extpintuan = array();
	$sql = 'SELECT a.* ' . 'FROM ' . $GLOBALS['ecs']->table('extpintuan') . ' AS a ' . 'WHERE act_id = \'' . $act_id . '\' and status=0 and create_succeed=1  ' . 'ORDER BY a.create_time desc LIMIT 10';
	$res = $GLOBALS['db']->query($sql);

	while ($row = $GLOBALS['db']->fetchRow($res)) {
		$row['create_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['create_time']);
		$row['price'] = price_format($row['price'], false);
		$row['user_nickname'] = 10 < strlen($row['user_nickname']) ? sub_str_for_extpt($row['user_nickname'], 10) : $row['user_nickname'];
		$new_extpintuan[] = $row;
	}

	return $new_extpintuan;
}

function get_extpintuan($supplier_id=0)
{
	$filter['act_id'] = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'create_time' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$where = (empty($filter['act_id']) ? '' : ' WHERE act_id=\'' . $filter['act_id'] . '\' ');
	if( $supplier_id>0 ) {
		$act_id_in = $GLOBALS['db']->getOne("SELECT group_concat(act_id separator '\',\'') FROM " . $GLOBALS['ecs']->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "'") ;
		if( $act_id_in ) {
			$where = ' where act_id in(\''.$act_id_in.'\') ';
		}
	}
	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('extpintuan') . $where;
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);
	$sql = 'SELECT * ' . ' FROM ' . $GLOBALS['ecs']->table('extpintuan') . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['create_time'] = local_date('Y-m-d H:i', $val['create_time']);
		$row[$key]['end_time'] = local_date('Y-m-d H:i', $val['end_time']);
	}

	$arr = array('extpintuan' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_extpintuan_detail()
{
	$filter['pt_id'] = empty($_REQUEST['pt_id']) ? 0 : intval($_REQUEST['pt_id']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'follow_time' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$where = (empty($filter['pt_id']) ? '' : ' WHERE pt_id=\'' . $filter['pt_id'] . '\' ');
	$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('extpintuan_orders') . $where;
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);
	$sql = 'SELECT s.* ' . ' FROM ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS s ' . $where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' LIMIT ' . $filter['start'] . ', ' . $filter['page_size'];
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['follow_time'] = local_date('Y-m-d H:i', $val['follow_time']);
	}

	$arr = array('extpintuan' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_extpintuan_info($id)
{
	global $ecs;
	global $db;
	global $_CFG;
	$sql = 'SELECT act_id, act_name AS cut_name, goods_id, product_id, goods_name, start_time, end_time, act_desc, ext_info' . ' FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' WHERE act_id=\'' . $id . '\' AND act_type = ' . GAT_EXTPINTUAN;
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

function get_extpintuan_by_ptid($pt_id)
{
	$sql = 'SELECT pt.* ' . ' FROM  ' . $GLOBALS['ecs']->table('extpintuan') . ' AS pt  ' . ' WHERE pt.pt_id=' . $pt_id . '  ';
	return $GLOBALS['db']->getRow($sql);
}

function create_lucky_orders()
{
	$sql = 'SELECT act_id,act_type,count(1) as total_extpintuan ' . ' FROM ' . $GLOBALS['ecs']->table('extpintuan') . ' AS a ' . ' WHERE status=3  ' . ' GROUP BY act_id,act_type  ' . ' ORDER BY a.act_id asc ';
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$sql = 'SELECT count(*) FROM  ' . $GLOBALS['ecs']->table('extpintuan') . ' WHERE act_id=\'' . $val['act_id'] . '\' AND status=0 ';
		$not_finished = $GLOBALS['db']->getOne($sql);

		if ($not_finished == 0) {
			$extpintuan_info = extpintuan_info($val['act_id']);

			if ($extpintuan_info['end_time'] < gmtime()) {
				$sql = 'SELECT count(*) FROM  ' . $GLOBALS['ecs']->table('order_info') . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $val['act_id'] . '\' AND pt_status>=3 AND pay_status=2 ';
				$total_orders = $GLOBALS['db']->getOne($sql);

				if ($total_orders <= $extpintuan_info['lucky_limit']) {
					$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =4,o.pt_lucky_order =1 ' . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $val['act_id'] . '\' AND pt_status=3 AND pay_status=2 ';
					$GLOBALS['db']->query($sql);
				}
				else {
					create_lucky_orders_by_rand($val['act_id'], $extpintuan_info['lucky_limit']);
				}

				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' AS pto SET pto.lucky_order =1 ' . 'WHERE exists (SELECT 1 FROM  ' . $GLOBALS['ecs']->table('order_info') . ' AS o WHERE  o.order_id=pto.order_id and extension_code = \'extpintuan\' AND extension_id=\'' . $val['act_id'] . '\' AND o.pt_lucky_order= 1  )';
				$GLOBALS['db']->query($sql);
				update_pt_status_to_4($val['act_id'], $total_orders, $extpintuan_info['lucky_limit']);
			}
		}
	}
}

function update_pt_status_to_4($act_id, $total_orders, $lucky_limit)
{
	$sql = 'SELECT count(*) FROM  ' . $GLOBALS['ecs']->table('order_info') . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $act_id . '\' AND pay_status=2 AND pt_lucky_order =1 ';
	$count_orders = $GLOBALS['db']->getOne($sql);
	$sql = 'SELECT count(*) FROM  ' . $GLOBALS['ecs']->table('extpintuan_orders') . ' WHERE act_id=\'' . $act_id . '\' AND lucky_order =1 ';
	$count_extpintuan_orders = $GLOBALS['db']->getOne($sql);

	if ($total_orders < $lucky_limit) {
		if (($total_orders <= $count_orders) && ($total_orders <= $count_extpintuan_orders)) {
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =4 ' . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $act_id . '\' AND pt_status=3 ';
			$GLOBALS['db']->query($sql);
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . '  SET status =4 ' . ' WHERE act_id=\'' . $act_id . '\' AND status=3 ';
			$GLOBALS['db']->query($sql);
		}

		return NULL;
	}

	if (($lucky_limit <= $count_orders) && ($lucky_limit <= $count_extpintuan_orders)) {
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =4 ' . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $act_id . '\' AND pt_status=3 ';
		$GLOBALS['db']->query($sql);
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('extpintuan') . '  SET status =4 ' . ' WHERE act_id=\'' . $act_id . '\' AND status=3 ';
		$GLOBALS['db']->query($sql);
	}
}

function create_lucky_orders_by_rand($act_id, $lucky_limit)
{
	$sql = 'SELECT count(*) FROM  ' . $GLOBALS['ecs']->table('order_info') . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $act_id . '\' AND pay_status=2 AND pt_lucky_order =1 ';
	$total_lucky_orders = $GLOBALS['db']->getOne($sql);
	$need_number = $lucky_limit - $total_lucky_orders;

	if (0 < $need_number) {
		$sql = 'SELECT *  FROM  ' . $GLOBALS['ecs']->table('order_info') . ' WHERE extension_code = \'extpintuan\' AND extension_id=\'' . $act_id . '\' AND pt_status>=3 AND pay_status=2 AND pt_lucky_order !=1 ' . ' ORDER BY RAND() LIMIT ' . $need_number;
		$rows = $GLOBALS['db']->getAll($sql);

		foreach ($rows as $key => $val) {
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . ' AS o SET o.pt_status =4,o.pt_lucky_order =1 ' . ' WHERE order_id = ' . $val['order_id'] . ' AND extension_code = \'extpintuan\' AND extension_id=\'' . $act_id . '\' AND pt_status=3 AND pay_status=2 ';
			$GLOBALS['db']->query($sql);
		}
	}
}

function extpintuan_lucky_list_count($act_id)
{
	$sql = 'SELECT COUNT(*) ' . 'FROM ' . $GLOBALS['ecs']->table('extpintuan_orders') . 'WHERE act_id=\'' . $act_id . '\' AND lucky_order=1  ';
	return $GLOBALS['db']->getOne($sql);
}

function extpintuan_lucky_list($act_id, $size, $page)
{
	$lucky_list = array();
	$sql = 'SELECT * ' . 'FROM ' . $GLOBALS['ecs']->table('extpintuan_orders') . 'WHERE act_id=\'' . $act_id . '\' AND lucky_order=1 ORDER BY order_id DESC';
	$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

	while ($luckyorder = $GLOBALS['db']->fetchRow($res)) {
		$lucky_list[] = $luckyorder;
	}

	return $lucky_list;
}

function is_wechat_browser_for_extpintuan()
{
	$user_agent = $_SERVER['HTTP_USER_AGENT'];

	if (strpos($user_agent, 'MicroMessenger') === false) {
		return false;
	}

	return true;
}

function sub_str_for_extpt($str, $length = 0, $append = true)
{
	$str = trim($str);
	$strlength = strlen($str);
	if (($length == 0) || ($strlength <= $length)) {
		return $str;
	}

	if ($length < 0) {
		$length = $strlength + $length;

		if ($length < 0) {
			$length = $strlength;
		}
	}

	if (function_exists('mb_substr')) {
		$newstr = mb_substr($str, 0, $length, EC_CHARSET);
	}
	else if (function_exists('iconv_substr')) {
		$newstr = iconv_substr($str, 0, $length, EC_CHARSET);
	}
	else {
		$newstr = substr($str, 0, $length);
	}

	if ($append && ($str != $newstr)) {
		$newstr .= '...';
	}

	return $newstr;
}
?>
