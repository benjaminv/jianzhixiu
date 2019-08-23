<?php
/****************************************************************************/
//商城对接oddo接口。实现会员，订单，商品的同步。
//本文件为客户端,加上鉴权，保证不被破解访问
/****************************************************************************/

define('IN_ECS', true);
error_reporting(E_ALL);
require('./init.php');
require_once('./library/ripcord.php');
//class OdooErp{
    ////保存例实例在此属性中 
	//private static $_instance;
	//private  $odooUid;
	//private  $erpObj;
    ////构造函数声明为private,防止直接创建对象 
	//private function __construct(){
        //$common = ripcord::client(ODOO_ERP_URL."/xmlrpc/2/common");
		//$this->odooUid = $common->authenticate(ODOO_ERP_DB, ODOO_ERP_USER, ODOO_ERP_PSD, array());
		//$this->erpObj = ripcord::client(ODOO_ERP_URL."/xmlrpc/2/object");
    //}
    ////单例方法 
	//public static function getInstance(){
        //if(!isset(self::$_instance)){
            //self::$_instance=new self();
        //}
        //return self::$_instance;
    //}
    ////阻止用户复制对象实例 
	//private function __clone(){
        //trigger_error('禁止克隆' ,E_USER_ERROR);
    //}

	//public function execute_erp($models,$action,$position=array(),$keyword=array()){
		//$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $uid, ODOO_ERP_PSD,$models,$action,$position=array(),$keyword=array());
		//return $res;
	//}
//}


//$obj = OdooErp::getInstance();
//$obj->execute_erp("goods",'sync_goods');
//echo "<pre>";print_r($obj);

//$hash_code = $db->getOne("SELECT `value` FROM " . $ecs->table('shop_config') . " WHERE `code`='hash_code'", true);
//$auth = md5(AUTH_KEY.$hash_code);
//if($auth != $_REQUEST['auth']){
	//return array("code"=>"fail","msg"=>"鉴权失败");
//}
$action = $_REQUEST['action'];
$data = $_REQUEST['data'];
$url = 'http://52.80.65.201:8062';
$db = "demo-gooderp";
$username = "admin";
$password = 'admin';
$model = "goods";
$func = "sync_goods";

$common = ripcord::client("$url/xmlrpc/2/common");
$uid = $common->authenticate($db, $username, $password, array());
$models = ripcord::client("$url/xmlrpc/2/object");
//$data = array(
    //'external_id' => 61,
    //'code' => 'CLG000061',
    //'name' => '简之绣匠心定制套盒',
    //'brand' => array
        //(
         //6,'简之绣'
        //),
    //'goods_class_id' => array
        //(
            //22,'高效营销'
        //),
    //'price' => 2680.00,
    //'specification' => 'AAA'
//);
//echo "<pre>";print_r($data);
//$id = $models->execute_kw($db, $uid, $password,$model, $func,[$data]);
//echo "<pre>";print_r($id);


$odoo_product = array(
	array(
		'goods_attribute_external_id'=>75,
		'goods_external_id'=> 56,
		'ean'=> 'CLG000056g_p75',
		'goods_attribute_details'=>array(
			array(
				'attribute_external_id'=>array(10,'颜色'),
				'attribute_value_external_id' => 10,
				'attribute_value_value'=>'碧穹蓝'
			)
		),
	),
	//array(
		//'goods_attribute_external_id'=>76,
		//'goods_external_id'=> 56,
		//'ean'=> 'CLG000056g_p76',
		//'goods_attribute_details'=>array(
			//array(
				//'attribute_external_id'=>array(10,'颜色'),
				//'attribute_value_external_id' => 10,
				//'attribute_value_value'=>'玫瑰金'
			//)
		//),
	//),
	//array(
		//'goods_attribute_external_id'=>77,
		//'goods_external_id'=> 56,
		//'ean'=> 'CLG000056g_p77',
		//'goods_attribute_details'=>array(
			//array(
				//'attribute_external_id'=>array(10,'颜色'),
				//'attribute_value_external_id' => 10,
				//'attribute_value_value'=>'星空紫'
			//)
		//),
	//),
	//array(
		//'goods_attribute_external_id'=>78,
		//'goods_external_id'=> 56,
		//'ean'=> 'CLG000056g_p78',
		//'goods_attribute_details'=>array(
			//array(
				//'attribute_external_id'=>array(10,'颜色'),
				//'attribute_value_external_id' => 10,
				//'attribute_value_value'=>'中国红'
			//)
		//),
	//)
);
		
$res = $models->execute_kw($db, $uid, $password,"attribute","sync_goods_attribute",array($odoo_product));
echo "<pre>";print_r($res);
echo $data;




//$brandid = $models->execute_kw($db, $uid, $password,"core.value", "search",[[['name', '=', '品牌一']]]);
//echo "<pre>";print_r($brandid);

//$id = $models->execute_kw($db, $uid, $password,$model, $func,array(array('code'=>'0001','name'=>'first test2','brand'=>$brandid[0],'uom_id'=>1,'category_id'=>1)));
//echo "<pre>";print_r($id);

//$client = ripcord::client( 'https://shop.lian-mei.com/api/rpc_server.php' );
//$client = ripcord::client( '52.80.65.201:8062' );
//$client->system->multiCall()->start();
//ripcord::bind( $methods, $client->system->listMethods() );
//ripcord::bind( $api_return, $client->$action($data) );
//$client->system->multiCall()->execute();
//return $api_return;