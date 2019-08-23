<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH. "includes/lib_comment.php");
include_once(ROOT_PATH. "includes/lib_order.php");
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
//检查活动
if($_REQUEST['act'] == "search_goods")
{
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'msg' => '');

    $password   = isset($_REQUEST['password']) ? $_REQUEST['password']: "";  //防伪码
    $time   = isset($_REQUEST['time']) ? $_REQUEST['time']: "";  //token
    $md   = isset($_REQUEST['md']) ? $_REQUEST['md']: "";  //加密
    $key="code_number";
    if(md5($password.$time.$key)!=$md)
    {
        $result['error']=1;
        $result['msg']="抽奖防伪码校验有误！";
        echo $json->encode($result);
        exit;
    }

    $sql = "select * from ". $ecs->table("security_codes") ." where code_number = '$password' ";
    $security_codes = $db->getRow($sql);
    if(empty($security_codes))
    {
        $result['error']=1;
        $result['msg']="抽奖防伪码有误！";
        echo $json->encode($result);
        exit;
    }
    if($security_codes['is_lottery'] == 1)
    {
        $result['error']=1;
        $result['msg']="抽奖防伪码已使用！";
        echo $json->encode($result);
        exit;
    }

    $id     = (isset($_REQUEST['dzp_id'])) ? intval($_REQUEST['dzp_id']) : 0;
    $sql = "select * from ". $ecs->table("dzp_type") ." where id = '$id'";
    $dzp_detail = $db->getRow($sql);
    if(empty($dzp_detail))
    {
        $result['error']=1;
        $result['msg']="无抽奖活动！";
    }
    else
    {
        //看活动是否结束
        $now_time=time();
        $start_time = strtotime($dzp_detail['dzp_start_day']." 00:00:00");
        $end_time = strtotime($dzp_detail['dzp_end_day']." 23:59:59");
        if($now_time>=$start_time&&$now_time<=$end_time)
        {
            //抽奖次数判断
            $sql = "select count(*) from ". $ecs->table("security_codes") ." where user_id = '$_SESSION[user_id]' and is_lottery = 1 ";
            $security_codes_count = $db->getOne($sql);
            if($security_codes_count>=$dzp_detail['dzp_draw_times'])
            {
                $result['error']=1;
                $result['msg']="您已经超过抽奖次数！";
                echo $json->encode($result);
                exit;
            }

            // //获取概率
            // $sql = "select * from ". $ecs->table("dzp_goods") ." where dzp_id = '$id' order by id asc";
            // $dzp_goods_lists = $db->getAll($sql);
            // if(empty($dzp_goods_lists))
            // {
            //     /* 如果没有找到任何记录则跳回到首页 */
            //     $result['error']=1;
            //     $result['msg']="抽奖数据异常！";
            // }
            // else
            // {
            //     $dzp_goods_lists=dzp_rand_goods($id,$dzp_goods_lists);
            //     foreach ($dzp_goods_lists as $key => $val) {
            //         $arr[$key] = $val['prize_prob'];
            //     }
            //     $get_rand=get_rand($arr);
            //     $item=$get_rand+1;

            //     //生成数据
            //     $result['get_rand']=$item;
            //     $sql = "select * from ". $ecs->table("dzp_goods") ." where dzp_id = '$id' order by id asc";
            //     $dzp_goods_lists = $db->getAll($sql);}
        if(!$security_codes['dzp_good_id']){

            $dzp_goods_list ='';
        }else{
            // 这里更改需求，直接固定的搜索数据库中二维码商品的奖项
            $sql = "select * from ". $ecs->table("dzp_goods") ." where id = ".$security_codes['dzp_good_id']." order by id asc";

            $dzp_goods_list = $db->getRow($sql);
        }
        
        

        // 获取对应的奖项顺序
        $order = $db->getAll('select id,type from ecs_dzp_goods where dzp_id="'.$_REQUEST['dzp_id'].'" order by id asc');
        foreach ($order as $key => $val) {
            
            if( $val['id'] == $security_codes['dzp_good_id'] && $dzp_goods_list){

                $len = $key + 1;

            }elseif(!$dzp_goods_list && $val['type'] == 2){

                $len = $key + 1;
            }
        }



        
        // 检测产品是否设置了奖，如果没有，默认给谢谢惠顾
        if($dzp_goods_list['type']==2 || !$dzp_goods_list){

                    $result['error']=0;
                    $result['type']=2;
                    $result['get_rand']=$len; // 前端对应的抽奖的顺序
                   
                    $sql = 'UPDATE ' . $ecs->table('security_codes') . " SET scan_num=scan_num+1,user_id = '$_SESSION[user_id]', is_lottery = '1',first_scantime = '".gmtime()."',update_scantime = '".gmtime()."',lottery_time = '".gmtime()."'  WHERE code_number = '$password'";
                    $db->query($sql);
                    log_account_change($_SESSION['user_id'], 0, 0, 0, $security_codes['points'], '扫码赠送积分');
        }
        elseif($dzp_goods_list['type']==1)  //红包
        {
                    $bonus_type_id=$dzp_goods_list['bouns_id'];

                    $sql = "select * from ". $ecs->table("user_bonus") ." where bonus_type_id = '$bonus_type_id' and user_id = 0 ";
                    $user_bonus = $db->getRow($sql);
                    if(empty($user_bonus))
                    {
                        $result['error']=1;
                        $result['msg']="红包已经抽完！";
                    }
                    else
                    {
                        $sql = 'UPDATE ' . $ecs->table('user_bonus') . " SET dzp_status = '1',user_id = '$_SESSION[user_id]',dzp_id = '$id'  WHERE bonus_id = '$user_bonus[bonus_id]'";
                        $db->query($sql);
                        $result['error']=0;
                        $result['type']=1;
                        $result['get_rand']=$len;

                        $sql = 'UPDATE ' . $ecs->table('security_codes') . " SET scan_num=scan_num+1,user_id = '$_SESSION[user_id]', is_lottery = '1',first_scantime = '".gmtime()."',update_scantime = '".gmtime()."',lottery_time = '".gmtime()."'  WHERE code_number = '$password'";
                        $db->query($sql);
                        log_account_change($_SESSION['user_id'], 0, 0, 0, $security_codes['points'], '扫码赠送积分');
                    }
                }
        elseif( isset($dzp_goods_list['type']) && $dzp_goods_list['type']==0)  //商品
        {
                    $goods_id=$dzp_goods_list['goods_id'];
                    $sql = "insert into " . $ecs->table('dzp_user_goods') . "(dzp_id, 	goods_id, user_id, status " . " ) " . " values('$id', '$goods_id', '$_SESSION[user_id]', '0')";
                    $db->query($sql);
                    $user_goods_id = $db->insert_id();
                    $result['error']=0;
                    $result['status']=1;
                    $result['user_goods_id']=$user_goods_id;
                    $result['type']=0;
                    $result['get_rand']=$len;

                    $sql = 'UPDATE ' . $ecs->table('security_codes') . " SET scan_num=scan_num+1,user_id = '$_SESSION[user_id]', is_lottery = '1',first_scantime = '".gmtime()."',update_scantime = '".gmtime()."',lottery_time = '".gmtime()."'  WHERE code_number = '$password'";
                    $db->query($sql);
                    log_account_change($_SESSION['user_id'], 0, 0, 0, $security_codes['points'], '扫码赠送积分');
                    //creat_dzp_order($_SESSION['user_id'],$dzp_goods_lists[$item-1]['goods_id']);
        }

                //$result['error']=0;
                //$result['get_rand']=$get_rand+1;
                //$result['msg']="开始抽奖";
            //}
        }
        else
        {
            $result['error']=1;
            $result['msg']="抽奖活动结束！";
        }
    }
    echo $json->encode($result);
    exit;
}
//生成订单
elseif($_REQUEST['act'] == "dzp_done")
{
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'msg' => '','status'=> '0');

    $id     = (isset($_REQUEST['dzp_id'])) ? intval($_REQUEST['dzp_id']) : 0;
    $item     = (isset($_REQUEST['item'])) ? intval($_REQUEST['item']) : 0;
    if(empty($item))
    {
        $result['error']=1;
        $result['msg']="抽奖异常，请重新抽奖！";
    }

    $sql = "select * from ". $ecs->table("dzp_type") ." where id = '$id'";
    $dzp_detail = $db->getRow($sql);
    if(empty($dzp_detail))
    {
        $result['error']=1;
        $result['msg']="无抽奖活动！";
    }
    else
    {
        //看活动是否结束
        $now_time=time();
        $start_time = strtotime($dzp_detail['dzp_start_day']." 00:00:00");
        $end_time = strtotime($dzp_detail['dzp_end_day']." 23:59:59");
        if($now_time>=$start_time&&$now_time<=$end_time)
        {
            $sql = "select * from ". $ecs->table("dzp_goods") ." where dzp_id = '$id' order by id asc";
            $dzp_goods_lists = $db->getAll($sql);
            if($dzp_goods_lists[$item-1]['type']==2)
            {
                $result['error']=0;
            }
            elseif($dzp_goods_lists[$item-1]['type']==1)  //红包
            {
                $bonus_type_id=$dzp_goods_lists[$item-1]['bouns_id'];
                $sql = "select * from ". $ecs->table("user_bonus") ." where bonus_type_id = '$bonus_type_id' and user_id = 0 ";
                $user_bonus = $db->getRow($sql);
                if(empty($user_bonus))
                {
                    $result['error']=1;
                    $result['msg']="红包已经抽完！";
                }
                else
                {
                    $sql = 'UPDATE ' . $ecs->table('user_bonus') . " SET dzp_status = '1',user_id = '$_SESSION[user_id]',dzp_id = '$id'  WHERE bonus_id = '$user_bonus[bonus_id]'";
                    $db->query($sql);
                    $result['error']=0;
                }
            }
            else  //商品
            {
                $goods_id=$dzp_goods_lists[$item-1]['goods_id'];
                $sql = "insert into " . $ecs->table('dzp_user_goods') . "(dzp_id, 	goods_id, user_id, status " . " ) " . " values('$id', '$goods_id', '$_SESSION[user_id]', '0')";
                $db->query($sql);
                $user_goods_id = $db->insert_id();
                $result['error']=0;
                $result['status']=1;
                $result['user_goods_id']=$user_goods_id;
                //creat_dzp_order($_SESSION['user_id'],$dzp_goods_lists[$item-1]['goods_id']);
            }
        }
        else
        {
            $result['error']=1;
            $result['msg']="抽奖活动结束！";
        }
    }
    echo $json->encode($result);
    exit;
}

elseif($_REQUEST['act'] == "address")
{
    include_once (ROOT_PATH . 'includes/lib_transaction.php');
    include_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
    $smarty->assign('lang', $_LANG);

    $id     = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;

    $sql = "select id from ". $ecs->table("dzp_user_goods") ." where id = '$id' and user_id = '$_SESSION[user_id] and status = 0' ";
    $user_goods_id = $db->getOne($sql);
    if(empty($user_goods_id))
    {
        ecs_header("Location: ./\n");
        exit;
    }
    $smarty->assign('user_goods_id', $user_goods_id);
    /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
    $smarty->assign('country_list', get_regions());
    $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

    // 取得国家列表，如果有收货人列表，取得省市区列表

    $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 1;
    $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : -1;
    $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : -1;
    $consignee['district'] = isset($consignee['district']) ? intval($consignee['district']) : -1;
    $province_list = get_regions_wap($consignee['country']);

    $city_list = get_regions_wap($consignee['province']);
    $district_list = get_regions_wap($consignee['city']);
    $xiangcun_list = get_regions_wap($consignee['district']);

    // 赋值于模板
    $smarty->assign('real_goods_count', 1);
    $smarty->assign('shop_country', $_CFG['shop_country']);
    $smarty->assign('shop_province', get_regions(1, $_CFG['shop_country']));
    $smarty->assign('province_list', $province_list);
    $smarty->assign('city_list', $city_list);
    $smarty->assign('district_list', $district_list);
    $smarty->assign('xiangcun_list', $xiangcun_list);
    $smarty->assign('address_id',$address_id);
    $smarty->assign('currency_format', $_CFG['currency_format']);
    $smarty->assign('integral_scale', $_CFG['integral_scale']);
    $smarty->assign('name_of_region', array(
        $_CFG['name_of_region_1'],$_CFG['name_of_region_2'],$_CFG['name_of_region_3'],$_CFG['name_of_region_4']
    ));


    $smarty->display('dzp_address.dwt');
    exit;
}
elseif($_REQUEST['act'] == "act_edit_address")
{
    $id     = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
    $sql = "select * from ". $ecs->table("dzp_user_goods") ." where id = '$id' and user_id = '$_SESSION[user_id] and status = 0' ";
    $user_goods_detail = $db->getRow($sql);
    if(empty($user_goods_detail))
    {
        show_message("没有此抽奖数据！");
        exit;
    }
    $consignee = array(
        'country' => isset($_POST['country']) ? intval($_POST['country']) : 0,'province' => isset($_POST['province']) ? intval($_POST['province']) : 0,'city' => isset($_POST['city']) ? intval($_POST['city']) : 0,'district' => isset($_POST['district']) ? intval($_POST['district']) : 0,'xiangcun' => isset($_POST['xiangcun']) ? intval($_POST['xiangcun']) : 0,'address' => isset($_POST['address']) ? compile_str(trim($_POST['address'])) : '','consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee'])) : '','email' => isset($_POST['email']) ? compile_str(trim($_POST['email'])) : '','tel' => isset($_POST['tel']) ? compile_str(make_semiangle(trim($_POST['tel']))) : '','mobile' => isset($_POST['mobile']) ? compile_str(make_semiangle(trim($_POST['mobile']))) : '',
        'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time'])) : '','sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '','zipcode' => isset($_POST['zipcode']) ? compile_str(make_semiangle(trim($_POST['zipcode']))) : ''
    );
    $new_order_id=creat_dzp_order($_SESSION['user_id'],$user_goods_detail['goods_id'],$consignee,$user_goods_detail['dzp_id']);


    $sql = 'UPDATE ' . $ecs->table('dzp_user_goods') . " SET status = 1 WHERE id = '$id'";
    $db->query($sql);

    show_message("抽奖商品提交收货地址成功", "订单详情", 'user.php?act=order_detail&order_id='.$new_order_id);
}
else
{
    if(empty($_SESSION['user_id']))
    {
        /* 如果没有找到任何记录则跳回到首页 */
        ecs_header("Location: user.php\n");
        exit;
    }
    //防伪码编辑
    $password   = isset($_REQUEST['password']) ? $_REQUEST['password']: "";  //防伪码
    $time   = isset($_REQUEST['time']) ? $_REQUEST['time']: "";  //token
    $md   = isset($_REQUEST['md']) ? $_REQUEST['md']: "";  //加密
    $key="code_number";
    if(md5($password.$time.$key)!=$md)
    {
        show_message("抽奖防伪码校验有误！");
    }
    $sql = "select * from ". $ecs->table("security_codes") ." where code_number = '$password' ";
    $security_codes = $db->getRow($sql);
    if(empty($security_codes))
    {
        show_message("抽奖防伪码有误！");
    }
    if($security_codes['is_lottery'] == 1)
    {
        show_message("抽奖防伪码已使用！");
    }

    $smarty->assign('password',              $password);
    $smarty->assign('time',              $time);
    $smarty->assign('md',              $md);



    $id     = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
    if(empty($id))
    {
        /* 如果没有找到任何记录则跳回到首页 */
        ecs_header("Location: ./\n");
        exit;
    }

    $sql = "select * from ". $ecs->table("dzp_type") ." where id = '$id'";
    $dzp_detail = $db->getRow($sql);
    if(empty($dzp_detail))
    {
        /* 如果没有找到任何记录则跳回到首页 */
        ecs_header("Location: ./\n");
        exit;
    }
    //奇迹添加
    $sql = "select id from ". $ecs->table("dzp_user_goods") ." where dzp_id = '$id' and user_id = '$_SESSION[user_id]' and status = 0 ";
    $user_goods_id = $db->getOne($sql);
   /* if($user_goods_id > 0)
    {
        ecs_header("Location: dzp.php?act=address&id=".$user_goods_id."\n");
        exit;
    }*/

    $smarty->assign('dzp_detail',              $dzp_detail);
    $smarty->assign('dzp_id',              $id);
    $sql = "select * from ". $ecs->table("dzp_goods") ." where dzp_id = '$id' order by id asc";
    $dzp_goods_lists = $db->getAll($sql);

    if(empty($dzp_goods_lists))
    {
        /* 如果没有找到任何记录则跳回到首页 */
        ecs_header("Location: ./\n");
        exit;
    }
    else
    {
        //优先比较库存
      /*  $prob=0;
        $hg_key=0;
        foreach($dzp_goods_lists as $k=>$v)
        {
            //增加产品和红包是否库存比较
            if($v['type']==0)   //产品库存比较
            {
                $sql = 'SELECT goods_number FROM ' . $GLOBALS['ecs']->table('goods') .
                    " WHERE goods_id = '$v[goods_id]' ";
                $goods_number=$GLOBALS['db']->getOne($sql);
                if(empty($goods_number))
                {
                    $prob +=$v['prize_prob'];
                    $dzp_goods_lists[$k]['prize_prob']=0;
                }
                else
                {
                    $sql = "select sum(goods_number) as dzp_goods_number from ".$ecs->table('order_goods')." as og left join ".$ecs->table('order_info')." as oi on og.order_id=oi.order_id where og.dzp_id = '$id' and og.goods_id = '$v[goods_id]' ";
                    $dzp_goods_number = $db->getOne($sql);
                    if($dzp_goods_number>0&&$dzp_goods_number>$v['prize_count'])
                    {
                        $prob +=$v['prize_prob'];
                        $dzp_goods_lists[$k]['prize_prob']=0;
                    }
                }
            }
            elseif($v['type']==1)
            {
                $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('user_bonus') .
                    " WHERE bonus_type_id = 4 and user_id = 0 and dzp_status = 0 and dzp_id =0  ";
                $user_bonus_number=$GLOBALS['db']->getOne($sql);
                if(empty($user_bonus_number))
                {
                    $prob +=$v['prize_prob'];
                    $dzp_goods_lists[$k]['prize_prob']=0;
                }
                else
                {
                    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('user_bonus') .
                        " WHERE bonus_type_id = 4 and dzp_id = '$id' and dzp_status = 1 ";
                    $bonus_number=$GLOBALS['db']->getOne($sql);
                    if($dzp_goods_number>0&&$bonus_number>$v['prize_count'])
                    {
                        $prob +=$v['prize_prob'];
                        $dzp_goods_lists[$k]['prize_prob']=0;
                    }
                }
            }
            else
            {
                $hg_key=$k;
            }
        }
        if($prob>0)
        {
            $dzp_goods_lists[$hg_key]['prize_prob']=$dzp_goods_lists[$hg_key]['prize_prob']+$prob;
        }*/
        $dzp_goods_lists=dzp_rand_goods($id,$dzp_goods_lists);
        //end
        $prize_level_out="[ ";
        $prize_prob_out="[ ";
        $prize_color_out="[ ";
        foreach($dzp_goods_lists as $k=>$v)
        {
            $prize_level_out .="'".$v['prize_level']."',";
            $prize_prob_out .="'".$v['prize_prob']."%',";
            if($k % 2 == 0)
            {
                $dzp_goods_lists[$k]['color']="#FFF4D6";
                $prize_color_out .="'"."#FFF4D6"."',";
            }
            else
            {
                $dzp_goods_lists[$k]['color']="#FFFFFF";
                $prize_color_out .="'"."#FFFFFF"."',";
            }
            $dzp_goods_lists[$k]['prize_prob_r']="'".$v['prize_prob']."%"."'";
        }

        $prize_level_out .=" ]";
        $prize_prob_out .="] ";
        $prize_color_out .="] ";
        $smarty->assign('dzp_goods_lists',              $dzp_goods_lists);
        $smarty->assign('prize_level_out',              $prize_level_out);
        $smarty->assign('prize_prob_out',              $prize_prob_out);
        $smarty->assign('prize_color_out',              $prize_color_out);

    }

    $smarty->display('dzp.dwt');
}

function get_region_info_wap($region_id)
{
    $sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
        " WHERE region_id = '$region_id' ";
    return $GLOBALS['db']->getOne($sql);
}

function get_regions_wap($region_id){
    $sql = 'SELECT region_id,region_name FROM ' . $GLOBALS['ecs']->table('region') .
        " WHERE parent_id = '$region_id' ";
    return $GLOBALS['db']->getAll($sql);
}

function dzp_rand_goods($id,$dzp_goods_lists)
{
    //优先比较库存
    $prob=0;
    $hg_key=0;
    foreach($dzp_goods_lists as $k=>$v)
    {
        //增加产品和红包是否库存比较
        if($v['type']==0)   //产品库存比较
        {
            $sql = 'SELECT goods_number FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id = '$v[goods_id]' ";
            $goods_number=$GLOBALS['db']->getOne($sql);
            if(empty($goods_number))
            {
                $prob +=$v['prize_prob'];
                $dzp_goods_lists[$k]['prize_prob']=0;
            }
            else
            {
                $sql = "select sum(goods_number) as dzp_goods_number from ".$GLOBALS['ecs']->table('order_goods')." as og left join ".$GLOBALS['ecs']->table('order_info')." as oi on og.order_id=oi.order_id where og.dzp_id = '$id' and og.goods_id = '$v[goods_id]' ";
                $dzp_goods_number = $GLOBALS['db']->getOne($sql);
                if($dzp_goods_number>0&&$dzp_goods_number>$v['prize_count'])
                {
                    $prob +=$v['prize_prob'];
                    $dzp_goods_lists[$k]['prize_prob']=0;
                }
            }
        }
        elseif($v['type']==1)
        {
            $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('user_bonus') . " AS ub
                    LEFT JOIN " . $GLOBALS['ecs']->table('bonus_type') .
                " AS b ON b.type_id = ub.bonus_type_id WHERE b.send_type =3 and ub.user_id = 0 and ub.dzp_status = 0 and ub.dzp_id =0 ";
            $user_bonus_number=$GLOBALS['db']->getOne($sql);
            if(empty($user_bonus_number))
            {
                $prob +=$v['prize_prob'];
                $dzp_goods_lists[$k]['prize_prob']=0;
            }
            else
            {
                $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('user_bonus') . " AS ub
                    LEFT JOIN " . $GLOBALS['ecs']->table('bonus_type') .
                    " AS b ON b.type_id = ub.bonus_type_id WHERE b.send_type = 3 and ub.dzp_id = '$id' and ub.dzp_status = 1 ";
                $bonus_number=$GLOBALS['db']->getOne($sql);
                if($dzp_goods_number>0&&$bonus_number>$v['prize_count'])
                {
                    $prob +=$v['prize_prob'];
                    $dzp_goods_lists[$k]['prize_prob']=0;
                }
            }
        }
        else
        {
            $hg_key=$k;
        }
    }
    if($prob>0)
    {
        $dzp_goods_lists[$hg_key]['prize_prob']=$dzp_goods_lists[$hg_key]['prize_prob']+$prob;
    }
    return $dzp_goods_lists;
    //end
}

function get_rand($proArr) {
    $result = '';

    //概率数组的总概率精度
    $proSum = array_sum($proArr);

    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset ($proArr);

    return $result;
}
