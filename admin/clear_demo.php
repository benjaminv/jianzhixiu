<?php

/**
 * ECSHOP 清除演示数据
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods.php 17217 2011-01-19 06:29:08Z liubo $
*/
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
admin_priv('clear_demo');// prince 1060626

/*------------------------------------------------------ */
//-- 载入界面
/*------------------------------------------------------ */
if($_REQUEST['act'] == 'start')
{
    $smarty->assign('ur_here', $_LANG['clear_demo']);
    $smarty->display('clear_demo.htm');
}

/*------------------------------------------------------ */
//-- 清除数据
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'clear')
{
    $_POST['username'] = isset($_POST['username']) ? trim($_POST['username']) : '';
    $_POST['password'] = isset($_POST['password']) ? trim($_POST['password']) : '';

    $sql="SELECT `ec_salt` FROM ". $ecs->table('admin_user') ."WHERE user_name = '" . $_POST['username']."'";
    $ec_salt =$db->getOne($sql);
    if(!empty($ec_salt))
    {
        /* 检查密码是否正确 */
        $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt".
            " FROM " . $ecs->table('admin_user') .
            " WHERE user_name = '" . $_POST['username']. "' AND password = '" . md5(md5($_POST['password']).$ec_salt) . "'";
    }
    else
    {
        /* 检查密码是否正确 */
        $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt".
            " FROM " . $ecs->table('admin_user') .
            " WHERE user_name = '" . $_POST['username']. "' AND password = '" . md5($_POST['password']) . "'";
    }
    $row = $db->getRow($sql);

    if($row)
    {
        $sql="SELECT `action_list` FROM ". $ecs->table('admin_user') ."WHERE user_name = '" . $_POST['username']."'";
        $action_list =$db->getOne($sql);

        if($action_list == 'all')
        {   
		    if (file_exists("../data/clear_demo.txt")) {
             sys_msg($_LANG['not_txt'], 1);
			 exit;
            }
            $tables = array(
                'account_log', 'admin_log', 'admin_message', 'auction_log', 
                'back_order', 'bonus_type', 'booking_goods', 'brand',
                'card', 'comment',
                'delivery_goods', 'delivery_order',
                'exchange_goods',
                'favourable_activity', 'feedback', 'friend_link',
                'goods', 'goods_activity', 'goods_article', 'goods_attr', 'goods_cat', 'goods_gallery', 'goods_type', 'group_goods',
                'keywords',
                'link_goods',
				'jilu',
                'member_price',
				 'ecsmart_article_ad_info',
                'ecsmart_article_ad_user',
                'weixin_prince_qrcode',
                'order_action', 'order_goods', 'order_info',
                'pack', 'package_goods', 'payment', 'pay_log', 'products',
                'shipping', 'shipping_area', 'snatch_log', 'stats',
                'supplier', 'supplier_admin_user', 'supplier_article', 'supplier_article_cat', 'supplier_cat_recommend', 'supplier_goods_cat', 'supplier_guanzhu',
                'supplier_rebate', 'supplier_rebate_log', 'supplier_shop_config', 'supplier_street', 'supplier_tag', 'supplier_tag_map', 'suppliers',
                'tag', 'takegoods', 'takegoods_goods', 'takegoods_order', 'takegoods_type', 'takegoods_type_goods',
                'user_account', 'user_address', 'user_bonus', 'user_feed', 'user_address', 'users',
                'validate_record', 'valuecard', 'valuecard_type', 'verifycode',
                'virtual_card', 'virtual_district', 'virtual_goods_card', 'virtual_goods_district','volume_price', 'vote', 'vote_log', 'vote_option',
                 'weixin_user', 'wholesale',
				 'cut','cut_log','extpintuan','extpintuan_orders','extpintuan_price',
				 'lucky_buy','lucky_buy_calculate','lucky_buy_detail'   //palenggege QQ497401495
            );

            foreach ($tables AS $table)
            {
                $sql = "TRUNCATE `{$prefix}$table`";
                $db->query($sql);
            }
            $file = fopen("../data/clear_demo.txt","w");
            fwrite($file,$_LANG['clear_txt']);
            clear_cache_files();
            sys_msg($_LANG['clear_success'], 0);
        }
        else
        {
            sys_msg($_LANG['not_permitted'], 1);
        }
    }
    else
    {
       
        sys_msg($_LANG['password_incorrect'], 1);
		
    }
}
?>