<?php
 /**
 * 秒杀活动管理
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: markzhou $
 * $Id: skill.php  2018-05-5  $
*/


define('IN_ECS', true);

if (!function_exists("htmlspecialchars_decode"))
{
    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
    {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
}

if (empty($_GET['encode']))
{
    $string = array_merge($_GET, $_POST);
    if (get_magic_quotes_gpc())
    {
        require(dirname(__FILE__) . '/includes/lib_base.php');
        //require(dirname(__FILE__) . '/includes/lib_common.php');

        $string = stripslashes_deep($string);
    }
    $string['search_encode_time'] = time();
    $string = str_replace('+', '%2b', base64_encode(serialize($string)));

    header("Location: skill.php?encode=$string\n");

    exit;
}
else
{
    $string = base64_decode(trim($_GET['encode']));
    if ($string !== false)
    {
        $string = unserialize($string);
        if ($string !== false)
        {
            /* 用户在重定向的情况下当作一次访问 */
            if (!empty($string['search_encode_time']))
            {
                if (time() > $string['search_encode_time'] + 2)
                {
                    define('INGORE_VISIT_STATS', true);
                }
            }
            else
            {
                define('INGORE_VISIT_STATS', true);
            }
        }
        else
        {
            $string = array();
        }
    }
    else
    {
        $string = array();
    }
}

require(dirname(__FILE__) . '/includes/init.php');

$_REQUEST = array_merge($_REQUEST, addslashes_deep($string));

$_REQUEST['act'] = !empty($_REQUEST['act']) ? trim($_REQUEST['act']) : '';

if (!empty($_REQUEST['search_type']) && $_REQUEST['search_type'] == 'stores')
{
	header("Location:stores.php?keywords=" . $_REQUEST['keywords']);
}

    $_REQUEST['keywords']   = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))     : '';
    $_REQUEST['brand']      = !empty($_REQUEST['brand'])      ? intval($_REQUEST['brand'])      : 0;
    $_REQUEST['category']   = !empty($_REQUEST['category'])   ? intval($_REQUEST['category'])   : 0;
    $_REQUEST['min_price']  = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
    $_REQUEST['max_price']  = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
    $_REQUEST['goods_type'] = !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
    $_REQUEST['sc_ds']      = !empty($_REQUEST['sc_ds']) ? intval($_REQUEST['sc_ds']) : 0;
    $_REQUEST['outstock']   = !empty($_REQUEST['outstock']) ? 1 : 0;
    $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
    $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
        /* 把搜索结果保存在session中 */
    if(!in_array($_REQUEST['keywords'],$_SESSION['search_goods'])&& !empty($_REQUEST['keywords'])){
        $_SESSION['search_goods'][]  = $_REQUEST['keywords'];
    }

    $category   = !empty($_REQUEST['category']) ? intval($_REQUEST['category'])        : 0;
    $categories = ($category > 0)               ? ' AND ' . get_children($category)    : '';
    $brand      = $_REQUEST['brand']            ? " AND brand_id = '$_REQUEST[brand]'" : '';
    $outstock   = !empty($_REQUEST['outstock']) ? " AND g.goods_number > 0 "           : '';

    $min_price  = $_REQUEST['min_price'] != 0                               ? " AND g.shop_price >= '$_REQUEST[min_price]'" : '';
    $max_price  = $_REQUEST['max_price'] != 0 || $_REQUEST['min_price'] < 0 ? " AND g.shop_price <= '$_REQUEST[max_price]'" : '';

    /* 排序、显示方式以及类型 */
    $default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'final_price' : 'last_update');

	$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'final_price', 'last_update', 'salenum'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;   /* 代码增加_start  By  www.68ecshop.com */
	
    $order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method;
    $display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_SESSION['display_search']) ? $_SESSION['display_search'] : $default_display_type);

    $_SESSION['display_search'] = $display;

    $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
    $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

    $intromode = '';    //方式，用于决定搜索结果页标题图片

	//不同促销活动的搜索
    if (!empty($_REQUEST['intro']))
    {
        switch ($_REQUEST['intro'])
        {
				//甜心  100   添加秒杀
            case 'seckill':
                $intro   = " AND s.seckill_id = $_REQUEST[seckill_id]";
                $intromode = 'seckill';
                $seckill_id = $_REQUEST['seckill_id'];
                $ur_here = $_LANG['seckill'];
				$display="list";
                break;
				//甜心  100   添加秒杀
            default:
                $intro   = '';
        }
    }
    else
    {
        $intro = '';
    }

    if (empty($ur_here))
    {
        $ur_here = $_LANG['search_goods'];
    }
    /*------------------------------------------------------ */
    //-- 属性检索
    /*------------------------------------------------------ */
    $attr_in  = '';
    $attr_num = 0;
    $attr_url = '';
    $attr_arg = array();
    /* 获得符合条件的商品总数 */
    $sql   = "SELECT COUNT(*) FROM " .$ecs->table('goods')."As  g LEFT JOIN " . $GLOBALS['ecs']->table('seckill_goods') . " AS s  on g.goods_id=s.goods_id".
        " WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND s.is_seckill=1 AND g.is_virtual = 0 $attr_in $intro ";
    $count = $db->getOne($sql);

    $max_page = ($count> 0) ? ceil($count / $size) : 1;
    if ($page > $max_page)
    {
        $page = $max_page;
    }

    /* 查询商品 */
    $sql = "SELECT s.*,round((s.seckill_total_num-s.seckill_num)/s.seckill_total_num,2)*100 as bf, g.goods_name, g.market_price,g.click_count, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ".
                " g.exclusive,  ".
                "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type ".
            "FROM " .$ecs->table('goods'). " AS g ".
            " LEFT JOIN " . $GLOBALS['ecs']->table('seckill_goods') . " AS s ".
                    "ON s.goods_id = g.goods_id ".
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND s.is_seckill = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0 $attr_in ".
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .
            "ORDER BY $sort $order";
    $res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
    $arr = array();
    while ($row = $db->FetchRow($res))
    {
		
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }
        $final_price  = get_final_price($row['goods_id'], 1, false);
        /* 处理商品水印图片 */
        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote_small";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new_small";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best_small";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }
		if(!empty($row['goods_id'])){
			if ($watermark_img != '')
			{
				$arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
			}
			//开始格式化显示的  商品数组
			$arr[$row['goods_id']]['goods_id']      = $row['goods_id'];
			if($display == 'grid')
			{
            $arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
			}
			else
			{
            $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
			}
			$arr[$row['goods_id']]['bf']          = $row['bf'];
			$arr[$row['goods_id']]['type']          = $row['goods_type'];
			$arr[$row['goods_id']]['market_price']  = price_format($row['market_price']);
			$arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
			$arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
			$arr[$row['goods_id']]['goods_brief']   = $row['goods_brief'];
			$arr[$row['goods_id']]['click_count'] = $row['click_count'];
	
			if(strpos($row['goods_img'], "ttps")){
				$arr[$row['goods_id']]['goods_thumb']   = get_image_path($row['goods_id'], $row['goods_thumb'], true);
				$arr[$row['goods_id']]['goods_img']     = get_image_path($row['goods_id'], $row['goods_img']);			
			}else{
				$arr[$row['goods_id']]['goods_thumb']   = '../'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
				$arr[$row['goods_id']]['goods_img']     = '../'.get_image_path($row['goods_id'], $row['goods_img']);				
			}
			$arr[$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
			$arr[$row['goods_id']]['wap_count']     = selled_wap_count($row['goods_id']);
			$arr[$row['goods_id']]['is_exclusive']  = is_exclusive($row['exclusive'],$final_price);
			$arr[$row['goods_id']]['final_price'] = $row['seckill_price'];
		}
    }
    if($display == 'grid')
    {
        if(count($arr) % 2 != 0)
        {
            $arr[] = array();
        }
    }
    $smarty->assign('goods_list', $arr);
    $smarty->assign('category',   $category);
    $smarty->assign('keywords',   htmlspecialchars(stripslashes($_REQUEST['keywords'])));
    $smarty->assign('search_keywords',   stripslashes(htmlspecialchars_decode($_REQUEST['keywords'])));
    $smarty->assign('brand',      $_REQUEST['brand']);
    $smarty->assign('min_price',  $min_price);
    $smarty->assign('max_price',  $max_price);
    $smarty->assign('outstock',  $_REQUEST['outstock']);

    /* 分页 */
    $url_format = "skill.php?category=$category&amp;keywords=" . urlencode(stripslashes($_REQUEST['keywords'])) . "&amp;brand=" . $_REQUEST['brand']."&amp;action=".$action."&amp;goods_type=" . $_REQUEST['goods_type'] . "&amp;sc_ds=" . $_REQUEST['sc_ds'];
    if (!empty($intromode))
    {
        $url_format .= "&amp;intro=" . $intromode;
    }
	//秒杀
    if(!empty($seckill_id)){
        $url_format .= "&amp;seckill_id=" . $seckill_id;
    }

    if (isset($_REQUEST['pickout']))
    {
        $url_format .= '&amp;pickout=1';
    }
    $url_format .= "&amp;min_price=" . $_REQUEST['min_price'] ."&amp;max_price=" . $_REQUEST['max_price'] . "&amp;sort=$sort";

    $url_format .= "$attr_url&amp;order=$order&amp;page=";

    $pager['search'] = array(
        'keywords'   => stripslashes(urlencode($_REQUEST['keywords'])),
        'category'   => $category,
        'brand'      => $_REQUEST['brand'],
        'sort'       => $sort,
        'order'      => $order,
        'min_price'  => $_REQUEST['min_price'],
        'max_price'  => $_REQUEST['max_price'],
        'action'     => $action,
        'intro'      => empty($intromode) ? '' : trim($intromode),
        'goods_type' => $_REQUEST['goods_type'],
        'sc_ds'      => $_REQUEST['sc_ds'],
        'outstock'   => $_REQUEST['outstock']
    );
	
	//甜心100  添加秒杀
    if(!empty($seckill_id)){
        $pager['search']['seckill_id'] = $seckill_id;
        //判断秒杀有没有开始
        $sql = "select * from ".$ecs->table("seckill_activity")."where id = $seckill_id";
        $seckill_data = $db->getRow($sql);
        $time = gmtime();
        if($time<$seckill_data['seckill_start_date']){
            //未开始
            $smarty->assign("seckill_begin",1);
            $smarty->assign("seckill_time",$seckill_data['seckill_start_date']-$time);
        }else{
            //开始
            $smarty->assign("seckill_begin",2);
            $smarty->assign("seckill_time",$seckill_data['seckill_end_date']-$time);
        }

        if($time>$seckill_data['seckill_end_date']){
            show_message($_LANG['snatch_is_end']);
        }

        $smarty->assign("now_time",gmtime());
    }
	//甜心100  添加秒杀
    $pager['search'] = array_merge($pager['search'], $attr_arg);

    $pager = get_pager('skill.php', $pager['search'], $count, $page, $size);
    $pager['display'] = $display;

    $smarty->assign('url_format', $url_format);
    $smarty->assign('pager', $pager);

    assign_template();
    assign_dynamic('search');
    $position = assign_ur_here(0, $ur_here . ($_REQUEST['keywords'] ? '_' . $_REQUEST['keywords'] : ''));
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
    $smarty->assign('intromode',      $intromode);
    $smarty->assign('categories', get_categories_tree()); // 分类树
    $smarty->assign('helps',       get_shop_help());      // 网店帮助
    $smarty->assign('top_goods',  get_top10());           // 销售排行
    $smarty->assign('promotion_info', get_promotion_info());
	

    $smarty->display("seckill.dwt");

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */
/**
 *
 *
 * @access public
 * @param
 *
 * @return void
 */
function is_not_null($value)
{
    if (is_array($value))
    {
        return (!empty($value['from'])) || (!empty($value['to']));
    }
    else
    {
        return !empty($value);
    }
}

/**
 * 获得可以检索的属性
 *
 * @access  public
 * @params  integer $cat_id
 * @return  void
 */
function get_seachable_attributes($cat_id = 0)
{
    $attributes = array(
        'cate' => array(),
        'attr' => array()
    );

    /* 获得可用的商品类型 */
    $sql = "SELECT t.cat_id, cat_name FROM " .$GLOBALS['ecs']->table('goods_type'). " AS t, ".
           $GLOBALS['ecs']->table('attribute') ." AS a".
           " WHERE t.cat_id = a.cat_id AND t.enabled = 1 AND a.attr_index > 0 ";
    $cat = $GLOBALS['db']->getAll($sql);

    /* 获取可以检索的属性 */
    if (!empty($cat))
    {
        foreach ($cat AS $val)
        {
            $attributes['cate'][$val['cat_id']] = $val['cat_name'];
        }
        $where = $cat_id > 0 ? ' AND a.cat_id = ' . $cat_id : " AND a.cat_id = " . $cat[0]['cat_id'];

        $sql = 'SELECT attr_id, attr_name, attr_input_type, attr_type, attr_values, attr_index, sort_order ' .
               ' FROM ' . $GLOBALS['ecs']->table('attribute') . ' AS a ' .
               ' WHERE a.attr_index > 0 ' .$where.
               ' ORDER BY cat_id, sort_order ASC';
        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->FetchRow($res))
        {
            if ($row['attr_index'] == 1 && $row['attr_input_type'] == 1)
            {
                $row['attr_values'] = str_replace("\r", '', $row['attr_values']);
                $options = explode("\n", $row['attr_values']);

                $attr_value = array();
                foreach ($options AS $opt)
                {
                    $attr_value[$opt] = $opt;
                }
                $attributes['attr'][] = array(
                    'id'      => $row['attr_id'],
                    'attr'    => $row['attr_name'],
                    'options' => $attr_value,
                    'type'    => 3
                );
            }
            else
            {
                $attributes['attr'][] = array(
                    'id'   => $row['attr_id'],
                    'attr' => $row['attr_name'],
                    'type' => $row['attr_index']
                );
            }
        }
    }

    return $attributes;
}

/**
 * 根据区域id 获得区域名称
 * @param int $region_id 区域id
 * @return $region_name 区域名称
 */
function get_region_name($region_id){
    $region_id = empty($region_id)?0:intval($region_id);
    $sql = "select region_name from ".$GLOBALS['ecs']->table('region')." where region_id = $region_id";
    $region_name = $GLOBALS['db'] -> getOne($sql);
    return $region_name;
}
?>