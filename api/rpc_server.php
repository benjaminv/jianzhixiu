<?php
/****************************************************************************/
//商城对接oddo接口。实现会员，订单，商品的同步。
//本文件为服务端
/****************************************************************************/
/**
** @desc 封装 curl 的调用接口，post的请求方式
**/
error_reporting(E_ALL);
$url = "https://jzx.lian-mei.com/api/order.php";
//$action = "update";
//$hash_code="31693422540744c0a6b6da635b7a5a93";
//$key='*5t8bw5HQKoba!3O$inTstcIhdGLIzMV';
$auth = 'a296b244ecd62504bc02c7d0c8b32d5e';

//配货中数据 更新发货单接口数据
//$data=array(
	//"action"=>$action,
	//"auth"=>$auth,
	//"data"=>array(
      	//"order_sn"=>'20190426574721',
      	//'shipping_status'=>1
	//)
//);



$action = "delivery";
//配货中数据 更新发货单接口数据
$data=array(
	"action"=>$action,
	"auth"=>$auth,
	"data"=>array(
		"delivery_sn"=>'WH/OUT/00005',
		"invoice_no"=>"111",
		"shipping_id"=>10,
		"shipping_name"=>"顺丰物流",
	)
);

//$data = '{"action":"update","data":{"delivery":{"action_user":"Administrator","goodsInfo":[{"goods_sn":"CLG000099","brand_name":"","goods_name":"\u7b80\u4e4b\u7ee3\u5546\u54c1\u5927\u5168","goods_id":99,"send_number":"1.0"}],"delivery_sn":"WH\/OUT\/00008"},"shipping_status":"3","order_sn":"20190429030421"},"auth":"a296b244ecd62504bc02c7d0c8b32d5e"}';
$data = json_decode($data,1);
http_request($url,$data);



//{
	//"action": "update",
	//"data": {
		//"delivery": {
			//"action_user": "Administrator",
			//"goodsInfo": [{
				//"goods_sn": "CLG000099",
				//"brand_name": "",
				//"goods_name": "\u7b80\u4e4b\u7ee3\u5546\u54c1\u5927\u5168",
				//"goods_id": 99,
				//"send_number": "1.0"
			//}],
			//"delivery_sn": "WH\/OUT\/00008"
		//},
		//"shipping_status": "3",
		//"order_sn": "20190429030421"
	//},
	//"auth": "a296b244ecd62504bc02c7d0c8b32d5e"
//}
//echo "<pre>";print_r(json_encode($data));exit;

function http_request($url, $data = null){
		$headers = array("Content-type: application/json;charset=utf-8","Accept: application/json","Cache-Control: no-cache","Pragma: no-cache");
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$rtn = curl_exec($ch);//CURLOPT_RETURNTRANSFER 不设置  curl_exec返回TRUE 设置  curl_exec返回json(此处) 失败都返回FALSE
		echo "<pre>";print_r($rtn);
  		curl_close($ch);
		return $rtn;
	}


//{"action":"update","data":{"delivery":{"action_user":"Administrator","goodsInfo":[{"goods_sn":"CLG000099","brand_name":"","goods_name":"\u7b80\u4e4b\u7ee3\u5546\u54c1\u5927\u5168","goods_id":99,"send_number":"1.0"}],"delivery_sn":"WH\/OUT\/00008"},"shipping_status":"3","order_sn":"20190429030421"},"auth":"a296b244ecd62504bc02c7d0c8b32d5e"}

//{"action":"delivery","data":{"shipping_name":"\u5fb7\u90a6360\u7279\u60e0\u4ef6","invoice_no":"8420092435","shipping_id":15,"delivery_sn":"WH\/OUT\/00008"},"auth":"a296b244ecd62504bc02c7d0c8b32d5e"}
