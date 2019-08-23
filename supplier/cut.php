<?php
/**
 * 砍价活动管理程序
 * $Author: PRINCE $
 * $Id: cut.php 17217 2016-01-07 06:29:08Z PRINCE 120029121 $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'mobile/includes/prince/lib_cut.php');
$exc = new exchange($ecs->table("goods_activity"), $db, 'act_id', 'act_name');


if( !$_SESSION['dm299com_supplier_cut'] ) {
	sys_msg($_LANG['priv_error'], 0, $link);
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */

    /* 初始化信息 */
    $start_time = local_date('Y-m-d H:i');
    $end_time   = local_date('Y-m-d H:i', strtotime('+1 month'));
    $cut     = array('start_price'=>'1.00','end_price'=>'5.00','max_price'=>'0','orders_limit'=>'1', 'need_follow'=>'1','start_time' => $start_time,'end_time' => $end_time,'option'=>'<option value="0">'.$_LANG['make_option'].'</option>');

    $smarty->assign('cut',       $cut);
    $smarty->assign('ur_here',      '添加砍价活动');
    $smarty->assign('action_link',  array('text' => '砍价活动列表', 'href'=>'cut.php?act=list'));
    $smarty->assign('cat_list',     cat_list());
    $smarty->assign('brand_list',   get_brand_list());
    $smarty->assign('form_action',  'insert');
    create_html_editor('act_desc', htmlspecialchars($cut['act_desc']));

    assign_query_info();
    $smarty->display('cut_info.htm');
}

elseif ($_REQUEST['act'] =='insert')
{
    /* 权限判断 */

    /* 检查商品是否存在 */
    $sql = "SELECT goods_name FROM ".$ecs->table('goods')." WHERE goods_id = '$_POST[goods_id]'";
    $_POST['goods_name'] = $db->GetOne($sql);
    if (empty($_POST['goods_name']))
    {
        sys_msg($_LANG['no_goods'], 1);
        exit;
    }

    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_activity').
           " WHERE act_type='" . GAT_CUT . "' AND act_name='" . $_POST['cut_name'] . "'" ;
    if ($db->getOne($sql))
    {
        sys_msg(sprintf($_LANG['cut_name_exist'],  $_POST['cut_name']) , 1);
    }

    /* 将时间转换成整数 */
    $_POST['start_time'] = local_strtotime($_POST['start_time']);
    $_POST['end_time']   = local_strtotime($_POST['end_time']);

    /* 处理提交数据 */
    if (empty($_POST['price']))
    {
        $_POST['price'] = 0;
    }
    if (empty($_POST['start_price']))
    {
        $_POST['start_price'] = 0;
    }
    if (empty($_POST['end_price']))
    {
        $_POST['end_price'] = 0;
    }
    if (empty($_POST['max_price']))
    {
        $_POST['max_price'] = 0;
    }
    if (empty($_POST['cost_points']))
    {
        $_POST['cost_points'] = 0;
    }
    if (isset($_POST['product_id']) && empty($_POST['product_id']))
    {
        $_POST['product_id'] = 0;
    }
    $orders_limit = intval($_POST['orders_limit']);
    if ($orders_limit < 0){
            $orders_limit = 0;
    }

    $info = array('price'=>$_POST['price'],'market_price'=>$_POST['market_price'],'virtual_sold'=>$_POST['virtual_sold'],'start_price'=>$_POST['start_price'], 'end_price'=>$_POST['end_price'], 'max_price'=>$_POST['max_price'], 'cost_points'=>$_POST['cost_points'],'showlimit'=>$_POST['showlimit'],'max_buy_price'=>$_POST['max_buy_price'],'show_max_buy_price'=>$_POST['show_max_buy_price'],'join_limit'=>$_POST['join_limit'],'cut_time_limit'=>$_POST['cut_time_limit'],'buy_time_limit'=>$_POST['buy_time_limit'],'cut_times_limit'=>$_POST['cut_times_limit'],'need_follow'=>$_POST['need_follow'],'fencheng'=>$_POST['fencheng'],'orders_limit'=>$orders_limit,'share_title'=>$_POST['share_title'],'share_brief'=>$_POST['share_brief'],'share_img'=>$_POST['share_img']);

    /* 插入数据 */
    $record = array('act_name'=>$_POST['cut_name'], 'act_desc'=>$_POST['act_desc'],
                    'act_type'=>GAT_CUT, 'goods_id'=>$_POST['goods_id'], 'goods_name'=>$_POST['goods_name'],
                    'start_time'=>$_POST['start_time'], 'end_time'=>$_POST['end_time'],
                    'product_id'=>$_POST['product_id'], 'supplier_id'=>$_SESSION['supplier_id'],
                    'is_finished'=>0, 'ext_info'=>serialize($info));

    $db->AutoExecute($ecs->table('goods_activity'),$record,'INSERT');

    admin_log($_POST['cut_name'],'add','cut');
    $link[] = array('text' => $_LANG['back_list'], 'href'=>'cut.php?act=list');
    $link[] = array('text' => $_LANG['continue_add'], 'href'=>'cut.php?act=add');
    sys_msg($_LANG['add_succeed'],0,$link);
}

/*------------------------------------------------------ */
//-- 活动列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      '砍价活动'.$_SESSION['supplier_id']);
    $smarty->assign('action_link',  array('text' => '添加砍价活动', 'href'=>'cut.php?act=add'));
    $smarty->assign('action_link2',  array('href' => 'cut.php?act=view', 'text' => '查看所有砍价数据'));

    $cuts = get_cutlist( $_SESSION['supplier_id']);

    $smarty->assign('cut_list',  $cuts['cuts']);
    $smarty->assign('filter',       $cuts['filter']);
    $smarty->assign('record_count', $cuts['record_count']);
    $smarty->assign('page_count',   $cuts['page_count']);

    $sort_flag  = sort_flag($cuts['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    $smarty->assign('full_page',    1);
    assign_query_info();
    $smarty->display('cut_list.htm');
}

/*------------------------------------------------------ */
//-- 查询、翻页、排序
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query')
{
    $cuts = get_cutlist( $_SESSION['supplier_id']);

    $smarty->assign('cut_list',  $cuts['cuts']);
    $smarty->assign('filter',       $cuts['filter']);
    $smarty->assign('record_count', $cuts['record_count']);
    $smarty->assign('page_count',   $cuts['page_count']);

    $sort_flag  = sort_flag($cuts['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('cut_list.htm'), '',
        array('filter' => $cuts['filter'], 'page_count' => $cuts['page_count']));
}

/*------------------------------------------------------ */
//-- 编辑活动名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_cut_name')
{

    $id = intval($_POST['id']);
    $val = json_str_iconv(trim($_POST['val']));

    /* 检查是否是自己的商品 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "' AND act_id = '$id'" ;
    if (!$db->getOne($sql))
    {
        make_json_error("你不能编辑此活动");
    }


    /* 检查活动重名 */
    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_activity').
           " WHERE act_type='" . GAT_CUT . "' AND act_name='$val' AND act_id <> '$id'" ;
    if ($db->getOne($sql))
    {
        make_json_error(sprintf($_LANG['cut_name_exist'],  $val));
    }

    $exc->edit("act_name='$val'", $id);
    make_json_result(stripslashes($val));
}

/*------------------------------------------------------ */
//-- 删除指定的活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{

    $id = intval($_GET['id']);
	
    $sql = "select count(*) FROM " . $GLOBALS['ecs']->table('cut') . " WHERE act_id = '$id' ";
    $cut=$GLOBALS['db']->getOne($sql);
	if($cut){
        make_json_error('已产生砍价数据，请勿删除！');
	}

    /* 检查是否是自己的商品 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "' AND act_id = '$id'" ;
    if (!$db->getOne($sql))
    {
        make_json_error("你不能编辑此活动");
    }


    $exc->drop($id);

    $url = 'cut.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 编辑活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */

    $cut        = get_cut_info($_REQUEST['id']);

    $cut['option'] = '<option value="'.$cut['goods_id'].'">'.$cut['goods_name'].'</option>';
    $smarty->assign('cut',               $cut);
    $smarty->assign('ur_here',              $_LANG['cut_edit']);
    $smarty->assign('action_link',          array('text' => '砍价活动列表', 'href'=>'cut.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action',        'update');

    /* 商品货品表 */
    $smarty->assign('good_products_select', get_good_products_select($cut['goods_id']));
    create_html_editor('act_desc', htmlspecialchars($cut['act_desc']));

    assign_query_info();
    $smarty->display('cut_info.htm');
}
elseif ($_REQUEST['act'] =='update')
{
    /* 权限判断 */

    /* 将时间转换成整数 */
    $_POST['start_time'] = local_strtotime($_POST['start_time']);
    $_POST['end_time']   = local_strtotime($_POST['end_time']);

    /* 处理提交数据 */
    if (empty($_POST['cut_name']))
    {
        $_POST['cut_name'] = '';
    }
    if (empty($_POST['goods_id']))
    {
        $_POST['goods_id'] = 0;
    }
    else
    {
        $_POST['goods_name'] = $db->getOne("SELECT goods_name FROM " . $ecs->table('goods') . "WHERE goods_id= '$_POST[goods_id]'");
    }
    if (empty($_POST['price']))
    {
        $_POST['price'] = 0;
    }
    if (empty($_POST['start_price']))
    {
        $_POST['start_price'] = 0;
    }
    if (empty($_POST['end_price']))
    {
        $_POST['end_price'] = 0;
    }
    if (empty($_POST['max_price']))
    {
        $_POST['max_price'] = 0;
    }
    if (empty($_POST['cost_points']))
    {
        $_POST['cost_points'] = 0;
    }
    if (isset($_POST['product_id']) && empty($_POST['product_id']))
    {
        $_POST['product_id'] = 0;
    }
    $orders_limit = intval($_POST['orders_limit']);
    if ($orders_limit < 0){
            $orders_limit = 0;
    }
    /* 检查活动重名 */
    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_activity').
           " WHERE act_type='" . GAT_CUT . "' AND act_name='" . $_POST['cut_name'] . "' AND act_id <> '" .  $_POST['id'] . "'" ;
    if ($db->getOne($sql))
    {
        sys_msg(sprintf($_LANG['cut_name_exist'],  $_POST['cut_name']) , 1);
    }

    /* 检查是否是自己的商品 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('goods_activity')." WHERE supplier_id='" .$_SESSION['supplier_id'] . "' AND act_id = '" .  $_POST['id'] . "'" ;
    if (!$db->getOne($sql))
    {
        make_json_error("你不能编辑此活动");
    }

    $info = array('price'=>$_POST['price'],'market_price'=>$_POST['market_price'],'virtual_sold'=>$_POST['virtual_sold'],'start_price'=>$_POST['start_price'], 'end_price'=>$_POST['end_price'], 'max_price'=>$_POST['max_price'], 'cost_points'=>$_POST['cost_points'],'showlimit'=>$_POST['showlimit'],'max_buy_price'=>$_POST['max_buy_price'],'show_max_buy_price'=>$_POST['show_max_buy_price'],'join_limit'=>$_POST['join_limit'],'cut_time_limit'=>$_POST['cut_time_limit'],'buy_time_limit'=>$_POST['buy_time_limit'],'cut_times_limit'=>$_POST['cut_times_limit'],'need_follow'=>$_POST['need_follow'],'fencheng'=>$_POST['fencheng'],'orders_limit'=>$orders_limit,'share_title'=>$_POST['share_title'],'share_brief'=>$_POST['share_brief'],'share_img'=>$_POST['share_img']);

    /* 更新数据 */
    $record = array('act_name' => $_POST['cut_name'], 'goods_id' => $_POST['goods_id'],
                    'goods_name' =>$_POST['goods_name'], 'start_time' => $_POST['start_time'],
                    'end_time' => $_POST['end_time'], 'act_desc' => $_POST['act_desc'],
                    'product_id'=>$_POST['product_id'],
                    'ext_info'=>serialize($info));
    $db->autoExecute($ecs->table('goods_activity'), $record, 'UPDATE', "act_id = '" . $_POST['id'] . "' AND act_type = " . GAT_CUT );

    admin_log($_POST['cut_name'],'edit','cut');
    $link[] = array('text' => $_LANG['back_list'], 'href'=>'cut.php?act=list&' . list_link_postfix());
    sys_msg($_LANG['edit_succeed'],0,$link);
 }

/*------------------------------------------------------ */
//-- 查看活动详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    /* 权限判断 */
    //admin_priv('cut_manage');

    $cut_id = empty($_REQUEST['cut_id']) ? 0 : intval($_REQUEST['cut_id']);
    $act_id = empty($_REQUEST['act_id']) ? 0 : intval($_REQUEST['act_id']);

    if($cut_id){
		$cut_list = get_cut_log_detail();
	
		$smarty->assign('cut_list',     $cut_list['cut']);
		$smarty->assign('filter',       $cut_list['filter']);
		$smarty->assign('record_count', $cut_list['record_count']);
		$smarty->assign('page_count',   $cut_list['page_count']);
	
		$sort_flag  = sort_flag($cut_list['filter']);
		$smarty->assign($sort_flag['tag'], $sort_flag['img']);
		/* 赋值 */
		$smarty->assign('info',         get_cut_info($act_id));
		$smarty->assign('full_page',    1);
		$smarty->assign('ur_here',      '活动详情' );
		$smarty->assign('action_link',  array('text' => '本砍价活动发起者列表', 'href'=>'cut.php?act=view&act_id='.$act_id));
        $smarty->assign('action_link2',  array('href' => 'cut.php?act=view', 'text' => '查看所有砍价数据'));
		$smarty->display('cut_log_view.htm');
	}else{
		$cut_list = get_cut_detail($_SESSION['supplier_id']);
	
		$smarty->assign('cut_list',     $cut_list['cut']);
		$smarty->assign('filter',       $cut_list['filter']);
		$smarty->assign('record_count', $cut_list['record_count']);
		$smarty->assign('page_count',   $cut_list['page_count']);
	
		$sort_flag  = sort_flag($cut_list['filter']);
		$smarty->assign($sort_flag['tag'], $sort_flag['img']);
		/* 赋值 */
		$smarty->assign('full_page',    1);
		$smarty->assign('ur_here',      '活动详情' );
		$smarty->assign('action_link',  array('text' => '砍价活动列表', 'href'=>'cut.php?act=list'));
		$smarty->display('cut_view.htm');
	}
}

/*------------------------------------------------------ */
//-- 排序、翻页活动详情
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query_cut')
{
    $cut_list = get_cut_detail($_SESSION['supplier_id']);

    $smarty->assign('cut_list',     $cut_list['cut']);
    $smarty->assign('filter',       $cut_list['filter']);
    $smarty->assign('record_count', $cut_list['record_count']);
    $smarty->assign('page_count',   $cut_list['page_count']);

    $sort_flag  = sort_flag($cut_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('cut_view.htm'), '',
        array('filter' => $cut_list['filter'], 'page_count' => $cut_list['page_count']));
}

elseif ($_REQUEST['act'] == 'query_cut_log')
{
    $cut_list = get_cut_log_detail();

    $smarty->assign('cut_list',     $cut_list['cut']);
    $smarty->assign('filter',       $cut_list['filter']);
    $smarty->assign('record_count', $cut_list['record_count']);
    $smarty->assign('page_count',   $cut_list['page_count']);

    $sort_flag  = sort_flag($cut_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('cut_log_view.htm'), '',
        array('filter' => $cut_list['filter'], 'page_count' => $cut_list['page_count']));
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{

    include_once(ROOT_PATH . 'includes/cls_json.php');

    $json   = new JSON;

    $filter = $json->decode($_GET['JSON']);

    $arr['goods']    = get_goods_list($filter);

    if (!empty($arr['goods'][0]['goods_id']))
    {
        $arr['products'] = get_good_products($arr['goods'][0]['goods_id']);
    }

    make_json_result($arr);
}

/*------------------------------------------------------ */
//-- 搜索货品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_products')
{

    include_once(ROOT_PATH . 'includes/cls_json.php');

    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    if (!empty($filters->goods_id))
    {
        $arr['products'] = get_good_products($filters->goods_id);
    }

    make_json_result($arr);
}




?>