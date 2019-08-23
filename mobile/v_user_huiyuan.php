<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if($_CFG['is_distrib'] == 0)
{
	show_message('没有开启微信分销服务！','返回首页','index.php'); 
}

if($_SESSION['user_id'] == 0)
{
	ecs_header("Location: ./\n");
    exit;	 
}

$is_distribor = is_distribor($_SESSION['user_id']);
if($is_distribor != 1)
{
	show_message('您还不是分销商！','去首页','index.php');
	exit;
}


if (!$smarty->is_cached('v_user_huiyuan.dwt', $cache_id))
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
	$smarty->assign('user_info',get_user_info_by_user_id($_SESSION['user_id'])); 
	$smarty->assign('one_level_user_count',get_user_count($_SESSION['user_id'],1));  //一级会员数量
	//$smarty->assign('two_level_user_count',get_user_count($_SESSION['user_id'],2));  //二级会员数量
	//$smarty->assign('three_level_user_count',get_user_count($_SESSION['user_id'],3));  //三级会员数量
	//$smarty->assign('four_level_user_count',get_user_count($_SESSION['user_id'],4));  //四级会员数量
	//$smarty->assign('five_level_user_count',get_user_count($_SESSION['user_id'],5));  //五级会员数量
	//$smarty->assign('six_level_user_count',get_user_count($_SESSION['user_id'],6));  //六级会员数量
	
	$smarty->assign('one_user_list',get_distrib_user_info_putong($_SESSION['user_id'],1)); //一级会员信息
	//$smarty->assign('two_user_list',get_distrib_user_info($_SESSION['user_id'],2)); //二级会员信息
	//$smarty->assign('three_user_list',get_distrib_user_info($_SESSION['user_id'],3)); //三级会员信息
	//$smarty->assign('four_user_list',get_distrib_user_info($_SESSION['user_id'],4)); //四级会员信息
	//$smarty->assign('five_user_list',get_distrib_user_info($_SESSION['user_id'],5)); //五级会员信息
	//$smarty->assign('six_user_list',get_distrib_user_info($_SESSION['user_id'],6)); //六级会员信息
	$smarty->assign('user_id',$_SESSION['user_id']);
	
    /* 页面中的动态内容 */
    assign_dynamic('v_user_huiyuan');
}

$smarty->display('v_user_huiyuan.dwt', $cache_id);


//获取分销商下级会员信息,$level代表哪一级，1代表是一级会员
function get_distrib_user_info_putong($user_id,$level)
{
    $call_username = $GLOBALS['_CFG']['call_username'];
    $up_uid = "'$user_id'";
    for ($i = 1; $i<=$level; $i++)
    {
        $count = 0;
        if ($up_uid)
        {
            //$sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE parent_id IN($up_uid)";
            $sql = 'SELECT u.user_id '.
                ' FROM ' .$GLOBALS['ecs']->table('users'). ' AS u ' .
                ' LEFT JOIN ' .$GLOBALS['ecs']->table('user_rank'). ' AS ub'.
                ' ON ub.rank_id = u.user_rank ' .
                " WHERE (ub.special_rank = 0 or ub.special_rank is null) and u.parent_id IN($up_uid)";
            $query = $GLOBALS['db']->query($sql);
            $up_uid = '';
            while ($rt = $GLOBALS['db']->fetch_array($query))
            {
                $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                $count++;
            }
        }
    }
    if($count)
    {
        $sql = "SELECT user_id, user_name, email, is_validated, user_money, frozen_money, rank_points, pay_points, reg_time ".
            " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id IN($up_uid)";
        $list = $GLOBALS['db']->getAll($sql);
        $arr = array();
        foreach($list as $key => $val)
        {
            if($call_username == 1)
            {
                $arr[$key]['call_username'] = '会员ID：'.$val['user_id'];
            }
            else
            {
                $arr[$key]['call_username'] = '会员名称：'.$val['user_name'];
            }
            $arr[$key]['call_username'] = '会员名称：'.$val['user_name'];
            $arr[$key]['user_id'] = $val['user_id'];
            $arr[$key]['user_name'] = $val['user_name'];
            $arr[$key]['order_count'] = get_affiliate_count_by_user_id_new($val['user_id']); //分成订单数量
            $arr[$key]['split_money'] = get_split_money_by_user_id($user_id,$val['user_id']); //分成金额
            $info = get_user_info_by_user_id($val['user_id']);
            $arr[$key]['headimgurl'] = $info['headimgurl'];
        }
        if(!empty($arr))
        {
            return $arr;
        }
    }
}

function get_affiliate_count_by_user_id_new($user_id)
{

    $where = '';
    if($GLOBALS['_CFG']['distrib_type'] == 1)
    {
        $where .= " WHERE total_money > 0";
    }

    if($GLOBALS['_CFG']['is_add_distrib'] == 0)
    {
        $sql_where = " AND supplier_id = 0";
    }
    $sql = "SELECT COUNT(*) FROM 
			(SELECT SUM(split_money) as total_money FROM " .
        $GLOBALS['ecs']->table('order_info') . " as o ," .
        $GLOBALS['ecs']->table('order_goods') . " as og where o.order_id = og.order_id AND o.user_id = '$user_id' AND o.pay_status = 2 $sql_where group by o.order_id ) as oog $where";
    return $GLOBALS['db']->getOne($sql);
}
?>