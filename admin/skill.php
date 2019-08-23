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
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_goods.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
$exc = new exchange($ecs->table('goods_activity'), $db, 'act_id', 'act_name');

/*------------------------------------------------------ */
//-- 活动列表页
/*------------------------------------------------------ */


//秒杀活动列表 --锋
if($_REQUEST['act'] == 'seckill_list'){
    /* 检查权限 */
    admin_priv('auction');

    $list = get_seckill();
    
    $smarty->assign('auction_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    /* 模板赋值 */
    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     "秒杀活动列表");
    $smarty->assign('action_link', array('href' => 'skill.php?act=add_seckill', 'text' => "添加秒杀活动"));

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('seckill_list.htm');
}
//查看秒杀订单 --锋
elseif($_REQUEST['act'] == 'view_seckill'){
    $seckill_id = trim(isset($_GET['id'])?$_GET['id']:"");
    if(empty($seckill_id)){
        sys_msg("缺少参数");
    }

    $smarty->assign('action_link', array('href' => 'skill.php?act=seckill_list', 'text' => "秒杀活动列表"));
    $order_list = order_list($seckill_id);

    $smarty->assign('full_page',   1);
    $smarty->assign('ur_here',     "秒杀活动订单");

    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('seckill_order.htm');
    
}
//秒杀活动编辑 --锋
elseif ($_REQUEST['act'] == 'seckill_edit'){
    $id = trim(isset($_GET['id'])?$_GET['id']:"");
    if(empty($id)){
        sys_msg("缺少参数");
    }

    $smarty->assign('ur_here',     "添加秒杀活动编辑");
    $smarty->assign('action_link', array('href' => 'skill.php?act=seckill_list', 'text' => "秒杀活动列表"));
    $sql = "select * from ".$ecs->table("seckill_activity")."where id = $id";
    $seckill_data = $db->getRow($sql);
    $seckill_data['seckill_start_date'] = local_date('Y-m-d H:i', $seckill_data['seckill_start_date']);
    $seckill_data['seckill_end_date'] = local_date('Y-m-d H:i', $seckill_data['seckill_end_date']);
    $content = unserialize($seckill_data['content']);
    foreach ($content as $key=>$value){
        $sql = "select goods_name from ".$ecs->table("goods")." where goods_id = $value[goods_id]";
        $goods_name = $db->getOne($sql);
        $content[$key]['goods_name'] = $goods_name;
    }

    //获取最大索引
    $num = 0;
    foreach ($content as $key=>$value){
        if($key>$num){
            $num = $key;
        }
    }

    $smarty->assign("num",$num);
    $smarty->assign("seckill_data",$seckill_data);
    $smarty->assign("content",$content);
    $smarty->assign("id",$seckill_data['id']);
    $smarty->display("add_seckill.htm");
}
//秒杀活动编辑 --锋
elseif ($_REQUEST['act'] == 'seckill_edit'){
    $id = trim(isset($_GET['id'])?$_GET['id']:"");
    if(empty($id)){
        sys_msg("缺少参数");
    }

    $smarty->assign('ur_here',     "添加秒杀活动编辑");
    $smarty->assign('action_link', array('href' => 'skill.php?act=seckill_list', 'text' => "秒杀活动列表"));
    $sql = "select * from ".$ecs->table("seckill_activity")."where id = $id";
    $seckill_data = $db->getRow($sql);
    $seckill_data['seckill_start_date'] = local_date('Y-m-d H:i', $seckill_data['seckill_start_date']);
    $seckill_data['seckill_end_date'] = local_date('Y-m-d H:i', $seckill_data['seckill_end_date']);
    $content = unserialize($seckill_data['content']);
    foreach ($content as $key=>$value){
        $sql = "select goods_name from ".$ecs->table("goods")." where goods_id = $value[goods_id]";
        $goods_name = $db->getOne($sql);
        $content[$key]['goods_name'] = $goods_name;
    }

    //获取最大索引
    $num = 0;
    foreach ($content as $key=>$value){
        if($key>$num){
            $num = $key;
        }
    }

    $smarty->assign("num",$num);
    $smarty->assign("seckill_data",$seckill_data);
    $smarty->assign("content",$content);
    $smarty->assign("id",$seckill_data['id']);
    $smarty->display("add_seckill.htm");
}


/*------------------------------------------------------ */
//-- 排序、分页、查询 --锋
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'seckill_query')
{

    $order_list = order_list();

    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag  = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch('seckill_order.htm'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}


//添加秒杀活动 --锋
elseif ($_REQUEST['act'] == 'add_seckill'){
    $smarty->assign('ur_here',     "添加秒杀活动");
    $smarty->assign('action_link', array('href' => 'skill.php?act=seckill_list', 'text' => "秒杀活动列表"));
    $smarty->assign("num",0);
    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('add_seckill.htm');
}



//搜索筛选商品名称 --锋
if($_REQUEST['act'] == 'search_goods_name'){

    // 全局变量

    include('../mobile/includes/cls_json.php');
    $json   = new JSON;

    $goods_value = $_REQUEST['goods_name'];
    if(empty($goods_value))
    {
        $sql = "select goods_id,goods_name from " .$GLOBALS['ecs']->table('goods');
    }
    else
    {
        $sql = "select goods_id,goods_name from " .$GLOBALS['ecs']->table('goods')." where goods_name like '%".$goods_value."%'";
    }
    $res = $db->getAll($sql);
    $arr = array();
    foreach($res as $key=>$val)
    {
        $arr[$key]=$val;
    }
    die($json->encode($arr));
}

//处理添加秒杀活动表单 --锋
elseif ($_REQUEST['act'] == 'add_seckill_form'){


    $id = trim(isset($_POST['id'])?$_POST['id']:"");
    $name = trim(isset($_POST['name'])?$_POST['name']:"");
    $seckill_start_date = trim(isset($_POST['seckill_start_date'])?local_strtotime($_POST['seckill_start_date']):0);
    $seckill_end_date = trim(isset($_POST['seckill_end_date'])?local_strtotime($_POST['seckill_end_date']):0);
    $path = isset($_POST['image'])?$_POST['image']:"";
    $files = isset($_FILES['img'])?$_FILES['img']:"";
    if($files == null){
        sys_msg("活动图片格式错误");
    }

    if(empty($path)){
        if(empty($files['name'])){
            sys_msg("请上传活动图片");
        }
    }


    $seckill = $_POST['seckill'];
    if(empty($name)){
        sys_msg("请输入活动名称");
    }

    if(empty($seckill_start_date)){
        sys_msg("请输入活动开始时间");
    }
    if(empty($seckill_end_date)){
        sys_msg("请输入活动结束时间");
    }
    if($seckill_start_date>=$seckill_end_date){
        sys_msg("请输入有效的时间");
    }
    if(empty($seckill)){
        sys_msg("请选择秒杀商品");
    }

    foreach ($seckill as $key=>$value){

        if(empty($value['goods_id'])){
            sys_msg("您有一个秒杀单品没有选择商品");
            break;
        }

        if(empty($value['seckill_price'])){
            sys_msg("您有一个秒杀单品没有设置秒杀价格");
            break;
        }

        if(empty($value['seckill_num'])){
            sys_msg("您有一个秒杀单品没有设置秒杀数量");
            break;
        }
        $sql = "select g.goods_name,g.goods_number,s.seckill_id from ". $GLOBALS['ecs']->table('goods') . " g"." LEFT JOIN " . $GLOBALS['ecs']->table('seckill_goods') . " s ON g.goods_id = s.goods_id"." where g.goods_id = $value[goods_id]";
        $goods_data = $db->getRow($sql);
        $seckill[$key]['goods_name'] = $goods_data['goods_name'];
        if($value['seckill_num'] > $goods_data['goods_number']){
            sys_msg("您为商品：".$goods_data['goods_name']."设置的秒杀数量大于该商品的库存数量",0,array(),false);
        }
        //如果有秒杀活动id,判断这个商品在不在该秒杀活动里面
        if(!empty($goods_data['seckill_id']) && $goods_data['seckill_id'] != $id){
            $sql = "select * from ".$ecs->table("seckill_activity")."where id = $goods_data[seckill_id]";
            $data = $db->getRow($sql);
            $seckill_content = unserialize($data['content']);
            foreach ($seckill_content as $k=>$v){
                if($value['goods_id'] == $v['goods_id']){
                    sys_msg("您添加的秒杀商品：".$goods_data['goods_name']."已经在秒杀活动：".$data['name']."中添加过",0,array(),false);
                }
            }
        }

    }


    if(!empty($files['name'])){
        //$path = $file['tmp_name'];
        $arr = explode(".", $files["name"]);
        $hz = $arr[count($arr) - 1];
        $files["name"] = time() . '_1.' . $hz;

        $save_path = "../images/images_seckill/";
        $path = $save_path.$files['name'];
        if (!file_exists($save_path)) {
            mkdir($save_path, 0777, true);//创建这个目录，可读可写可执行
            chmod($save_path, 0777); //mod — 改变文件权限
        }
        $rs = move_uploaded_file($files['tmp_name'], $path);
        if($rs){
            if(isImage($path) == false){
                sys_msg("图片格式错误");
            }

        }
    }

	//添加
    if(empty($id)){
        $seckill = serialize($seckill);
        $sql = "insert into ".$ecs->table("seckill_activity")."(`name`,`seckill_start_date`,`seckill_end_date`,`content`,`img`) values ('$name','$seckill_start_date','$seckill_start_date','$seckill','$path')";
        $rs = $db->query($sql);
		
        if($rs){
            $inser_id = $db->insert_id();
            $seckill = unserialize($seckill);
            //对每个商品进行属性修改
            foreach ($seckill as $key=>$value){
                $sql = "update ".$ecs->table("goods")."set `zhekou`=round($value[seckill_price]/market_price,2)*10 where goods_id = $value[goods_id]";
                $db->query($sql);
				//将商品数据插入表中
                $sql = "insert into ".$ecs->table("seckill_goods")."set `is_seckill` = 1,`seckill_start_date`='$seckill_start_date',`seckill_end_date`='$seckill_end_date',`seckill_price`=$value[seckill_price],`seckill_num`=$value[seckill_num],`seckill_id`=$inser_id,`seckill_total_num`=$value[seckill_num],`goods_id`=$value[goods_id]";
                $db->query($sql);				
				
            }
            sys_msg("操作成功" ,0, array(array('href'=>'skill.php?act=seckill_list' , 'text' =>'返回秒杀活动列表')));
        }else{
            sys_msg("操作失败" ,0, array(array('href'=>'skill.php?act=seckill_list' , 'text' =>'返回秒杀活动列表')));
        }
    }else{
	//更新
        $sql = "select goods_id from ".$ecs->table("seckill_goods")."where seckill_id = $id";
        $data = $db->getAll($sql);
        foreach ($data as $key=>$value){
            $i=0;
            foreach ($seckill as $k=>$v){
                if($value['goods_id'] != $v['goods_id']){
                    $i++;
                    if($i == count($seckill)){
                        //更改商品状态
                        $sql = "update ".$ecs->table("goods")."set `zhekou`=round(shop_price/market_price,2)*10    where goods_id = $value[goods_id]";
                        $db->query($sql);	
                        //更改商品状态
                        $sql = "update ".$ecs->table("seckill_goods")."set `is_seckill` = 0 where goods_id = $value[goods_id] and seckill_id=$id";
                        $db->query($sql);							
                    }
                }
            }
        }
        //对每个商品进行属性修改
        foreach ($seckill as $key=>$value){
			
			//查询商品之前是否存在，如果不存在就新增，存在就更新
			$sql = "select goods_id from ".$ecs->table("seckill_goods")."where seckill_id = $id and goods_id = $value[goods_id]";
			$goods_id = $db->getOne($sql);
			if(empty($goods_id)){
					
				$sql = "update ".$ecs->table("goods")."set `zhekou`=round($value[seckill_price]/market_price,2)*10 where goods_id = $value[goods_id]";
                $db->query($sql);
				//将商品数据插入表中
                $sql = "insert into ".$ecs->table("seckill_goods")."set `is_seckill` = 1,`seckill_start_date`='$seckill_start_date',`seckill_end_date`='$seckill_end_date',`seckill_price`=$value[seckill_price],`seckill_num`=$value[seckill_num],`seckill_id`=$id,`seckill_total_num`=$value[seckill_num],`goods_id`=$value[goods_id]";
                $db->query($sql);		
	
			}else{
					
				//标记新增商品总数，在原来基础新增
				$num=$value['seckill_num']-$data['seckill_total_num'];
				if($num>0){
					$new_num=$num+$data['seckill_num'];
				}else{
				
					$new_num=$data['seckill_num'];
				}
				$sql = "update ".$ecs->table("goods")."set `zhekou`=round($value[seckill_price]/market_price,2)*10 where goods_id = $value[goods_id]";
				$db->query($sql);
				//更新秒杀商品表
				$sql = "update ".$ecs->table("seckill_goods")."set `is_seckill` = 1,`seckill_start_date`='$seckill_start_date',`seckill_end_date`='$seckill_end_date',`seckill_price`=$value[seckill_price],`seckill_num`=$new_num,`seckill_total_num`=$value[seckill_num] where goods_id = $value[goods_id] and seckill_id=$id ";
				$db->query($sql);	
			}			
        }
        $seckill = serialize($seckill);
        $sql = "update ".$ecs->table("seckill_activity")."set `name` = '$name',`seckill_start_date`='$seckill_start_date',`seckill_end_date` = '$seckill_end_date',`content` = '$seckill',`img`='$path' where id = $id";
        $rs = $db->query($sql);
        if($rs){
            sys_msg("操作成功" ,0, array(array('href'=>'skill.php?act=seckill_list' , 'text' =>'返回秒杀活动列表')));
        }else{
            sys_msg("操作失败" ,0, array(array('href'=>'skill.php?act=seckill_list' , 'text' =>'返回秒杀活动列表')));
        }
    }
}

//秒杀活动分页查询,搜索 --锋
elseif ($_REQUEST['act'] == 'seckill_search')
{
    $list = get_seckill();

    $smarty->assign('auction_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);
    make_json_result($smarty->fetch('seckill_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除秒杀  --锋
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'seckill_remove')
{

    $id = intval($_GET['id']);
    
    //更改商品状态
    $sql = "select * from ".$ecs->table("seckill_activity")."where id=$id";
    $data = $db->getRow($sql);
    $content = unserialize($data['content']);
    foreach ($content as $key=>$value){
		
        $sql = "update ".$ecs->table("goods")."set `zhekou`=round(shop_price/market_price,2)*10 where goods_id = $value[goods_id]";
        $db->query($sql);
		
        $sql = "update ".$ecs->table("seckill_goods")."set `is_seckill` = 0 where goods_id = $value[goods_id] and  seckill_id=$id";
        $db->query($sql);		
    }
    //删除秒杀活动
    $sql = "delete from ".$ecs->table("seckill_activity")."where id = $id";
    $db->query($sql);
    /* 清除缓存 */
    clear_cache_files();

    $url = 'skill.php?act=seckill_search&' . str_replace('act=seckill_remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}


/*------------------------------------------------------ */
//-- 查看秒杀商品进度  --锋
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'progress')
{
	$id=$_GET['id'];
	
    $sql = "select s.id,g.goods_name,s.seckill_num, s.seckill_total_num from " .$GLOBALS['ecs']->table('seckill_goods')." as s left join  ".$GLOBALS['ecs']->table('goods')." as g on g.goods_id= s.goods_id where s.seckill_id ='$id' and s.is_seckill>0";
	
    $res = $db->getAll($sql);	
    /* 模板赋值 */
    $smarty->assign('res',   $res);
    $smarty->assign('ur_here',     "秒杀商品进度");	
	$smarty->display('seckill_progress.htm');
	
}




//获取秒杀活动列表  --锋
function get_seckill(){
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $where = " 1 ";
        if (!empty($filter['keyword']))
        {
            $where .= " AND name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }


        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('seckill_activity') .
            " WHERE $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT id,name,seckill_start_date, seckill_end_date".
            " FROM " . $GLOBALS['ecs']->table('seckill_activity') .
            " WHERE $where ".
            " LIMIT ". $filter['start'] .", $filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $res = $GLOBALS['db']->getAll($sql);
	foreach($res as $key=>$value){

		    $res[$key]['seckill_start_date'] = local_date('Y-m-d H:i', $value['seckill_start_date']);
			$res[$key]['seckill_end_date'] = local_date('Y-m-d H:i', $value['seckill_end_date']);
	}
    $arr = array('item' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}



function isImage($filename){
    $types =".jpg|.jpeg|.bmp|.gif|.png|";//全部图片格式类型
    if(file_exists($filename)){
        $info = getimagesize($filename);
        $ext = image_type_to_extension($info['2']);
        return stripos($types,$ext);
    }else{
        return false;
    }
}


?>