<?php

define('IN_ECS', true);
define('ECS_ADMIN', true);

require('../includes/init.php');
$is_weixin = is_weixin();				
if($is_weixin && !empty($_POST['id']) && !empty($_POST['uid']) && !empty($_POST['show']) && !empty($_POST['type']) && !empty($_GET['act']) && !empty($_SESSION['user_id'])){
	if($_GET['act'] == 'mycheck')
	{
		if($_POST['type'] == 'pengyouquan'){
			$weixin_info = $db->getRow("select * from ".$ecs->table('weixin_config')." where id='1'");
			$share_money = round(randomFloat_share($weixin_info['pengyouquan_money'], $weixin_info['pengyouquan_money_up']),2);
			$share_point = round(randomFloat_share($weixin_info['pengyouquan_point'], $weixin_info['pengyouquan_point_up']));
			if($weixin_info['appid'] && $weixin_info['appsecret'] && $weixin_info['title'] && $weixin_info['is_pengyouquan'] == '1'){
				$nowtime = mktime();
				//$yestime = strtotime(date('Y-m-d'));
				$yestime = strtotime(date('Y-m-d', time()));
				
                $count = $db->getOne("select count(*) from ".$ecs->table('weixin_share')." where type='2' and user_id=".$_SESSION['user_id']." and create_time > '$yestime'");
				
				if($weixin_info['is_everyday'] == '1'){
					
					if($count <= $weixin_info['pengyouquan_times'] ){
						$info = date('Y-m-d H:i:s')."微信分享朋友圈,获得".$share_money.'元,'.$share_point.'积分';
						log_account_change($_SESSION['user_id'], $share_money, 0, 0, $share_point, $info);
					}else{
				$db->query("insert into ".$ecs->table('weixin_share')." (`user_id`,`type`,`create_time`) values ('$_SESSION[user_id]','1','$nowtime') ");
				     $state = '0';
				     echo json_encode(array('state'=>$state ));   
					 exit;
					 
				     }
				}
				else
				{
					if($count <= $weixin_info['pengyouquan_times'] ){
						$info = date('Y-m-d H:i:s')."微信分享朋友圈,获得".$share_money.'元,'.$share_point.'积分';
						log_account_change($_SESSION['user_id'], $share_money, 0, 0, $share_point, $info);
					}else{
				$db->query("insert into ".$ecs->table('weixin_share')." (`user_id`,`type`,`create_time`) values ('$_SESSION[user_id]','1','$nowtime') ");
				     $state = '0';
				     echo json_encode(array('state'=>$state ));   
					 exit;
					 
				     }
				}
				$db->query("insert into ".$ecs->table('weixin_share')." (`user_id`,`type`,`create_time`) values ('$_SESSION[user_id]','2','$nowtime') ");
			
		    $state = '1';
			echo json_encode(array('share_money'=>$share_money,'share_point'=>$share_point,'state'=>$state));
			}
		}

		if($_POST['type'] == 'pengyou'){
			$weixin_info = $db->getRow("select * from ".$ecs->table('weixin_config')." where id='1'");
		    $share_money = round(randomFloat_share($weixin_info['pengyou_money'], $weixin_info['pengyou_money_up']),2);
			$share_point = round(randomFloat_share($weixin_info['pengyou_point'], $weixin_info['pengyou_point_up']));
			if($weixin_info['appid'] && $weixin_info['appsecret'] && $weixin_info['title'] && $weixin_info['is_pengyou'] == '1'){
				$nowtime = mktime();
				//$yestime = strtotime(date('Y-m-d'));
				$yestime = strtotime(date('Y-m-d', time()));
                $count = $db->getOne("select count(*) from ".$ecs->table('weixin_share')." where type='1' and user_id=".$_SESSION['user_id']." and create_time > '$yestime'");
				if($weixin_info['is_everyday'] == '1'){
					
					if($count < $weixin_info['pengyou_times'] ){
						$info = date('Y-m-d H:i:s')."微信分享给朋友,获得".$share_money.'元,'.$share_point.'积分';
						log_account_change($_SESSION['user_id'], $share_money, 0, 0, $share_point, $info);
					}else{
				$db->query("insert into ".$ecs->table('weixin_share')." (`user_id`,`type`,`create_time`) values ('$_SESSION[user_id]','1','$nowtime') ");
				    $state = '0';
				     echo json_encode(array('state'=>$state ));   
					 exit;
					 
				     }
				}
				else
				{
					if($count < $weixin_info['pengyou_times'] ){
						$info = date('Y-m-d H:i:s')."微信分享给朋友,获得".$share_money.'元,'.$share_point.'积分';
						log_account_change($_SESSION['user_id'], $share_money, 0, 0, $share_point, $info);
					}else{
				 $db->query("insert into ".$ecs->table('weixin_share')." (`user_id`,`type`,`create_time`) values ('$_SESSION[user_id]','1','$nowtime') ");
				     $state = '0';
				     echo json_encode(array('state'=>$state ));   
					 exit;
					 
				     }
				}
				$db->query("insert into ".$ecs->table('weixin_share')." (`user_id`,`type`,`create_time`) values ('$_SESSION[user_id]','1','$nowtime') ");
			$state = '1';
			echo json_encode(array('share_money'=>$share_money,'share_point'=>$share_point,'state'=>$state));

			}
		}
	}
}

function is_weixin()
{
	$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
	if(strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false )
	{
		return false;
	}
	else
	{
		return true;
	}
}

//生成随机数 P R I N C E Q Q 120 029 121
function randomFloat_share($min = 0, $max = 1) {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}