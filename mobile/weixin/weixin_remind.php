<?php

require(dirname(__FILE__) . '/api.class.php');
require(dirname(__FILE__) . '/wechat.class.php');

$time = time();

$notice = intval($_GET['notice']);

/*获取消息模板信息*/
$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('weixin_remind'). " WHERE id = 1";
$ret_re = $GLOBALS['db']->getRow($sql);



/*获取手机版地址*/

$wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
$wap_url = $db -> getOne($wap_url_sql);

$access_token = access_token($db);

$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;


//订单提交成功提醒开始
if($notice == 1){
	$wx_user_id = $_GET['is_one_user'];
	
	$order_id = $_GET['order_id'];
	
	if($wx_user_id > 0 &&  $ret_re['buynotice'] == 1) {
	
		$query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";
	
		$ret_w = $db->getRow($query_sql);
	
		$wxid = $ret_w['fake_id'];
	
		$nickname = $ret_w['nickname'];
	
		$orders = $db->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE `order_id` = '$order_id' ");
	
		$order_goods = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . "  WHERE `order_id` = '$order_id'");
	
		$shopinfo = '';
	
		if(!empty($order_goods)) {
			foreach($order_goods as $v) {
				$shopinfo .= $v['goods_name'].'(数量'.$v['goods_number'].'),';
			}
			$shopinfo = substr($shopinfo, 0, strlen($shopinfo)-1);
		}
	
		if($orders['pay_status'] == 0) {
			$pay_status = '支付状态：未付款';
		}elseif($orders['pay_status'] == 1) {
			$pay_status = '支付状态：付款中';
		}
		elseif($orders['pay_status'] == 2) {
			$pay_status = '支付状态：已付款';
		}
	
		$wxch_address = "\r\n收件地址：".$orders['address'];
		$wxch_consignee = "\r\n收件人：".$orders['consignee'];
	
		if($orders['order_amount'] == '0.00') {
			$orders['order_amount'] = $orders['surplus'];
		}
	
		$w_order_sn = $orders['order_sn'];
		$w_order_amount = $orders['order_amount'];
		$w_order_time = date("Y-m-d",$orders['add_time']);
		$w_url = $wap_url."user.php?act=order_detail&order_id=".$order_id;
		
		$w_title = "提单提交成功！请尽快完成付款！";
				
		$post_msg = '{
		   "touser":"'.$wxid.'",
		   "template_id":"'.$ret_re['buymsg'].'",
		   "url":"'.$w_url.'",
		   "topcolor":"#FF0000",
			   "data":{
					   "first": {
						   "value":"'.$w_title.'",
						   "color":"#0000FF"
					   },
					   "keyword1":{
						   "value":"'.$GLOBALS['_CFG']['shop_name'].'",
						   "color":"#0000FF"
					   },
					   "keyword2": {
						   "value":"'.$w_order_time.'",
						   "color":"#0000FF"
					   },
					   "keyword3": {
						   "value":"'.$shopinfo.'",
						   "color":"#FF0000"
					   },

                         "keyword4": {
						   "value":"'.$w_order_amount.'",
						   "color":"#FF0000"
					   },




					   "remark":{
						   "value":"'.$pay_status.'",
						   "color":"#0000FF"
					   }
			   }
		 }';
	  
		$ret_json = curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
		
		if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
			$access_token = access_token($db);
			$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
			$ret_json = curl_grab_page($url, $post_msg);
			$ret = json_decode($ret_json);		

		}
	
	}
}
//订单提交成功提醒end

//订单支付成功提醒开始
elseif($notice == 2){
	$wx_user_id = $_GET['is_one_user'];
	
	$order_id = $_GET['order_id'];
	
	  $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";
	
		$ret_w = $db->getRow($query_sql);
	
		$wxid = $ret_w['fake_id'];
	
		$nickname = $ret_w['nickname'];
	
		$orders = $db->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE `order_id` = '$order_id' ");
	
		$order_goods = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . "  WHERE `order_id` = '$order_id'");
	
		$shopinfo = '';

		if(!empty($order_goods)) {
			foreach($order_goods as $v) {
				$shopinfo .= $v['goods_name'].'(数量'.$v['goods_number'].'),';
			}
			$shopinfo = substr($shopinfo, 0, strlen($shopinfo)-1);
		}
	
		if($orders['pay_status'] == 0) {
			$pay_status = '支付状态：未付款';
		}elseif($orders['pay_status'] == 1) {
			$pay_status = '支付状态：付款中';
		}
		elseif($orders['pay_status'] == 2) {
			$pay_status = '支付状态：已付款';
		}
	
		$wxch_address = "\r\n收件地址：".$orders['address'];
		$wxch_consignee = "\r\n收件人：".$orders['consignee'];
	
		if($orders['order_amount'] == '0.00') {
			$orders['order_amount'] = $orders['goods_amount'];
		}
	
	if($wx_user_id > 0 &&  $ret_re['paytice'] == 1) //支付成功提醒本人
	    {

		$w_order_sn = $orders['order_sn'];
		$w_order_amount = $orders['order_amount'];
		$w_order_time = date("Y-m-d",$orders['add_time']);
		$w_url = $wap_url."user.php?act=order_detail&order_id=".$order_id;
		
		$w_title = "我们已收到您的货款，开始为您打包商品，请耐心等待 : )";
				
		$post_msg = '{
		   "touser":"'.$wxid.'",
		   "template_id":"'.$ret_re['paymsg'].'",
		   "url":"'.$w_url.'",
		   "topcolor":"#FF0000",
			   "data":{
					   "first": {
						   "value":"'.$w_title.'",
						   "color":"#0000FF"
					   },
					   "name": {
						   "value":"'.$shopinfo.'",
						   "color":"#FF0000"
					   },
					   "Remark":{
						   "value":"如有问题请联系在线客服或直接在微信留言，我们将第一时间为您服务！",
						   "color":"#0000FF"
					   }
			   }
		 }';

		$ret_json = curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);

		if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
			$access_token = access_token($db);
			$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
			$ret_json = curl_grab_page($url, $post_msg);
			$ret = json_decode($ret_json);		

		}
	
	}
	
	if($ret_re['buysuppliertice'] == 1) { //支付成功提醒管理员/商家
	      
		 if($orders['supplier_id'] > 0) {//提醒商家
		 
		 $supplier_id = $orders['supplier_id'];
		 
		 $user_id_sql = "SELECT `user_id` FROM  " . $GLOBALS['ecs']->table('supplier') . " WHERE `supplier_id`='$supplier_id'";
	
		 $user_id =  $GLOBALS['db'] -> getOne($user_id_sql);
		 
		 $user_wxid_sql = "SELECT `fake_id` FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$user_id'";
	
		 $wxid =  $GLOBALS['db'] -> getOne($user_wxid_sql);
		 
		 $w_title = "亲爱的【".$orders['referer']."】店长，您的店铺有了新订单，请及时处理  : )";
		 
		}else{//提醒管理员
		
		 $admin_id_sql = "SELECT `admin_id` FROM  " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id`=1";
	
		 $admin_id =  $GLOBALS['db'] -> getOne($admin_id_sql);
		 
		 $admin_wxid_sql = "SELECT `fake_id` FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$admin_id'";
	
		 $wxid =  $GLOBALS['db'] -> getOne($admin_wxid_sql);
		 
		 $w_title = "亲爱的管理员，您的网站有了新订单，请及时处理  : )";
		}
	
		$w_order_sn = $orders['order_sn'];
		$w_order_amount = $orders['order_amount'];
		$w_order_time = date("Y-m-d",$orders['add_time']);
		$w_url = $wap_url."user.php?act=order_detail&order_id=".$order_id;
				
		$post_msg = '{
		   "touser":"'.$wxid.'",
		   "template_id":"'.$ret_re['buysuppliermsg'].'",
		   "url":"'.$w_url.'",
		   "topcolor":"#FF0000",
			   "data":{
					   "first": {
						   "value":"'.$w_title.'",
						   "color":"#FF0000"
					   },
					   "keyword1":{
						   "value":"'.$w_order_sn.'",
						   "color":"#0000FF"
					   },
					   "keyword2": {
						   "value":"'.$shopinfo.'",
						   "color":"#0000FF"
					   },
					   "keyword3": {
						   "value":"'.$w_order_amount.'",
						   "color":"#0000FF"
					   },
					   "keyword4": {
						   "value":"'.$w_order_time.'",
						   "color":"#0000FF"
					   },
					   "remark":{
						   "value":"亲爱的客服MM，您尽快发货啦，捉急哦，抓紧哦~",
						   "color":"#FF0000"
					   }
			   }
		 }';
	  
		$ret_json = curl_grab_page($url, $post_msg);
		$ret = json_decode($ret_json);
		
		if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {
			$access_token = access_token($db);
			$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
			$ret_json = curl_grab_page($url, $post_msg);
			$ret = json_decode($ret_json);		

		}
	
	}
	
	  

	$up_uid_sql = "SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$wx_user_id'";;

	$up_uid =  $GLOBALS['db'] -> getOne($up_uid_sql);
	
	$my_wxname_sql = "SELECT nickname FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";
	
	$my_wxname =  $GLOBALS['db'] -> getOne($my_wxname_sql);
	

    if($up_uid > 0 &&  $ret_re['payuptice'] == 1) { //付款成功提醒上级
	
	  if($orders['pay_status'] == 0) {
			$pay_status = '未付款';
		}elseif($orders['pay_status'] == 1) {
			$pay_status = '付款中';
		}
		elseif($orders['pay_status'] == 2) {
			$pay_status = '已付款';
		}
	
	    $num = 3;

	    for ($i=0; $i < $num; $i++){

	   	$query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$up_uid'";
	
		$ret_w = $db->getRow($query_sql);
	
		$wxid = $ret_w['fake_id'];
		
		$num_hanbing=$i+1;

		//获取上级微信信息

	
    if(!empty($wxid)){

	$w_title="您的".$num_hanbing."级会员【".$my_wxname."】付款了";

	$w_description="下级会员消费您都将有提成哦！";

	$w_time = date("Y-m-d",time());

	$w_url=$wap_url."v_user_huiyuan_list.php?u=".$wx_user_id."&user_id=".$up_uid;
	
	$post_msg = '{

       "touser":"'.$wxid.'",

	   "template_id":"'.$ret_re['payupmsg'].'",

	   "url":"'.$w_url.'",

	   "topcolor":"#FF0000",

           "data":{

                   "first": {

                       "value":"'.$w_title.'",

                       "color":"#FF0000"

                   },

                   "keyword1":{

                       "value":"'.$shopinfo.'",

                       "color":"#0000FF"

                   },

                   "keyword2": {

                       "value":"待分成",

                       "color":"#0000FF"

                   },
                    "keyword3": {

                       "value":"'.$pay_status.'",

                       "color":"#0000FF"

                   },
                   "remark":{

                       "value":"'.$w_description.'",

                       "color":"#FF0000"

                   }

           }



   }';

    $ret_json = curl_grab_page($url, $post_msg);

	$ret = json_decode($ret_json);

	if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {

		$access_token = access_token($db);

		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

		$ret_json = curl_grab_page($url, $post_msg);

		$ret = json_decode($ret_json);

	}

		$wxid='';

		$qu_wxid = "SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$up_uid'";;

		$parent_wxid = $db -> getRow($qu_wxid);

		$up_uid = $parent_wxid['parent_id'];

	}else{

		$i=3;

		}

  }

 }
	
}
//订单支付成功提醒end


/*******订单标记为发货提醒用户*********/

elseif($notice == 4){

  $order_id = $_GET['order_id'];//获取订单ID
   
  $wx_user_id = $_GET['is_one_user'];
  if($wx_user_id > 0 &&  $ret_re['sendnotice'] == 1) {
 
	$query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";
	
		$ret_w = $db->getRow($query_sql);

		$wxid = $ret_w['fake_id'];
		
		$wx_name = $ret_w['nickname'];
	
		$orders = $db->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE `order_id` = '$order_id' ");
	
		$order_goods = $db->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . "  WHERE `order_id` = '$order_id'");
	
		$shopinfo = '';
	
		if(!empty($order_goods)) {
			foreach($order_goods as $v) {
				$shopinfo .= $v['goods_name'].'(数量'.$v['goods_number'].'),';
			}
			$shopinfo = substr($shopinfo, 0, strlen($shopinfo)-1);
		}
	
		if($orders['pay_status'] == 0) {
			$pay_status = '支付状态：未付款';
		}elseif($orders['pay_status'] == 1) {
			$pay_status = '支付状态：付款中';
		}
		elseif($orders['pay_status'] == 2) {
			$pay_status = '支付状态：已付款';
		}
	
		$wxch_address = "\r\n收件地址：".$orders['address'];
		$wxch_consignee = "\r\n收件人：".$orders['consignee'];
	
		if($orders['order_amount'] == '0.00') {
			$orders['order_amount'] = $orders['surplus'];
		}


		
	if(!empty($wxid)){

	$w_title = "亲爱的【".$wx_name."】您购买的商品商家已发货！";
	
	$w_order_sn = $orders['order_sn'];
	
	$w_order_time = date("Y-m-d",$orders['add_time']);
	
	$w_url = $wap_url."user.php?act=order_detail&order_id=".$order_id;

	$w_order_amount = "￥".$orders['goods_amount'];

	$wx_consignee = "收件人:".$orders['consignee']."   电话：".$orders['mobile'];

	$wx_goods_name = $order_goods['goods_name'];

	$post_msg = '{

       "touser":"'.$wxid.'",

	   "template_id":"'.$ret_re['sendmsg'].'",

	   "url":"'.$w_url.'",

	   "topcolor":"#FF0000",

           "data":{

                   "first": {

                       "value":"'.$w_title.'",

                       "color":"#0000FF"

                   },

                   "orderProductPrice":{

                       "value":"'.$w_order_amount.'",

                       "color":"#FF0000"

                   },

                   "orderProductName": {

                       "value":"'.$shopinfo.'",

                       "color":"#FF0000"

                   },

                   "orderAddress": {

                       "value":"'.$wx_consignee.'",

                       "color":"#FF0000"

                   },
				   "orderName": {

                       "value":"'.$w_order_sn.'",

                       "color":"#FF0000"

                   },

                    "remark":{

                       "value":"请您收到货后及时好评，如有疑问联系在线客服,谢谢！",

                       "color":"#FF0000"

                   }

           }



   }';
	$ret_json = request_post_new($url, $post_msg);
	$ret = json_decode($ret_json);

	if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {

		$access_token = access_token($db);

		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

		$ret_json = curl_grab_page($url, $post_msg);

		$ret = json_decode($ret_json);

	}

	}

}

}

/*****************会员加入提醒上级**********************/

elseif($notice == 5){

    $num = 3;

	$up_uid = $_GET['up_uid'];

    if($up_uid > 0 &&  $ret_re['jointice'] == 1) { //提醒开关

	for ($i=0; $i < $num; $i++)

	{

	   	$query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$up_uid'";
	
		$ret_w = $db->getRow($query_sql);
	
		$wxid = $ret_w['fake_id'];
		
		$wx_name = $ret_w['nickname'];

		//获取上级微信信息
      $num_hanbing=$i+1;
	
    if(!empty($wxid)){

	$my_name = $_GET['my_name'];

	$w_title="哇！微信好友【".$my_name."】通过分享成为了您的".$num_hanbing."级会员，赶紧看看吧！";

	$w_description="新朋友的消费您都将有提成哦！";

	$w_time = date("Y-m-d",time());

	$w_url=$wap_url."v_user_huiyuan.php";

	$post_msg = '{

       "touser":"'.$wxid.'",

	   "template_id":"'.$ret_re['joinmsg'].'",

	   "url":"'.$w_url.'",

	   "topcolor":"#FF0000",

           "data":{

                   "first": {

                       "value":"'.$w_title.'",

                       "color":"#0000FF"

                   },

                   "keyword1":{

                       "value":"'.$my_name.'",

                       "color":"#0000FF"

                   },

                   "keyword2": {

                       "value":"'.$w_time.'",

                       "color":"#0000FF"

                   },

                   "remark":{

                       "value":"'.$w_description.'",

                       "color":"#FF0000"

                   }

           }



   }';

    $ret_json = curl_grab_page($url, $post_msg);

	$ret = json_decode($ret_json);

	if($ret->errmsg != 'ok' ||  empty($ret->errmsg)) {

		$access_token = access_token($db);

		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

		$ret_json = curl_grab_page($url, $post_msg);

		$ret = json_decode($ret_json);

	}

		$wxid='';

		$qu_wxid = "SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$up_uid'";;

		$parent_wxid = $db -> getRow($qu_wxid);

		$up_uid = $parent_wxid['parent_id'];

	}else{

		$i=3;

		}

  }

 }

}//会员加入提醒上级end

/*****************提现成功**********************/

elseif($notice == 10){

    $num = 3;

    $wx_user_id = $_GET['is_one_user'];
    $wx_money = $_GET['money'];
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        $wx_user_id = $_GET['is_one_user'];

        $order_id = $_GET['order_id'];

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $db->getRow($query_sql);

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
                $access_token = access_token($db);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }

    }

}//提现成功

/*****************佣金提成**********************/

elseif($notice == 11){

    $num = 3;

    $wx_user_id = $_GET['is_one_user'];
    $wx_money = $_GET['money'];
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        $wx_user_id = $_GET['is_one_user'];

        $order_id = $_GET['order_id'];

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $db->getRow($query_sql);

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
                $access_token = access_token($db);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }

    }

}//佣金提成

/****************分校月初提成到账**********************/

elseif($notice == 12){

    $num = 3;

    $wx_user_id = $_GET['is_one_user'];
    $wx_money = $_GET['money'];
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        $wx_user_id = $_GET['is_one_user'];

        $order_id = $_GET['order_id'];

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $db->getRow($query_sql);

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
                $access_token = access_token($db);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }

    }

}//分校月初提成到账

/****************会员加入**********************/

elseif($notice == 13){

    $num = 3;

    $wx_user_id = $_GET['is_one_user'];
    $jr_one_user = $_GET['jr_one_user'];
    $wx_money = $_GET['money'];
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        $wx_user_id = $_GET['is_one_user'];

        $order_id = $_GET['order_id'];

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $db->getRow($query_sql);

            $query_sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$jr_one_user'";

            $user_name = $db->getOne($query_sql);

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
                $access_token = access_token($db);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }

    }

}//会员加入

/****************一级代理加入通知**********************/

elseif($notice == 14){

    $num = 3;

    $wx_user_id = $_GET['is_one_user'];
    $jr_one_user = $_GET['jr_one_user'];
    $wx_money = $_GET['money'];
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        $wx_user_id = $_GET['is_one_user'];

        $order_id = $_GET['order_id'];

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $db->getRow($query_sql);

            $query_sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$jr_one_user'";

            $user_name = $db->getOne($query_sql);

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
						   "value":"'."普通会员".'",
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
                $access_token = access_token($db);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }

    }

}//一级代理加入通知

elseif($notice == 15){

    $num = 3;

    $wx_user_id = $_GET['is_one_user'];
    $old_rank = $_GET['old_rank'];
    $now_rank = $_GET['now_rank'];
    $w_url = $wap_url."user.php";
    if($wx_user_id > 0) { //提醒开关

        $wx_user_id = $_GET['is_one_user'];

        $order_id = $_GET['order_id'];

        if($wx_user_id > 0) {

            $query_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE ecuid = '$wx_user_id'";

            $ret_w = $db->getRow($query_sql);

            $query_sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$jr_one_user'";

            $user_name = $db->getOne($query_sql);

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
                $access_token = access_token($db);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
                $ret_json = curl_grab_page($url, $post_msg);
                $ret = json_decode($ret_json);

            }

        }

    }

}//会员等级变更通知

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