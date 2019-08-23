<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if(empty($_REQUEST['act'])) $_REQUEST['act'] ='change';

// 显示设置
if($_REQUEST['act'] =='change'){

    $where = ' where 1=1';
    if(isset($_REQUEST['biaohao']) && $_REQUEST['biaohao']){

    	$where .= ' and  code_number = "'.$_REQUEST['biaohao'].'"';
    }

    if(isset($_REQUEST['picihao']) && $_REQUEST['picihao']){
       
        $where .= ' and  batch_number = "'.$_REQUEST['picihao'].'"';	
    	
    }
    // 是否抽过奖
    if(isset($_REQUEST['is_lotteryed']) && $_REQUEST['is_lotteryed'] >=1){

        if($_REQUEST['is_lotteryed'] == 2){ // 未抽奖

            $where .= ' and ecs_security_codes.user_id=0 and scan_num =0';

        }elseif($_REQUEST['is_lotteryed'] == 1){ // 抽奖

            $where .=' and ecs_security_codes.user_id >0 and scan_num >0';
        }
    }

    // 是否中奖
    if(isset($_REQUEST['is_prize']) && $_REQUEST['is_prize'] >=1){

        if($_REQUEST['is_prize'] == 1){

            $where .=' and ecs_dzp_goods.type <2 and ecs_security_codes.user_id >0';
       
        }elseif( $_REQUEST['is_prize'] == 2){

            $where .=' and ecs_security_codes.user_id >0 and ecs_dzp_goods.type =2';
        }
 
    }

    $pagesize = isset($_REQUEST['pagesize'])?$_REQUEST['pagesize']:'15';
	$nowpage = isset($_REQUEST['nowpage'])?$_REQUEST['nowpage']:'1';
    
     if($nowpage < 1){

    	$nowpage = 1;
    }

    if($pagesize<1){

    	$pagesize =1;
    }
    $begin = ($nowpage-1)*$pagesize;

   $count = $db->getOne('select count(ecs_security_codes.id) from ecs_security_codes left join ecs_dzp_goods on ecs_dzp_goods.id=ecs_security_codes.dzp_good_id'.$where);

   $info = $db->getAll('select batch_number,code_number,dzp_name,dzp_good_id,prize_level,ecs_security_codes.dzp_id,points,is_lottery,addtime,product  
       from  ecs_security_codes  
       left join ecs_dzp_type on ecs_dzp_type.id=ecs_security_codes.dzp_id  
       left join ecs_dzp_goods on ecs_dzp_goods.id=ecs_security_codes.dzp_good_id '.$where.'  limit '.$begin.','.$pagesize);

   foreach ($info as $key => $value) {

   		$info[$key]['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
   }

    $lastpage = ceil($count/$pagesize);
   
    $smarty->assign('nowpage', $nowpage);
    $smarty->assign('prepage', $nowpage-1);
    $smarty->assign('nextpage', $nowpage+1);
    $smarty->assign('pagesize', $pagesize);
    $smarty->assign('lastpage', $lastpage);

    $smarty->assign('prize', $_REQUEST['is_prize']);
    $smarty->assign('choujiang', $_REQUEST['is_lotteryed']);

    $smarty->assign('code_mumber',$_REQUEST['biaohao']); // 编号
    $smarty->assign('pici_number', $_REQUEST['picihao']); // 批次号
    




    $smarty->assign('count', $count); // 记录总数


    $smarty->assign('info',$info); // 详细信息

    $smarty->display('er_wei_ma.htm');


// 更换防伪码的抽奖奖项
}elseif( $_REQUEST['act'] == 'update_active'){

    if($_REQUEST['code_number'] && $_REQUEST['active']){

        $up = $db->query('update ecs_security_codes set dzp_good_id="'.$_REQUEST['active'].'" where code_number="'.$_REQUEST['code_number'].'"');

        sys_msg('添加防伪码奖项成功！',0,$link);
    }


}elseif($_REQUEST['act'] =='add_prize'){

    $dzp_id =  json_decode(stripslashes($_REQUEST['JSON']));

    if($_REQUEST['JSON']){

        $rel = $db->getAll('select * from ecs_dzp_goods where dzp_id="'.$dzp_id[0].'"');
    }    
   
    make_json_result($rel);



}


