<?php
	//define('IN_ECS', true);
	//require('./init.php');
	//$db = $GLOBALS['db'];
	//$ecs = $GLOBALS['ecs'];
	////将所有的粉丝同步到gooderp,加入队列
	//$sql = 'SELECT ecuid FROM '. $ecs->table('weixin_user') .' WHERE 1 ORDER BY ecuid ASC';
	//$list = $db->getAll($sql);
	//$sql = "INSERT INTO ". $ecs->table('queue') ." (queue_type,queue_param,operate_status,create_time) VALUES ";
	//foreach($list as $k=>$v){
		//$sql .=" (0,'".serialize(array('userid'=>$v['ecuid']))."',0,'".time()."'),";
	//}
	//$sql = substr($sql,0,-1);
	//echo $sql;




define('IN_ECS', true);
require('./init.php');
$db = $GLOBALS['db'];
$ecs = $GLOBALS['ecs'];
set_time_limit(3600);  //设置程序执行时间
ignore_user_abort(true);    //设置断开连接继续执行
header('X-Accel-Buffering: no');    //关闭buffer
header('Content-type: text/html;charset=utf-8');    //设置网页编码
ob_start(); //打开输出缓冲控制


////导入代理商
//echo "代理商导入","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

//init_agent('./init_file/dailishang.text',11);
//echo "代理商导入end","<br/>";
//echo "代理商下级导入","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

//init_agent('./init_file/dailishang_child.text',0,true);
//echo "代理商下级导入end","<br/>";
//echo "分校导入","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

//init_agent('./init_file/fenxiao.text',10);
//echo "分校导入end","<br/>";
//echo "分校下级导入","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

//init_agent('./init_file/fenxiao_child.text',0,true);
//echo "分校下级导入end","<br/>";
//echo 'end';

//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出


//echo "init_file/fenxiao.csv导入","<br/>";
//init_user('./init_file/fenxiao.csv',10);
//echo "init_file/fenxiao.csv导入end","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

//导入代理商以及下属会员
//echo "init_file/agent.csv导入","<br/>";
//init_user('./init_file/agent.csv',11);
//echo "init_file/agent.csv导入end","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

//echo "init_file/fenxiao.text 导入","<br/>";
//init_user_text('./init_file/fenxiao.text',10);
//echo "init_file/fenxiao.text 导入end","<br/>";
//echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
//flush();   //刷新缓冲区的内容，输出

echo "init_file/agent.text 导入","<br/>";
init_user_text('./init_file/agent.text',11);
echo "init_file/agent.text 导入end","<br/>";
echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
flush();   //刷新缓冲区的内容，输出






//查询一级代理商的所有有手机号的下级会员
//generate_init_agent(11);

//查询分校的所有有手机号的下级会员
//generate_init_agent(10);

//查询一级代理商所有购买过vip的下级会员
//generate_init_order(10);

//查询分销所有购买过vip的下级会员
//generate_init_order(11);




function generate_init_order($agentid){
	$mobile_phones = $GLOBALS['db']->getAll("SELECT `mobile_phone` FROM ". $GLOBALS['ecs']->table('users')." WHERE user_rank=".$agentid);
	if(!isset($mobile_phones) || empty($mobile_phones)){
		return false;
	}else{
		$mobiles = '(';
		foreach($mobile_phones as $v){
			$mobiles.= '"'.$v['mobile_phone'].'",';
		}
		$mobiles = substr($mobiles,0,-1).")";
		$sql = 'SELECT DISTINCT(b.mobile),b.realname,b.nickname,c.mobile as pmobile,c.realname as prealname,c.nickname as pnickname,a.ordersn
		FROM ims_fy_lesson_member_order a
		LEFT JOIN ims_mc_members b ON a.uid=b.uid
		LEFT JOIN ims_mc_members c ON a.member1=c.uid
		WHERE c.mobile IN '.$mobiles.' AND a.`status`>0';
		$user = $GLOBALS['db']->getAll($sql);
		//echo "<pre>";print_r($user);
		if($agentid == 10){ //fenxiao.text
			foreach($user as $k=>$v){
				//写入培训系统的导入分校文件
				file_put_contents('./init_file/fenxiao.text',json_encode($v).PHP_EOL, FILE_APPEND);
			}
		}elseif($agentid == 11){ //agent.text
			foreach($user as $k=>$v){
				//写入培训系统的导入代理商文件
				file_put_contents('./init_file/agent.text',json_encode($v).PHP_EOL, FILE_APPEND);
			}
		}
		//echo "<pre>";print_r($user);
	}
}




//根据agentid获取一级代理商和分校。再通过分校或者代理商手机号去查询培训中的下级会员
function generate_init_agent($agentid){
	$mobile_phones = $GLOBALS['db']->getAll("SELECT `mobile_phone` FROM ". $GLOBALS['ecs']->table('users')." WHERE user_rank=".$agentid);
	if(!isset($mobile_phones) || empty($mobile_phones)){
		return false;
	}else{
		$mobiles = '(';
		foreach($mobile_phones as $v){
			$mobiles.= '"'.$v['mobile_phone'].'",';
		}
		$mobiles = substr($mobiles,0,-1).")";
		$sql = 'SELECT b.mobile,b.realname,b.nickname,c.mobile as pmobile,c.realname as prealname,c.nickname as pnickname 
		FROM `ims_fy_lesson_member` a 
		LEFT JOIN ims_mc_members b ON a.uid=b.uid 
		LEFT JOIN ims_mc_members c ON a.parentid=c.uid 
		WHERE c.mobile IN '.$mobiles.' AND b.mobile is NOT NULL and b.mobile !=""';

		$user = $GLOBALS['db']->getAll($sql);
		if($agentid == 10){ //fenxiao.text
			foreach($user as $k=>$v){
				//写入培训系统的导入分校文件
				file_put_contents('./init_file/fenxiao.text',json_encode($v).PHP_EOL, FILE_APPEND);
			}
		}elseif($agentid == 11){ //agent.text
			foreach($user as $k=>$v){
				//写入培训系统的导入代理商文件
				file_put_contents('./init_file/agent.text',json_encode($v).PHP_EOL, FILE_APPEND);
			}
		}
		echo "<pre>";print_r($user);
	}
}

//初始化导入会员 
/*
**filename		导入文件
**agentid		会员等级id
**parent_flag	是否有上级,true的时候根据parent_openid查询上级id
*/
function init_agent($filename,$agentid = 0,$parent_falg=false){
	$file = fopen($filename, "r");
	$info=array();
	
	//输出文本中所有的行，直到文件结束为止。
	while(! feof($file))
	{
		 $info = explode(',',fgets($file));//fgets()函数从文件指针中读取一行
		 $openid		= trim($info[3]);
		 if(empty($openid)){
			continue;
		 }else{
			 $userinfo = $GLOBALS['db']->getOne("SELECT `ecuid` FROM ". $GLOBALS['ecs']->table('weixin_user')." WHERE fake_id='".$openid."'");
			 if(isset($userinfo) && !empty($userinfo)){
				continue;
			 }else{
				 $real_name		= trim($info[0]);
				 $nick_name		= addslashes(trim($info[1]));
				 $mobile		= trim($info[2]);
				 $images		= trim($info[4]);
				 $parentid		= 0;
				 if($parent_falg){
					$parent_openid = trim($info[5]);
					$parentid = $GLOBALS['db']->getOne("SELECT `ecuid` FROM ".$GLOBALS['ecs']->table('weixin_user')." WHERE fake_id='".$parent_openid."'");
				 }
				 $user_name = (!empty($nick_name))?$nick_name:((!empty($real_name))?$real_name:$openid);
				
				$count = $GLOBALS['db']->getOne("SELECT COUNT(user_name) FROM ". $GLOBALS['ecs']->table('users')." WHERE user_name='".$user_name."'");
				 if($count) // 重名处理
				 {
					$user_name = $user_name . '_' . 'weixin' .(rand(10000, 99999)).(substr(time(),-5));
				 }
				 $user_sql = "INSERT INTO ". $GLOBALS['ecs']->table('users') ."  (real_name,user_name,mobile_phone,aite_id,avatar,user_rank,parent_id) VALUES ('".$real_name."', '".$user_name."', '".$mobile."', 'weixin_".$openid."','".$images."',".$agentid.",".intval($parentid).")";
				 $GLOBALS['db']->query($user_sql);
				 $user_id = $GLOBALS['db']->insert_id();
				 $fansql = "INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (ecuid,fake_id,createtime,createymd,nickname,headimgurl) VALUES (".$user_id.",'".$openid."','".time()."','".date('Y-m-d')."','".$nick_name."','".$images."')";
				 $GLOBALS['db']->query($fansql);
			 }
		 }
	}
	fclose($file);
}




//查询上级会员是否存在 不存在则新增上级会员，根据返回的上级id锁定上下级
function user_exist_or_insert($userinfo,$agentid){
	$parentid = $GLOBALS['db']->getOne("SELECT `user_id` FROM ". $GLOBALS['ecs']->table('users')." WHERE mobile_phone='".$userinfo['parent_phone']."'");
	if(!isset($parentid) || empty($parentid)){
		$parentid = insert_user($userinfo['parent_name'],$userinfo['parent_phone'],0,$agentid);
	}else{
		echo "上级会员【'$userinfo[parent_name]'】存在，跳过<br/>";
	}
	if($userinfo['user_phone']){
		//查询会员是否存在，
		$user_id =  $GLOBALS['db']->getOne("SELECT `user_id` FROM ". $GLOBALS['ecs']->table('users')." WHERE mobile_phone='".$userinfo['user_phone']."'");
		if(!isset($user_id) || empty($user_id)){
			$parentid = insert_user($userinfo['user_name'],$userinfo['user_phone'],$parentid);
		}else{
			echo "会员【'$userinfo[user_name]'】存在，跳过<br/>";
		}
	}
}

//新增会员返回会员id
function insert_user($user_name,$mobile_phone,$parentid,$agentid = 0){
	if(empty($mobile_phone)){
		echo "会员【'$user_name'】添加失败，手机号为空<br/>";
		return 0;
	}else{
		if(empty($user_name)){
			$user_name = $mobile_phone;
		}
		$count = $GLOBALS['db']->getOne("SELECT COUNT(user_name) FROM ". $GLOBALS['ecs']->table('users')." WHERE user_name='".$user_name."'");
		if($count) // 重名处理
		{
			$user_name = $user_name . '_' . 'weixin' .(rand(10000, 99999)).(substr(time(),-5));
		}
		$users = & init_users();
		if(!$users->add_user($user_name, substr($mobile_phone,-6), $mobile_phone.'@jzx.com'))
		{
			/* 插入会员数据失败 */
			if($users->error == ERR_INVALID_USERNAME)//4
			{
				$msg = 'username_invalid';
			}
			elseif($users->error == ERR_USERNAME_NOT_ALLOW)//7
			{
				$msg = 'email_invalid';
			}
			elseif($users->error == ERR_USERNAME_EXISTS)//1
			{
				$msg = 'email_invalid';
			}
			elseif($users->error == ERR_INVALID_EMAIL)//6
			{
				$msg = 'email_invalid';
			}
			elseif($users->error == ERR_EMAIL_NOT_ALLOW)//8
			{
				$msg = 'email_not_allow';
			}
			elseif($users->error == ERR_EMAIL_EXISTS)//2
			{
				$msg = 'email_exists';
			}
			echo "会员【'$user_name'】添加失败,".$msg."<br/>";
			return 0;
		}else{
			$sql = "update " . $GLOBALS['ecs']->table('users') . " set  parent_id='$parentid' ,mobile_phone='$mobile_phone' , `real_name`='$user_name',`user_rank`='$agentid' where user_name = '" . $user_name . "'";
			$GLOBALS['db']->query($sql);
			$sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . "  where user_name = '" . $user_name . "'";
			$user_id = $GLOBALS['db']->getOne($sql);
			echo "会员【'$user_name'】添加成功<br/>";
			return $user_id;
		}
	}
}



function init_user($filename,$agentid){
	$file = fopen($filename, "r");
	$info=array();
	
	//输出文本中所有的行，直到文件结束为止。
	while(! feof($file))
	{
		 $info = explode(',',fgets($file));//fgets()函数从文件指针中读取一行
		 $userinfo = array(
			'parent_name'=>trim($info[1]),
			'parent_phone'=>trim($info[2]),
			'user_name'=>trim($info[4]),
			'user_phone'=>trim($info[5]),
		 );
		 //根据手机号查询上级会员是否存在
		 user_exist_or_insert($userinfo,$agentid);
	}
	fclose($file);
}

function init_user_text($filename,$agentid){
	$file = fopen($filename, "r");
	$info=array();
	//输出文本中所有的行，直到文件结束为止。
	$i = 0;
	while(! feof($file))
	{
		 $info = json_decode(fgets($file),true);//fgets()函数从文件指针中读取一行
		 $userinfo = array(
			'parent_name'=>trim($info['prealname'])?trim($info['prealname']):trim($info['pnickname']),
			'parent_phone'=>trim($info['pmobile']),
			'user_name'=>trim($info['realname'])?trim($info['realname']):trim($info['nickname']),
			'user_phone'=>trim($info['mobile']),
		 );
		 //根据手机号查询上级会员是否存在
		 user_exist_or_insert($userinfo,$agentid);
		 $i++;
	}
	fclose($file);
}