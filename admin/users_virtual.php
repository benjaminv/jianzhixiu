<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');
/* 代码增加2014-12-23 by www.68ecshop.com _star */
include_once (ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
/* 代码增加2014-12-23 by www.68ecshop.com _end */

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
// -- 用户帐号列表
/* ------------------------------------------------------ */
function action_list ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_manage');
	$sql = "SELECT rank_id, rank_name, min_points FROM " . $ecs->table('user_rank') . " ORDER BY min_points ASC ";
	$rs = $db->query($sql);

	$ranks = array();
	while($row = $db->FetchRow($rs))
	{
		$ranks[$row['rank_id']] = $row['rank_name'];
	}

	$smarty->assign('user_ranks', $ranks);
	$smarty->assign('ur_here', $_LANG['03_users_virtual_list']);
//	$smarty->assign('action_link', array(
//		'text' => $_LANG['04_users_add'],'href' => 'users_virtual.php?act=add'
//	));

	$user_list = user_list();

	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

	assign_query_info();
	$smarty->display('users_virtual_list.htm');
}

/* ------------------------------------------------------ */
// -- ajax返回用户列表
/* ------------------------------------------------------ */
function action_query ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$user_list = user_list();
	$sql = "select * from".$ecs->table("weixin_user");
	$data = $db->getAll($sql);
	foreach ($user_list['user_list'] as $key=>$value){
		foreach ($data as $k=>$v){
			if($value['user_id'] == $v['ecuid']){
				$user_list['user_list'][$key]['user_name'].="丨".$v['nickname'];
				$user_list['user_list'][$key]['headimgurl']=$v['headimgurl'];
				continue;
			}
		}
	}//echo '<pre>';var_dump($user_list['user_list']);exit;

	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);

	$sort_flag = sort_flag($user_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);

	make_json_result($smarty->fetch('users_virtual_list.htm'), '', array(
		'filter' => $user_list['filter'],'page_count' => $user_list['page_count']
	));
}


/* ------------------------------------------------------ */
// -- 批量删除会员帐号
/* ------------------------------------------------------ */
function action_batch_remove ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_virtual_drop');

	if(isset($_POST['checkboxes']))
	{
		$sql = "SELECT user_id FROM " . $ecs->table('users_virtual') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
		$col = $db->getCol($sql);

		$usernames = implode(',', addslashes_deep($col));
		$count = count($col);
		/* 通过插件来删除用户 */
//		$users = & init_users();
//		$users->remove_user($col);
		$id_str=implode(',',$col);

		$sql="DELETE FROM " . $ecs->table('users_virtual') ." WHERE user_id in ('$id_str')";
		$db->query($sql);
		if(ODOO_ERP){
			$odooErpObj = OdooErp::getInstance();
			//edit yhy 同步erp中的会员
			$res = $odooErpObj->syncUserUnlinkByUserids($_POST['checkboxes']);
		}
		admin_log($usernames, 'batch_remove', 'users');

		$lnk[] = array(
			'text' => $_LANG['go_back'], 'href' => 'users_virtual.php?act=list'
		);
		sys_msg(sprintf($_LANG['batch_remove_success'], $count), 0, $lnk);
	}
	else
	{
		$lnk[] = array(
			'text' => $_LANG['go_back'], 'href' => 'users_virtual.php?act=list'
		);
		sys_msg($_LANG['no_select_user'], 0, $lnk);
	}
}




/* ------------------------------------------------------ */
// -- 删除会员帐号
/* ------------------------------------------------------ */
function action_remove ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_virtual_drop');
	/* 如果会员已申请或正在申请入驻商家，不能删除会员 */
	$sql=" SELECT COUNT(*) FROM " . $ecs->table('supplier') . " WHERE user_id='" . $_GET['id'] . "'";
	$issupplier=$db->getOne($sql);
	if($issupplier>0){
		/* 提示信息 */
		$link[] = array(
				'text' => $_LANG['go_back'], 'href' => 'users_virtual.php?act=list'
		);
		sys_msg(sprintf('该会员已申请或正在申请入驻商，不能删除！'), 0, $link);
	}else{
		$sql = "SELECT * FROM " . $ecs->table('users_virtual') . " WHERE user_id = '" . $_GET['id'] . "'";
		$user = $db->getOne($sql);

		$sql="DELETE FROM " . $ecs->table('users_virtual') ." WHERE user_id=".$_GET['id'];
		$db->query($sql);

		/* 记录管理员操作 */
		admin_log(addslashes($username), 'remove', 'users_virtual');

		/* 提示信息 */
		$link[] = array(
			'text' => $_LANG['go_back'], 'href' => 'users_virtual.php?act=list'
		);
		sys_msg(sprintf($_LANG['remove_success'], $username), 0, $link);
	}
}

/**
 * 返回用户列表数据
 *
 * @access public
 * @param
 *
 * @return void
 */
function user_list ()
{
	$result = get_filter();
	if($result === false)
	{
		/* 过滤条件 */
		$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
		{
			$filter['keywords'] = json_str_iconv($filter['keywords']);
		}
		$filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
		$filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
		$filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);

		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'user_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

		$ex_where = ' WHERE 1 ';
		if($filter['keywords'])
		{
			$ex_where .= " AND  u.alias LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or u.user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or u.email like  '%" . mysql_like_quote($filter['keywords']) . "%' or u.mobile_phone like  '%" . mysql_like_quote($filter['keywords']) . "%' or w.nickname like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
		}
		if($filter['rank'])
		{
			$sql = "SELECT min_points, max_points, special_rank FROM " . $GLOBALS['ecs']->table('user_rank') . " WHERE rank_id = '$filter[rank]'";
			$row = $GLOBALS['db']->getRow($sql);
			if($row['special_rank'] > 0)
			{
				/* 特殊等级 */
				$ex_where .= " AND u.user_rank = '$filter[rank]' ";
			}
			else
			{
				$ex_where .= " AND u.rank_points >= " . intval($row['min_points']) . " AND u.rank_points < " . intval($row['max_points']);
			}
		}
		if($filter['pay_points_gt'])
		{
			$ex_where .= " AND u.pay_points >= '$filter[pay_points_gt]' ";
		}
		if($filter['pay_points_lt'])
		{
			$ex_where .= " AND u.pay_points < '$filter[pay_points_lt]' ";
		}

		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users_virtual')."as u left join ".$GLOBALS['ecs']->table("weixin_user")."as w on u.user_id=w.ecuid" . $ex_where);

		/* 分页大小 */
		$filter = page_and_size($filter);
		if(!empty($filter['sort_by'])){
			$a = $filter['sort_by'];
			$b = substr($a,0,2);
			if($b !== 'u.'){
				$filter['sort_by'] ="u.".$a;
			}
		}
		$sql = "SELECT u.*,w.nickname,w.headimgurl ".
                " FROM " . $GLOBALS['ecs']->table('users_virtual')."as u ".
				"left join".$GLOBALS['ecs']->table("weixin_user")."as w on u.user_id=w.ecuid" . $ex_where . " ".
				"ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}

	$user_list = $GLOBALS['db']->getAll($sql);


	$rank_list_all = $GLOBALS['db']->getAll("select * from " . $GLOBALS['ecs']->table('user_rank'));
	$rank_list = array();
	foreach($rank_list_all as $key=>$val) {
		$rank_list[$val['rank_id']] = $val;
	}
	$count = count($user_list);


	foreach($user_list as $key=>$val) {
		$user_list[$key]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $val['reg_time']);
		$user_list[$key]['froms'] = $val['froms'];
		if( $val['headimgurl'] ) { // 微信头像
			$user_list[$key]['headimg'] = $val['headimgurl'];
		}
		elseif( isset($val['avatar']) ) { // 小程序的头像 {
			$user_list[$key]['headimg'] = $val['avatar'];
			$user_list[$key]['froms'] = 'wxadoc';
		}
		else {
			$user_list[$key]['headimg'] = "/".$val['headimg'];
		}

//
//		if( !empty($val['nickname']) ) {
//			$user_list[$key]['user_name'] .="丨".$val['nickname'];
//		}
//		else {
//			$user_list[$key]['user_name']  .="丨".$val['alias'];
//		}





		if($val['user_rank']){
			$user_list[$key]['rank_name'] = $rank_list[$val['user_rank']]['rank_name'];
		}
		else {
			$rank_points = $val['rank_points'];

			foreach($rank_list_all as $kr=>$vr) {
				$min_point = $vr['min_points'];
				$max_point = $vr['max_points'];
				if($rank_points <= $max_point && $rank_points >= $min_point)
				{
					$user_list[$key]['rank_name'] = $vr['rank_name'];
					break;
				}
			}

		}

	}


	$arr = array(
		'user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);

	return $arr;
}

?>
