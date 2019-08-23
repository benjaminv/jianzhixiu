<?php
/****************************************************************************/
//商城对接oddo接口。实现会员，订单，商品的同步。
//本文件为客户端,加上鉴权，保证不被破解访问
/****************************************************************************/
//商城对接erp接口，订单模块
//update更新订单状态，以及回传发货单
/*秘钥为hash_code和auth_key的md5 正式环境下回更换auth_key*/
//49250e5cf855b6e6ec4a3ba0c24f9b24
//hash_code:31693422540744c0a6b6da635b7a5a93
//key:*5t8bw5HQKoba!3O$inTstcIhdGLIzMV
define('IN_ECS', true);
require('./init.php');

//error_reporting(E_ALL);


$_POST = json_decode(file_get_contents('php://input'),1);
$hash_code = $db->getOne("SELECT `value` FROM " . $ecs->table('shop_config') . " WHERE `code`='hash_code'", true);
$auth = md5(AUTH_KEY.$hash_code);
file_put_contents('./log.text',date('Y-m-d H:i:s').":".json_encode($_POST).PHP_EOL, FILE_APPEND);

//echo json_encode(array("result"=>"test","code"=>444,"msg"=>$_POST));exit;
$orderActionList = array("update",'delivery','deposit','refund');
if($auth != $_POST['auth']){
	echo json_encode(array("result"=>"fail","code"=>400,"msg"=>"鉴权失败"));exit;
}
$action = $_POST['action'];

if(!in_array($action,$orderActionList)){
	
	echo json_encode(array("result"=>"fail","code"=>401,"msg"=>"非法操作"));exit;
}

$function_name = 'api_action_' . $action;
if(! function_exists($function_name)){
	
	echo json_encode(array("result"=>"fail","code"=>401,"msg"=>"非法操作"));exit;
}
call_user_func($function_name);

/*
订单order_info
order_status 1已确认2取消订单，3无效，4退货，5分单，6部分分单，
shipping_status 0未发货1已发货 2收货确认 3配货中 4已发货（部分商品）5发货中
发货单delivery_order
status 其他 已发货 1退货 2正常
*/
/*更新订单接口，根据返回来的状态进行更新*/
function api_action_update(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$data = $_POST['data'];
	$data['shipping_status'] = intval($data['shipping_status'])?intval($data['shipping_status']):0;
	$order_sn = $data['order_sn'];
	if(!isset($order_sn) || empty($order_sn)){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数order_sn"));exit;
	}
	$orderInfo = $db->getRow("SELECT order_id,order_sn,user_id,order_status,shipping_status,pay_status,consignee,country,province,city,district,address,mobile,shipping_id,shipping_name,zipcode,email,best_time,how_oos,shipping_fee FROM " .$ecs->table('order_info'). " WHERE order_sn = '".$order_sn."'");
	if(!isset($orderInfo) || empty($orderInfo)){
		echo json_encode(array("result"=>"fail","code"=>304,"msg"=>"不存在的订单"));exit;
	}
	if(in_array($data['shipping_status'],array(1,3,4))){
		//更新订单状态
		$sql = "UPDATE " . $ecs->table('order_info') . " SET shipping_status = ".$data['shipping_status']." WHERE order_sn = '".$order_sn."'";
		$db->query($sql); 
	}
	
	//更新物流状态
	switch($data['shipping_status']){
		case "1"://已发货
		case"4"://已发货（部分商品）
			if(isset($data['delivery']) && !empty($data['delivery'])){
				$delivery = array(
					"delivery_sn"=>$data['delivery']['delivery_sn'],
					"order_sn"=>$order_sn,
					"order_id"=>$orderInfo['order_id'],
					"invoice_no"=>$data['delivery']['invoice_no'],//物流号
					"shipping_id"=>$data['delivery']['shipping_id'],
					"shipping_name"=>$data['delivery']['shipping_name'],
					"user_id"=>$orderInfo['user_id'],
					"action_user"=>$data['delivery']['action_user'],
					"consignee"=>$orderInfo['consignee'],
					"country"=>$orderInfo['country'],
					"city"=>$orderInfo['city'],
					"district"=>$orderInfo['district'],
					"address"=>$orderInfo['address'],
					"province"=>$orderInfo['province'],
					"how_oos"=>$orderInfo['how_oos'],
					"shipping_fee"=>$orderInfo['shipping_fee'],
						"zipcode"=>$orderInfo['zipcode'],
						"email"=>$orderInfo['email'],
						"best_time"=>$orderInfo['best_time'],
						"mobile"=>$orderInfo['mobile'],
					'update_time'=>time(),
					'add_time'=>time()
				);

				if(isset($data['delivery']['shipping_id']) && !empty($data['delivery']['shipping_id'])){
					$delivery['shipping_id'] = $data['delivery']['shipping_id'];
				}

				//新增一张发货单
				do{
					if ($db->autoExecute($ecs->table('delivery_order'), $delivery, 'INSERT', '', 'SILENT')){
						break;
					}else{
						if ($db->errno() != 1062){
							echo json_encode(array("result"=>"fail","code"=>301,"msg"=>"发货单新增出错"));exit;
						}
					}
				}while (true); // 防止订单号重复
				$deliveryId = $db->insert_id();

				//发货单商品表
				foreach($data['delivery']['goodsInfo'] as $v){
					//查询订单该商品已发货数量
					//$sendsql = "SELECT send_number,goods_number FROM ".$ecs->table('order_goods')." WHERE order_id='".$orderInfo['order_id']."' AND goods_id = ".$v['goods_id']." AND product_id=".$v['product_id'];
					//$order_goods_info = $db->getRow($sendsql);
					//$left_number  = $order_goods_info['goods_number']-$order_goods_info['send_number'];//商品未发货数量。


					$deliveryGoods = array(
						"delivery_id"=>$deliveryId,
						"goods_id"=>$v['goods_id'],
						"product_id"=>$v['product_id'],
						"product_sn"=>$v['product_sn'],
						"goods_name"=>$v['goods_name'],
						"brand_name"=>$v['brand_name'],
						"goods_sn"=>$v['goods_sn'],
						"send_number"=>$v['send_number'],
						"goods_attr"=>$v['goods_attr'],
						"is_real"=>1,
					);

					$db->autoExecute($ecs->table('delivery_goods'), $deliveryGoods, 'INSERT', '', 'SILENT');

					//更新订单该商品已发货数量
					$sendsql = "UPDATE ".$ecs->table('order_goods')." SET  send_number = send_number+".abs(intval($v['send_number']))." WHERE order_id='".intval($orderInfo['order_id'])."' AND goods_id = ".$v['goods_id'];
					if($v['product_id'] > 0){
						$sendsql.=" AND product_id=".intval($v['product_id']);
					}

					$db->query($sendsql);
				}
			}
			if($data['shipping_status'] == 4){
				//更新订单状态为已发货部分发货
				$db->autoExecute($ecs->table('order_info'), array('order_status'=>6), 'UPDATE', 'order_sn = "'.$order_sn.'"');
			}else{
				//发货后,用户赠送优惠券
				//send_order_bonus($orderInfo['order_id']);
				$db->autoExecute($ecs->table('order_info'), array('order_status'=>5), 'UPDATE', 'order_sn = "'.$order_sn.'"');
			}
			
			echo json_encode(array("result"=>"success","code"=>200,"msg"=>"成功"));
			break;
		case "3":
			echo json_encode(array("result"=>"success","code"=>200,"msg"=>"成功"));
			break;
		default:
			echo json_encode(array("result"=>"fail","code"=>302,"msg"=>"不合法的配送状态"));
	}
}

function api_action_delivery(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$data = $_POST['data'];

	$delivery_sn = $data['delivery_sn'];
	
	if(!isset($delivery_sn) || empty($delivery_sn)){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数delivery_sn"));exit;
	}
	if(!isset($data['shipping_id']) || empty($data['shipping_id'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数shipping_id"));exit;
	}
	if(!isset($data['shipping_name']) || empty($data['shipping_name'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数shipping_name"));exit;
	}
	if(!isset($data['invoice_no']) || empty($data['invoice_no'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数invoice_no"));exit;
	}
	$info = $db->getRow("SELECT delivery_id FROM " .$ecs->table('delivery_order'). " WHERE delivery_sn = '".$delivery_sn."'");
	if(!isset($info) || empty($info)){
		echo json_encode(array("result"=>"fail","code"=>304,"msg"=>"不存在的发货单"));exit;
	}
	
	$deliveryGoods = array(
		//"shipping_id"=>$data['shipping_id'],
		"shipping_name"=>$data['shipping_name'],
		"invoice_no"=>$data['invoice_no'],
	);
		if(isset($data['shipping_id']) && !empty($data['shipping_id'])){
			$deliveryGoods['shipping_id'] = $data['shipping_id'];
		}
	
	$res = $db->autoExecute($ecs->table('delivery_order'), $deliveryGoods, 'UPDATE', 'delivery_sn = "'.$delivery_sn.'"');
	if($res){
		echo json_encode(array("result"=>"success","code"=>200,"msg"=>"更新成功"));exit;
	}else{
		echo json_encode(array("result"=>"fail","code"=>201,"msg"=>"数据库异常"));exit;
	}
}

function api_action_deposit(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$data = $_POST['data'];
	$user_id = $data['user_id'];
	$company_id = $data['company_id'];
	if(!isset($data['user_id']) || empty($data['user_id'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数 user_id"));exit;
	}
	if(!isset($data['company_id']) || empty($data['company_id'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数 company_id"));exit;
	}
	if(!isset($data['money']) || empty($data['money'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数 money"));exit;
	}
	if(!isset($data['originid']) || empty($data['originid'])){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数originid"));exit;
	}
	//查询是否存在关联odoo公司的店铺
	$supplierInfo = $db->getAll("SELECT supplier_id FROM " .$ecs->table('supplier'). " WHERE erp_id = ".$company_id);
	if(!isset($supplierInfo) || empty($supplierInfo)){
		echo json_encode(array("result"=>"fail","code"=>301,"msg"=>"当前公司没有绑定到商城店铺!"));exit;
	}
	
	$db->query('BEGIN');
    try {
		$wallet_detail = $db->getRow("SELECT id FROM " .$ecs->table('wallet_detail'). " WHERE originid = ".$data['originid']);
		if(isset($wallet_detail) && !empty($wallet_detail)){
			throw new \Exception("该记录已经同步过，不能重复同步");
		}
		//用户是否存在该商家预存款，存在则更新,不存在则新增,并且增加预存款明细。成功增加同步成功明细，失败增加失败明细
		$wallet = $db->getRow("SELECT * FROM ".$ecs->table('wallet'). " WHERE erp_id=".$company_id." AND user_id = ".$data['user_id']);
		$changedata = array(
			"user_id"=>$data['user_id'],
			"erp_id"=>$company_id
		);
		if(isset($wallet) && !empty($wallet)){
			$changedata['money'] = $wallet['money'] + $data['money'];
			$walletres = $db->autoExecute($ecs->table('wallet'), $changedata, 'UPDATE', 'id = "'.$wallet['id'].'"');
		}else{
			$changedata['money'] = $data['money'];
			$walletres = $db->autoExecute($ecs->table('wallet'), $changedata, 'INSERT', '', 'SILENT');
		}
		
		//新增一条明细记录，以及成功的日志
		$detail = array(
			"user_id" =>$data['user_id'],
			"erp_id"  =>$company_id,
			'money'   => $data['money'],
			'add_time'   => time(),
			'origin'   => 0,//0表示erp充值
			'admin_user'   => $data['admin'],
			'originid'   => $data['originid'],
		);
		$detailres = $db->autoExecute($ecs->table('wallet_detail'), $detail, 'INSERT', '', 'SILENT');
		
		$detail['status'] = 1;
		$detail['reason'] = "同步成功";
		$logres = $db->autoExecute($ecs->table('wallet_sync_log'), $detail, 'INSERT', '', 'SILENT');
		
		
		if ($walletres && $detailres && $logres) { 
			$db->query('COMMIT');
			echo json_encode(array("result"=>"success","code"=>200,"msg"=>"同步成功"));exit;
		} else { 
			throw new \Exception('同步失败'); 
		}
	} catch (Exception $e) { 
		$db->query('ROLLBACK'); 
		$message = $e->getMessage() ;
		$detail = array(
			"user_id" =>$data['user_id'],
			"erp_id"  =>$company_id,
			'money'   => $data['money'],
			'add_time'   => time(),
			'origin'   => 0,//0表示erp充值
			'admin_user'   => $data['admin'],
			'originid'   => $data['originid'],
			'status'	=> 0,
			"reason"	=> $message
		);
		$db->autoExecute($ecs->table('wallet_sync_log'), $detail, 'INSERT', '', 'SILENT');
		//增加同步失败日志
		echo json_encode(array("result"=>"fail","code"=>201,"msg"=>$message));exit;
	}
}

//更新退货单 退货单时，erp端收到用户产品需要更新退货单状态为收到货物 back=true更新退货单状态
function api_action_refund(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$data = $_POST['data'];
	$back_id = $data['back_id'];
	if(!isset($back_id) || empty($back_id)){
		echo json_encode(array("result"=>"fail","code"=>303,"msg"=>"缺少参数 back_id"));exit;
	}
	$info = $db->getRow("SELECT back_id,status_back FROM " .$ecs->table('back_order'). " WHERE back_id = '".$back_id."'");
	if(!isset($info) || empty($info)){
		echo json_encode(array("result"=>"fail","code"=>304,"msg"=>"不存在的退货单"));exit;
	}
	if($info['status_back'] == 1){
		echo json_encode(array("result"=>"fail","code"=>302,"msg"=>"该发货单订单已经为收到货物状态"));exit;
	}
	
	$res = $db->autoExecute($ecs->table('back_order'), array("status_back"=>1), 'UPDATE', 'back_id = "'.$back_id.'"');

	 $db->autoExecute($ecs->table('back_goods'), array("status_back"=>1), 'UPDATE', 'back_id = "'.$back_id.'"');
	if($res){
		echo json_encode(array("result"=>"success","code"=>200,"msg"=>"更新成功"));exit;
	}else{
		echo json_encode(array("result"=>"fail","code"=>201,"msg"=>"数据库异常"));exit;
	}
}