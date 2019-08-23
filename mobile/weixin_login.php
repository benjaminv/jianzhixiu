<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');
$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
$weixin = new core_lib_wechat($weixinconfig);
if($_GET['code']){
	$json = $weixin->getOauthAccessToken();
	
	if(isset($json['openid']) && !empty($json['openid'])){
		$info = $weixin->getOauthUserinfo($json['access_token'],$json['openid']);
		$fans_info = $weixin->getUserInfo($json['openid']);
		$isfollow = isset($fans_info['subscribe']) ? intval($fans_info['subscribe']) : 0;
		
		$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$json['openid']}'");
		if($rows){
			//粉丝信息存在
			if($rows['ecuid'] > 0){
				//已关联会员表
				$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
				$rows2 = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='" . $rows['ecuid'] . "'");
				$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET headimg = '".$info['headimgurl']."' WHERE user_id = '" . $rows2['user_id'] . "'");
				$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('weixin_user')." SET headimgurl = '".$info['headimgurl']."',isfollow=".intval($isfollow)." WHERE ecuid = '" . $rows2['user_id'] . "'");
			}else{
				//未关联会员表
				$userInfo = $GLOBALS['db']->getRow("SELECT user_name,user_id FROM ".$GLOBALS['ecs']->table('users')." WHERE aite_id like '%" . $json['openid'] . "%'");
				if($userInfo){
					//未关联会员表，但是有会员数据
					$username = $userInfo['user_name'];
					$user_id = $userInfo['user_id'];
				}else{
					//未关联会员表，并且没有会员数据
					if($info['nickname']){
						$info['name'] = str_replace("'", "", $info['nickname']);
						// 过滤掉emoji表情
						$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);
						if($GLOBALS['user']->check_user($info['name'])) // 重名处理
						{
							$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
						}
					}else{
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					if(!$info['name']){
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					$info_user_id = 'weixin' . '_' . $info['openid'];
					$user_pass = $GLOBALS['user']->compile_password(array(
						'password' => $info['openid']
					));
					$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time , is_validated,froms,headimg) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[headimgurl]')";
					$GLOBALS['db']->query($sql);
					$username = $info['name'];
					$user_id = $db->insert_id();
					if(ODOO_ERP){
						$odooErpObj = OdooErp::getInstance();
						$res = $odooErpObj->syncUserByUserid($user_id);
					}
				}
				$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('weixin_user')." SET ecuid = '".$user_id."',isfollow=".intval($isfollow)." WHERE uid = '" . $rows['user_id'] . "'");
			}
			$GLOBALS['user']->set_session($username);
			$GLOBALS['user']->set_cookie($username,1);
			update_user_info();  //更新用户信息
			recalculate_price(); //重新计算购物车中的商品价格
			header("Location:user.php");exit;
		}else{
			
			//粉丝数据不存在，但是会员数据存在
			$userInfo = $GLOBALS['db']->getRow("SELECT user_name,user_id,headimg FROM ".$GLOBALS['ecs']->table('users')." WHERE aite_id like '%" . $json['openid'] . "%'");
			if($userInfo){
				//查看用户名是否为空
				if(isset($userInfo['user_name']) && !empty($userInfo['user_name'])){
					$username = $userInfo['user_name'];
				}else{
					if($info['nickname']){
						$info['name'] = str_replace("'", "", $info['nickname']);
						// 过滤掉emoji表情
						$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);
						if($GLOBALS['user']->check_user($info['name'])) // 重名处理
						{
							$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
						}
					}else{
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					if(!$info['name']){
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET user_name = '".$info['name']."' WHERE user_id = '" . $userInfo['user_id'] . "'");		
					$username = $info['name'];		
				}
					$createtime = gmtime();
					$createymd = date('Y-m-d',gmtime());
					
					$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`nickname`,`headimgurl`,`isfollow`) value (".$userInfo['user_id'].",'" . $json['openid'] . "','{$createtime}','{$createymd}','".$username."','".$info['headimgurl']."',".intval($isfollow).")");
			}else{
				//新注册用户流程
					if($info['nickname']){
						$info['name'] = str_replace("'", "", $info['nickname']);
						// 过滤掉emoji表情
						$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);
						if($GLOBALS['user']->check_user($info['name'])) // 重名处理
						{
							$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
						}
					}else{
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					if(!$info['name']){
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					$info_user_id = 'weixin' . '_' . $info['openid'];
					$user_pass = $GLOBALS['user']->compile_password(array(
						'password' => $info['openid']
					));
					//扫码绑定上级
					$parent_id=$_GET['user_id']?intval($_GET['user_id']):0;
					$type=$_GET['erweima_type']?intval($_GET['erweima_type']):0;
					$parent = 0;
					if($type == 1){
						$parent_user_rank = $GLOBALS['db']->getRow("SELECT IFNULL(a.user_rank,0) as user_rank,IFNULL(b.special_rank,0) as special_rank FROM ".$GLOBALS['ecs']->table('users')." a LEFT JOIN ".$GLOBALS['ecs']->table('user_rank')." b ON b.rank_id=a.user_rank WHERE a.user_id = ".$parent_id);
						if($parent_user_rank['user_rank'] == 0 || $parent_user_rank['special_rank'] == 0){
							$parent = 0;
						}else{
							$parent = $parent_id;
						}
					}

					//奇迹增加绑定账号
                    $bind=$_GET['bind']?intval($_GET['bind']):0;

                    if(empty($bind))
                    {
                        ecs_header("Location: affiliate_weixin_login.php?act=bind&parent_id=$parent_id \n");
                        exit;
                    }
                    else
                    {
						
                        $profile_type=$_GET['profile_type']?intval($_GET['profile_type']):0;
                        $mobile_phone = trim($_GET['mobile_phone']);
                        //新注册
                        if($profile_type==0)
                        {
                            $sql="SELECT * FROM " . $GLOBALS['ecs']->table('users') .
                                " WHERE mobile_phone = '$mobile_phone'";
                            $get_user=$GLOBALS['db']->getRow($sql);
                            if(!empty($get_user))
                            {
                                header("Location:./");exit;
                                exit;
                            }
                            $password=md5(substr($mobile_phone,-6));
							$info['name'] = trim($info['name']);
                            $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(mobile_phone, user_name , password, aite_id , sex , reg_time , is_validated,froms,headimg,is_fenxiao,parent_id) VALUES ' . "('$mobile_phone', '$info[name]' , '$password' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[headimgurl]',2,".$parent.")";
                            $GLOBALS['db']->query($sql);
                            $username = $info['name'];
                            $user_id = $db->insert_id();

                            if(ODOO_ERP){
                                $odooErpObj = OdooErp::getInstance();
                                $res = $odooErpObj->syncUserByUserid($user_id);
                            }
                            if($parent>0)
                            {
                                //require_once(ROOT_PATH  . 'weixin/weixin_notice.php');
                                //huiyuan_join($user_id,$parent);
                            }
                        }
                        else
                        {
                            $user_id=$_GET['ud']?intval($_GET['ud']):0;
                            $mp = trim($_GET['mp']);
                            $sql="SELECT * FROM " . $GLOBALS['ecs']->table('users') .
                                " WHERE user_id = '$user_id'";
                            $get_user=$GLOBALS['db']->getRow($sql);

                            //增加重名判断
                            $sql="SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') .
                                " WHERE ecuid = '$user_id'";
                            $weixin_user=$GLOBALS['db']->getRow($sql);
                            if(!empty($weixin_user))
                            {
                                header("Location:./");
                                exit;
                            }

                            
                            if(empty($get_user))
                            {
                                header("Location:./");exit;
                                exit;
                            }
                            if($mp!=md5($user_id.$get_user['mobile_phone']))
                            {
                                header("Location:./");exit;
                                exit;
                            }
                            $username=$get_user['user_name'];

                        }
                    }
					$createtime = gmtime();
					$createymd = date('Y-m-d',gmtime());
					$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`nickname`,`headimgurl`,`isfollow`) value (".$user_id.",'" . $json['openid'] . "','{$createtime}','{$createymd}','".$info['name']."','".$info['headimgurl']."',".intval($isfollow).")");
			}
			$GLOBALS['user']->set_session($username);
			$GLOBALS['user']->set_cookie($username);
			update_user_info();
			recalculate_price();
		}
		
		$url = $GLOBALS['ecs']->url()."user.php";
		header("Location:$url");exit;
	}else{
		echo "获取微信信息失败";
		$url = $GLOBALS['ecs']->url()."user.php";
		header("Location:$url");exit;
		exit;
	}
}
$bind=$_GET['bind']?intval($_GET['bind']):0;
if($bind==1)
{
    $profile_type=$_GET['profile_type']?intval($_GET['profile_type']):0;
    $mobile_phone = trim($_GET['mobile_phone']);
    $user_id=$_GET['ud']?intval($_GET['ud']):0;
    $mp = trim($_GET['mp']);
    if($profile_type==0)
    {
        $url = $GLOBALS['ecs']->url()."weixin_login.php?bind=1&user_id=".$_GET['user_id']."&erweima_type=1&mobile_phone=$mobile_phone&profile_type=0";
    }
    else
    {
        $url = $GLOBALS['ecs']->url()."weixin_login.php?bind=1&user_id=".$_GET['user_id']."&erweima_type=1&ud=$user_id&profile_type=1&mp=$mp";
    }
}
else
{
    $url = $GLOBALS['ecs']->url()."weixin_login.php?user_id=".$_GET['user_id']."&erweima_type=1";
}

$url = $weixin->getOauthRedirect($url,1,'snsapi_userinfo');
header("Location:$url");exit;
?>