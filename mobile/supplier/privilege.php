<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_supplier_common_wap.php');
$_REQUEST['act'] = empty($_REQUEST['act'])?'':trim($_REQUEST['act']);

/*------------------------------------------------------ */
//-- 退出登录
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'logout')
{
    /* 清除cookie */
    setcookie('ECSCP[supplier_id]',   '', 1);
    setcookie('ECSCP[supplier_user_id]',   '', 1);
    setcookie('ECSCP[supplier_pass]', '', 1);

    $sess->destroy_session();

    $_REQUEST['act'] = 'login';
}

/*------------------------------------------------------ */
//-- 登录界面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'login')
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    if ((intval($_CFG['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0)
    {
        $smarty->assign('gd_version', gd_version());
        $smarty->assign('random',     mt_rand());
    }
    _wap_assign_header_info('登录','登录',1,1,0);
    _wap_display_page('login.htm');
}

/*------------------------------------------------------ */
//-- 验证登录
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'signin')
{
    $_POST['username'] = isset($_POST['username']) ? trim($_POST['username']) : '';
    $_POST['password'] = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if(empty($_POST['username']))
    {
        sys_msg('用户名不能为空！',1);
    }
    if(empty($_POST['password']))
    {
        sys_msg('密码不能为空！',1);
    }

    //判断是否开启验证码
    if ((intval($_CFG['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0)
    {
        if(empty($_POST['captcha']))
        {
            sys_msg('请输入验证码！',1);
        }
        else
        {
            include_once(ROOT_PATH . 'includes/cls_captcha.php');
            $validator = new captcha();
            if(!$validator->check_word($_POST['captcha']))
            {
                sys_msg('验证码错误！',1);
            }
        }
    }

 	$user_name = $_POST['username'];
	
	if(is_email($user_name))
	{
		$sql = "select user_name from " . $ecs->table('supplier_admin_user') . " where email='" . $user_name . "'";
		$username_email = $db->getOne($sql);
		if($username_email)
		{
			$user_name = $username_email;
		}
	}
	else if(is_mobile_phone($user_name))
	{
		$sql = "select user_name from " . $ecs->table('supplier_admin_user') . " where mobile_phone='" . $user_name . "'";
		$rows = $db->query($sql);
		$i = 0;
		while($row = $db->fetchRow($rows))
		{
			$username_mobile = $row['user_name'];
			$i = $i + 1;
		}
		if($i > 1)
		{
			show_message('本网站有多个会员ID绑定了和您相同的手机号，请使用其他登录方式，如：邮箱或用户名。', $_LANG['relogin_lnk'], 'user.php', 'error');
		}
		if(isset($username_mobile))
		{
			$user_name = $username_mobile;
		}
	}
		
	$sql = "SELECT `ec_salt` FROM " . $ecs->table('supplier_admin_user') . " WHERE user_name = '" . $user_name . "'";
	$ec_salt = $db->getOne($sql);
	if(! empty($ec_salt))
	{
		/* 检查密码是否正确 */
		$sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,supplier_id,ec_salt" . " FROM " . $ecs->table('supplier_admin_user') . " WHERE user_name = '" . $user_name . "' AND password = '" . md5(md5($_POST['password']) . $ec_salt) . "' AND checked=1";
	}
	else
	{
		/* 检查密码是否正确 */
		$sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,supplier_id,ec_salt" . " FROM " . $ecs->table('supplier_admin_user') . " WHERE user_name = '" . $user_name . "' AND password = '" . md5($_POST['password']) . "'  AND checked=1";
	}
	$row = $db->getRow($sql);
    if ($row)
    {
        // 登录成功
    	$_SESSION['supplier_id'] = $row['supplier_id'];//店铺的id
		$_SESSION['supplier_user_id'] = $row['user_id'];//管理员id
		$_SESSION['supplier_name']  = $row['user_name'];//管理员名称
		$_SESSION['supplier_action_list'] = $row['action_list'];//管理员权限
    	$_SESSION['supplier_last_check']  = $row['last_login']; // 用于保存最后一次检查订单的时间
        set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_login']);
		if(empty($row['ec_salt']))
	    {
			$ec_salt=rand(1,9999);
			$md5_password=md5(md5($_POST['password']).$ec_salt);
             $db->query("UPDATE " .$ecs->table('supplier_admin_user').
                 " SET ec_salt='" . $ec_salt . "', password='" .$md5_password . "'".
                 " WHERE user_id='$_SESSION[supplier_user_id]'");
		}

        if($row['action_list'] == 'all')
        {
        	$_SESSION['supplier_admin_id'] = $row['user_id'];//超级管理员的标识管理员id
            $_SESSION['supplier_shop_guide'] = true;//超级管理员标识
        }

        // 更新最后登录时间和IP
        $db->query("UPDATE " .$ecs->table('supplier_admin_user').
                 " SET last_login='" . gmtime() . "', last_ip='" . real_ip() . "'".
                 " WHERE user_id='$_SESSION[supplier_user_id]'");

        if (isset($_REQUEST['remember']))
        {
            $time = gmtime() + 3600 * 24 * 365;
            setcookie('ECSCP[supplier_id]',   $row['supplier_id'],                            $time);
			setcookie('ECSCP[supplier_user_id]',   $row['user_id'],                            $time);
            setcookie('ECSCP[supplier_pass]', md5($md5_password.$_CFG['hash_code']), $time);
        }

        ecs_header("Location: ./index.php\n");
        exit;
    }
    else
    {
        sys_msg('用户名和密码不匹配！',1);
    }
}
function is_mobile_phone ($mobile_phone)
{
	
	$chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
	if(preg_match($chars, $mobile_phone))
	{
		return true;
	}
	
	return false;
}