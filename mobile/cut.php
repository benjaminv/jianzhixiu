<?php

/**
 *  砍价前台文件
 * $Author: palenggege $
 * $Id: cut.php 17217 2016-01-10 06:29:08Z paleng QQ 497401495 $
 */


define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/prince/lib_cut.php');

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}

/*------------------------------------------------------ */
//-- 砍价活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得砍价活动总数 */
    $count = cut_count();

    if ($count > 0)
    {
        /* 取得每页记录数 */
        $size = isset($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

        /* 计算总页数 */
        $page_count = ceil($count / $size);

        /* 取得当前页 */
        $page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        $page = $page > $page_count ? $page_count : $page;

        /* 缓存id：语言 - 每页记录数 - 当前页 */
        $cache_id = $_CFG['lang'] . '-' . $size . '-' . $page;
        $cache_id = sprintf('%X', crc32($cache_id));
    }
    else
    {
        /* 缓存id：语言 */
        $cache_id = $_CFG['lang'];
        $cache_id = sprintf('%X', crc32($cache_id));
    }
    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('cut_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的砍价活动 */
            $cut_list = cut_list($size, $page);
            $smarty->assign('cut_list',  $cut_list);

            /* 设置分页链接 */
            $pager = get_pager('cut.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }


        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('u', $_SESSION['user_id']?$_SESSION['user_id']:0);    // 
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typecut.xml" : 'feed.php?type=cut'); // RSS URL

        assign_dynamic('cut_list');
    }

    /* 显示模板 */
    $smarty->display('cut_list.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 某会员砍价活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'userlist')
{
    /* 取得会员砍价活动总数 */
    $now = gmtime();
    $sql = "SELECT COUNT(*) " .
            "FROM " . $GLOBALS['ecs']->table('cut') .
            "WHERE act_type = '" . GAT_CUT . "' " .
            "AND user_id=".$_SESSION['user_id'];
    $count =  $GLOBALS['db']->getOne($sql);

    if ($count > 0)
    {
        /* 取得每页记录数 */
        $size = isset($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 1;

        /* 计算总页数 */
        $page_count = ceil($count / $size);

        /* 取得当前页 */
        $page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        $page = $page > $page_count ? $page_count : $page;

        /* 缓存id：语言 - 每页记录数 - 当前页 */
        $cache_id = $_CFG['lang'] . '-' . $size . '-' . $page;
        $cache_id = sprintf('%X', crc32($cache_id));
    }
    else
    {
        /* 缓存id：语言 */
        $cache_id = $_CFG['lang'];
        $cache_id = sprintf('%X', crc32($cache_id));
    }

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('cut_user_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的砍价活动 */
            $cut_list = cut_user_list($size, $page,$_SESSION['user_id']);
            $smarty->assign('cut_user_list',  $cut_list);

            /* 设置分页链接 */
            $pager = get_pager('cut.php', array('act' => 'userlist'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('u', $_SESSION['user_id']?$_SESSION['user_id']:0);    // 


        assign_dynamic('cut_user_list');
    }

    /* 显示模板 */
    $smarty->display('cut_user_list.dwt', $cache_id);
}


/*------------------------------------------------------ */
//-- 砍价商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{    

    /* 取得参数：砍价活动id */
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $u=$_SESSION['user_id'];
    $user_id=$_SESSION['user_id'];

	if($_SESSION['user_id']){
		$sql = "SELECT * FROM  " .  $GLOBALS['ecs']->table('weixin_user') . " WHERE  ecuid=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
		if(!$weixininfo['isfollow']){
			$continue_url="http://".$_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
			$sql = "UPDATE ".  $GLOBALS['ecs']->table('weixin_user') . "  SET continue_url = '". $continue_url . "'".
				   " WHERE ecuid = '" . $_SESSION['user_id'] . "'";
			$db->query($sql);
		}
	}

    if ($id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得砍价活动信息 */
    $cut = cut_info($id);
    if (empty($cut))
    {
		ecs_header("Location: ./\n");
        exit;
    }

    /* 缓存id：语言，砍价活动id，状态，如果是进行中，还要最后出价的时间（如果有的话） */
    $cache_id = $_CFG['lang'] . '-' . $id . '-' . $cut['status_no'];
    if ($cut['status_no'] == UNDER_WAY)
    {
        if (isset($cut['last_cut']))
        {
            $cache_id = $cache_id . '-' . $cut['last_cut']['cut_time'];
        }
    }
    elseif ($cut['status_no'] == FINISHED && $cut['last_cut']['cut_user'] == $_SESSION['user_id']
        && $cut['order_count'] == 0)
    {
        $cut['is_winner'] = 1;
        $cache_id = $cache_id . '-' . $cut['last_cut']['cut_time'] . '-1';
    }

    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('cut.dwt', $cache_id))
    {
        //取货品信息
        if ($cut['product_id'] > 0)
        {
            $goods_specifications = get_specifications_list($cut['goods_id']);

            $good_products = get_good_products($cut['goods_id'], 'AND product_id = ' . $cut['product_id']);

            $_good_products = explode('|', $good_products[0]['goods_attr']);
            $products_info = '';
            foreach ($_good_products as $value)
            {
                $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
            }
            $smarty->assign('products_info',     $products_info);
            unset($goods_specifications, $good_products, $_good_products,  $products_info);
        }

		//取得活动剩余时间
		$left_time_tip="活动剩余时间";
		$cut['gmt_end_time']= local_strtotime($cut['end_time'])+3600*8;
		$cut['left_time_tip']= $left_time_tip;
		
		
		
		


        /* 取得砍价商品信息 */
        $goods_id = $cut['goods_id'];
        $goods = goods_info($goods_id);
        if (empty($goods))
        {
            ecs_header("Location: ./\n");
            exit;
        }
        $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
        $smarty->assign('cut_goods', $goods);
        $smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册

        $cut['share_url'] ='http://'.$_SERVER['HTTP_HOST']."/mobile/cut.php?act=view&id=".$id."&u=".$_SESSION['user_id']; //20160119 added by PRINCE 120029121
        $cut['share_img'] =strpos($cut['share_img'],'ttp')>0?$cut['share_img']:$goods['goods_thumb'];
        $smarty->assign('cut', $cut);
		

		
        $smarty->assign('cut_log', cut_log($id));
        $smarty->assign('user_id', $_SESSION['user_id']?$_SESSION['user_id']:0);
		
		
        /* 提示 */	
		if($_SESSION['cut_tips'] ){
            $smarty->assign('cut_tips', $_SESSION['cut_tips']);  
			 unset($_SESSION['cut_tips']);
		}

        /*右边按钮*/
	    if($cut['status_no'] == FINISHED || $cut['status_no'] == 0){
			 $right_action="更多活动";
			 $right_url="cut.php?act=list";
		}else{
				 $right_action='立即参与';
				 $right_url="cut.php?act=join&id=$id";
		}
        $smarty->assign('right_action', $right_action);  // 右边按钮
        $smarty->assign('right_url', $right_url);   // 右边按钮
        $smarty->assign('right_click', $right_click);   // 右边按钮

		$sql = "SELECT w.* FROM  " . $GLOBALS['ecs']->table('users') . " u ".
				"left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
				"WHERE  u.user_id=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
        $smarty->assign('weixininfo',    $weixininfo); 
		
        $smarty->assign('is_wechat_browser',    is_wechat_browser_for_cut()); 

		$web_url ='http://'.$_SERVER['HTTP_HOST'].'/';
        $smarty->assign('web_url',    $web_url); 
		
		$wap_url ='http://'.$_SERVER['HTTP_HOST'].'mobile/';
        $smarty->assign('wap_url',    $wap_url); 

        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $cut['goods_name']?$cut['goods_name']:$goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

		require_once "wxjs/jssdk.php";

		$ret = $db->getRow("SELECT  *  FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1");
		$jssdk = new JSSDK($appid=$ret['appid'], $ret['appsecret']);

		$signPackage = $jssdk->GetSignPackage();

		$smarty->assign('signPackage',  $signPackage);

        assign_dynamic('cut');
    }

    //更新商品点击次数
    $sql = 'UPDATE ' . $ecs->table('goods') . ' SET click_count = click_count + 1 '.
           "WHERE goods_id = '" . $cut['goods_id'] . "'";
    $db->query($sql);
	
	
    $smarty->assign('now_time',  gmtime());           // 当前系统时间
    $smarty->display('cut.dwt', $cache_id);
}



/*------------------------------------------------------ */
//-- 砍价商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'cut_view')
{    

    /* 取得参数：砍价活动id */
    $cut_id = isset($_REQUEST['cut_id']) ? intval($_REQUEST['cut_id']) : 0;
    $user_id=$_SESSION['user_id'];

	if($_SESSION['user_id']){
		$sql = "SELECT * FROM  " .  $GLOBALS['ecs']->table('weixin_user') . " WHERE  ecuid=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
		if(!$weixininfo['isfollow']){
			$continue_url="http://".$_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
			$sql = "UPDATE ".  $GLOBALS['ecs']->table('weixin_user') . "  SET continue_url = '". $continue_url . "'".
				   " WHERE ecuid = '" . $_SESSION['user_id'] . "'";
			$db->query($sql);
		}
	}

    if ($cut_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得砍价活动信息 */
    $cut = cut_detail_info($cut_id);
    if (empty($cut))
    {
		ecs_header("Location: ./\n");
        exit;
    }

    /* 缓存id：语言，砍价活动id，状态，如果是进行中，还要最后出价的时间（如果有的话） */
    $cache_id = $_CFG['lang'] . '-' . $cut_id . '-' . $cut['status_no'];

    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('cut.dwt', $cache_id))
    {
        //取货品信息
        if ($cut['product_id'] > 0)
        {
            $goods_specifications = get_specifications_list($cut['goods_id']);

            $good_products = get_good_products($cut['goods_id'], 'AND product_id = ' . $cut['product_id']);

            $_good_products = explode('|', $good_products[0]['goods_attr']);
            $products_info = '';
            foreach ($_good_products as $value)
            {
                $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
            }
            $smarty->assign('products_info',     $products_info);
            unset($goods_specifications, $good_products, $_good_products,  $products_info);
        }

		//取得活动剩余时间
		$left_time_tip="活动剩余时间";
		$cut['gmt_end_time']=$cut['end_cut_time']>gmtime()?$cut['end_cut_time']+3600*8:$cut['end_buy_time']+3600*8;

		$cut['left_time_tip']= $left_time_tip;
		$cut['show_create_time']= local_date($GLOBALS['_CFG']['time_format'], $cut['create_time']);

		
		/* 判断能否砍价 */	
		$sql = "select count(*) from ". $GLOBALS['ecs']->table('cut_log') . " where cut_user='$user_id' and cut_id='$cut_id' ";
		$had_cut =$GLOBALS['db']->getOne($sql);
		if($had_cut){
            $smarty->assign('can_cut', 0);
		}else{
            $smarty->assign('can_cut', 1);
		}
		


        /* 取得砍价商品信息 */
        $goods_id = $cut['goods_id'];
        $goods = goods_info($goods_id);
        if (empty($goods))
        {
            ecs_header("Location: ./\n");
            exit;
        }
        $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
        $smarty->assign('cut_goods', $goods);
        $smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册

        $cut['share_url'] ='http://'.$_SERVER['HTTP_HOST']."/mobile/cut.php?act=cut_view&cut_id=".$cut_id."&u=".$_SESSION['user_id']; //20160119 added by PRINCE 120029121
        $cut['share_img'] =strpos($cut['share_img'],'ttp')>0?$cut['share_img']:$goods['goods_thumb'];
		
		
        $sql = "SELECT cut_price " .
            "FROM  " . $GLOBALS['ecs']->table('cut_log') . " " .
            "WHERE cut_id=".$cut_id." and cut_user=act_user  LIMIT 1";
	    $cut_price = $GLOBALS['db']->getOne($sql);
		$cut['cut_price']=$cut_price ;
		
        $smarty->assign('cut', $cut);
		

        $smarty->assign('user_id', $_SESSION['user_id']?$_SESSION['user_id']:0);
		
		
        /* 提示 */	
		if($_SESSION['cut_tips'] ){
            $smarty->assign('cut_tips', $_SESSION['cut_tips']);  
			 unset($_SESSION['cut_tips']);
		}
		
		if($_SESSION['cut_done'] ){
                 $smarty->assign('cut_done', $_SESSION['cut_done'] );  
				 unset($_SESSION['cut_done']);
		}

				 
		$sql = "SELECT w.* FROM  " . $GLOBALS['ecs']->table('users') . " u ".
				"left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
				"WHERE  u.user_id=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
        $smarty->assign('weixininfo',    $weixininfo); 
		
        $smarty->assign('is_wechat_browser',    is_wechat_browser_for_cut()); 

		$web_url ='http://'.$_SERVER['HTTP_HOST'].'/';
        $smarty->assign('web_url',    $web_url); 
		
		$wap_url ='http://'.$_SERVER['HTTP_HOST'].'mobile/';
        $smarty->assign('wap_url',    $wap_url); 
		
		
		//砍价记录
        $sql = "SELECT cl.* " .
            "FROM  " . $GLOBALS['ecs']->table('cut_log') . " AS cl " .
            "WHERE cl.cut_id=".$cut_id." and cl.cut_user!=cl.act_user ORDER BY cl.log_id DESC";
	    $cut_logs = $GLOBALS['db']->getAll($sql);
		
		$log_list = array();
		foreach ($cut_logs AS $key => $val){
			$val['formated_cut_time']=local_date($GLOBALS['_CFG']['time_format'], $val['cut_time']);
			$log_list[] = $val;
		}
        $smarty->assign('cut_logs', $log_list);
		

        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $cut['goods_name']?$cut['goods_name']:$goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

		require_once "wxjs/jssdk.php";

		$ret = $db->getRow("SELECT  *  FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1");
		$jssdk = new JSSDK($appid=$ret['appid'], $ret['appsecret']);

		$signPackage = $jssdk->GetSignPackage();

		$smarty->assign('signPackage',  $signPackage);

        assign_dynamic('cut_view');
    }

    //更新商品点击次数
    $sql = 'UPDATE ' . $ecs->table('goods') . ' SET click_count = click_count + 1 '.
           "WHERE goods_id = '" . $cut['goods_id'] . "'";
    $db->query($sql);
	
	
    $smarty->assign('now_time',  gmtime());           // 当前系统时间
    $smarty->display('cut_view.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 砍价商品 --> 发起
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'join')
{
    include_once(ROOT_PATH . 'include/lib_order.php');

    /* 取得参数：砍价活动id */
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $u=$_SESSION['user_id'];
    $user_id=$_SESSION['user_id'];

    if ($id <= 0 )
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得砍价活动信息 */
    $cut = cut_info($id);
    if (empty($cut))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 活动是否正在进行 */
    if ($cut['status_no'] != UNDER_WAY)
    {
        show_message('活动未开始或已结束', '', '', 'error');
    }

    /* 是否登录 */
    if ($user_id <= 0)
    {
        show_message($_LANG['au_buy_after_login'], "马上登陆", 'user.php', 'error');
    }
		
    /* 判断能否参加 */	
    $sql = "select count(*) from ". $GLOBALS['ecs']->table('cut') . " where user_id='$user_id' and act_id='$id' ";
    $join_times =$GLOBALS['db']->getOne($sql);

	if($join_times>=$cut['join_limit'] && $cut['join_limit']>0 ){
        $_SESSION['cut_tips']='对不起。本活动目前限制只能参与'.$cut['join_limit'].'次。'.'<br />您已参与本活动'.$join_times.'次。';
		ecs_header("Location: cut.php?act=view&id=$id\n");
		exit;
	}

	
	//获取用户昵称、头像
    $sql = "SELECT u.*,w.nickname,w.headimgurl FROM  " . $GLOBALS['ecs']->table('users') . " u ".
            "left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
            "WHERE  u.user_id=".$_SESSION['user_id'];
    $getinfo =$GLOBALS['db']->getRow($sql);
	
    $goods_id = $cut['goods_id'];
    $goods = goods_info($goods_id);
	
    
    /* 插入用户活动记录 */
	$cut['cut_time_limit']=$cut['cut_time_limit']?$cut['cut_time_limit']:48;
	$cut['buy_time_limit']=$cut['buy_time_limit']?$cut['buy_time_limit']:96;
	$end_time=local_strtotime($cut['end_time']);
	$end_cut_time=(gmtime()+3600*$cut['cut_time_limit'])>$end_time?$end_time:gmtime()+3600*$cut['cut_time_limit'];
	$end_buy_time=(gmtime()+3600*$cut['buy_time_limit'])>$end_time?$end_time:gmtime()+3600*$cut['buy_time_limit'];

    $cut = array(
        'user_id'    => $user_id,
        'user_nickname'  => $getinfo['nickname']?$getinfo['nickname']:$getinfo['user_name'],
        'user_head'  => $getinfo['headimgurl'],
        'act_id'  => $id,
        'act_type'  => GAT_CUT,
        'shop_price' => $cut['price'],
        'max_buy_price' => $cut['max_buy_price'],
        'new_price' =>  $cut['price'],
        'create_time'  => gmtime(),
        'end_cut_time' => $end_cut_time,
        'end_buy_time' => $end_buy_time

    );
    $db->autoExecute($ecs->table('cut'), $cut, 'INSERT');	
	$cut_id = $db->insert_id();

    $_SESSION['cut_tips']='恭喜您成功参与活动。快分享给好友帮您砍价吧。';

    /* 参加成功 跳转到活动详情页 */
    ecs_header("Location: cut.php?act=cut_view&id=$id&cut_id=$cut_id&u=$user_id\n");
    exit;
}

/*------------------------------------------------------ */
//-- 砍价商品 --> 砍价记录
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'logpage')
{

    /* 取得参数：砍价活动id */
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $actuid = isset($_REQUEST['actuid']) ? intval($_REQUEST['actuid']) : 0;
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $u=$_SESSION['user_id'];

    if ($id <= 0 ||  $actuid <=0)
    {
        ecs_header("Location: ./\n");
        exit;
    }else{
        $_SESSION['cut_logpage']=$page;
		/* 跳转到活动详情页 */
		ecs_header("Location: cut.php?act=view&id=$id&actuid=$actuid&u=$u\n");
		exit;
	}
	
	
}

/*------------------------------------------------------ */
//-- 砍价商品 --> 砍价
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'cut')
{
    include_once(ROOT_PATH . 'include/lib_order.php');
    $u=$_SESSION['user_id'];
    $user_id=$_SESSION['user_id'];
    $cut_id = isset($_POST['cut_id']) ? intval($_POST['cut_id']) : 0;

    if ($cut_id <= 0 )
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得砍价活动信息 */
    $cut = cut_detail_info($cut_id);
    if (empty($cut))
    {
        ecs_header("Location: ./\n");
        exit;
    }
	$act_id=$cut['act_id'];
	$act_user=$cut['user_id'];

    /* 是否登录 */
    if ($user_id <= 0)
    {
        show_message($_LANG['au_buy_after_login'], "马上登陆", 'user.php', 'error');
    }
		
	/* 判断能否砍价 */	
	$sql = "select count(*) from ". $GLOBALS['ecs']->table('cut_log') . " where cut_user='$user_id' and cut_id='$cut_id' ";
	$had_cut =$GLOBALS['db']->getOne($sql);
	if($had_cut){
	    $_SESSION['cut_tips']="抱歉！您已经参与过本次砍价";
   		ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
	 }
		
		
     //是否达到砍价次数
	$sql = "select count(*) from ". $GLOBALS['ecs']->table('cut_log') . " where act_id='$act_id' and cut_user=".$_SESSION['user_id'];
	$total_cut =$GLOBALS['db']->getOne($sql);
    if ($total_cut>=$cut['cut_times_limit']  && $cut['cut_times_limit']!=0){    
	    $_SESSION['cut_tips']="抱歉！砍价失败！本商品的本次活动每位会员限砍".$cut['cut_times_limit']."次。您已在本活动帮别人或自己砍价达到".$total_cut."次。";
   		ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
    }
    
		
	//是否超时砍价
    if ($cut['end_cut_time']<gmtime()){    
	    $_SESSION['cut_tips']="抱歉！砍价失败！已超过砍价时限。";
   		ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
    }
	
	//获取用户昵称、头像
    $sql = "SELECT u.*,w.nickname,w.headimgurl FROM  " . $GLOBALS['ecs']->table('users') . " u ".
            "left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
            "WHERE  u.user_id='$user_id'";
    $getinfo =$GLOBALS['db']->getRow($sql);
	
	
	if(1){
		
		/* 取得当前价 */	
		$sql = "select new_price from ". $GLOBALS['ecs']->table('cut') . " where cut_id='$cut_id'  ";
		$current_price =$GLOBALS['db']->getOne($sql);
		
		if($current_price <=$cut['max_price']){
			$_SESSION['cut_tips']="抱歉！本次砍价已经砍到底价了。";
			ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
		}
	
		/* 取得砍价 */	
		$cut_price = round(randomFloat($cut['start_price'],$cut['end_price']),2);
		
		
		/* 如果砍后价格大于最低限价，则修改砍价值 */
		if ( $current_price - $cut_price<$cut['max_price']){
			$cut_price =$current_price-$cut['max_price'];
		}
		
		$after_cut_price=$current_price-$cut_price;
		
		
		/* 插入砍价记录 */
		$cut_log = array(
			'cut_id'    => $cut_id,
			'act_id'    => $act_id,
			'act_user'  => $act_user,
			'cut_user'  => $user_id,
			'cut_user_nickname'  => $getinfo['nickname']?$getinfo['nickname']:$getinfo['user_name'],
			'cut_user_head'  => $getinfo['headimgurl'],
			'cut_price' => $cut_price,
			'after_cut_price' => $after_cut_price,
			'cut_time'  => gmtime()
		);
		$db->autoExecute($ecs->table('cut_log'), $cut_log, 'INSERT');
		
		/*更新发起者价格*/
		$sql = "UPDATE " . $ecs->table('cut') . " SET new_price = '$after_cut_price' WHERE cut_id = '$cut_id' LIMIT 1";
		$db->query($sql);
		
        $_SESSION['cut_done']=$cut_price;
		
		//发送微信提醒
		$nowuserid=$_SESSION['user_id']?$_SESSION['user_id']:0;
		if($act_user!=$nowuserid ){
		    require(ROOT_PATH . 'wxm_cut.php');
		}
	}
	
	    /* 跳转到活动详情页 */
    ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
    exit;
}

/*------------------------------------------------------ */
//-- 砍价商品 --> 购买
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'buy')
{
    /* 查询：取得参数：砍价活动id */
    $cut_id = isset($_POST['cut_id']) ? intval($_POST['cut_id']) : 0;
    $act_id = isset($_POST['act_id']) ? intval($_POST['act_id']) : 0;

    $u=$_SESSION['user_id'];
    $user_id=$_SESSION['user_id'];
	
    if ($cut_id <= 0){
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：取得砍价活动信息 */
    $cut_info = cut_info($act_id);
    if (empty($cut_info)){
        ecs_header("Location: ./\n");
        exit;
    }

    /* 活动是否正在进行 */
    if ($cut_info['status_no'] != UNDER_WAY){
        show_message('活动已结束', '', '', 'error');
    }
	


    /* 查询：是否登录 */
    if ($user_id <= 0){
		show_message($_LANG['au_buy_after_login'], "马上登陆", 'user.php', 'error');
    }


	$sql = "select * from ". $GLOBALS['ecs']->table('cut') . " where cut_id='$cut_id'  ";
	$cut =$GLOBALS['db']->getRow($sql);

	
	//是否超时购买
    if ($cut['end_buy_time'] < gmtime() ){    
	    $_SESSION['cut_tips']="对不起！已超过下单时限。";
        ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
    }

	//是否已砍到最高售价
    if ($cut['new_price'] > $cut_info['max_buy_price'] &&  $cut_info['max_buy_price']>=0){    
	    $_SESSION['cut_tips']="对不起！本次活动必须砍到 ¥".$cut_info['max_buy_price']." 元或以下才能下单！";
        ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
    }
	
	//是否已到达购买次数
    if ($cut['order_times'] >= $cut_info['orders_limit']  &&  $cut_info['orders_limit']!=0){    
	    $_SESSION['cut_tips']="对不起！您已经下单 ".$cut['order_times']." 次了。<br />已达到本次砍价的最大下单次数！";
        ecs_header("Location: cut.php?act=cut_view&cut_id=$cut_id&u=$u\n");
    }
	

    /* 查询：取得商品信息 */
    $goods = goods_info($cut_info['goods_id']);

    /* 查询：处理规格属性 */
    $goods_attr = '';
    $goods_attr_id = '';
    if (0){
        $product_info = get_good_products($cut_info['goods_id'], 'AND product_id = ' . $cut_info['product_id']);

        $goods_attr_id = str_replace('|', ',', $product_info[0]['goods_attr']);

        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $ecs->table('goods_attr') . " AS g, " .
                    $ecs->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($goods_attr_id);
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);
    }
    else{
        $cut['product_id'] = 0;
    }

    /* 清空购物车中所有砍价商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
    clear_cart(CART_CUT_GOODS);

    /* 加入购物车 */
    $cart = array(
        'user_id'        => $user_id,
        'session_id'     => SESS_ID,
        'goods_id'       => $cut_info['goods_id'],
        'goods_sn'       => addslashes($goods['goods_sn']),
        'goods_name'     => $cut_info['act_name']?$cut_info['act_name']:addslashes($goods['goods_name']),
        'market_price'   => $goods['market_price'],
        'goods_price'    => $cut['new_price'],
        'cost_price'       => addslashes($goods['cost_price']),
        'split_money'   => addslashes($goods['cost_price']),
        'goods_number'   => 1,
        'goods_attr'     => $goods_attr,
        'goods_attr_id'  => $goods_attr_id,
        'is_real'        => $goods['is_real'],
        'is_shipping'    =>$goods['is_shipping'],
        'extension_code' => addslashes($goods['extension_code']),
        'parent_id'      => 0,
        'rec_type'       => CART_CUT_GOODS,
        'is_gift'        => 0,
        'split_money'   => $cut_info['fencheng'],
		'cost_price'    =>$cut_info['fencheng']
    );
    $db->autoExecute($ecs->table('cart'), $cart, 'INSERT');
	$_SESSION['sel_cartgoods'] = $db->insert_id();

    /* 记录购物流程类型：砍价 */
    $_SESSION['flow_type'] = CART_CUT_GOODS;
    $_SESSION['extension_code'] = 'cut';
    $_SESSION['extension_id'] = $act_id;
    $_SESSION['cut_id'] = $cut_id;

    /* 进入收货人页面 */
    ecs_header("Location: ./flow.php?step=checkout\n");
    exit;
}



//生成随机数 P R I N C E Q Q 120 029 121
function randomFloat($min = 0, $max = 1) {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

?>