<?php
/**
 * ECSHOP 文章及文章分类相关函数库
 * ============================================================================
 * 版权所有 2005-2011 商派网络，并保留所有权利。
 * ecshop.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_article.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}



/*****************提现成功**********************/

function tixian($wx_user_id,$wx_money)
{


    //require(dirname(__FILE__) . '/api.class.php');
    //require(dirname(__FILE__) . '/wechat.class.php');
    /*获取手机版地址*/

    $wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
    $wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);

    $access_token = access_token( $GLOBALS['db']);

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w =  $GLOBALS['db']->getRow($query_sql);
            if(!empty($ret_w))
            {
                $wxid = $ret_w['fake_id'];

                $nickname = $ret_w['nickname'];

                $w_title = "提现成功！";

                $post_msg = '{
		   "touser":"'.$wxid.'",
		   "template_id":"'."_hZaVFzkENjvQck-XhVLMP_PqEs0Xs4zw9jc8NMB5Xo".'",
		   "url":"'.$w_url.'",
		   "topcolor":"#FF0000",
			   "data":{
					   "first": {
						   "value":"'.$w_title.'",
						   "color":"#0000FF"
					   },
					   "keyword1":{
						   "value":"'.$wx_money.'元",
						   "color":"#0000FF"
					   },
					   "keyword2": {
						   "value":"'.local_date($GLOBALS['_CFG']['time_format'], gmtime()).'",
						   "color":"#0000FF"
					   },
					   "remark":{
						   "value":"'."提现成功，请注意查收，如有疑问联系在线客服,谢谢！".'",
						   "color":"#0000FF"
					   }
			   }
		 }';
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

                if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
                    $access_token = access_token( $GLOBALS['db']);
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                    $ret_json = curl_grab_page($url, $post_msg);
                    $ret = json_decode($ret_json);

                }

            }
        }

    }

}

/****************会员加入**********************/

function huiyuan_join($is_one_user,$jr_one_user)
{

    $wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
    $wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);

    $access_token = access_token( $GLOBALS['db']);

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

    $wx_user_id = $is_one_user;
    $jr_one_user = $jr_one_user;
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关
        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $GLOBALS['db']->getRow($query_sql);
            if(!empty($ret_w))
            {
                $query_sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$jr_one_user'";

                $user_name = $GLOBALS['db']->getOne($query_sql);

                $wxid = $ret_w['fake_id'];

                $nickname = $ret_w['nickname'];

                $w_title = "下级加入通知";

                $post_msg = '{
               "touser":"'.$wxid.'",
               "template_id":"'."GbprrPMDCZZObn4jlvgiEIvkO_DTO_3ppmRzOUlnVNo".'",
               "url":"'.$w_url.'",
               "topcolor":"#FF0000",
                   "data":{
                           "first": {
                               "value":"'.$w_title.'",
                               "color":"#0000FF"
                           },
                           "keyword1":{
                               "value":"'.local_date($GLOBALS['_CFG']['time_format'], gmtime()).'",
                               "color":"#0000FF"
                           },
                           "keyword2": {
                               "value":"'.$user_name.'",
                               "color":"#0000FF"
                           },
                           "keyword3": {
                               "value":"'."普通会员".'",
                               "color":"#0000FF"
                           },
                           "remark":{
                               "value":"'."下级加入成功通知，请注意查收，如有疑问联系在线客服,谢谢！".'",
                               "color":"#0000FF"
                           }
                   }
             }';

                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

                if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
                    $access_token = access_token($GLOBALS['db']);
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                    $ret_json = curl_grab_page($url, $post_msg);
                    $ret = json_decode($ret_json);

                }
            }
        }

    }
}

function rank_change($wx_user_id,$old_rank,$now_rank)
{

$wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
$wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);

$access_token = access_token( $GLOBALS['db']);

$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

$w_url = $wap_url."user.php";
if($wx_user_id > 0) { //提醒开关


    if($wx_user_id > 0) {

        $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

        $ret_w = $GLOBALS['db']->getRow($query_sql);
        if(!empty($ret_w))
        {


            $wxid = $ret_w['fake_id'];

            $nickname = $ret_w['nickname'];

            $w_title = "会员等级变更通知";

            $post_msg = '{
		   "touser":"'.$wxid.'",
		   "template_id":"'."CnnSj7-1JTdyzw1qwKZLdKKKazVCKiESH8AF7rOcskY".'",
		   "url":"'.$w_url.'",
		   "topcolor":"#FF0000",
			   "data":{
					   "first": {
						   "value":"'.$w_title.'",
						   "color":"#0000FF"
					   },
					   "keyword1":{
						   "value":"'.$old_rank.'",
						   "color":"#0000FF"
					   },
					   "keyword2": {
						   "value":"'.$now_rank.'",
						   "color":"#0000FF"
					   },
					   "remark":{
						   "value":"'."会员等级变更通知，请注意查收，如有疑问联系在线客服,谢谢！".'",
						   "color":"#0000FF"
					   }
			   }
		 }';

            $ret_json = curl_grab_page($url, $post_msg);
            $ret = json_decode($ret_json);

            if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
                $access_token = access_token($GLOBALS['db']);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }
    }

}

}//会员等级变更通知

function daili_jiaru($wx_user_id,$jr_one_user)
{

    $wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
    $wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);

    $access_token = access_token( $GLOBALS['db']);

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $GLOBALS['db']->getRow($query_sql);
            if(!empty($ret_w))
            {
                $query_sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$jr_one_user'";

                $user_name = $GLOBALS['db']->getOne($query_sql);

                $wxid = $ret_w['fake_id'];

                $nickname = $ret_w['nickname'];

                $w_title = "一级代理加入通知";

                $post_msg = '{
               "touser":"'.$wxid.'",
               "template_id":"'."GbprrPMDCZZObn4jlvgiEIvkO_DTO_3ppmRzOUlnVNo".'",
               "url":"'.$w_url.'",
               "topcolor":"#FF0000",
                   "data":{
                           "first": {
                               "value":"'.$w_title.'",
                               "color":"#0000FF"
                           },
                           "keyword1":{
                               "value":"'.local_date($GLOBALS['_CFG']['time_format'], gmtime()).'",
                               "color":"#0000FF"
                           },
                           "keyword2": {
                               "value":"'.$user_name.'",
                               "color":"#0000FF"
                           },
                           "keyword3": {
                               "value":"'."一级代理加入".'",
                               "color":"#0000FF"
                           },
                           "remark":{
                               "value":"'."一级代理加入通知，请注意查收，如有疑问联系在线客服,谢谢！".'",
                               "color":"#0000FF"
                           }
                   }
             }';

                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

                if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
                    $access_token = access_token($GLOBALS['db']);
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                    $ret_json = curl_grab_page($url, $post_msg);
                    $ret = json_decode($ret_json);

                }
            }
        }

    }

}//一级代理加入通知

/*****************佣金提成**********************/

function yongjin($wx_user_id,$wx_money)
{
    $wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
    $wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);

    $access_token = access_token( $GLOBALS['db']);

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

    $w_url = $wap_url."user.php";

    if($wx_user_id > 0) { //提醒开关
        if($wx_user_id > 0) {
            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";
            $ret_w = $GLOBALS['db']->getRow($query_sql);
            if(!empty($ret_w))
            {
                $wxid = $ret_w['fake_id'];

                $nickname = $ret_w['nickname'];

                $w_title = "佣金提成到账通知";

                $post_msg = '{
               "touser":"'.$wxid.'",
               "template_id":"'."NP5jsjy7ILGQagjUX7m9_mQmS3bbgA9PlY0PoBn7Kyc".'",
               "url":"'.$w_url.'",
               "topcolor":"#FF0000",
                   "data":{
                           "first": {
                               "value":"'.$w_title.'",
                               "color":"#0000FF"
                           },
                           "keyword1":{
                               "value":"'.$wx_money.'元",
                               "color":"#0000FF"
                           },
                           "keyword2": {
                               "value":"'.local_date($GLOBALS['_CFG']['time_format'], gmtime()).'",
                               "color":"#0000FF"
                           },
                           "remark":{
                               "value":"'."佣金提成到账，请注意查收，如有疑问联系在线客服,谢谢！".'",
                               "color":"#0000FF"
                           }
                   }
             }';

                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

                if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
                    $access_token = access_token($GLOBALS['db']);
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                    $ret_json = curl_grab_page($url, $post_msg);
                    $ret = json_decode($ret_json);

                }
            }
        }

    }

}//佣金提成

/****************分校月初提成到账**********************/

function fenxiao_tic($wx_user_id,$wx_money){

    $wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
    $wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);

    $access_token = access_token( $GLOBALS['db']);

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关
        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $GLOBALS['db']->getRow($query_sql);
            if(!empty($ret_w))
            {
                $wxid = $ret_w['fake_id'];

                $nickname = $ret_w['nickname'];

                $w_title = "分校月初提成到账";

                $post_msg = '{
               "touser":"'.$wxid.'",
               "template_id":"'."NP5jsjy7ILGQagjUX7m9_mQmS3bbgA9PlY0PoBn7Kyc".'",
               "url":"'.$w_url.'",
               "topcolor":"#FF0000",
                   "data":{
                           "first": {
                               "value":"'.$w_title.'",
                               "color":"#0000FF"
                           },
                           "keyword1":{
                               "value":"'.$wx_money.'元",
                               "color":"#0000FF"
                           },
                           "keyword2": {
                               "value":"'.local_date($GLOBALS['_CFG']['time_format'], gmtime()).'",
                               "color":"#0000FF"
                           },
                           "remark":{
                               "value":"'."分校月初提成到账，请注意查收，如有疑问联系在线客服,谢谢！".'",
                               "color":"#0000FF"
                           }
                   }
             }';

                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

                if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
                    $access_token = access_token($GLOBALS['db']);
                    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                    $ret_json = curl_grab_page($url, $post_msg);
                    $ret = json_decode($ret_json);

                }
            }
        }

    }

}//分校月初提成到账

function access_token($db) {
	$time = time();
	$ret = $db->getRow("SELECT * FROM ". $GLOBALS['ecs']->table('weixin_config') ." WHERE `id` = 1");
	$appid = $ret['appid'];
	$appsecret = $ret['appsecret'];
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
	$ret_json = request_post($url);

	$ret = json_decode($ret_json);
	return $ret->access_token;
}



function curl_get_contents($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$r = curl_exec($ch);
	curl_close($ch);
	return $r;
}

/*function curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') {
    $header = array('Expect:');  
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($proxystatus == 'true') {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	if(!empty($ref_url)){
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_REFERER, $ref_url);
	}
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	ob_start();
	return curl_exec ($ch);
	ob_end_clean();
	curl_close ($ch);
	unset($ch);

}*/
function curl_grab_page($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}
function request_post($url = '') {
    if (empty($url)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = "ceshi";
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}
function request_post_new($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}
?>