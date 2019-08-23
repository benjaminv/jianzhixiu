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

/*------------------------------------------------------ */
//-- 根据关键字搜索商品
/*------------------------------------------------------ */
function action_searchgoods()
{
	include_once(ROOT_PATH . 'includes/cls_json.php');
	$json = new JSON();

	$keyword = empty($_REQUEST['goods_key']) ? '' : json_str_iconv(trim($_REQUEST['goods_key']));

	$result = array('error'=>0, 'message'=>'', 'content'=>'');

	if ($keyword != '')
	{
		$sql = "SELECT goods_id, goods_name, goods_sn FROM " . $GLOBALS['ecs']->table('goods') .
			" WHERE is_delete = 0" .
			" AND is_on_sale = 1" .
			" AND is_alone_sale = 1" .
			" AND (goods_id LIKE '%" . mysql_like_quote($keyword) . "%'" .
			" OR goods_name LIKE '%" . mysql_like_quote($keyword) . "%'" .
			" OR goods_sn LIKE '%" . mysql_like_quote($keyword) . "%')" .
			" LIMIT 20";
		$res = $GLOBALS['db']->query($sql);

		$result['goodslist'] = array();
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
            $result['content'] .="<option value ='".$row['goods_id']."'>".$row['goods_name']."</option>";
		}
        $result['error'] = 0;
	}
	else
	{
		$result['error'] = 1;
		$result['message'] = 'NO KEYWORDS';
	}
	die($json->encode($result));
}

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
    $smarty->assign('ur_here', "抽奖活动列表");
    $smarty->assign('action_link', array(
        'text' => "添加抽奖活动",'href' => 'dzp.php?act=add'
    ));

    $user_list = user_list();

    $smarty->assign('dzp_list', $user_list['user_list']);
    $smarty->assign('filter', $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count', $user_list['page_count']);
    $smarty->assign('full_page', 1);
    $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

    assign_query_info();
    $smarty->display('dzp_list.htm');
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
    foreach($user_list['user_list'] as $k=>$v){
        $sql = "select nickname,headimgurl from ".$ecs->table("weixin_user")." WHERE ecuid=".$v['user_id'];
        $data = $db->getRow($sql);
        $user_list['user_list'][$k]['user_name'].="丨".$data['nickname'];
        $user_list['user_list'][$k]['headimgurl']=$data['headimgurl'];
    }
    //$sql = "select * from".$ecs->table("weixin_user");
    //$data = $db->getAll($sql);
    //foreach ($user_list['user_list'] as $key=>$value){
    //foreach ($data as $k=>$v){
    //if($value['user_id'] == $v['ecuid']){
    //$user_list['user_list'][$key]['user_name'].="丨".$v['nickname'];
    //$user_list['user_list'][$key]['headimgurl']=$v['headimgurl'];
    //continue;
    //}
    //}
    //}

    //echo '<pre>';var_dump($user_list['user_list']);exit;

    $smarty->assign('user_list', $user_list['user_list']);
    $smarty->assign('filter', $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count', $user_list['page_count']);

    $sort_flag = sort_flag($user_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('dzp_list.htm'), '', array(
        'filter' => $user_list['filter'],'page_count' => $user_list['page_count']
    ));
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_add ()
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

    $user = array(
        'rank_points' => $_CFG['register_points'],'pay_points' => $_CFG['register_points'],'sex' => 0,'credit_line' => 0
    );
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    $smarty->assign('ur_here', "添加抽奖活动");
    $smarty->assign('action_link', array(
        'text' => "抽奖列表",'href' => 'dzp.php?act=list'
    ));
    $smarty->assign('form_action', 'insert');
    $smarty->assign('user', $user);
    $smarty->assign('special_ranks', get_rank_list(true));

    $sql = 'SELECT * FROM ' . $ecs->table('bonus_type') . ' WHERE send_type = 3 ORDER BY type_id';
    $bonus_type_list = $db->getAll($sql);
    $smarty->assign('bonus_type_list', $bonus_type_list);

    $smarty->assign('lang', $_LANG);
    $smarty->assign('country_list', get_regions());
    $province_list = get_regions(1, $row['country']);
    $city_list = get_regions(2, $row['province']);
    $district_list = get_regions(3, $row['city']);

    $smarty->assign('province_list', $province_list);
    $smarty->assign('city_list', $city_list);
    $smarty->assign('district_list', $district_list);

    assign_query_info();
    $smarty->display('dzp_info.htm');
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_insert ()
{
    // 全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];


    $dzp_name = empty($_POST['dzp_name']) ? '' : trim($_POST['dzp_name']);
    $dzp_draw_times = empty($_POST['dzp_draw_times']) ? '' : trim($_POST['dzp_draw_times']);
    $dzp_address = empty($_POST['dzp_address']) ? '' : trim($_POST['dzp_address']);
    $dzp_start_day = empty($_POST['dzp_start_day']) ? '' : trim($_POST['dzp_start_day']);
    $dzp_end_day = empty($_POST['dzp_end_day']) ? '' : trim($_POST['dzp_end_day']);
    $dzp_description = empty($_POST['dzp_description']) ? '' : trim($_POST['dzp_description']);

    if(empty($dzp_name))
	{
        sys_msg("请输入活动名称");
	}
    if(empty($dzp_draw_times))
    {
        sys_msg("请填写抽奖次数");
    }
    if(empty($dzp_address))
    {
        sys_msg("请填写抽奖地址");
    }
    if(empty($dzp_start_day))
    {
        sys_msg("请填写抽奖起始时间");
    }
    if(empty($dzp_end_day))
    {
        sys_msg("请填写抽奖结束时间");
    }

    if(empty($_POST['cfg_value']))
    {
        sys_msg("请填写活动列表");
    }
    //必须添加谢谢惠顾
    $xxhg=0;
    foreach($_POST['cfg_value']['type'] as $k=>$v)
    {
        if($v == 0)  //商品
        {
            if(empty($_POST['cfg_value']['goods_id'][$k]))
            {
                sys_msg("抽奖奖项：".$_POST['cfg_value']['prize_level'][$k]." 填写不全或有误！");
            }
        }
        else if($v == 1)  //商品
        {
            if(empty($_POST['cfg_value']['bouns_id'][$k]))
            {
                sys_msg("抽奖奖项：".$_POST['cfg_value']['prize_level'][$k]." 填写不全或有误！");
            }
        }
        else
        {
            $xxhg++;
        }
    }
    if($xxhg==0)
    {
        sys_msg("抽奖奖品必须添加一项谢谢惠顾！");
    }
    else if($xxhg>1)
    {
        $links[0]['text'] = "返回抽奖活动列表";
        $links[0]['href'] = 'dzp.php?act=list&' . list_link_postfix();

        sys_msg("编辑抽奖活动成功！", 0, $links);
    }


    //增加判断条件

    $sql = "insert into ".$GLOBALS['ecs']->table('dzp_type')." (dzp_name, dzp_draw_times, dzp_address, dzp_start_day, dzp_end_day, dzp_description) values ('$dzp_name', '$dzp_draw_times', '$dzp_address', '$dzp_start_day', '$dzp_end_day', '$dzp_description')";
    $GLOBALS['db']->query($sql);
    $dzp_id = $GLOBALS['db']->insert_id();

    $dzp_goods_count=count($_POST['cfg_value']['prize_level']);
    if($dzp_goods_count>0)
	{
        for ($x=0; $x<$dzp_goods_count; $x++) {
            $prize_level=$_POST['cfg_value']['prize_level'][$x];
            $prize_name=$_POST['cfg_value']['prize_name'][$x];
            $prize_count=$_POST['cfg_value']['prize_count'][$x];
            $prize_prob=$_POST['cfg_value']['prize_prob'][$x];
            $type = empty($_POST['cfg_value']['type'][$x]) ? 0 : intval($_POST['cfg_value']['type'][$x]);
            $bouns_id = empty($_POST['cfg_value']['bouns_id'][$x]) ? 0 : intval($_POST['cfg_value']['bouns_id'][$x]);
            $goods_id = empty($_POST['cfg_value']['goods_id'][$x]) ? 0 : intval($_POST['cfg_value']['goods_id'][$x]);
            $sql = "insert into ".$GLOBALS['ecs']->table('dzp_goods')." (dzp_id, prize_level, prize_name, prize_count, prize_prob, type, bouns_id, goods_id) values ('$dzp_id', '$prize_level', '$prize_name', '$prize_count', '$prize_prob', '$type', '$bouns_id', '$goods_id')";
            $GLOBALS['db']->query($sql);
        }
    }

    /* 提示信息 */
    $link[] = array(
        'text' => $_LANG['go_back'],'href' => 'dzp.php?act=list'
    );
    sys_msg("添加成功", 0, $link);
}

/* ------------------------------------------------------ */
// -- 编辑用户帐号
/* ------------------------------------------------------ */
function action_edit ()
{
    // 全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = 'SELECT * FROM ' . $ecs->table('bonus_type') . ' WHERE send_type = 3 ORDER BY type_id';
    $bonus_type_list = $db->getAll($sql);
    $smarty->assign('bonus_type_list', $bonus_type_list);


    $sql="select * from ". $ecs->table('dzp_type') ." where id = '$id'";
    $dzp_type_detail = $db->getRow($sql);
    if(empty($dzp_type_detail))
	{
        sys_msg("此抽奖活动异常！");
	}

	//奖品详情
    $sql="select * from ". $ecs->table('dzp_goods') ." where dzp_id = '$id' order by id asc";
    $dzp_goods = $db->getAll($sql);
    foreach ($dzp_goods AS $k => $v)
	{
        $dzp_goods[$k]['count']=$k;
        $dzp_goods[$k]['count1']=$k+1;
	}
    $smarty->assign('dzp_goods', $dzp_goods);


    /* 代码增加2014-12-23 by www.68ecshop.com _star */
    $smarty->assign('lang', $_LANG);

    /* 代码增加2014-12-23 by www.68ecshop.com _end */

    assign_query_info();
    $smarty->assign('ur_here', '抽奖活动编辑');
    $smarty->assign('action_link', array(
        'text' => "抽奖活动列表",'href' => 'dzp.php?act=list&' . list_link_postfix()
    ));

    $smarty->assign('user',$dzp_type_detail);

    $smarty->assign('form_action', 'update');
    $smarty->assign('special_ranks', get_rank_list(true));
    $smarty->display('dzp_info.htm');
}

/* ------------------------------------------------------ */
// -- 更新用户帐号
/* ------------------------------------------------------ */
function action_update ()
{
    // 全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    /* 检查权限 */
    admin_priv('users_manage');

    $dzp_name = empty($_POST['dzp_name']) ? '' : trim($_POST['dzp_name']);
    $dzp_draw_times = empty($_POST['dzp_draw_times']) ? '' : trim($_POST['dzp_draw_times']);
    $dzp_address = empty($_POST['dzp_address']) ? '' : trim($_POST['dzp_address']);
    $dzp_start_day = empty($_POST['dzp_start_day']) ? '' : trim($_POST['dzp_start_day']);
    $dzp_end_day = empty($_POST['dzp_end_day']) ? '' : trim($_POST['dzp_end_day']);
    $dzp_description = empty($_POST['dzp_description']) ? '' : trim($_POST['dzp_description']);

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

    if(empty($dzp_name))
    {
        sys_msg("请输入活动名称");
    }
    if(empty($dzp_draw_times))
    {
        sys_msg("请填写抽奖次数");
    }
    if(empty($dzp_address))
    {
        sys_msg("请填写抽奖地址");
    }
    if(empty($dzp_start_day))
    {
        sys_msg("请填写抽奖起始时间");
    }
    if(empty($dzp_end_day))
    {
        sys_msg("请填写抽奖结束时间");
    }
    if(empty($_POST['cfg_value']))
    {
        sys_msg("请填写活动列表");
    }
    //必须添加谢谢惠顾
    $xxhg=0;
    foreach($_POST['cfg_value']['type'] as $k=>$v)
    {
         if($v == 0)  //商品
         {
             if(empty($_POST['cfg_value']['goods_id'][$k]))
             {
                 sys_msg("抽奖奖项：".$_POST['cfg_value']['prize_level'][$k]." 填写不全或有误！");
             }
         }
        else if($v == 1)  //商品
        {
            if(empty($_POST['cfg_value']['bouns_id'][$k]))
            {
                sys_msg("抽奖奖项：".$_POST['cfg_value']['prize_level'][$k]." 填写不全或有误！");
            }
        }
        else
        {
            $xxhg++;
        }
    }
    if($xxhg==0)
    {
        sys_msg("抽奖奖品必须添加一项谢谢惠顾！");
    }
    else if($xxhg>1)
    {
        $links[0]['text'] = "返回抽奖活动列表";
        $links[0]['href'] = 'dzp.php?act=list&' . list_link_postfix();

        sys_msg("编辑抽奖活动成功！", 0, $links);
    }

    $sql = "update " . $ecs->table('dzp_type') . " set `dzp_name`='$dzp_name',`dzp_draw_times`='$dzp_draw_times',`dzp_address`='$dzp_address',`dzp_start_day`='$dzp_start_day',`dzp_end_day`='$dzp_end_day',`dzp_description`='$dzp_description' where id = '" . $id . "'";
    $db->query($sql);

	$sql = "DELETE FROM ". $ecs->table('dzp_goods') ." where dzp_id = '$id'";
    $db->query($sql);

    $dzp_goods_count=count($_POST['cfg_value']['prize_level']);
    if($dzp_goods_count>0)
    {
        for ($x=0; $x<$dzp_goods_count; $x++) {
            $prize_level=$_POST['cfg_value']['prize_level'][$x];
            $prize_name=$_POST['cfg_value']['prize_name'][$x];
            $prize_count=$_POST['cfg_value']['prize_count'][$x];
            $prize_prob=$_POST['cfg_value']['prize_prob'][$x];
            $type = empty($_POST['cfg_value']['type'][$x]) ? 0 : intval($_POST['cfg_value']['type'][$x]);
            $bouns_id = empty($_POST['cfg_value']['bouns_id'][$x]) ? 0 : intval($_POST['cfg_value']['bouns_id'][$x]);
            $goods_id = empty($_POST['cfg_value']['goods_id'][$x]) ? 0 : intval($_POST['cfg_value']['goods_id'][$x]);
            $sql = "insert into ".$GLOBALS['ecs']->table('dzp_goods')." (dzp_id, prize_level, prize_name, prize_count, prize_prob, type, bouns_id, goods_id) values ('$id', '$prize_level', '$prize_name', '$prize_count', '$prize_prob', '$type', '$bouns_id', '$goods_id')";
            $GLOBALS['db']->query($sql);
        }
    }


    /* 提示信息 */
    $links[0]['text'] = "返回抽奖活动列表";
    $links[0]['href'] = 'dzp.php?act=list&' . list_link_postfix();

    sys_msg("编辑抽奖活动成功！", 0, $links);
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
    admin_priv('users_drop');

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $sql = "DELETE FROM ". $ecs->table('dzp_goods') ." where dzp_id = '$id'";
    $db->query($sql);
    $sql = "DELETE FROM ". $ecs->table('dzp_type') ." where id = '$id'";
    $db->query($sql);
	/* 提示信息 */
	$link[] = array(
		'text' => $_LANG['go_back'], 'href' => 'dzp.php?act=list'
	);
	sys_msg("删除抽奖活动成功", 0, $link);
}


/* ------------------------------------------------------ */
// -- 收货地址查看
/* ------------------------------------------------------ */
function action_address_list ()
{
    // 全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sql = "SELECT a.*, c.region_name AS country_name, p.region_name AS province, ct.region_name AS city_name, d.region_name AS district_name " . " FROM " . $ecs->table('user_address') . " as a " . " LEFT JOIN " . $ecs->table('region') . " AS c ON c.region_id = a.country " . " LEFT JOIN " . $ecs->table('region') . " AS p ON p.region_id = a.province " . " LEFT JOIN " . $ecs->table('region') . " AS ct ON ct.region_id = a.city " . " LEFT JOIN " . $ecs->table('region') . " AS d ON d.region_id = a.district " . " WHERE user_id='$id'";
    $address = $db->getAll($sql);
    $smarty->assign('address', $address);
    assign_query_info();
    $smarty->assign('ur_here', $_LANG['address_list']);
    $smarty->assign('action_link', array(
        'text' => $_LANG['03_users_list'], 'href' => 'dzp.php?act=list&' . list_link_postfix()
    ));
    $smarty->display('user_address_list.htm');
}

/* ------------------------------------------------------ */
// -- 脱离推荐关系
/* ------------------------------------------------------ */
function action_remove_parent ()
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

    $sql = "UPDATE " . $ecs->table('users') . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
    $db->query($sql);

    /* 记录管理员操作 */
    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
    $username = $db->getOne($sql);
    admin_log(addslashes($username), 'edit', 'users');

    /* 提示信息 */
    $link[] = array(
        'text' => $_LANG['go_back'], 'href' => 'dzp.php?act=list'
    );
    sys_msg(sprintf($_LANG['update_success'], $username), 0, $link);
}

/* ------------------------------------------------------ */
// -- 查看用户推荐会员列表
/* ------------------------------------------------------ */
function action_aff_list ()
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
    $smarty->assign('ur_here', $_LANG['03_users_list']);

    $auid = $_GET['auid'];
    $user_list['user_list'] = array();

    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    $smarty->assign('affiliate', $affiliate);

    empty($affiliate) && $affiliate = array();

    $num = count($affiliate['item']);
    $up_uid = "'$auid'";
    $all_count = 0;
    for($i = 1; $i <= $num; $i ++)
    {
        $count = 0;
        if($up_uid)
        {
            $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
            $query = $db->query($sql);
            $up_uid = '';
            while($rt = $db->fetch_array($query))
            {
                $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                $count ++;
            }
        }
        $all_count += $count;

        if($count)
        {
            $sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated, user_money, frozen_money, rank_points, pay_points, reg_time " . " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id IN($up_uid)" . " ORDER by level, user_id";
            $user_list['user_list'] = array_merge($user_list['user_list'], $db->getAll($sql));
        }
    }

    $temp_count = count($user_list['user_list']);
    for($i = 0; $i < $temp_count; $i ++)
    {
        $user_list['user_list'][$i]['reg_time'] = local_date($_CFG['date_format'], $user_list['user_list'][$i]['reg_time']);
    }

    $user_list['record_count'] = $all_count;

    $smarty->assign('user_list', $user_list['user_list']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('full_page', 1);
    $smarty->assign('action_link', array(
        'text' => $_LANG['back_note'], 'href' => "dzp.php?act=edit&id=$auid"
    ));

    assign_query_info();
    $smarty->display('affiliate_list.htm');
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

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $ex_where = ' WHERE 1 ';

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('dzp_type') . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT * ".
            " FROM " . $GLOBALS['ecs']->table('dzp_type'). $ex_where . " ".
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



    $count = count($user_list);

    $arr = array(
        'user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
    );

    return $arr;
}

function action_sync_erp(){
    //同步会员到erp
    $user_id = intval($_REQUEST['user_id']);
    $link[] = array('href' => 'dzp.php?act=list', 'text' => "会员列表");
    if(!$user_id){
        sys_msg("缺少必要参数", 0, $link);
    }else{
        if(ODOO_ERP){
            $odooErpObj = OdooErp::getInstance();
            $res = $odooErpObj->syncUserByUserid($user_id);
            sys_msg("加入队列成功", 1, $link);exit;
            //if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
            //sys_msg("同步成功", 1, $link);exit;
            //}else{
            //sys_msg("同步失败:".$res['faultString'], 0, $links);exit;
            //}
        }else{
            sys_msg("系统未开启同步", 0, $link);
        }
    }
}
?>
