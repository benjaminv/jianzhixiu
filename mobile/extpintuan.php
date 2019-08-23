<?php

/**
 * 新版拼团文件
 * $Author: RINCE 120029121  $
 * $Id: extpintuan.php 17217 2016-04-20 09:29:08Z RINCE 120029121  $
 */



define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'weixin/weixin_oauth.php');  //授权登陆文件 如果贵站没有这项功能请屏蔽
require_once(ROOT_PATH . 'includes/prince/lib_extpintuan.php');
include_once(ROOT_PATH . 'wxm_extpintuan.php');  
update_extpintuan_info();
create_lucky_orders();
send_lucky_extpintuan_wxm();
/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}

/*------------------------------------------------------ */
//-- 拼团商品 --> 拼团活动商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{    
    $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
	
    /* 取得拼团活动总数 */
    $count = extpintuan_count();
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
    if (!$smarty->is_cached('extpintuan_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的拼团活动 */
            $pt_list = extpintuan_list($size, $page);
            $smarty->assign('pt_list',  $pt_list);
            // print_r( $pt_list );
            /* 设置分页链接 */
            $pager = get_pager('extpintuan.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }
        $smarty->assign('type', $type);

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置


        assign_dynamic('extpintuan_list');
    }

    /* 显示模板 */
    $smarty->display('extpintuan_list.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 拼团商品 --> 拼团活动商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'asynclist')
{

    /* 取得拼团活动总数 */

    $count = extpintuan_count();
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
    $goodslist = extpintuan_list($size, $page);
    $sayList = array();
    if (is_array($goodslist)) {
        foreach ($goodslist as $vo) {
			
			//PRINCE 120029121
			if(strpos($vo['original_img'],'ttp')>0){
				$img_url=$vo['original_img'];
			}else{
				$img_url=$config['site_url'] . $vo['original_img'];
			}
			

			
			if($vo['lucky_extpintuan']){
				$round_t1='限量';
				$round_t2=$vo['lucky_limit'] .'份';
			}else{
				if($vo['ladder_amount']<=1){
					$round_t1=$vo['discount'] . '折';
					$round_t2=$vo['lowest_amount'] .'人团';
				}else{
					$round_t1=$vo['discount'] . '折';
					$round_t2=$vo['lowest_amount'] .'人起';
				}
			}
			
			
           $vo['sold'] =$vo['virtual_sold']+selled_count($vo['goods_id']) ;
		   
		   
			$now = gmtime();
			if($vo['ext_act_type']==2  && $vo['end_time']<$now ){
				 $vo['url'] = 'extpintuan.php?act=view&act_id='.$vo['act_id'].'&level='.$vo['lowest_amount'].'&u='.$_SESSION['user_id'];
			    
                if($vo['min_price']==$vo['max_price']){
					$price=price_format($vo['min_price']);
				}else{
					$price='¥'.round($vo['min_price'],2).'~¥'.round($vo['max_price'],2).'元';
				}
				$sayList[] = array(
					'pro-inner' => '
						<div class="proImg-wrap"> <a href="' . $vo['url'] . '" > <img src="../' . $img_url . '" alt="' . $vo['goods_name'] . '"> </a> 
				
												<span class="tuan_mark tuan_mark2">
													<em>已开奖</em>
												</span>
						
						</div>
						<div class="proInfo-wrap"> <a href="' . $vo['url'] . '" >
						  <div class="extproTitle">' . $vo['act_name'] . '</div>
						  <div class="ptPrice">
							 <div class="ptPrice1" style="font-size:12px;">
							 <em style="margin-left:50px;">已开奖</em>
							 <em style="float:right;font-size:12px;" >查看结果></em>
							 </div> 
						  </div>
						  <br /><div  class="mkPrice" >
							<em>市场价：</em> 
							<del >' .price_format($vo['market_price']) . '</del> 
							<em>销量：'.$vo['sold'].'</em> 
						  </div>
						  </div>
						  </a> 
						</div>'
				);
				
			}elseif($vo['ladder_amount']<=1 && !$vo['single_buy']){
				 $vo['url'] = 'extpintuan.php?act=view&act_id='.$vo['act_id'].'&level='.$vo['lowest_amount'].'&u='.$_SESSION['user_id'];
			    
                if($vo['min_price']==$vo['max_price']){
					$price=price_format($vo['min_price']);
				}else{
					$price='¥'.round($vo['min_price'],2).'~¥'.round($vo['max_price'],2).'元';
				}
				$sayList[] = array(
					'pro-inner' => '
			<div class="proImg-wrap"> <a href="' . $vo['url'] . '" > <img src="../' . $img_url . '" alt="' . $vo['goods_name'] . '"> </a> 
	
									<span class="tuan_mark tuan_mark2">
										<b>' . $round_t1 . '</b>
										<span>' . $round_t2. '</span>
									</span>
			
			</div>
			<div class="proInfo-wrap"> <a href="' . $vo['url'] . '" >
			  <div class="extproTitle">' . $vo['act_name'] . '</div>
			  <div class="ptPrice">
				 <div class="ptPrice1" style="font-size:12px;">
				 <em >' . $vo['lowest_amount'] ."人团&nbsp;&nbsp;".$price.'</em>
				 <em style="float:right;font-size:12px;" >去开团></em>
				 </div> 
			  </div>
			  <br /><div  class="mkPrice" >
				<em>市场价：</em> 
				<del >' .price_format($vo['market_price']) . '</del> 
				<em>销量：'.$vo['sold'].'</em> 
			  </div>
			  <br /><div  class="mkPrice" ><div class="extgoods_brief">' . $vo['goods_brief'] . '</div></div>
			  </a> 
			</div>'
				);
			}else{
				if($vo['single_buy']){
					$vo['ladder_amount']=$vo['ladder_amount']+1;
				}

				$htmldiv='';
				$i=0;
				$price_ladder = $vo['price_ladder'];
				foreach ($price_ladder as $key => $item){
					   $vo['url'] = 'extpintuan.php?act=view&act_id='.$vo['act_id'].'&level='.$item['amount'].'&u='.$_SESSION['user_id'];
					   if($item['minprice']==$item['maxprice']){
							$price=price_format($item['minprice']);
						}else{
							$price='¥'.round($item['minprice'],2).'~¥'.round($item['maxprice'],2).'元';
						}
						
						if($i==0 && ($vo['ladder_amount']%2!=0)){
							$thisclass="newkt_item  newkt_item_rank2";
						}else{
							$thisclass="newkt_item  newkt_item_rank";
						}
					
					
						$htmldiv .='<div class="newkt" >
								 <a class="'.$thisclass.'"  href="' . $vo['url'] . '" >
								 <div class="newkt_price" ><b >'.$item['amount'].'人团</b></div>
								 <div class="newkt_btn" ><b >¥'.$price.'元</b></div>
								 </a>
							  </div>';
					$i=$i+1;
				}
				
				if($vo['single_buy']){
					$htmldiv .=' <div class="newkt">
								<form action="extpintuan.php?act=buy" method="post" name="form'.$vo['act_id'].'">
								  <input type="hidden" name="act_id" value="'.$vo['act_id'].'" />
								  <input type="hidden" name="number" value="1" />
								</form>
								 <a class="newkt_item_sg  newkt_item_rank_sg" onclick="form'.$vo['act_id'].'.submit()" >
								 <div class="newkt_price" ><b >¥'.price_format($vo['single_buy_price']).'元</b></div>
								 <div class="newkt_btn" ><b >单独购买</b></div>
								 </a>
							  </div>';
				}
				
				$sayList[] = array(
					'pro-inner' => '
			<div class="proImg-wrap"> <a href="#123456" onclick="alert(\'请点击下面带价格的框框进行购买\');" > <img src="../' . $img_url . '" alt="' . $vo['goods_name'] . '"> </a> 
	
									<span class="tuan_mark tuan_mark2">
										<b>' . $round_t1 . '</b>
										<span>' . $round_t2. '</span>
									</span>
			
			</div>
			<div class="proInfo-wrap"> <a href="#123456" onclick="alert(\'请点击下面带价格的框框进行购买\');" >
			  <div class="extproTitle">' . $vo['act_name'] . '</div>
			  ' .$htmldiv .'
			  <br /><div  class="mkPrice" >
				<em>市场价：</em> 
				<del >' .price_format($vo['market_price']) . '</del> 
				<em>销量：'.$vo['sold'].'</em> 
			  </div>
			  <br /><div  class="mkPrice" ><div class="extgoods_brief">' . $vo['goods_brief'] . '</div></div>
			  </a> 
			</div>'
				);
			}
			
        }
    }

    echo json_encode($sayList);
    exit;
    /*
     * 异步显示商品列表 by wang end
     */

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('extpintuan_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的拼团活动 */
            $pt_list = extpintuan_list($size, $page);
            $smarty->assign('pt_list',  $pt_list);

            /* 设置分页链接 */
            $pager = get_pager('extpintuan.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typeextpintuan.xml" : 'feed.php?type=extpintuan'); // RSS URL

        assign_dynamic('extpintuan_list');
    }

    /* 显示模板 */
    $smarty->display('extpintuan_list.dwt', $cache_id);
}



/*------------------------------------------------------ */
//-- 用户拼团列表 --> 用户拼团列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'userlist')
{    
    
    /* 取得拼团活动总数 */
    $count = user_extpintuan_count();
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
    if (!$smarty->is_cached('extpintuan_user_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的拼团活动 */
            $pt_user_list = extpintuan_user_list($size, $page);
            $smarty->assign('pt_user_list',  $pt_user_list);

            /* 设置分页链接 */
            $pager = get_pager('extpintuan.php', array('act' => 'userlist'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置


        assign_dynamic('extpintuan_user_list');
    }

    /* 显示模板 */
    $smarty->display('extpintuan_user_list.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 用户拼团列表 --> 用户拼团列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'asyncuserlist')
{
    /* 取得拼团活动总数 */
    $count = user_extpintuan_count();
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
    $goodslist = extpintuan_user_list($size, $page);
    $sayList = array();
    if (is_array($goodslist)) {
        foreach ($goodslist as $vo) {
			
			
			/* 取得拼团活动信息 */
			$extpintuan = extpintuan_info($vo['act_id']);
			
			//PRINCE 120029121
			if(strpos($vo['goods_thumb'],'ttp')>0){
				$img_url=$vo['goods_thumb'];
			}else{
				$img_url=$config['site_url'] . $vo['goods_thumb'];
			}
			
			if($vo['status']==1 || $vo['status']==3 || $vo['status']==4){
				$status="拼团成功";
			}elseif($vo['status']==2){
				$status="拼团失败";
			}else{
				$status="拼团进行中";
			}
			
			if($vo['lucky_extpintuan']){
				if($vo['status']==3){
					$status.='(待抽奖)';
				}elseif($vo['status']==4){
					if($vo['lucky_order']){
						$status.='(已中奖)';
					}else{
						$status.='(未中奖)';
					}
				}else{
					$status.='(限量抽奖团)';
				}
			}
			
			foreach ($vo['price_ladder'] as $item){   
				if ($vo['this_need_people'] == $item['amount']){
					if($item['minprice']==$item['maxprice']){
						$price = price_format($item['maxprice']);
					}else{
						$price = price_format($item['minprice']).'起';
					}
				}
			}
			
			$vo['url']='extpintuan.php?act=pt_view&pt_id=' . $vo['pt_id'].'&level=' . $vo['this_need_people'];
            $sayList[] = array(
                'pro-inner' => '<div>
        <div class="proImg-wrap" style="width:100%;background-color:#fff;"> <a href="' . $vo['url'] . '" > <img style="height:100px; width:auto;" src="../' . $img_url . '" alt="' . $vo['goods_name'] . '" style="margin-left:5px;"   > </a> 		
		</div>
        <table style="position:absolute; right:0px;top:10px;"><tr><td><div class="ptInfo-wrap"> <a href="' . $vo['url'] . '" >
          <div class="proTitle" style="font-size:12px;line-height:16px; text-align:right; padding-right:20px;padding-left:30%;height:auto; " >' . $vo['act_name'] . '</div>
          <div class="ptPrice">
            <em >' . $vo['this_need_people'] ."人团&nbsp;&nbsp;".$price.'</em> 
          </div></a> 
		</div></td></tr></table>
		</div>',
                'pro-pt_inner' => '
		  <div class="pt_status" >' . $status . '</div>	
		  <div class="pt_actions" ><a href="user.php?act=order_detail&order_id=' . $vo['order_id'] . '">查看订单</a></div>
		  <div class="pt_actions" ><a href="' . $vo['url'] . '">拼团详情</a></div>	'
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
    if (!$smarty->is_cached('extpintuan_user_list.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的拼团活动 */
            $pt_user_list = extpintuan_user_list($size, $page);
            $smarty->assign('pt_user_list',  $pt_user_list);
            // print_r( $pt_user_list );
            /* 设置分页链接 */
            $pager = get_pager('extpintuan.php', array('act' => 'userlist'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typeextpintuan.xml" : 'feed.php?type=extpintuan'); // RSS URL

        assign_dynamic('extpintuan_user_list');
    }

    /* 显示模板 */
    $smarty->display('extpintuan_user_list.dwt', $cache_id);
}







/*------------------------------------------------------ */
//-- 幸运订单列表 --> 幸运订单列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'luckylist')
{    

	$act_id=$_REQUEST['act_id']?$_REQUEST['act_id']:0;	
    $smarty->assign('act_id', $act_id);
    $extpintuan = extpintuan_info($act_id);
    $goods = goods_info($extpintuan['goods_id']);
	$extpintuan['goods_thumb']=$goods['goods_thumb'];
    $smarty->assign('extpintuan', $extpintuan);
    $smarty->display('extpintuan_lucky_list.dwt',  0);
}

/*------------------------------------------------------ */
//-- 幸运订单列表 --> 幸运订单列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'asyncluckylist')
{
    /* 取得拼团活动总数 */
	$act_id=$_REQUEST['act_id']?$_REQUEST['act_id']:0;
    $count = extpintuan_lucky_list_count($act_id);

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
    if ($_GET['act'] == 'asyncluckylist') {
        $asyn_last = intval($_POST['last']) + 1;
        $size = $_POST['amount'];
        $page = ($asyn_last > 0) ? ceil($asyn_last / $size) : 1;
    }
    $luckylist = extpintuan_lucky_list($act_id,$size, $page);
    $sayList = array();
	
/*
     <div class="hdjs">参团记录<span style="font-size:12px;">（自 {$extpintuan.create_time} 开团）</span></div>
    <!--{/if }-->
    <!--{ if $item.pt_user_seq gt 2} -->
  <div class="otherboderhr">
	<div class="dleft">
		  <!--{if $item.user_head}-->
         <img src="{$item.user_head}">
         <!--{else}-->
         <img src="images/extpt_icon_index.png">
        <!--{/if }-->
	</div>
	<div class="dright">团友：<b>{$item.follow_user_nickname}</b><br/>
	<span>参团时间：{$item.formated_follow_time}</span>
	</div>
  </div>*/
  
	
    if (is_array($luckylist)) {
        foreach ($luckylist as $vo) {
        	$vo['formated_follow_time']   = local_date($GLOBALS['_CFG']['time_format'], $vo['follow_time']);
            $sayList[] = array(
                'pro-inner' => '<div>
        <div class="lucklist">	
			<div class="dleft">
		         <img src="themesmobile/dm299_com/images/extpt_icon_index.png">
		     </div>
		    <div class="dright">团友：<b>' . $vo['follow_user_nickname'] .'</b><br/>
		    <span>参团时间：' . $vo['formated_follow_time'] .'</span>
		    </div>
        </div>
		</div>'
            );
        }
    }
    echo json_encode($sayList);
}


/*------------------------------------------------------ */
//-- 拼团商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{   

    //include_once(ROOT_PATH . 'weixin/weixin_oauth.php');  //授权登陆文件 如果贵站没有这项功能请屏蔽
    /* 取得参数：拼团活动id */
	
	$_SESSION['back_act'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];


    $extpintuan_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
	$user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;
    $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
    $level = isset($_REQUEST['level']) ? intval($_REQUEST['level']) : 0;
    $pt_id = isset($_REQUEST['pt_id']) ? intval($_REQUEST['pt_id']) : 0;

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
	
    if ($extpintuan_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得拼团活动信息 */
    $extpintuan = extpintuan_info($extpintuan_id);

    if (empty($extpintuan))
    {
        ecs_header("Location: ./\n");
        exit;
    }


    /* 缓存id：语言，拼团活动id，状态，（如果是进行中）当前数量和是否登录 */
    $cache_id = $_CFG['lang'] . '-' . $extpintuan_id . '-' . $extpintuan['status']. '-' . $level;
    if ($extpintuan['status'] == GBS_UNDER_WAY)
    {
        $cache_id = $cache_id . '-' . $extpintuan['valid_goods'] . '-' . $level.'-' . intval($_SESSION['user_id'] > 0);
    }
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('extpintuan_goods.dwt', $cache_id))
    {
        $extpintuan['gmt_end_date'] = $extpintuan['end_date'];
        $extpintuan['show_single_buy_price'] = price_format($extpintuan['single_buy_price']);
        $extpintuan['show_market_price'] = price_format($extpintuan['market_price']);
        $extpintuan['pt_id'] = $pt_id;

		$j=0;
		foreach ($extpintuan['org_price_ladder'] as $item){  
		    if($j==0){//默认先取最初一个阶梯
      			$extpintuan['level'] = $item['amount'];
				$level =$level ?$level:$item['amount'];
        		$extpintuan['share_people'] = $item['amount']-1;
				$extpintuan['min_price'] = $item['minprice'];
				$extpintuan['max_price'] = $item['maxprice'];
				$extpintuan['show_min_price'] = price_format($item['minprice']);
				$extpintuan['show_max_price'] = price_format($item['maxprice']);
				$extpintuan['orderlimit'] = $item['orderlimit'];
				$extpintuan['tuanzhangdis'] = $item['tuanzhangdis']<10?'团长'.$item['tuanzhangdis'].'折':'';
				if($item['minprice']==$item['maxprice']){
				    $extpintuan['show_price'] = price_format($item['maxprice']);
				}
			}
			$j=$j+1;
			if ($level == $item['amount']){//根据参数取对应数据
      			$extpintuan['level'] = $item['amount'];
				$level =$level ?$level:$item['amount'];
        		$extpintuan['share_people'] = $item['amount']-1;
				$extpintuan['min_price'] = $item['minprice'];
				$extpintuan['max_price'] = $item['maxprice'];
				$extpintuan['show_min_price'] = price_format($item['minprice']);
				$extpintuan['show_max_price'] = price_format($item['maxprice']);
				$extpintuan['show_price'] = FALSE;
				$extpintuan['orderlimit'] = $item['orderlimit'];
				$extpintuan['tuanzhangdis'] = $item['tuanzhangdis']<10?'团长'.$item['tuanzhangdis'].'折':'';
				if($item['minprice']==$item['maxprice']){
				    $extpintuan['show_price'] = price_format($item['maxprice']);
				}
			}
		}


        /* 取得拼团商品信息 */
        $goods_id = $extpintuan['goods_id'];
        $goods = goods_info($goods_id);
        if (empty($goods))
        {
            ecs_header("Location: ./\n");
            exit;
        }
        $extpintuan['virtual_sold'] =$extpintuan['virtual_sold']+selled_count($goods_id) ;
        $extpintuan['share_url'] ='http://'.$_SERVER['HTTP_HOST']."/mobile/extpintuan.php?act=view&act_id=".$extpintuan_id."&u=".$user_id;
        $extpintuan['share_img'] =$extpintuan['share_img']?$extpintuan['share_img']:$goods['goods_thumb'];

        $smarty->assign('extpintuan', $extpintuan);
        $smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册

		$sql = "SELECT w.* FROM  " . $GLOBALS['ecs']->table('users') . " u ".
				"left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
				"WHERE  u.user_id=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
        $smarty->assign('weixininfo',    $weixininfo); 
        $smarty->assign('is_wechat_browser',    is_wechat_browser_for_extpintuan()); 
		
       // $extpintuan['need_follow'].'-'.$weixininfo['isfollow'].'-'.is_wechat_browser_for_extpintuan(); 

		$web_url ='http://'.$_SERVER['HTTP_HOST'].'/';
        $smarty->assign('web_url',    $web_url); 
		
		$wap_url ='http://'.$_SERVER['HTTP_HOST'].'mobile/';
        $smarty->assign('wap_url',    $wap_url); 
		
		$rand_price = $GLOBALS['db']->getOne("SELECT price FROM ecs_extpintuan_price WHERE user_id = '$user_id' and act_id='$act_id' and level='$level' and status=0 LIMIT 1 ");
        $smarty->assign('rand_price',    $rand_price);  // 取出价格
        $smarty->assign('show_rand_price',    price_format($rand_price));  // 取出价格
		
		 //下单限制
		$sql = "select count(*) from ". $GLOBALS['ecs']->table('extpintuan') . " where  user_id= '$user_id' and act_id='$act_id' and need_people='$level' and create_succeed =1 ";
		$total =$GLOBALS['db']->getOne($sql);
		if($total>=$extpintuan['orderlimit'] && $extpintuan['orderlimit']>0){
        	$smarty->assign('had_bought',    $total);  // 判断是否已购买
		}
		
		 //是否有待付款订单
		$sql = "select eo.* from ". $GLOBALS['ecs']->table('extpintuan_orders') . " AS eo  " .
        " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS o ON eo.order_id    = o.order_id    " .
		" WHERE  eo.follow_user='$user_id' and eo.act_id='$act_id' and o.pt_level='$level' and  o.pay_status=0 and o.order_status <=1 limit 1 ";
		$waiting_pay_order =$GLOBALS['db']->getRow($sql);
		if(!empty($waiting_pay_order)){
			$pay_url="user.php?act=order_detail&order_id=".$waiting_pay_order['order_id'];
			$smarty->assign('pay_url',    $pay_url);  // 判断是否已购买
		}


		require_once "wxjs/jssdk.php";

		$ret = $db->getRow("SELECT  *  FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1");
		$jssdk = new JSSDK($appid=$ret['appid'], $ret['appsecret']);

		$signPackage = $jssdk->GetSignPackage();

		$smarty->assign('signPackage',  $signPackage);
		
        $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
        $smarty->assign('pt_goods', $goods);
        $smarty->assign('user_id', $_SESSION['user_id']?$_SESSION['user_id']:0);
		
		$new_extpintuan=get_new_extpintuan($extpintuan_id,$level);
        $smarty->assign('new_extpintuan', $new_extpintuan);


        /* 取得商品的规格 */
        $properties = get_goods_properties($goods_id);
        $smarty->assign('specification', $properties['spe']); // 商品规格
		
        /* 提示 */	
		if($_SESSION['pt_tips'] ){
            $smarty->assign('tips', $_SESSION['pt_tips']);  
			 unset($_SESSION['pt_tips']);
		}

        //模板赋值
       // print_r( $_CFG['show_goodssn'] );
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置


        assign_dynamic('extpintuan_goods');
    }

    //更新商品点击次数
    $sql = 'UPDATE ' . $ecs->table('goods') . ' SET click_count = click_count + 1 '.
           "WHERE goods_id = '" . $extpintuan['goods_id'] . "'";
    $db->query($sql);

    $smarty->assign('now_time',  gmtime());           // 当前系统时间
    $smarty->display('extpintuan_goods.dwt', $cache_id);
}


elseif ($_REQUEST['act'] == 'pt_view')
{
    //include_once(ROOT_PATH . 'weixin/weixin_oauth.php');  //授权登陆文件 如果贵站没有这项功能请屏蔽
	/* 取得参数：拼团活动id */
	$_SESSION['back_act'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $extpintuan_id = isset($_REQUEST['pt_id']) ? intval($_REQUEST['pt_id']) : 0;
    $level = isset($_REQUEST['level']) ? intval($_REQUEST['level']) : 0;

	$user_id=$_SESSION['user_id']?$_SESSION['user_id']:0;
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
	
    if ($extpintuan_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得拼团活动信息 */
    $extpintuan = extpintuan_detail_info($extpintuan_id);

    if (empty($extpintuan))
    {
        ecs_header("Location: ./\n");
        exit;
    }


    /* 缓存id：语言，拼团活动id，状态，（如果是进行中）当前数量和是否登录 */
    $cache_id = $_CFG['lang'] . '-' . $extpintuan_id . '-' . $extpintuan['status'];
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('extpintuan_view.dwt', $cache_id))
    {
        $extpintuan['end_time'] = $extpintuan['end_time'];  //同步系统时间
        $extpintuan['share_url'] ='http://'.$_SERVER['HTTP_HOST']."/mobile/extpintuan.php?act=pt_view&pt_id=".$extpintuan_id."&u=".$user_id;
        $extpintuan['share_img'] =$extpintuan['share_img']?$extpintuan['share_img']:$extpintuan['goods_thumb'];
        $extpintuan['show_min_price'] = price_format($extpintuan['min_price']);
        $extpintuan['show_max_price'] = price_format($extpintuan['max_price']);
        $level =$extpintuan['this_need_people'] ;
        $extpintuan['level'] =$extpintuan['this_need_people'] ;
		
		foreach ($extpintuan['price_ladder'] as $item){  
			if ($level == $item['amount']){// 如果存在该阶梯则取阶梯数据
				    $extpintuan['show_price'] = $item['price']?$item['price']:$item['minprice'];
			}
		}
		$extpintuan['show_price'] = $extpintuan['show_price']?$extpintuan['show_price']:$extpintuan['price'];
		$extpintuan['show_price'] = price_format($extpintuan['show_price']);

		
		
		
		
		
        $smarty->assign('extpintuan', $extpintuan);
		

		
       $smarty->assign('user_id', $_SESSION['user_id']?$_SESSION['user_id']:0);
		
       $sql = "SELECT pto.*,o.order_status,o.shipping_status,o.pay_status " .
            "FROM  " . $GLOBALS['ecs']->table('extpintuan_orders') . " AS pto  " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS o ON pto.order_id    = o.order_id    " .
            "WHERE pto.pt_id=".$extpintuan_id." and pto.follow_user=".$user_id." ";
       $my_extpintuan = $GLOBALS['db']->getRow($sql);
	   $smarty->assign('my_extpintuan', $my_extpintuan);    

        /*中间按钮*/
	    if($extpintuan['create_succeed'] == 0 &&  $extpintuan['status'] == 0){
			 $center_action="正在开团";
	    }else{
			 $center_action="立即分享";
			 $center_click=1;
		}
		$smarty->assign('center_action', $center_action);    // 中间按钮
		$smarty->assign('center_click', $center_click);    // 中间按钮
		
		
        /*右边按钮*/ 
	    if($extpintuan['status'] >0){
			 $right_action='更多拼团';
			 $right_url="extpintuan.php";
		}else{ 
			 if(empty($my_extpintuan)){
				 $right_action="立即参团";
				 $right_click=1;
			 }elseif($my_extpintuan['pay_status']==0 && $my_extpintuan['order_status']<=1){
				 $right_action="立即付款";
				 $right_url="user.php?act=order_detail&order_id=".$my_extpintuan['order_id'];
			 }elseif($my_extpintuan['order_id']){
				 $right_action="查看订单";
				 $right_url="user.php?act=order_detail&order_id=".$my_extpintuan['order_id'];
			 }else{
				 $right_action="更多拼团";
				 $right_url="extpintuan.php";
			 }
		}
        $smarty->assign('right_action', $right_action);  // 右边按钮
        $smarty->assign('right_url', $right_url);   // 右边按钮
		$smarty->assign('right_click', $right_click);    // 中间按钮


		$sql = "SELECT w.* FROM  " . $GLOBALS['ecs']->table('users') . " u ".
				"left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
				"WHERE  u.user_id=".$_SESSION['user_id'];
		$weixininfo =$GLOBALS['db']->getRow($sql);
        $smarty->assign('weixininfo',    $weixininfo); 
        $smarty->assign('is_wechat_browser',    is_wechat_browser_for_extpintuan()); 

		$web_url ='http://'.$_SERVER['HTTP_HOST'].'/';
        $smarty->assign('web_url',    $web_url); 
		
		$wap_url ='http://'.$_SERVER['HTTP_HOST'].'mobile/';
        $smarty->assign('wap_url',    $wap_url); 


        $act_id = $extpintuan['act_id'];
        $level = $extpintuan['this_need_people'];
		$rand_price = $GLOBALS['db']->getOne("SELECT price FROM ecs_extpintuan_price WHERE user_id = '$user_id' and act_id='$act_id' and level='$level'  and status=0 LIMIT 1 ");
        $smarty->assign('rand_price',    $rand_price);  // 取出价格
        $smarty->assign('show_rand_price',    price_format($rand_price));  // 取出价格
		
		 //下单限制
		$sql = "select count(*) from ". $GLOBALS['ecs']->table('extpintuan_orders') . " AS eo  " .
        " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS o ON eo.order_id    = o.order_id    " .
		" WHERE  eo.follow_user='$user_id' and eo.pt_id='$extpintuan_id' and o.order_status !=2";
		$total =$GLOBALS['db']->getOne($sql);
        $smarty->assign('had_bought',    $total);  // 判断是否已购买
		
	
		require_once "wxjs/jssdk.php";

		$ret = $db->getRow("SELECT  *  FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1");
		$jssdk = new JSSDK($appid=$ret['appid'], $ret['appsecret']);

		$signPackage = $jssdk->GetSignPackage();

		$smarty->assign('signPackage',  $signPackage);
		
        $sql = "SELECT pto.*,o.order_status,o.shipping_status,o.pay_status " .
            "FROM  " . $GLOBALS['ecs']->table('extpintuan_orders') . " AS pto  " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS o ON pto.order_id    = o.order_id    " .
            "WHERE pto.pt_id=".$extpintuan_id." and (pay_status=2 or o.user_id=pto.act_user) ORDER BY pto.follow_time ASC";
	    $extpintuan_orders = $GLOBALS['db']->getAll($sql);
		
		$pt_order_list = array();
		$i=1;
		foreach ($extpintuan_orders AS $key => $val){
	
			$val['pt_user_seq']=$i;
			$i=$i+1;
			$val['formated_follow_time']=local_date($GLOBALS['_CFG']['time_format'], $val['follow_time']);
           
			$pt_order_list[] = $val;
	
		}
        $smarty->assign('extpintuan_orders', $pt_order_list);

		if($extpintuan['available_people']>0){
			$virtual_touxiang=array();
			for($i=1;$i<=$extpintuan['available_people'];$i++){
			   $virtual_touxiang[] = $i;
			}
			$smarty->assign('virtual_touxiang', $virtual_touxiang);
		}

		

        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $extpintuan['act_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置


        assign_dynamic('extpintuan_view');
    }

    $smarty->assign('now_time',  gmtime());           // 当前系统时间
    $smarty->display('extpintuan_view.dwt', $cache_id);
}

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'setprice')
{
    include('includes/cls_json.php');

    $json   = new JSON;
    $res    = array('err_msg' => '', 'result' => '', 'qty' => 1);

    $act_id    = (isset($_REQUEST['act_id'])) ? intval($_REQUEST['act_id']) : 0;
    $price     = (isset($_REQUEST['price'])) ? floatval($_REQUEST['price']) : 0;
    $level    = (isset($_REQUEST['level'])) ? intval($_REQUEST['level']) : 0;

    $user_id   =$_SESSION['user_id']?$_SESSION['user_id']:0;
    if ($act_id == 0 || $user_id ==0){
        $res['err_msg'] = '非法请求';
        $res['err_no']  = 1;
    }
    else{
		$rand_price = $GLOBALS['db']->getOne("SELECT price FROM ecs_extpintuan_price WHERE user_id = '$user_id' and act_id='$act_id' and level='$level' and status=0 LIMIT 1 ");
		if(empty($rand_price)){
		$price = array(
			'user_id'        => $_SESSION['user_id'],
			'act_id'         => $act_id,
			'price'  		 => $price,
			'level'  		 => $level,
			'create_time'    => gmtime() );
		$db->autoExecute($ecs->table('extpintuan_price'), $price, 'INSERT');
		}
    }

    die($json->encode($res));
}
/*------------------------------------------------------ */
//-- 拼团商品 --> 购买
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'buy')
{    	

    /* 查询：判断是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        show_message('您还未登陆，不能参团', "马上登陆", 'user.php', 'error');
    }
    $user_id = $_SESSION['user_id']?$_SESSION['user_id']:0;

    /* 查询：取得参数：拼团活动id */
    $extpintuan_id = isset($_POST['act_id']) ? intval($_POST['act_id']) : 0;
    $extpintuan_level = isset($_POST['level']) ? intval($_POST['level']) : 0;
    $pt_id = isset($_POST['pt_id']) ? intval($_POST['pt_id']) : 0;
    $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
	

    if ($extpintuan_id <= 0 && $extpintuan_level <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }
	
	
    /* 查询：取得拼团活动信息 */
    $extpintuan = extpintuan_info($extpintuan_id, $extpintuan_level);
    if (empty($extpintuan))
    {
        ecs_header("Location: ./\n");
        exit;
    }
	
     //拼团个数限制
	 /*if($pt_id ==0  &&  $extpintuan_level!=0){
			$sql = "select count(*) from ". $GLOBALS['ecs']->table('extpintuan') . " where status=0 and act_id=".$extpintuan_id. " and user_id =".$_SESSION['user_id'];
			$total =$GLOBALS['db']->getOne($sql);
			if ($total>=$extpintuan['open_limit']  && $extpintuan['open_limit']!=0){    
				$_SESSION['pt_tips']="抱歉！您已经有 ".$total." 个进行中的拼团。 暂时不能继续发起拼团，快快点击左下方\"我的拼团\"把您的拼团分享给好友吧。";
				ecs_header("Location: extpintuan.php?act=view&act_id=$extpintuan_id\n");
			}
	 }*/
	 
    /* 查询：检查拼团活动是否是进行中 */
	$chk_create_succeed = $GLOBALS['db']->getOne("SELECT create_succeed FROM ecs_extpintuan WHERE pt_id = '$pt_id' ");
    if ($pt_id>0 && !$chk_create_succeed)
    {
        show_message('对不起，团长未付款您暂时不能参团。', '', '', 'error');
    }

    /* 查询：检查拼团活动是否是进行中 */
    if ($extpintuan['status'] != GBS_UNDER_WAY)
    {
        show_message($_LANG['gb_error_status'], '', '', 'error');
    }

    /* 查询：取得拼团商品信息 */
    $goods = goods_info($extpintuan['goods_id']);
    if (empty($goods))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：判断数量是否足够 */
    if (($extpintuan['restrict_amount'] > 0 && $number > ($extpintuan['restrict_amount'] - $extpintuan['valid_goods'])) || $number > $goods['goods_number'])
    {
        show_message($_LANG['gb_error_goods_lacking'], '', '', 'error');
    }

    /* 查询：取得规格 */
    $specs = '';
    foreach ($_POST as $key => $value)
    {
        if (strpos($key, 'spec_') !== false)
        {
            $specs .= ',' . intval($value);
        }
    }
    $specs = trim($specs, ',');

    /* 查询：如果商品有规格则取规格商品信息 配件除外 */
    if ($specs)
    {
        $_specs = explode(',', $specs);
        $product_info = get_products_info($goods['goods_id'], $_specs);
    }

    empty($product_info) ? $product_info = array('product_number' => 0, 'product_id' => 0) : '';

    /* 查询：判断指定规格的货品数量是否足够 */
    if ($specs && $number > $product_info['product_number'])
    {
        show_message($_LANG['gb_error_goods_lacking'], '', '', 'error');
    }

    /* 查询：查询规格名称和值，不考虑价格 */
    $attr_list = array();
    $sql = "SELECT a.attr_name, g.attr_value " .
            "FROM " . $ecs->table('goods_attr') . " AS g, " .
                $ecs->table('attribute') . " AS a " .
            "WHERE g.attr_id = a.attr_id " .
            "AND g.goods_attr_id " . db_create_in($specs);
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
    }
    $goods_attr = join(chr(13) . chr(10), $attr_list);


	
	//获取该拼团信息 
    $sql = "SELECT * FROM  " . $GLOBALS['ecs']->table('extpintuan').
            "  WHERE  pt_id=".$pt_id;
    $get_ptinfo =$GLOBALS['db']->getRow($sql);
	
	
	//取得该阶梯相关信息
	foreach ($extpintuan['price_ladder'] as $item){   
		if ($extpintuan_level == $item['amount']){
			$price = $item['price'];
			$minprice = $item['minprice'];
			$maxprice = $item['maxprice'];
			$tuanzhangdis = $item['tuanzhangdis'];
			$fencheng = $item['fencheng'];
		}
	}
	
	//获取价格
	/*$rand_price = $GLOBALS['db']->getOne("SELECT price FROM ecs_extpintuan_price WHERE user_id = '$user_id' and act_id='$extpintuan_id' and level='$extpintuan_level' and status=0 LIMIT 1 ");
	if(empty($rand_price)){
		$rand_price = $minprice;
	}*/

	$pt_price=$price?$price:$get_ptinfo['price'];
	
	if($pt_id==0 && $tuanzhangdis>=0 && $tuanzhangdis<10){
		$tz_discount=$tuanzhangdis/10;
		$pt_price=$pt_price*$tz_discount;
	}
	
    $goods_price =  $extpintuan_level>1?$pt_price:$extpintuan['single_buy_price'];
	$goods_price=round($goods_price,2);
	if($goods_price<0.01){
		$goods_price=0.01;
	}
	

	
    /* 更新：清空购物车中所有拼团商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
    clear_cart(CART_EXTPINTUAN_GOODS);

    /* 更新：加入购物车 */
    $cart = array(
        'user_id'        => $_SESSION['user_id'],
        'session_id'     => SESS_ID,
        'goods_id'       => $extpintuan['goods_id'],
        'product_id'     => $product_info['product_id'],
        'goods_sn'       => addslashes($goods['goods_sn']),
        'goods_name'     => ($extpintuan['act_name'] && $extpintuan_level>1)?$extpintuan['act_name']:addslashes($goods['goods_name']),
        'market_price'   => $goods['market_price'],
        'goods_price'    => $goods_price,
        'goods_number'   => $number,
        'goods_attr'     => addslashes($goods_attr),
        'goods_attr_id'  => $specs,
        'is_real'        => $goods['is_real'],
        'is_shipping'    =>$goods['is_shipping'],
        'extension_code' => addslashes($goods['extension_code']),
        'parent_id'      => 0,
        'rec_type'       => CART_EXTPINTUAN_GOODS,
        'is_gift'        => 0,
        'split_money'   => $fencheng,
		'cost_price'      =>$fencheng);
    $db->autoExecute($ecs->table('cart'), $cart, 'INSERT');
	$_SESSION['sel_cartgoods'] = $db->insert_id();


	//获取用户昵称、头像
	$user_id = $_SESSION['user_id'];
    $sql = "SELECT u.*,w.nickname,w.headimgurl FROM  " . $GLOBALS['ecs']->table('users') . " u ".
            "left join  " . $GLOBALS['ecs']->table('weixin_user') . " w on u.user_id=w.ecuid ".
            "WHERE  u.user_id='$user_id'";
    $getinfo =$GLOBALS['db']->getRow($sql);
	

    /* 更新：记录购物流程类型：拼团 */
	if($extpintuan_level>1){
		$_SESSION['flow_type'] = CART_EXTPINTUAN_GOODS;
		$_SESSION['extension_code'] = 'extpintuan';
		$_SESSION['extension_id'] = $extpintuan_id;
		$_SESSION['extpintuan_level'] = $extpintuan_level;
		$_SESSION['extpintuan_price'] =$price;
		$_SESSION['extpintuan_pt_id'] = $pt_id;
		$_SESSION['extpintuan_nickname'] = $getinfo['nickname']?$getinfo['nickname']:$getinfo['user_name'];
		$_SESSION['extpintuan_headimgurl'] =$getinfo['headimgurl'];
		$_SESSION['extpintuan_time_limit'] =$extpintuan['time_limit'];
		$_SESSION['extpintuan_end_time'] =$extpintuan['end_time'];
		$_SESSION['extpintuan_lucky_extpintuan'] =$extpintuan['lucky_extpintuan'];
		$_SESSION['extpintuan_act_user']=$get_ptinfo['user_id']?$get_ptinfo['user_id']:$_SESSION['user_id'];
		/* 进入收货人页面 */
		ecs_header("Location: ./flow.php?step=checkout\n");
		exit;
	}else{
		/* 单独购买 */
		unset($_SESSION['extension_code']);
		$_SESSION['flow_type'] = CART_EXTPINTUAN_GOODS;
		ecs_header("Location: ./flow.php?step=checkout\n");
		exit;
	}


}




?>