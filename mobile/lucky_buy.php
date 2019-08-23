<?php

/**
 * ECSHOP 云购前台文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lucky_buy.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'weixin/weixin_oauth.php');  //授权登陆文件 如果贵站没有这项功能请屏蔽
require_once(ROOT_PATH . 'includes/prince/lib_lucky_buy.php');
ship_code();
calculate_lucky_code();
/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}

 

/*------------------------------------------------------ */
//-- 云购活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得云购活动总数 */
    $count = lucky_buy_count();

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
    if (!$smarty->is_cached('lucky_buy_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的云购活动 */      

            $lucky_buy_list = lucky_buy_list($size, $page);  
            $smarty->assign('lucky_buy_list',  $lucky_buy_list);

            /* 设置分页链接 */
            $pager = get_pager('lucky_buy.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('u', $_SESSION['user_id']);    // 
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

        assign_dynamic('lucky_buy_list');
    }

    /* 显示模板 */
    $smarty->display('lucky_buy_list.dwt', $cache_id);
}



/*------------------------------------------------------ */
//-- 云购商品 --> 云购活动商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'asynclist')
{
    /* 取得云购活动总数 */
    $count = lucky_buy_count();
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

     /*
     * 异步显示商品列表 by wang
     */
    if ($_GET['act'] == 'asynclist') {
        $asyn_last = intval($_POST['last']) + 1;
        $size = $_POST['amount'];
        $page = ($asyn_last > 0) ? ceil($asyn_last / $size) : 1;
    }
    $goodslist = lucky_buy_list($size, $page);

	
    $sayList = array();
    if (is_array($goodslist)) {
        foreach ($goodslist as $vo) {
			
			//palenggege qq497401495
			if(strpos($vo['goods_thumb'],'ttp')>0){
				$img_url=$vo['goods_thumb'];
			}else{
				$img_url=$config['site_url'] . $vo['goods_thumb'];
			}
			//palenggege qq497401495

			$sql = "select * from ". $GLOBALS['ecs']->table('lucky_buy') . " where  act_id='$vo[act_id]' order by schedule_id desc limit 1";
	        $last_lucky_buy =$GLOBALS['db']->getRow($sql);

			$canyu=$last_lucky_buy['total']-$last_lucky_buy['available'];


			 $chicun =($last_lucky_buy['total']-$last_lucky_buy['available'])*100/$last_lucky_buy['total'];
         
            $sayList[] = array(
                'pro-inner' => '
         <li id="23740" class="">
     <span class="gList_l fl"> <a href="' . $vo['url'] . '"><img src="../' . $img_url . '"></a>
     
      </span>
      <div class="gList_r">
        <h3 class="gray6">'. $vo['act_name'] . '</h3>
        <em class="gray9">价值：'. $vo['allprice'] . '</em>
        <div class="gRate">
          <div class="Progress-bar">
            <p class="u-progress"><span style="width: '.$chicun.'%;" class="pgbar"><span class="pging"></span></span></p>
            <ul class="Pro-bar-li">
              <li class="P-bar01"><em>'. $canyu.'</em>已参与</li>
              <li class="P-bar02"><em>'. $vo['number'].'</em>总需人次</li>
              <li class="P-bar03"><em>'. $last_lucky_buy['available'].'</em>剩余</li>
            </ul>
          </div>
          <a class="" href="' . $vo['url'] . '"><s></s></a> </div>
      </div>
    </li>'
            );
        }
    }
   //  print_r( $goodslist  );
    echo json_encode($sayList);
    exit;
    /*
     * 异步显示商品列表 by wang end
     */

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('lucky_buy_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的云购活动 */
            $pt_list = lucky_buy_list($size, $page);
            $smarty->assign('pt_list',  $pt_list);

            /* 设置分页链接 */
            $pager = get_pager('lucky_buy.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置


        assign_dynamic('lucky_buy_list');
    }

    /* 显示模板 */
    $smarty->display('lucky_buy_list.dwt', $cache_id);
}


/*------------------------------------------------------ */
//-- 某会员云购活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'userlist')
{
    /* 取得会员云购活动总数 */
    $now = gmtime();
    $count = user_lucky_buy_count();

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
    if (!$smarty->is_cached('lucky_buy_user_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的云购活动 */
            $lucky_buy_list = lucky_buy_user_list($size, $page);
            $smarty->assign('lucky_buy_user_list',  $lucky_buy_list);

            /* 设置分页链接 */
            $pager = get_pager('lucky_buy.php', array('act' => 'userlist'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('u', $_SESSION['user_id']?$_SESSION['user_id']:0);    // 


        assign_dynamic('lucky_buy_user_list');
    }

    /* 显示模板 */
    $smarty->display('lucky_buy_user_list.dwt', $cache_id);
}


/*------------------------------------------------------ */
//-- 用户云购列表 --> 用户云购列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'asyncuserlist')
{
    /* 取得云购活动总数 */
    $count = user_lucky_buy_count();
	
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

     /*
     * 异步显示商品列表 by wang
     */
    if ($_GET['act'] == 'asyncuserlist') {
        $asyn_last = intval($_POST['last']) + 1;
        $size = $_POST['amount'];
        $page = ($asyn_last > 0) ? ceil($asyn_last / $size) : 1;
    }
    $goodslist = lucky_buy_user_list($size, $page);
    $sayList = array();
    if (is_array($goodslist)) {
        foreach ($goodslist as $vo) {
			
			//PRINCE 120029121
			if(strpos($vo['goods_thumb'],'ttp')>0){
				$img_url=$vo['goods_thumb'];
			}else{
				$img_url=$config['site_url'] . $vo['goods_thumb'];
			}
			
			if($vo['status']==1){
				if($vo['lucky_user_id']==$_SESSION['user_id'] &&  $vo['lucky_user_order_id']== $vo['order_id']){
				    $status="已中奖";
				}else{
				    $status="未中奖";
				}
			}else{
				$status="进行中";
			}
			$vo['url']='lucky_buy.php?act=schedule_view&lucky_buy_id=' . $vo['lucky_buy_id'];
			
            $sayList[] = array(
                'pro-inner' => '<div>
        <div class="proImg-wrap" > <a href="' . $vo['url'] . '" > <img src="../' . $img_url . '" alt="' . $vo['goods_name'] . '" > </a> 		
		</div>
        <table><tr><td><div class="ptInfo-wrap"> <a href="' . $vo['url'] . '" >
          <div class="proTitle" style="font-size:12px;" >' . $vo['act_name'] . '</div>
          <div class="lbPrice">
            <span >&nbsp;&nbsp;¥'.$vo['oneprice'].'/人次</span> 
          </div></a> 
		</div></td></tr></table>
		</div>',
                'pro-lb_inner' => '
		  <div class="lb_status" >第' . $vo['schedule_id'] . '期&nbsp;&nbsp;' . $status . '</div>	
		  <div class="lb_actions" ><a href="user.php?act=order_detail&order_id=' . $vo['order_id'] . '">查看订单</a></div>
		  <div class="lb_actions" ><a href="' . $vo['url'] . '">云购详情</a></div>	'
            );
        }
    }
   //  print_r( $goodslist  );
    echo json_encode($sayList);
    exit;
    /*
     * 异步显示商品列表 by wang end
     */

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('lucky_buy_user_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的云购活动 */
            $pt_user_list = lucky_buy_user_list($size, $page);
            $smarty->assign('pt_user_list',  $pt_user_list);
            // print_r( $pt_user_list );
            /* 设置分页链接 */
            $pager = get_pager('lucky_buy.php', array('act' => 'userlist'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

        assign_dynamic('lucky_buy_user_list');
    }

    /* 显示模板 */
    $smarty->display('lucky_buy_user_list.dwt', $cache_id);
}



/*------------------------------------------------------ */
//-- 云购列表 --> 云购往期列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'schedulelist')
{
    $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
	/* 取得会员云购活动总数 */
    $now = gmtime();
    $count = schedulelist_count($act_id );

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
    if (!$smarty->is_cached('lucky_buy_schedule_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的云购活动 */
            $lucky_buy_list = schedulelist_list($size, $page,$act_id );
            $smarty->assign('lucky_buy_schedule_list',  $lucky_buy_list);

            /* 设置分页链接 */
            $pager = get_pager('lucky_buy.php', array('act' => 'userlist'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('u', $_SESSION['user_id']?$_SESSION['user_id']:0);    // 

        $smarty->assign('act_id', $act_id );    // 

        assign_dynamic('lucky_buy_schedule_list');
    }

    /* 显示模板 */
    $smarty->display('lucky_buy_schedule_list.dwt', $cache_id);
}


/*------------------------------------------------------ */
//-- 云购列表 --> 云购往期列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'asyncschedulelist')
{   
    $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
	/* 取得云购活动总数 */
    $count = schedulelist_count($act_id );
     /*
     * 异步显示商品列表 by wang
     */
    if ($_GET['act'] == 'asyncschedulelist') {
        $asyn_last = intval($_POST['last']) + 1;
        $size = $_POST['amount'];
        $page = ($asyn_last > 0) ? ceil($asyn_last / $size) : 1;
    }
    $schedulelist = schedulelist_list($size, $page,$act_id );
    $sayList = array();
    if (is_array($schedulelist)) {
        foreach ($schedulelist as $vo) {
			

			$vo['lucky_user_head']=$vo['lucky_user_head']?$vo['lucky_user_head']:'images/weixin/lucky_buy_default.png';


			$vo['url']='lucky_buy.php?act=schedule_view&lucky_buy_id=' . $vo['lucky_buy_id'];
            $sayList[] = array(
                'pro-inner' => '<div>
        <div class="proImg-wrap" > <a href="' . $vo['url'] . '" > <img src="' . $vo['lucky_user_head']. '" alt="' . $vo['lucky_user_name'] . '" > </a> 		
		</div>
        <table><tr><td><div class="ptInfo-wrap"> <a href="' . $vo['url'] . '" >
		  <div class="schedulelist_title" >云购期号：<em>' . $vo['schedule_id'] . '</em></div>	
		  <div class="schedulelist_title" >揭晓时间：<em>' . $vo['end_time'] . '</em></div>	
		  <div class="schedulelist_title" >中奖会员：<em>' . $vo['lucky_user_name'] . '</em></div>	
		  <div class="schedulelist_title" >中奖号码：<em>' . $vo['lucky_code'] . '</em></div>	
		  <div class="schedulelist_actions" >云购详情</div>	
         </a> 
		</div></td></tr></table>
		</div>',
                'pro-lb_inner' => ''
            );
        }
    }
    echo json_encode($sayList);
    exit;
}



/*------------------------------------------------------ */
//-- 云购商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    /* 取得参数：云购活动id */
    $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
    $user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;
    $schedule_id = isset($_REQUEST['schedule_id']) ? intval($_REQUEST['schedule_id']) : 0;
	
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

    if ($act_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得云购活动信息 */
    $lucky_buy = lucky_buy_info($act_id);

    if (empty($lucky_buy))
    {
		ecs_header("Location: ./\n");
        exit;
    }
	

	
	//判断是否已经结束 是否进入下一期
	if($lucky_buy['status_no']==1){
		 goto_next_schedule($act_id);
	}

    /* 默认取得最新一期的信息：*/
	$sql = "select * from ". $GLOBALS['ecs']->table('lucky_buy') . " where  act_id='$act_id' order by schedule_id desc limit 1";
	$last_lucky_buy =$GLOBALS['db']->getRow($sql);
	$last_lucky_buy['schedule'] =($last_lucky_buy['total']-$last_lucky_buy['available'])*100/$last_lucky_buy['total'];
    $smarty->assign('last_lucky_buy', $last_lucky_buy);
	

    /* 缓存id：*/
    $cache_id = $_CFG['lang'] . '-' . $act_id . '-' . $lucky_buy['status_no'];
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('lucky_buy.dwt', $cache_id))
    {   
	

	
        //取货品信息
        if ($lucky_buy['product_id'] > 0)
        {
            $goods_specifications = get_specifications_list($lucky_buy['goods_id']);

            $good_products = get_good_products($lucky_buy['goods_id'], 'AND product_id = ' . $lucky_buy['product_id']);

            $_good_products = explode('|', $good_products[0]['goods_attr']);
            $products_info = '';
            foreach ($_good_products as $value)
            {
                $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
            }
            $smarty->assign('products_info',     $products_info);
            unset($goods_specifications, $good_products, $_good_products,  $products_info);
        }
		
        $goods_id = $lucky_buy['goods_id'];
        $goods = goods_info($goods_id);

        $lucky_buy['gmt_end_time'] = local_strtotime($lucky_buy['end_time']);
        $lucky_buy['share_url'] ='http://'.$_SERVER['HTTP_HOST']."/mobile/lucky_buy.php?act=view&act_id=".$act_id."&u=".$user_id;
        $lucky_buy['share_img'] =$lucky_buy['share_img']?$lucky_buy['share_img']:$goods['goods_thumb'];
		
        $smarty->assign('lucky_buy', $lucky_buy);
        $smarty->assign('pictures',            get_goods_gallery($lucky_buy['goods_id']));                    // 商品相册

		
		//取得当期信息
		$sql = "select * from ". $GLOBALS['ecs']->table('lucky_buy') . " where  act_id='$act_id' order by schedule_id desc limit 1";
		$schedule_info =$GLOBALS['db']->getRow($sql);
        $smarty->assign('schedule_info', $schedule_info);
        $smarty->assign('user_id', $user_id);
		
		//取出当前用户信息
		if($user_id>0){
			//先取参与次数
			$schedule_id=$schedule_info['schedule_id'];
			$sql = "select count(*) from ". $GLOBALS['ecs']->table('lucky_buy_detail') . " where  act_id='$act_id' and schedule_id='$schedule_id' and user_id='$user_id' ";
			$count_buy =$GLOBALS['db']->getOne($sql);
       		$smarty->assign('count_buy', $count_buy);
			//取出云购码
			$sql = "select * from ". $GLOBALS['ecs']->table('lucky_buy_detail') . " where  act_id='$act_id' and schedule_id='$schedule_id' and user_id='$user_id' ORDER BY code ASC ";
			$codes_buy =$GLOBALS['db']->getAll($sql);
       		$smarty->assign('codes_buy', $codes_buy);

		}

        /* 取得云购商品信息 */
        $goods_id = $lucky_buy['goods_id'];
        $goods = goods_info($goods_id);
        if (empty($goods))
        {
            ecs_header("Location: ./\n");
            exit;
        }
        $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
        $smarty->assign('lucky_buy_goods', $goods);
		

		$sql = "SELECT w.* FROM  " . $GLOBALS['ecs']->table('users') . " u ".
				"left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
				"WHERE  u.user_id=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
        $smarty->assign('weixininfo',    $weixininfo); 
        $smarty->assign('is_wechat_browser',    is_wechat_browser_for_lucky_buy()); 
		

		$web_url ='http://'.$_SERVER['HTTP_HOST'].'/';
        $smarty->assign('web_url',    $web_url); 
		
		$wap_url ='http://'.$_SERVER['HTTP_HOST'].'mobile/';
        $smarty->assign('wap_url',    $wap_url); 

		require_once "wxjs/jssdk.php";

		$ret = $db->getRow("SELECT  *  FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1");
		$jssdk = new JSSDK($appid=$ret['appid'], $ret['appsecret']);

		$signPackage = $jssdk->GetSignPackage();

		$smarty->assign('signPackage',  $signPackage);


        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $lucky_buy['act_name']?$lucky_buy['act_name']:$goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

        assign_dynamic('lucky_buy');
    }

    //更新商品点击次数
    $sql = 'UPDATE ' . $ecs->table('goods') . ' SET click_count = click_count + 1 '.
           "WHERE goods_id = '" . $lucky_buy['goods_id'] . "'";
    $db->query($sql);
	

    $smarty->assign('now_time',  gmtime());           // 当前系统时间
    $smarty->display('lucky_buy.dwt', $cache_id);
}


/*------------------------------------------------------ */
//-- 云购商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'schedule_view')
{
    /* 取得参数：云购活动id */
    $lucky_buy_id = isset($_REQUEST['lucky_buy_id']) ? intval($_REQUEST['lucky_buy_id']) : 0;
    $user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;

    if ($lucky_buy_id <= 0)
    {   
        ecs_header("Location: ./\n");
        exit;
    }


    /* 缓存id：*/
    $cache_id = $_CFG['lang'] . '-' . $lucky_buy_id . '-' . $lucky_buy['status_no'];
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('lucky_buy_schedule_view.dwt', $cache_id))
    {   
		
		//取得当期信息
		$lucky_buy = lucky_buy_by_lucky_buy_id($lucky_buy_id);
	
		if (empty($lucky_buy))
		{  
			ecs_header("Location: ./\n");
			exit;
		}
		
        $lucky_buy['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $lucky_buy['end_time']);
		$lucky_buy['lucky_user_head']=$lucky_buy['lucky_user_head']?$lucky_buy['lucky_user_head']:'images/weixin/lucky_buy_default.png';
	    $lucky_buy['schedule'] =($lucky_buy['total']-$lucky_buy['available'])*100/$lucky_buy['total'];

		$smarty->assign('lucky_buy', $lucky_buy);
		
		$sql = "select count(*) from ". $GLOBALS['ecs']->table('lucky_buy_detail') . " where lucky_buy_id='$lucky_buy_id' and user_id=".$lucky_buy['lucky_user_id'];
		$lucky_user_buy_amount =$GLOBALS['db']->getOne($sql);
       	$smarty->assign('lucky_user_buy_amount', $lucky_user_buy_amount);
		
		
		//取出当前用户信息
		if($user_id>0){
			//先取参与次数
			$sql = "select count(*) from ". $GLOBALS['ecs']->table('lucky_buy_detail') . " where lucky_buy_id='$lucky_buy_id' and user_id='$user_id' ";
			$my_count_buy =$GLOBALS['db']->getOne($sql);
       		$smarty->assign('my_count_buy', $my_count_buy);
			//取出云购码
			$sql = "select * from ". $GLOBALS['ecs']->table('lucky_buy_detail') . " where   lucky_buy_id='$lucky_buy_id' and user_id='$user_id' ORDER BY code ASC ";
			$my_codes_buy =$GLOBALS['db']->getAll($sql);
       		$smarty->assign('my_codes_buy', $my_codes_buy);

		}


        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $lucky_buy['act_name']?$lucky_buy['act_name']:$goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('user_id', $user_id);

        assign_dynamic('lucky_buy_schedule_view');
    }

    $smarty->display('lucky_buy_schedule_view.dwt', $cache_id);
}




/*------------------------------------------------------ */
//-- 云购 -->计算规则及详情 
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'calculate')
{
    /* 取得参数：云购活动id */
    $lucky_buy_id = isset($_REQUEST['lucky_buy_id']) ? intval($_REQUEST['lucky_buy_id']) : 0;
    $user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;


    /* 缓存id：*/
    $cache_id = $_CFG['lang'] . '-' . $lucky_buy_id . '-';
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('lucky_buy_calculate.dwt', $cache_id))
    {   
		
		$sql = "select * from ". $GLOBALS['ecs']->table('lucky_buy') . " where lucky_buy_id='$lucky_buy_id'  ";
		$get_lucky_buy_info =$GLOBALS['db']->getRow($sql);
		$get_lucky_buy_info['zhengshu']=intval($get_lucky_buy_info['sum_of_calculate_number']/$get_lucky_buy_info['total']);
		$get_lucky_buy_info['yushu']=$get_lucky_buy_info['sum_of_calculate_number']%$get_lucky_buy_info['total'];

        $smarty->assign('lucky_buy', $get_lucky_buy_info);    


        $smarty->assign('calculate_info', get_calculate_info($lucky_buy_id));    

        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('user_id', $user_id);

        assign_dynamic('lucky_buy_calculate');
    }

    $smarty->display('lucky_buy_calculate.dwt', $cache_id);
}




/*------------------------------------------------------ */
//-- 云购商品 --> 当期购买记录
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'lucky_buy_log')
{   
    $lucky_buy_id = isset($_REQUEST['lucky_buy_id']) ? intval($_REQUEST['lucky_buy_id']) : 0;
    /* 取得云购活动总数 */
    $count = count_lucky_buy_detail($lucky_buy_id);
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

     /*
     * 异步显示记录列表 
     */
    if ($_GET['act'] == 'lucky_buy_log') {
        $asyn_last = intval($_POST['last']) + 1;
        $size = $_POST['amount'];
        $page = ($asyn_last > 0) ? ceil($asyn_last / $size) : 1;
    }
    $lucky_buy_detail = lucky_buy_detail($size, $page,$lucky_buy_id);
    $sayList = array();
    if (is_array($lucky_buy_detail)) {
	
        foreach ($lucky_buy_detail as $vo) {
			$vo['used_time'] = local_date($GLOBALS['_CFG']['time_format'], $vo['used_time']);
			$vo['user_head']=$vo['user_head']?$vo['user_head']:'images/weixin/lucky_buy_default.png';
            $sayList[] = array(
                'pro-inner' => '<div>
        <div class="lb_proImg-wrap" > <img src="' . $vo['user_head'] . '" style=" width:40px; height:40px;"  alt="' . $vo['user_name'] . '" > 		
		</div>
        <table><tr><td><div class="lbInfo-wrap"> 
          <div class="lbTitle" style="font-size:15px;" >' . $vo['user_name'] ."&nbsp;&nbsp;购买<em>".$vo['total'].'</em>人次</div>
          <div class="lbTime">
            时间:&nbsp;' . $vo['used_time'].':'. $vo['used_time_millisecond'] .' 
          </div>
		</div></td></tr></table>
		</div>'
            );
        }
    }
    echo json_encode($sayList);
    exit;
}




/*------------------------------------------------------ */
//-- 云购商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view_goods')
{
    /* 取得参数：云购活动id */
    $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
    $user_id=$_SESSION['user_id'];

    if ($act_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得云购活动信息 */
    $lucky_buy = lucky_buy_info($act_id);

    if (empty($lucky_buy))
    {
		ecs_header("Location: ./\n");
        exit;
    }

    /* 缓存id：*/
    $cache_id = $_CFG['lang'] . '-' . $act_id . '-' . $lucky_buy['status_no'];
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('lucky_buy_goods.dwt', $cache_id))
    {   
        $smarty->assign('lucky_buy', $lucky_buy);

        /* 取得云购商品信息 */
        $goods_id = $lucky_buy['goods_id'];
        $goods = goods_info($goods_id);
        if (empty($goods))
        {
            ecs_header("Location: ./\n");
            exit;
        }
        $smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册
        $smarty->assign('lucky_buy_goods', $goods);
		

        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $lucky_buy['act_name']?$lucky_buy['act_name']:$goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('user_id',    $user_id);  // 当前位置

        assign_dynamic('lucky_buy_goods');
    }

    //更新商品点击次数
    $sql = 'UPDATE ' . $ecs->table('goods') . ' SET click_count = click_count + 1 '.
           "WHERE goods_id = '" . $lucky_buy['goods_id'] . "'";
    $db->query($sql);
	
    $smarty->display('lucky_buy_goods.dwt', $cache_id);
}



/*------------------------------------------------------ */
//-- 云购商品 --> 购买
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'buy')
{
    /* 查询：取得参数：云购活动id */
    $act_id = isset($_POST['act_id']) ? intval($_POST['act_id']) : 0;
    $number = isset($_POST['buy_number']) ? intval($_POST['buy_number']) : 1;
    $available = isset($_POST['available']) ? intval($_POST['available']) : 0;
	
	if($number >$available && $available!=0){
		$number=$available;
	}

    if ($act_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：取得云购活动信息 */
    $lucky_buy = lucky_buy_info($act_id);
    if (empty($lucky_buy))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 活动是否正在进行 */
    if ($lucky_buy['status_no'] != UNDER_WAY)
    {
        show_message('活动已结束或未开始', '', '', 'error');
    }


    /* 查询：是否登录 */
    $user_id = $_SESSION['user_id'];
    if ($user_id <= 0)
    {
		show_message($_LANG['au_buy_after_login'], "马上登陆", 'user.php', 'error');
    }



    /* 查询：取得商品信息 */
    $goods = goods_info($lucky_buy['goods_id']);

    /* 查询：处理规格属性 */
    $goods_attr = '';
    $goods_attr_id = '';
    if ($lucky_buy['product_id'] > 0)
    {
        $product_info = get_good_products($lucky_buy['goods_id'], 'AND product_id = ' . $lucky_buy['product_id']);

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
    else
    {
        $lucky_buy['product_id'] = 0;
    }

    /* 清空购物车中所有云购商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
    clear_cart(CART_LUCKY_BUY_GOODS);

    /* 加入购物车 */
    $cart = array(
        'user_id'        => $user_id,
        'session_id'     => SESS_ID,
        'goods_id'       => $lucky_buy['goods_id'],
        'goods_sn'       => addslashes($goods['goods_sn']),
        'goods_name'     => $lucky_buy['act_name']?$lucky_buy['act_name']:addslashes($goods['goods_name']),
        'market_price'   => $goods['market_price'],
        'goods_price'    => $lucky_buy['oneprice'],
        'goods_number'   => $number,
        'goods_attr'     => $goods_attr,
        'goods_attr_id'  => $goods_attr_id,
        'is_real'        => $goods['is_real'],
        'is_shipping'    =>$goods['is_shipping'],
        'extension_code' => addslashes($goods['extension_code']),
        'parent_id'      => 0,
        'rec_type'       => CART_LUCKY_BUY_GOODS,
        'is_gift'        => 0
    );
    $db->autoExecute($ecs->table('cart'), $cart, 'INSERT');
	$_SESSION['sel_cartgoods'] = $db->insert_id();

    /* 记录购物流程类型：云购 */
    $_SESSION['flow_type'] = CART_LUCKY_BUY_GOODS;
    $_SESSION['extension_code'] = 'lucky_buy';
    $_SESSION['extension_id'] = $act_id;

    /* 进入收货人页面 */
    ecs_header("Location: ./flow.php?step=checkout\n");
    exit;
}



?>