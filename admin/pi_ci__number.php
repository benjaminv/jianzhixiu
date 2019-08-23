<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

    if(empty($_REQUEST['act'])) $_REQUEST['act'] = 'list';
    
    if($_REQUEST['act'] == 'list'){

	   	$pagesize = isset($_REQUEST['pagesize'])?$_REQUEST['pagesize']:'15';
		$nowpage = isset($_REQUEST['nowpage'])?$_REQUEST['nowpage']:'1';
	    
	     if($nowpage < 1){

	    	$nowpage = 1;
	    }

	    if($pagesize<1){

	    	$pagesize =1;
	    }
	    $begin = ($nowpage-1)*$pagesize;

	    $count_arr = $db->getAll('select batch_number from ecs_security_codes group by batch_number');

	    $info = $db->getAll('select ecs_security_codes.batch_number,ecs_dzp_type.dzp_name,ecs_dzp_type.id,prize_level 
	    	from ecs_security_codes 
	    	left join ecs_dzp_type on ecs_dzp_type.id=ecs_security_codes.dzp_id 
	    	left join ecs_dzp_goods on ecs_dzp_goods.id=ecs_security_codes.dzp_good_id
	    	group by batch_number limit '.$begin.','.$pagesize);

	    $arr =  []; 

	    foreach ($info as  $value) {

	   		$rel = 	$db->getOne('select count(id) from ecs_security_codes where batch_number="'.$value['batch_number'].'"');


	   		$arr[] = ['batch_number'=>$value['batch_number'],'count'=>$rel,'dzp_name' =>$value['dzp_name'],'dzp_id'=>$value['id'],'dzp_good_name'=>$value['prize_level']];
	    }

	    $lastpage = ceil(count($count_arr)/$pagesize);
	    


	     $smarty->assign('nowpage', $nowpage);
	     $smarty->assign('count', $count);
	     $smarty->assign('prepage', $nowpage-1);
	     $smarty->assign('nextpage', $nowpage+1);
	     $smarty->assign('pagesize', $pagesize);
	     $smarty->assign('lastpage', $lastpage);
	     $smarty->assign('arr_info', $arr);


	     // 获取抽奖的所有活动
	    $active = $db->getAll('select id,dzp_name from ecs_dzp_type');
	    $smarty->assign('active', $active);

	    

    }elseif($_REQUEST['act'] == 'update_active'){

   		if($_REQUEST['batch_number'] && $_REQUEST['active'] ){

   			$rel = $db->query('update ecs_security_codes set dzp_id="'.$_REQUEST['active'].'" where batch_number="'.$_REQUEST['batch_number'].'"');

   			sys_msg('添加批次号的抽奖活动成功！',0,$link);
   		}

    }elseif($_REQUEST['act'] == 'get_prize'){

    	$dzp_id =  json_decode(stripslashes($_REQUEST['JSON']));

    	if($dzp_id){

           $rel = $db->getAll('select * from ecs_dzp_goods where dzp_id="'.$dzp_id[0].'"');
        
           make_json_result($rel);
        }

    }elseif($_REQUEST['act'] == 'add_prize'){

        if($_REQUEST['active_id'] && $_REQUEST['batch_number'] && $_REQUEST['prize_id']){

        	$rel = $db->query('update ecs_security_codes set dzp_good_id="'.$_REQUEST['prize_id'].'" where batch_number="'.$_REQUEST['batch_number'].'"');

   			sys_msg('添加'.$_REQUEST['acive'].'的奖项成功！',0,$link);

        }

    }

    

$smarty->display('pi_ci_number.htm');