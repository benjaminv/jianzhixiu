<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}


if ($_REQUEST['act'] == 'list')
{	
	$smarty->assign('full_page',    1);
	
	$distrib_sort_list = get_distrib_sort();

    $smarty->assign('distrib_sort_list',  $distrib_sort_list['arr']);
    $smarty->assign('filter',          $distrib_sort_list['filter']);
    $smarty->assign('record_count',    $distrib_sort_list['record_count']);
    $smarty->assign('page_count',      $distrib_sort_list['page_count']);
	$smarty->display('distrib_sort_list.htm');

}
elseif($_REQUEST['act'] == 'query')
{
	
	$distrib_sort_list = get_distrib_sort();
    
    $smarty->assign('distrib_sort_list',  $distrib_sort_list['arr']);
    $smarty->assign('filter',          $distrib_sort_list['filter']);
    $smarty->assign('record_count',    $distrib_sort_list['record_count']);
    $smarty->assign('page_count',      $distrib_sort_list['page_count']);

	make_json_result($smarty->fetch('distrib_sort_list.htm'), '',array('filter' => $distrib_sort_list['filter'], 'page_count' => $distrib_sort_list['page_count']));
}
/**
 *
 * 根据分销商名称 或者时间导出数据
 */
elseif($_REQUEST['act'] == 'select_distribute'){
   
    
    $begin = strtotime($_REQUEST['begin']);
    $end = strtotime($_REQUEST['end']);


    $distrib_sort_list = get_distrib_sort_by_action($_REQUEST['fenxiao_name'],$begin,$end);

    $smarty->assign('full_page',    1);

    $smarty->assign('distr_name',  $_REQUEST['fenxiao_name']);
    $smarty->assign('begin',          $_REQUEST['begin']);
    $smarty->assign('end',    $_REQUEST['end']);

    $smarty->assign('distrib_sort_list',  $distrib_sort_list['arr']);
    $smarty->assign('filter',          $distrib_sort_list['filter']);
    $smarty->assign('record_count',    $distrib_sort_list['record_count']);
    $smarty->assign('page_count',      $distrib_sort_list['page_count']);
    $smarty->display('distrib_sort_list.htm');
}
// ajax查看某商户的详细信息
elseif($_REQUEST['act'] == 'ajax_select_info'){


    $pagesize = $_REQUEST['pagesize']?$_REQUEST['pagesize']:'10'; // 每一页的显示条数
    $page = $_REQUEST['page']?$_REQUEST['page']:'1'; // 页数

    $begin = strtotime($_REQUEST['begin']); // 起始时间
    $end = strtotime($_REQUEST['end'])?strtotime($_REQUEST['end']):time();     //结束时间

    $where1 = '';
    if($begin){

        $where1 .= 'and time >="'.$begin.'" and time  < "'.$end.'"';
    }

    $where2 = '';
    if($begin){

        $where2 .= ' and ecs_affiliate_log.time >="'.$begin.'" and ecs_affiliate_log.time  < "'.$end.'"';
    }


    $distri_name = $_REQUEST['distrib_name'];

    $totalcount = $db->getOne('select count(log_id) from ecs_affiliate_log where money <> 0 and user_name="'.$distri_name.'" '.$where1); // 总记录数

    $totalpage = ceil($totalcount/$pagesize); // 总页数

    $begin = $pagesize*($page-1);

    $total_money = $db->getAll('select ecs_affiliate_log.user_name,ecs_affiliate_log.time,money,change_desc,ecs_users.user_name as xia_name from ecs_affiliate_log  
        left join ecs_order_info on ecs_order_info.order_id=ecs_affiliate_log.order_id 
        left join ecs_users on ecs_users.user_id = ecs_order_info.user_id 
        where money <> 0 and ecs_affiliate_log.user_name="'.$distri_name.'" '.$where2.' limit '.$begin.','.$pagesize);

    foreach ($total_money as $key => $value) {
        
        $total_money[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
    }

    $return = ['info'=>$total_money,'totalcount'=>$totalcount,'totalpage'=>$totalpage,'page'=>$page,'pagesize'=>$pagesize];

    make_json_result($return);

}
/**
 * 导出排行榜操作
 *
 */
elseif($_REQUEST['act'] == 'excel_shuju'){

    $begin = strtotime($_REQUEST['begin']);
    $end = strtotime($_REQUEST['end']);

	$distrib_sort_list = get_distrib_sort_by_action2($_REQUEST['fenxiao_name'],$begin,$end);
    
    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=orderexcel.xls");

    $data = "<table border='1' style='center'>
            <tr>
                 <th>".iconv('utf-8', 'gbk','分销商名称')."</th>
                 <th>".iconv('utf-8', 'gbk','分成时间')."</th>
                 <th>".iconv('utf-8', 'gbk','总分成金额')."</th>
            </tr>";
   
    foreach ($distrib_sort_list as  $val) {
        $data .="
            <tr>
                <td>".iconv('utf-8', 'gbk',$val['user_name'])."&nbsp</td>
                <td>".iconv('utf-8', 'gbk',date('Y-m-d H:i:s',$val['time']))."</td>
                <td>".iconv('utf-8', 'gbk',$val['total_money'])."</td>
            </tr>";
    
    }
    $data .='</table>';

    echo $data;
 
}




function get_distrib_sort()
{
	 $filter = array();
	 $sql = "SELECT COUNT(distinct user_id) FROM " .$GLOBALS['ecs']->table('affiliate_log');
     $filter['record_count'] = $GLOBALS['db']->getOne($sql);

     $filter = page_and_size($filter);

     $arr = array();
	 $sql = "SELECT d.*,sum(money) as total_money,u.user_name FROM " .$GLOBALS['ecs']->table('affiliate_log') ." as d inner join " . $GLOBALS['ecs']->table('users') . " as u on d.user_id = u.user_id group by d.user_id order by total_money desc";
	 $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
	 while ($rows = $GLOBALS['db']->fetchRow($res))
	 {
          $rows['time'] = date('Y-m-d H:i:s',$rows['time']);
		  $arr[] = $rows;
	 } 
	 return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']); 
}


/**
 * 功能： 根据条件获得分销商的分成总金额
 * 参数： distri_name  begin   end
 * 返回： array 
 */
function get_distrib_sort_by_action($distri_name,$begin,$end){

    $where = '';
    
    if($distri_name){
       $where .= 'user_name like "%'.trim($distri_name).'%"';

    }
    if($begin && $end){

        if(!$where){

            $where .= 'time >="'.$begin.'" and time  < "'.$end.'"';

        }else{

            $where .= ' and time >= "'.$begin.'" and time  < "'.$end.'"';
        }
    }

    $where2 = '';
    
    if($distri_name){
       $where2 .= 'd.user_name like "%'.trim($distri_name).'%"';

    }
    if($begin && $end){

        if(!$where2){

            $where2 .= 'd.time >="'.$begin.'" and d.time  < "'.$end.'"';

        }else{

            $where2 .= ' and d.time >= "'.$begin.'" and d.time  < "'.$end.'"';
        }
    }

    if($where)  $where = ' where '.$where; //  搜索条件

    if($where2)  $where2 = ' where '.$where2; //  搜索条件

     $filter = array();
     $sql = "SELECT COUNT(distinct user_id) FROM " .$GLOBALS['ecs']->table('affiliate_log').$where;
     $filter['record_count'] = $GLOBALS['db']->getOne($sql);

     $filter = page_and_size($filter);
     $arr = array();
     $sql = "SELECT d.*,sum(money) as total_money,u.user_name FROM " .$GLOBALS['ecs']->table('affiliate_log') ." as d inner join " . $GLOBALS['ecs']->table('users') . " as u on d.user_id = u.user_id ".$where2." group by d.user_id order by total_money desc";
     $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
     while ($rows = $GLOBALS['db']->fetchRow($res))
     {    
          $rows['time'] = date('Y-m-d H:i:s',$rows['time']);
          $arr[] = $rows;
     } 
     return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']); 
}



/**
 * 功能： 导出订单的根据条件获得分销商的分成总金额
 * 参数： distri_name  begin   end
 * 返回： array 
 */
function get_distrib_sort_by_action2($distri_name,$begin,$end){


    $where2 = '';
    
    if($distri_name){
       $where2 .= 'd.user_name like "%'.trim($distri_name).'%"';

    }
    if($begin && $end){

        if(!$where2){

            $where2 .= 'd.time >="'.$begin.'" and d.time  < "'.$end.'"';

        }else{

            $where2 .= ' and d.time >= "'.$begin.'" and d.time  < "'.$end.'"';
        }
    }


    if($where2)  $where2 = ' where '.$where2; //  搜索条件

    
    $sql = "SELECT d.*,sum(money) as total_money,u.user_name FROM " .$GLOBALS['ecs']->table('affiliate_log') ." as d inner join " . $GLOBALS['ecs']->table('users') . " as u on d.user_id = u.user_id ".$where2." group by d.user_id order by total_money desc";
    $res = $GLOBALS['db']->getAll($sql);
     
     return $res;

}


function get_distrib_sort1()
{

     
	 $sql = "SELECT d.*,sum(money) as total_money,u.user_name FROM " .$GLOBALS['ecs']->table('affiliate_log') ." as d inner join " . $GLOBALS['ecs']->table('users') . " as u on d.user_id = u.user_id group by d.user_id order by total_money desc";
	 $res = $GLOBALS['db']->getAll($sql);
	 
	 return $res; 
}
