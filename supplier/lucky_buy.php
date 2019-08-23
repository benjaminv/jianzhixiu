<?php

/**
 * 管理中心云购商品管理
 * $Author: PRINCE QQ 120029121
 * $Id: lucky_buy.php PRINCE QQ 120029121
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_goods.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'mobile/includes/prince/lib_lucky_buy.php');
/* 检查权限 */
//admin_priv('lucky_buy');


if( !$_SESSION['dm299com_supplier_lucky_buy'] ) {
	sys_msg($_LANG['priv_error'], 0, $link);
}


/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 云购活动列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{   

    $smarty->assign('full_page',    1);
    $smarty->assign('ur_here',      $_LANG['lucky_buy_list']);
    $smarty->assign('action_link',  array('href' => 'lucky_buy.php?act=add', 'text' => $_LANG['add_lucky_buy']));
    $smarty->assign('action_link2',  array('href' => 'lucky_buy.php?act=view', 'text' => '查看所有云购概览'));

    $list = lucky_buy_list_adm($_SESSION['supplier_id']); 
    $smarty->assign('lucky_buy_list',   $list['item']);
    $smarty->assign('filter',           $list['filter']);
    $smarty->assign('record_count',     $list['record_count']);
    $smarty->assign('page_count',       $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('lucky_buy_list.htm');
}

elseif ($_REQUEST['act'] == 'query')
{
    $list = lucky_buy_list_adm($_SESSION['supplier_id']);

    $smarty->assign('lucky_buy_list', $list['item']);
    $smarty->assign('filter',         $list['filter']);
    $smarty->assign('record_count',   $list['record_count']);
    $smarty->assign('page_count',     $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('lucky_buy_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}



/*------------------------------------------------------ */
//-- 添加/编辑云购活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{    
    /* 初始化/取得云购活动信息 */
    if ($_REQUEST['act'] == 'add')
    {
        $lucky_buy = array(
            'act_id'  => 0,
            'lucky_title'  => '',
            'oneprice'  => '1.00',
            'need_follow'  => 1,
            'start_time'    => date('Y-m-d', time()),
            'end_time'      => date('Y-m-d', time() + 365 * 86400),
            'price_ladder'  => array(array('amount' => 0, 'price' => 0))
        );
    }
    else
    {    
        $lucky_buy_id = intval($_REQUEST['id']);
        if ($lucky_buy_id <= 0)
        {
            die('invalid param');
        }
        $lucky_buy = lucky_buy_info($lucky_buy_id);
    }
    $smarty->assign('lucky_buy', $lucky_buy);
    create_html_editor('act_desc', htmlspecialchars($lucky_buy['act_desc']));

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['add_lucky_buy']);
    $smarty->assign('action_link', list_link($_REQUEST['act'] == 'add'));
    $smarty->assign('cat_list', cat_list());
    $smarty->assign('brand_list', get_brand_list());

    /* 显示模板 */
    assign_query_info();
    $smarty->display('lucky_buy_info.htm');
}

/*------------------------------------------------------ */
//-- 添加/编辑云购活动的提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] =='insert_update')
{
    /* 取得云购活动id */
    $act_id = intval($_POST['act_id']);


        /* 保存云购信息 */
        $goods_id = intval($_POST['goods_id']);
        if ($goods_id <= 0)
        {
            sys_msg($_LANG['error_goods_null']);
        }
        /*$info = goods_lucky_buy($goods_id);
        if ($info && $info['act_id'] != $act_id)
        {
            sys_msg($_LANG['error_goods_exist']);
        }*/

        $goods_name = $db->getOne("SELECT goods_name FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'");

        $act_name = empty($_POST['act_name']) ? $goods_name : sub_str($_POST['act_name'], 0, 255, false);


		

        $allprice = number_format(floatval($_POST['allprice']),2);
        if ($allprice < 0)
        {
            $allprice = 0;
        }
		
        $oneprice = number_format(floatval($_POST['oneprice']),2);
        if ($oneprice < 0)
        {
            $oneprice = 0;
        }
		
        $number = intval($_POST['number']);
        if ($number < 0)
        {
            $number = 0;
        }

        $restrict_amount = intval($_POST['restrict_amount']);
        if ($restrict_amount < 0)
        {
            $restrict_amount = 0;
        }
		
        $need_follow = intval($_POST['need_follow']);
        if ($need_follow < 0)
        {
            $need_follow = 0;
        }

        $gift_integral = intval($_POST['gift_integral']);
        if ($gift_integral < 0)
        {
            $gift_integral = 0;
        }



        /* 检查开始时间和结束时间是否合理 */
        $start_time = local_strtotime($_POST['start_time']);
        $end_time = local_strtotime($_POST['end_time']);
        if ($start_time >= $end_time)
        {
            sys_msg($_LANG['invalid_time']);
        }

        $lucky_buy = array(
            'act_name'   => $act_name,
            'act_desc'   => $_POST['act_desc'],
            'act_type'   => GAT_LUCKY_BUY,
            'goods_id'   => $goods_id,
            'goods_name' => $goods_name,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'ext_info'   => serialize(array(
                    'price_ladder'      => $price_ladder,
                    'restrict_amount'   => $restrict_amount,
                    'gift_integral'     => $gift_integral,
                    'allprice'     => $allprice,
                    'oneprice'     => $oneprice,
                    'number'     => $number,
                    'need_follow'     => $need_follow,
                    'share_title'       => $_POST['share_title'],
                    'share_brief'       => $_POST['share_brief'],
                    'share_img'         => $_POST['share_img']
                    ))
        );

        /* 清除缓存 */
        clear_cache_files();

        /* 保存数据 */
        if ($act_id > 0)
        {
            /* update */
            $db->autoExecute($ecs->table('goods_activity'), $lucky_buy, 'UPDATE', "act_id = '$act_id'");

            /* log */
            admin_log(addslashes($goods_name) . '[' . $act_id . ']', 'edit', 'lucky_buy');

            /* todo 更新活动表 */

            /* 提示信息 */
            $links = array(
                array('href' => 'lucky_buy.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_list'])
            );
            sys_msg($_LANG['edit_success'], 0, $links);
        }
        else
        {
			$extpintuan['supplier_id'] = $_SESSION['supplier_id'];

            /* insert */
            $db->autoExecute($ecs->table('goods_activity'), $lucky_buy, 'INSERT');

            /* log */
            admin_log(addslashes($goods_name), 'add', 'lucky_buy');

            /* 提示信息 */
            $links = array(
                array('href' => 'lucky_buy.php?act=add', 'text' => $_LANG['continue_add']),
                array('href' => 'lucky_buy.php?act=list', 'text' => $_LANG['back_list'])
            );
            sys_msg($_LANG['add_success'], 0, $links);
        }
    
}




/*------------------------------------------------------ */
//-- 查看活动详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    /* 权限判断 */
    //admin_priv('cut_manage');

    $act_id = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);
    $lucky_buy_id = empty($_REQUEST['lucky_buy_id']) ? 0 : intval($_REQUEST['lucky_buy_id']);

    if($act_id && $lucky_buy_id){
		$info = get_lucky_buy_detail();
		
			$lucky_buy_info=get_lucky_buy_by_id($lucky_buy_id );
			$lucky_buy_info['start_time']   = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy_info['start_time']);
			$lucky_buy_info['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy_info['end_time']);
			$smarty->assign('lucky_buy_info',     $lucky_buy_info);
	
		$smarty->assign('info',     $info['info']);
		$smarty->assign('filter',       $info['filter']);
		$smarty->assign('record_count', $info['record_count']);
		$smarty->assign('page_count',   $info['page_count']);
	
		$sort_flag  = sort_flag($info['filter']);
		$smarty->assign($sort_flag['tag'], $sort_flag['img']);
		/* 赋值 */
		$smarty->assign('full_page',    1);
		$smarty->assign('ur_here',      '活动详情' );
		$smarty->assign('action_link',  array('text' => '云购概览', 'href'=>'lucky_buy.php?act=view&act_id='.$act_id));
		$smarty->display('lucky_buy_view_detail.htm');
	}else{
		$info = get_lucky_buy($_SESSION['supplier_id']);
		$smarty->assign('lucky_buy_list',         $info['info']);

		$smarty->assign('filter',       $info['filter']);
		$smarty->assign('record_count', $info['record_count']);
		$smarty->assign('page_count',   $info['page_count']);
	
		$sort_flag  = sort_flag($info['filter']);
		$smarty->assign($sort_flag['tag'], $sort_flag['img']);
		/* 赋值 */
		$smarty->assign('full_page',    1);
		$smarty->assign('ur_here',      '活动详情' );
		$smarty->assign('action_link',  array('text' => '云购活动列表', 'href'=>'lucky_buy.php?act=list'));
		$smarty->display('lucky_buy_view.htm');
	}
}


/*------------------------------------------------------ */
//-- 批量删除云购活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_drop')
{
    if (isset($_POST['checkboxes']))
    {
        $del_count = 0; //初始化删除数量
        foreach ($_POST['checkboxes'] AS $key => $id)
        {
            /* 取得云购活动信息 */
            $lucky_buy = lucky_buy_info($id);

            /* 如果云购活动已经有订单，不能删除 */
            if ($lucky_buy['valid_order'] <= 0)
            {
                /* 删除云购活动 */
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_activity') .
                        " WHERE act_id = '$id' and supplier_id='".$_SESSION['supplier_id']."' LIMIT 1";
                $GLOBALS['db']->query($sql, 'SILENT');

                admin_log(addslashes($lucky_buy['goods_name']) . '[' . $id . ']', 'remove', 'lucky_buy');
                $del_count++;
            }
        }

        /* 如果删除了云购活动，清除缓存 */
        if ($del_count > 0)
        {
            clear_cache_files();
        }

        $links[] = array('text' => $_LANG['back_list'], 'href'=>'lucky_buy.php?act=list');
        sys_msg(sprintf($_LANG['batch_drop_success'], $del_count), 0, $links);
    }
    else
    {
        $links[] = array('text' => $_LANG['back_list'], 'href'=>'lucky_buy.php?act=list');
        sys_msg($_LANG['no_select_lucky_buy'], 0, $links);
    }
}

/*------------------------------------------------------ */
//-- 排序、翻页活动详情
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query_lucky_buy')
{
    $lucky_buy_list = get_lucky_buy($_SESSION['supplier_id']);

    $smarty->assign('lucky_buy_list',     $lucky_buy_list['info']);
    $smarty->assign('filter',       $lucky_buy_list['filter']);
    $smarty->assign('record_count', $lucky_buy_list['record_count']);
    $smarty->assign('page_count',   $lucky_buy_list['page_count']);

    $sort_flag  = sort_flag($lucky_buy_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('lucky_buy_view.htm'), '',
        array('filter' => $lucky_buy_list['filter'], 'page_count' => $lucky_buy_list['page_count']));
}

/*------------------------------------------------------ */
//-- 排序、翻页活动详情
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query_lucky_buy_detail')
{
    $lucky_buy_list = get_lucky_buy_detail();


    $smarty->assign('info',     $lucky_buy_list['info']);
    $smarty->assign('filter',       $lucky_buy_list['filter']);
    $smarty->assign('record_count', $lucky_buy_list['record_count']);
    $smarty->assign('page_count',   $lucky_buy_list['page_count']);

    $sort_flag  = sort_flag($lucky_buy_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('lucky_buy_view_detail.htm'), '',
        array('filter' => $lucky_buy_list['filter'], 'page_count' => $lucky_buy_list['page_count']));
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    //check_authz_json('group_by');

    include_once(ROOT_PATH . 'includes/cls_json.php');

    $json   = new JSON;
    $filter = $json->decode($_GET['JSON']);
    $arr    = get_goods_list($filter);

    make_json_result($arr);
}




/*------------------------------------------------------ */
//-- 删除云购活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{

    $id = intval($_GET['id']);

    /* 取得云购活动信息 */
    $lucky_buy = lucky_buy_info($id);

    /* 如果云购活动已经有订单，不能删除 */
    if ($lucky_buy['valid_order'] > 0)
    {
        make_json_error($_LANG['error_exist_order']);
    }

    /* 删除云购活动 */
    $sql = "DELETE FROM " . $ecs->table('goods_activity') . " WHERE act_id = '$id' and supplier_id='".$_SESSION['supplier_id']."' LIMIT 1";
    $db->query($sql);

    admin_log(addslashes($lucky_buy['goods_name']) . '[' . $id . ']', 'remove', 'lucky_buy');

    clear_cache_files();

    $url = 'lucky_buy.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 编辑处理状态
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_handl_status')
{
    //check_authz_json('lucky_buy');

    $id = intval($_POST['id']);
    $val = json_str_iconv(trim($_POST['val']));
    //$val = intval($val);
	if($val==='1' || $val==='0'  ||  $val==='待处理' || $val==='已处理'){
		if($val==='已处理'){
			$val='1';
		}
		if($val==='待处理'){
			$val='0';
		}
	}else{
   		 make_json_error(sprintf('请输入数字或（已处理、待处理）表示 ：1 代表已处理   0 代表待处理',  $val));
	}

	$sql = 'UPDATE ' . $ecs->table('lucky_buy') . " SET `handl_status` =" . $val. " WHERE lucky_buy_id = '$id' ";
	$db->query($sql);
	if($val==1){
		$val='已处理';
	}else{
		$val='待处理';
	}
    make_json_result(stripslashes($val));
}



?>