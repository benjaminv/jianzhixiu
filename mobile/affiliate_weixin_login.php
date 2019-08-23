<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
if($_REQUEST['act']=="bind")
{
    $parent_id = empty($_REQUEST['parent_id'])?0:  intval($_REQUEST['parent_id']);
    $smarty->assign('parent_id',      $parent_id);
    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
    $smarty->assign('user_info',get_user_info_by_user_id($_SESSION['user_id']));

    $smarty->display('affiliate_login_bind.dwt');
}
elseif($_REQUEST['act']=="checkmobile")
{
    include_once (ROOT_PATH . 'includes/cls_json.php');
    require_once (ROOT_PATH . 'includes/lib_sms.php');
    $json = new JSON();
    $result = array(
        'error' => '0','message' => ''
    );
    $mobile_phone = trim($_POST['mobile_phone']);

    $sql="SELECT * FROM " . $GLOBALS['ecs']->table('users') .
        " WHERE mobile_phone = '$mobile_phone'";
    $get_user=$GLOBALS['db']->getRow($sql);
    if(empty($get_user))
    {
        $result['error']=0;
        $result['mp']=base64_encode($mobile_phone);
    }
    else
    {
        $result['error']=1;
    }
    die($json->encode($result));
}
elseif($_REQUEST['act']=="checkpassword")
{
    include_once (ROOT_PATH . 'includes/cls_json.php');
    require_once (ROOT_PATH . 'includes/lib_sms.php');
    $json = new JSON();
    $result = array(
        'error' => '0','msg' => ''
    );
    $mobile_phone = trim($_POST['mobile_phone']);
    $password = trim($_POST['password']);

    $sql="SELECT * FROM " . $GLOBALS['ecs']->table('users') .
        " WHERE mobile_phone = '$mobile_phone'";
    $get_user=$GLOBALS['db']->getRow($sql);
    if(!empty($get_user))
    {
        $sql="SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') .
            " WHERE ecuid = '$get_user[user_id]'";
        $weixin_user=$GLOBALS['db']->getRow($sql);
        if(!empty($weixin_user))
        {
            $result['error']=1;
            $result['msg']="此会员已绑定，不能重复绑定！";
            die($json->encode($result));
        }

        if(empty($get_user['ec_salt']))
        {
            $user_exist = $GLOBALS['db']->getOne("SELECT user_id FROM " . $GLOBALS['ecs']->table("users") . " WHERE mobile_phone='$mobile_phone' AND password = '" . MD5($password) ."'");
            if(empty($user_exist))
            {
                $result['error']=1;
                $result['msg']="会员电话或者密码有误！";
                die($json->encode($result));
            }
            else
            {
                $result['error']=0;
                $result['ud']=$user_exist;
                $result['mp']=md5($user_exist.$mobile_phone);
            }
        }
        else
        {
            $user_exist = $GLOBALS['db']->getOne("SELECT user_id FROM " . $GLOBALS['ecs']->table("users") . " WHERE mobile_phone='$mobile_phone' AND password = '" . MD5(MD5($password). $get_user['ec_salt'])."'");
            if(empty($user_exist))
            {
                $result['error']=1;
                $result['msg']="会员电话或者密码有误！";
                die($json->encode($result));
            }
            else
            {
                $result['error']=0;
                $result['ud']=$user_exist;
                $result['mp']=md5($user_exist.$mobile_phone);
            }
        }
    }
    else
    {
        $result['error']=1;
        $result['msg']="无会员信息！";
    }
    die($json->encode($result));
}
else
{
    $user_id = intval($_REQUEST['user_id']);
    if (empty($user_id))
    {
        ecs_header("Location: ./\n");
        exit;
    }
    else
    {
        $get_user=$GLOBALS['db']->getRow('SELECT * FROM ' . $GLOBALS['ecs']->table('users') .
            " WHERE user_id = '$user_id'");
        if(empty($get_user))
        {
            ecs_header("Location: ./\n");
            exit;
        }
    }

    //if (!$smarty->is_cached('affiliate_weixin_login.dwt', $cache_id))
    {
        assign_template();

        $position = assign_ur_here();
        $smarty->assign('page_title',      $position['title']);    // 页面标题
        $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

        /* meta information */
        $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
        $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
        $smarty->assign('user_info',get_user_info_by_user_id($_SESSION['user_id']));

        $smarty->assign('user_detail',$get_user);

        /* 页面中的动态内容 */
        assign_dynamic('v_user_huiyuan');
    }

    $smarty->display('affiliate_login.dwt');
}
?>