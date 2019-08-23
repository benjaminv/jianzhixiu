<?php
//同步商城会员，订单，商品到erp系统
//商城店铺的商品和订单，同步到对应的erp公司中，平台商品和订单同步到默认公司(平台公司)
////执行方法
//会员 订单 退款单数据加入queue通道，定时任务执行
require_once($_SERVER['DOCUMENT_ROOT']. '/api/library/ripcord.php');
class OdooErp{
    //保存例实例在此属性中 
	private static $_instance;
	private  $odooUid;
	private  $erpObj;
    //构造函数声明为private,防止直接创建对象 
	private function __construct(){
        $common = ripcord::client(ODOO_ERP_URL."/xmlrpc/2/common");
		if(isset($_SESSION['odooUid']) && !empty($_SESSION['odooUid'])){
			$this->odooUid = $_SESSION['odooUid'];
		}else{
			$this->odooUid = $common->authenticate(ODOO_ERP_DB, ODOO_ERP_USER, ODOO_ERP_PSD, array());
		}
		if(isset($_SESSION['erpObj']) && !empty($_SESSION['erpObj'])){
			$this->erpObj = $_SESSION['erpObj'];
		}else{
			$this->erpObj = ripcord::client(ODOO_ERP_URL."/xmlrpc/2/object");
		}
    }
    //单例方法 
	public static function getInstance(){
        if(!isset(self::$_instance)){
            self::$_instance=new self();
        }
        return self::$_instance;
    }
    //阻止用户复制对象实例 
	private function __clone(){
        trigger_error('禁止克隆' ,E_USER_ERROR);
    }
	
	/*通用同步接口,参数为模型，方法以及变量*/
	public function executeErp($models,$action,$position=array(),$keyword=array()){
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,$models,$action,$position,$keyword);
		return $res;
	}
	
	
	/*同步店铺单个商品到erp
	*params  goodsid 
	*/
	public function syncGoodsToErpByGoodsid($goodsid){
		//查询商品如果属于供应商，并且供应商对接了erp的话，同步商品到erp
		$goodsSql="SELECT a.goods_id,a.goods_name,a.goods_unit,a.brand_id,a.goods_sn,a.cat_id,a.shop_price,b.brand_name,c.cat_name,a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('brand')." b ON a.brand_id=b.brand_id LEFT JOIN ".$GLOBALS['ecs']->table('category')." c ON a.cat_id=c.cat_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$goodsid;
		$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
		
		$sync_data = array(
				"external_id"=>$goodsInfo['goods_id'],
				"code"=>$goodsInfo['goods_sn'],
				"name"=>$goodsInfo['goods_name'],
				"uom_id"=>$goodsInfo['goods_unit'],
				"brand"=>array(
							$goodsInfo['brand_id'],$goodsInfo['brand_name']
						),
				'goods_class_id'=>array(
							$goodsInfo['cat_id'],$goodsInfo['cat_name']
						),
				'price'=>$goodsInfo['shop_price'],
				'specification'=>'AAA',
		);
		if($goodsInfo['supplier_id']>0){
			if($goodsInfo['sync_erp']==1 && $goodsInfo['erp_id']>1){
				//同步到店铺的erp中
				$sync_data['company_id'] = $goodsInfo['erp_id'];
				$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods",array($sync_data),array());

				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_erp = 1,sync_result = '同步成功' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}
				return $res;
			}
			return false;
		}else{
			//同步到平台的默认erp公司中
			$sync_data['company_id'] = false;
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods",array($sync_data),array());
			
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_erp = 1,sync_result = '同步成功' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}

			return $res;
		}
	}
	
	/*同步多个商品到erp
	*params  goodsid 
	*/
	public function syncGoodsToErpByGoodsids($goodsids){
		foreach($goodsids as $v){
			syncGoodsToErpByGoodsid($v);
		}
	}

	/*同步单个商品以及该商品的货品到erp
	//2018/12/12 星期三 增加参数company_id，店铺的商品同步到自身店铺管理的erp中，平台商品同步到平台默认店铺中
	*params  goodsid 
	*/
	public function syncGoodsAndProductToErpByGoodsid($goodsid){
		$goodsSql="SELECT a.goods_id,a.goods_name,a.goods_unit,a.brand_id,a.goods_sn,a.cat_id,a.shop_price,b.brand_name,c.cat_name,a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('brand')." b ON a.brand_id=b.brand_id LEFT JOIN ".$GLOBALS['ecs']->table('category')." c ON a.cat_id=c.cat_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$goodsid;
		
		$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
		
		$sync_data = array(
			"external_id"=>$goodsInfo['goods_id'],
			"code"=>$goodsInfo['goods_sn'],
			"name"=>$goodsInfo['goods_name'],
			"uom_id"=>$goodsInfo['goods_unit'],
			"brand"=>array(
						$goodsInfo['brand_id'],$goodsInfo['brand_name']
					),
			'goods_class_id'=>array(
						$goodsInfo['cat_id'],$goodsInfo['cat_name']
					),
			'price'=>$goodsInfo['shop_price'],
			'specification'=>'AAA',
		);
		//店铺商品同步到店铺的erp，平台商品同步到平台的erp公司，
		if($goodsInfo['supplier_id']>0){
			if($goodsInfo['sync_erp']==1 && $goodsInfo['erp_id']>1){
				//同步到店铺的erp中
				$sync_data['company_id'] = $goodsInfo['erp_id'];
				$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods",array($sync_data),array());
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_erp = 1,sync_result = '同步成功' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}
				//echo "<pre>";print_r($res);
				//同步商品的货品到erp中
				$pres = $this->syncProductToErpByGoodsid($goodsid);
				$res['pres'] = $pres;
				return $res;
			}
		}else{
			//同步到平台的默认erp公司中
			$sync_data['company_id'] = false;
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods",array($sync_data),array());
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_erp = 1,sync_result = '同步成功' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE goods_id = ".$goodsid;
					$GLOBALS['db']->query($sql);
				}
			$pres = $this->syncProductToErpByGoodsid($goodsid);
			$res['pres'] = $pres;
			return $res;
		}
		//exit;
	}
	
	//同步单个商品的所有货品到erp
	public function syncProductToErpByGoodsid($goodsid){
		$productInfo = $GLOBALS['db']->getAll("SELECT a.*,b.supplier_id,b.shop_price,b.supplier_status,c.sync_erp,c.erp_id FROM ". $GLOBALS['ecs']->table('products') . " a LEFT JOIN ".$GLOBALS['ecs']->table('goods')." b ON b.goods_id=a.goods_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." c ON c.supplier_id=b.supplier_id  WHERE a.goods_id =".$goodsid);
		
			$sync_data = array();
			foreach($productInfo as $v){
				if($v['supplier_id']>0 ){
					
					//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
					if($v['sync_erp']==0 || $v['erp_id']==0){
						continue;
					}
				}

				$product_price = $v['shop_price'];

					$odoo_product=array();
					$odoo_product=array(
						"goods_attribute_external_id"=>$v['product_id'],
						"goods_external_id"=>$v['goods_id'],
						"ean"=>$v['product_sn'],
					);
					$goods_attr_data = implode(",",explode("|",$v['goods_attr']));
					$goods_attr_info = $GLOBALS['db']->getAll("SELECT a.goods_attr_id,a.attr_id,a.attr_value,a.attr_price,b.attr_name FROM ". $GLOBALS['ecs']->table('goods_attr') . " a LEFT JOIN ". $GLOBALS['ecs']->table('attribute') ." b ON a.attr_id=b.attr_id WHERE a.goods_attr_id in (".$goods_attr_data.")");
					$erp_attr_detail = array();
					foreach($goods_attr_info as $attr){
						$product_price += $attr['attr_price'];
						$erp_attr_detail[]=array(
								'attribute_external_id'=>array($attr['attr_id'],$attr['attr_name']),
								"attribute_value_external_id"=>$attr['goods_attr_id'],
								"attribute_value_value"=>$attr['attr_value'],
						);
					}
					$odoo_product['goods_attribute_details']=$erp_attr_detail;
					$odoo_product['price']=$product_price;
					$sync_data[] = $odoo_product;
				
			}
			//echo "<pre>";print_r($sync_data);
			@file_put_contents("sync_product.txt", "同步的货品信息:".json_encode($sync_data).PHP_EOL, FILE_APPEND);
			if(!empty($sync_data)){
				$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"attribute","sync_goods_attribute",array($sync_data),array());
				//echo "<pre>";print_r($res);exit;

				return $res;
			}else{
				
				return false;
			}
		
	}

	/*同步单个货品到erp
	*params  productid 
	*/
	public function syncProductToErpByProductid($productid){
		if(!isset($productid) || empty($productid) || !$productid){
			return false;
		}else{
			$productInfo = $GLOBALS['db']->getRow("SELECT a.*,b.supplier_id,b.shop_price,b.supplier_status,c.sync_erp,c.erp_id FROM ". $GLOBALS['ecs']->table('products') . " a LEFT JOIN ".$GLOBALS['ecs']->table('goods')." b ON b.goods_id=a.goods_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." c ON c.supplier_id=b.supplier_id  WHERE product_id =".$productid);
			
			if($productInfo['supplier_id']>0 ){
				//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
				if($productInfo['sync_erp']==0 || $productInfo['erp_id']==0){
					return false;
				}
			}
				$sync_data = array();
				$odoo_product=array(
						"goods_attribute_external_id"=>$productInfo['product_id'],
						"goods_external_id"=>$productInfo['goods_id'],
						"ean"=>$productInfo['product_sn'],
				);
				$goods_attr_data = implode(",",explode("|",$productInfo['goods_attr']));
				
				$product_price = $productInfo['shop_price'];

				$goods_attr_info = $GLOBALS['db']->getAll("SELECT a.goods_attr_id,a.attr_id,a.attr_value,a.attr_price,b.attr_name FROM ". $GLOBALS['ecs']->table('goods_attr') . " a LEFT JOIN ". $GLOBALS['ecs']->table('attribute') ." b ON a.attr_id=b.attr_id WHERE a.goods_attr_id in (".$goods_attr_data.")");
				foreach($goods_attr_info as $attr){
					$product_price += $attr['attr_price'];
						$erp_attr_detail[]=array(
								'attribute_external_id'=>array($attr['attr_id'],$attr['attr_name']),
								"attribute_value_external_id"=>$attr['goods_attr_id'],
								"attribute_value_value"=>$attr['attr_value'],
						);
				}
				$odoo_product['goods_attribute_details']=$erp_attr_detail;
				$odoo_product['price']=$product_price;
				$sync_data[] = $odoo_product;
				$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"attribute","sync_goods_attribute",array($sync_data),array());
				
				
				return $res;
			
		}
	}
	/*同步多个货品到erp
	*params  productids 
	*/
	public function syncProductToErpByProductids($productids){
		if(!isset($productids) || empty($productids)){
			return false;
		}else{
			$productids = implode(",",$productids);

			$productsInfo = $GLOBALS['db']->getAll("SELECT a.*,b.supplier_id,b.shop_price,b.supplier_status,c.sync_erp,c.erp_id FROM ". $GLOBALS['ecs']->table('products') . " a LEFT JOIN ".$GLOBALS['ecs']->table('goods')." b ON b.goods_id=a.goods_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." c ON c.supplier_id=b.supplier_id  WHERE product_id in (".$productids.")");
			
			$sync_data = array();
			foreach($productsInfo as $v){
				if($v['supplier_id']>0 ){
					//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
					if($v['sync_erp']==0 || $v['erp_id']==0){
						continue;
					}
				}
				$product_price = $v['shop_price'];
					$odoo_product=array();
					$odoo_product=array(
						"goods_attribute_external_id"=>$v['product_id'],
						"goods_external_id"=>$v['goods_id'],
						"ean"=>$v['product_sn'],
					);
					$goods_attr_data = implode(",",explode("|",$v['goods_attr']));
					$goods_attr_info = $GLOBALS['db']->getAll("SELECT a.goods_attr_id,a.attr_id,a.attr_value,a.attr_price,b.attr_name FROM ". $GLOBALS['ecs']->table('goods_attr') . " a LEFT JOIN ". $GLOBALS['ecs']->table('attribute') ." b ON a.attr_id=b.attr_id WHERE a.goods_attr_id in (".$goods_attr_data.")");
					$erp_attr_detail = array();
					foreach($goods_attr_info as $attr){
						$product_price+=$attr['attr_price'];
						$erp_attr_detail[]=array(
								'attribute_external_id'=>array($attr['attr_id'],$attr['attr_name']),
								"attribute_value_external_id"=>$attr['goods_attr_id'],
								"attribute_value_value"=>$attr['attr_value'],
						);
					}
					$odoo_product['goods_attribute_details']=$erp_attr_detail;
					$odoo_product['price']=$product_price;
					$sync_data[] = $odoo_product;
				
			}

			if(!empty($sync_data)){
				$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"attribute","sync_goods_attribute",array($sync_data),array());
				//echo "<pre>";print_r($sync_data);
				//echo "<pre>";print_r($res);
				//exit;
				return $res;
			}else{
				return false;
			}
		}
	}
	
	//同步回收站单个商品
	public function syncRestoreGoodsByGoodsid($goodsid){
		$goodsSql="SELECT a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$goodsid;
		$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
		if($goodsInfo['supplier_id']>0 ){
			//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
			if($goodsInfo['sync_erp']==0 || $goodsInfo['erp_id']==0){
					return false;
			}
		}
			$goodsid=array($goodsid);
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_inactive",array($goodsid),array());
			return $res;
		
	}

	//同步回收站多个商品
	public function syncRestoreGoodsByGoodsids($goodsids){
		if(!is_array($goodsids)){
			$goodsids = explode(",",$goodsids);
		}
		foreach($goodsids as $k=>$v){
			$goodsSql="SELECT a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$v;
			$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
			if($goodsInfo['supplier_id']>0 ){
				//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
				if($goodsInfo['sync_erp']==0 || $goodsInfo['erp_id']==0){
						unset($goodsids[$k]);
				}
			}
		}
		if(!empty($goodsids)){
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_inactive",array($goodsids),array());
			return $res;
		}
	}

	//同步恢复回收站单个商品
	public function syncStoreGoodsByGoodsid($goodsid){
		$goodsSql="SELECT a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$goodsid;
		$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
		if($goodsInfo['supplier_id']>0 ){
			//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
			if($goodsInfo['sync_erp']==0 || $goodsInfo['erp_id']==0){
					return false;
			}
		}
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_active",array($goodsid),array());
			return $res;
		
	}

	//同步恢复回收站多个商品
	public function syncStoreGoodsByGoodsids($goodsids){
		if(!is_array($goodsids)){
			$goodsids = explode(",",$goodsids);
		}
		foreach($goodsids as $k=>$v){
			$goodsSql="SELECT a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$v;
			$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
			if($goodsInfo['supplier_id']>0 ){
				//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
				if($goodsInfo['sync_erp']==0 || $goodsInfo['erp_id']==0){
						unset($goodsids[$k]);
				}
			}
		}
		if(!empty($goodsids)){
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_active",array($goodsids),array());
			return $res;
		}
	}

	//同步删除单个商品
	public function syncUnlinkGoodsByGoodsid($goodsid){
		$goodsSql="SELECT a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$goodsid;
		$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
		if($goodsInfo['supplier_id']>0 ){
			//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
			if($goodsInfo['sync_erp']==0 || $goodsInfo['erp_id']==0){
					return false;
			}
		}
			$goodsid=array($goodsid);
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_unlink",array($goodsid),array());
			return $res;
		
	}

	//同步删除多个商品
	public function syncUnlinkGoodsByGoodsids($goodsids){
		if(!is_array($goodsids)){
			$goodsids = explode(",",$goodsids);
		}
		foreach($goodsids as $k=>$v){
			$goodsSql="SELECT a.supplier_id,a.supplier_status,d.sync_erp,d.erp_id FROM ".$GLOBALS['ecs']->table('goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." d ON d.supplier_id=a.supplier_id  WHERE a.goods_id=".$v;
			$goodsInfo =$GLOBALS['db']->getRow($goodsSql);
			if($goodsInfo['supplier_id']>0 ){
				//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
				if($goodsInfo['sync_erp']==0 || $goodsInfo['erp_id']==0){
						unset($goodsids[$k]);
				}
			}
		}
		if(!empty($goodsids)){
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_unlink",array($goodsids),array());
			return $res;
		}
	}

	//同步删除单个货品，真删除
	public function syncUnlinkProductByProductid($productid){
		$productInfo = $GLOBALS['db']->getRow("SELECT a.*,b.supplier_id,b.supplier_status,c.sync_erp,c.erp_id FROM ". $GLOBALS['ecs']->table('products') . " a LEFT JOIN ".$GLOBALS['ecs']->table('goods')." b ON b.goods_id=a.goods_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." c ON c.supplier_id=b.supplier_id  WHERE product_id =".$productid);
		if($productInfo['supplier_id']>0 ){
				//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
				if($productInfo['sync_erp']==0 || $productInfo['erp_id']==0){
					return false;
				}
		}
			$productid=array($productid);
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"attribute","sync_goods_attribute_unlink",array($productid),array());
			return $res;
		
	}

	//同步删除多个货品，真删除
	public function syncUnlinkProductByProductids($productids){
		if(!is_array($productids)){
			$productids = explode(",",$productids);
		}
		foreach($productids as $k=>$v){
			$productInfo = $GLOBALS['db']->getRow("SELECT a.*,b.supplier_id,b.supplier_status,c.sync_erp,c.erp_id FROM ". $GLOBALS['ecs']->table('products') . " a LEFT JOIN ".$GLOBALS['ecs']->table('goods')." b ON b.goods_id=a.goods_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." c ON c.supplier_id=b.supplier_id  WHERE product_id =".$v);
			if($productInfo['supplier_id']>0 ){
				//店铺商品的货品，如果没有配置erp店铺，将不会同步到erp
				if($productInfo['sync_erp']==0 || $productInfo['erp_id']==0){
					unset($productids[$k]);
				}
			}
		}
		if(!empty($productids)){
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"goods","sync_goods_attribute_unlink",array($productids),array());
			return $res;
		}
	}
	
	//同步单个会员到erp系统
	public function syncUserByUserid($userid){
		$queue_data = array('queue_type'=>0,'queue_param'=>serialize(array('userid'=>$userid)),'operate_status'=>0,'create_time'=>local_gettime());
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('queue'), $queue_data, 'INSERT', '', 'SILENT');
		$res['SuccessCode'] = 1;
		return $res;
		//会员等级列表
		//$rank_list_all = $GLOBALS['db']->getAll("select rank_id,rank_name,min_points,max_points from " . $GLOBALS['ecs']->table('user_rank'));
		//$rank_list = array();
		//foreach($rank_list_all as $key=>$val) {
			//$rank_list[$val['rank_id']] = $val;
		//}
		//$userInfo = $GLOBALS['db']->getRow("SELECT user_id,aite_id,user_name,mobile_phone,user_rank,real_name,rank_points FROM ". $GLOBALS['ecs']->table('users') ." WHERE user_id=".$userid);
		//$sync_data=array(
			//"external_id"=>$userInfo['user_id'],
			//"code"=>"shop_".$userInfo['user_id'],
			//"name"=>$userInfo['user_name']." / ".$userInfo['real_name'],
			//"main_mobile"=>$userInfo['mobile_phone'],
		//);
		//if($userInfo['user_rank']){
			//$sync_data['c_category_id']=array($userInfo['user_rank'],$rank_list[$userInfo['user_rank']]['rank_name']);
		//}else{
			//foreach($rank_list_all as $kr=>$vr) {
				//$min_point = $vr['min_points'];
				//$max_point = $vr['max_points'];
				//if($userInfo['rank_points'] <= $max_point && $userInfo['rank_points'] >= $min_point)
				//{
					//$sync_data['c_category_id']=array($vr['rank_id'],$vr['rank_name']);
					//break;
				//}
			//}
		//}
		//$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner","sync_customer",array($sync_data),array());
				//if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET sync_erp = 1,sync_result = '同步成功' WHERE user_id = ".$userid;
					//$GLOBALS['db']->query($sql);
				//}else{
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET sync_erp = 0,sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE user_id = ".$userid;
					//$GLOBALS['db']->query($sql);
				//}
		//return $res;	
	}
	
	//同步删除单个会员
	public function syncUserUnlinkByUserid($userid){
		$sync_data=array($userid); 
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner","sync_customer_unlink",array($sync_data),array());
		return $res;
	}

	//同步删除多个会员
	public function syncUserUnlinkByUserids($userids){
		if(!is_array($userids)){
			$userids = explode(",",$userids);
		}
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner","sync_customer_unlink",array($userids),array());
		return $res;
	}

	//同步用户地址到erp系统
	public function syncAddressByUserid($userid){
		$address_id = $GLOBALS['db']->getOne("SELECT address_id FROM ". $GLOBALS['ecs']->table('users') ." WHERE user_id=".$userid);
		$sql = "SELECT a.address_id,a.consignee,a.email,a.mobile,b.region_name as province_name,c.region_name as city_name,d.region_name as district_name,a.address FROM ". $GLOBALS['ecs']->table('user_address') ." a LEFT JOIN ". $GLOBALS['ecs']->table('region') ." b ON a.province=b.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." c ON a.city=c.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." d ON a.district=d.region_id WHERE a.user_id=".$userid;
		$addressList = $GLOBALS['db']->getAll($sql);
		$sync_data=array();
		foreach($addressList as $v){
			$sync_data[]=array(
				"external_id"=>$v['address_id'],
				"customer_external_id"=>$userid,
				"contact"=>$v['consignee'],
				"mobile"=>$v['mobile'],
				"email"=>$v['email'],
				"province_id"=>$v['province_name'],
				"city_id"=>$v['city_name'],
				"country_id"=>$v['district_name'],
				"town_id"=>"无",
				"detail_adress"=>$v['address'],
				"is_default_add"=>$address_id?true:false,
			);
		}
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner.address","sync_partner_address",array($sync_data),array());
		return $res;
	
	}
	
	//
	public function syncAddressByAddressid($addressid){

		$sql = "SELECT a.user_id,a.address_id,a.consignee,a.email,a.mobile,b.region_name as province_name,c.region_name as city_name,d.region_name as district_name,a.address FROM ". $GLOBALS['ecs']->table('user_address') ." a LEFT JOIN ". $GLOBALS['ecs']->table('region') ." b ON a.province=b.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." c ON a.city=c.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." d ON a.district=d.region_id WHERE a.address_id=".$addressid;
		$addressInfo = $GLOBALS['db']->getRow($sql);

		$address_id = $GLOBALS['db']->getOne("SELECT address_id FROM ". $GLOBALS['ecs']->table('users') ." WHERE user_id=".$addressInfo['user_id']);
		$sync_data=array();
		$sync_data[]=array(
				"external_id"=>$addressInfo['address_id'],
				"customer_external_id"=>$addressInfo['user_id'],
				"contact"=>$addressInfo['consignee'],
				"mobile"=>$addressInfo['mobile'],
				"email"=>$addressInfo['email'],
				"province_id"=>$addressInfo['province_name'],
				"city_id"=>$addressInfo['city_name'],
				"county_id"=>$addressInfo['district_name'],
				"town"=>"无",
				"detail_address"=>$addressInfo['address'],
				"is_default_add"=>($address_id==$addressInfo['address_id'])?true:false,
			);
		//echo "<pre>";print_r($sync_data);
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner.address","sync_partner_address",array($sync_data),array());
		//echo "<pre>";print_r($res);exit;
		return $res;
	}

	//同步删除地址erp系统
	public function syncAddressUnlinkByAddressid($addressid){
		$sync_data=array($addressid);
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner.address","sync_partner_address_unlink",array($sync_data),array());
		return $res;
	}
	
	//同步店铺订单和平台订单到erp后台
	//参数 需要同步的订单ordersn 
	public function syncOrderByOrdersns($ordersns){
		$queue_data = array('queue_type'=>1,'queue_param'=>serialize(array('ordersns'=>$ordersns)),'operate_status'=>0,'create_time'=>local_gettime());
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('queue'), $queue_data, 'INSERT', '', 'SILENT');
		$res['SuccessCode'] = 1;
		return $res;


		//$str = "";
		//foreach($ordersns as $v){
			//$str .= "'".$v."',";
		//}
		//$str = substr($str,0,-1);
		//$sql = "SELECT a.order_id,a.order_sn,a.supplier_id,a.user_id,a.add_time,b.sync_erp,b.erp_id,a.consignee,a.address,a.mobile,c.region_name as province_name,d.region_name as city_name,e.region_name as district_name FROM ". $GLOBALS['ecs']->table('order_info') ."  a LEFT JOIN ". $GLOBALS['ecs']->table('supplier') ." b ON a.supplier_id=b.supplier_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." c ON a.province=c.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." d ON a.city=d.region_id  LEFT JOIN ". $GLOBALS['ecs']->table('region') ." e ON a.district=e.region_id WHERE  a.order_sn IN (".$str.")";
		//$orderInfo = $GLOBALS['db']->getAll($sql);
		//$sync_data = $this->getOrderlistByOrdersns($orderInfo);
		//if(isset($sync_data) && !empty($sync_data)){
			//$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"sell.order","sync_sell_order",array($sync_data),array());
				//$str_orderids = "";
				//foreach($sync_data as $v){
					//$str_orderids .= "'".$v['external_id']."',";
				//}
				//$str_orderids = substr($str_orderids,0,-1);
				//if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET sync_erp = 1,sync_result = '同步成功' WHERE order_id in (".$str_orderids.")";
					//$GLOBALS['db']->query($sql);
				//}else{
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET sync_erp = 0,sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE order_id in (".$str_orderids.")";
					//$GLOBALS['db']->query($sql);
				//}
			////exit;
			//return $res;
		//}
	}
	
	//构造订单同步数据
	public function getOrderlistByOrdersns($orderInfo){
		$sync_data = array();
		foreach($orderInfo as $k=>$v){
			$data = array();
			$data['company_id'] = false;
			if($v['supplier_id']>0){
				if($v['sync_erp']==0 || $v['erp_id']==0){
					//供应商订单并且没有配置erp店铺
					continue;
				}else{
					//供应商订单同步到供应商erp店铺
					$data['company_id'] = $v['erp_id'];
				}
			}else{
				$data['company_id'] = false;
			}
			
			$data['external_id'] = $v['order_id'];
			$data['partner_external_id'] = $v['user_id'];
			$data['name'] = $v['order_sn'];
			$data['date'] = date("Y-m-d",$v['add_time']);
			$data['type'] = 'sell';
			//$data['user_id'] = 1;//此处user_id为什么
			$data['partner_address_vals'] = array(
				'contact'=>$v['consignee'],
				'mobile'=>$v['mobile'],
				'province_id'=>$v['province_name'],
				'city_id'=>$v['city_name'],
				'county_id'=>$v['district_name'],
				'detail_address'=>$v['address'],
			);
			//查询订单商品详情
				$goodssql = "SELECT rec_id,goods_name,goods_id,product_id,goods_number,goods_price FROM ". $GLOBALS['ecs']->table('order_goods') ."  WHERE  order_id=".$v['order_id'];
				$goodsInfo = $GLOBALS['db']->getAll($goodssql);

				foreach($goodsInfo as $gv){
					$rec_id = 10000000+intval($gv['rec_id']);
					$order_line_detail = array();
					$order_line_detail = array(
							'external_id'=>$rec_id,
							'goods_external_id'=>$gv['goods_id'],
							'quantity'=>$gv['goods_number'],
							'price'=>$gv['goods_price'],
							'tax_rate'=>0
						);
					if($gv['product_id']>0){
						$order_line_detail['goods_attribute_external_id'] = $gv['product_id'];
					}
					$data['order_line_ids'][]=array(
						0,$rec_id,$order_line_detail
					);
				}
				
			$sync_data[]=$data;
		}
		return $sync_data;
	}
	
	//同步店铺和平台退货单 mode 0新增 2删除 1更新(业务上用不到)  back_type 4为退货退款 1为退款  //用户更新退货物流时调用更新接口
	public function syncRefundOrderByOrdersn($back_id,$mode,$back_type){
		$queue_data = array('queue_type'=>2,'queue_param'=>serialize(array('back_id'=>$back_id,'mode'=>$mode,'back_type'=>$back_type)),'operate_status'=>0,'create_time'=>local_gettime());
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('queue'), $queue_data, 'INSERT', '', 'SILENT');
		$res['SuccessCode'] = 1;
		return $res;
		
		//$sql = "SELECT a.back_id,a.order_sn,a.order_id,a.shipping_name,a.invoice_no,a.supplier_id,a.user_id,a.add_time,b.sync_erp,b.erp_id,a.consignee,a.address,a.mobile,c.region_name as province_name,d.region_name as city_name,e.region_name as district_name FROM ". $GLOBALS['ecs']->table('back_order') ."  a LEFT JOIN ". $GLOBALS['ecs']->table('supplier') ." b ON a.supplier_id=b.supplier_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." c ON a.province=c.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." d ON a.city=d.region_id  LEFT JOIN ". $GLOBALS['ecs']->table('region') ." e ON a.district=e.region_id WHERE  a.back_id='".$back_id."'";
		//$backInfo = $GLOBALS['db']->getRow($sql);
		//if($back_type == 1){//退货流程
			//$sync_data = $this->getBackByBackInfo(array($backInfo),$mode,$back_type);
			//if(isset($sync_data) && !empty($sync_data)){
				//$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"sell.order","sync_sell_order",array($sync_data),array());

				//if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_erp = 1,sync_result = '同步成功' WHERE back_id = ".$back_id;
					//$GLOBALS['db']->query($sql);
				//}else{
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_erp = 0,sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE back_id = ".$back_id;
					//$GLOBALS['db']->query($sql);
				//}
				//return $res;
			//}
		//}elseif($back_type == 4){//退款流程
			//$res = $this->sync_sell_order_refund(array($backInfo));
				//if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_erp = 1,sync_result = '同步成功' WHERE back_id = ".$back_id;
					//$GLOBALS['db']->query($sql);
				//}else{
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_erp = 0,sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE back_id = ".$back_id;
					//$GLOBALS['db']->query($sql);
				//}
			//return $res;
		//}
	}
	//订单退款流程    mode 0默认为新增  1为编辑 2为删除
	public function sync_sell_order_refund($backInfo,$mode = 0){
		$sync_data = array();
		foreach($backInfo as $k=>$v){
			$data['external_id'] = $v['order_id'];
			$data['type'] = 'sell';
			$sync_data[]=$data;
		}

		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"sell.order","sync_sell_order_refund",array($sync_data),array());
		return $res;
	}

	//构造退货单数据 mode 0新增 1编辑 2删除 
	public function getBackByBackInfo($backInfo,$mode,$back_type){
		

		$sync_data = array();
		foreach($backInfo as $k=>$v){
			$data['external_id'] = $v['back_id'];
			$data['partner_external_id'] = $v['user_id'];
			$data['name'] = $v['order_sn'];
			$data['date'] = date("Y-m-d",$v['add_time']);
			$data['type'] = 'return';
			$data['express_type'] = $v['shipping_name'];
			$data['express_code'] = $v['invoice_no'];
			$data['partner_address_vals'] = array(
				'contact'=>$v['consignee'],
				'mobile'=>$v['mobile'],
				'province_id'=>$v['province_name'],
				'city_id'=>$v['city_name'],
				'county_id'=>$v['district_name'],
				'detail_address'=>$v['address'],
			);
			if($back_type ==1 ){
				$goodssql = "SELECT rec_id,goods_name,goods_id,product_id,back_goods_number,back_goods_price FROM ". $GLOBALS['ecs']->table('back_goods') ."  WHERE  back_id=".$v['back_id'];
				$goodsInfo = $GLOBALS['db']->getAll($goodssql);
					foreach($goodsInfo as $gv){
						
						$order_line_detail = array();
							$order_line_detail = array(
									'external_id'=>$gv['rec_id'],
									'goods_external_id'=>$gv['goods_id'],
									'quantity'=>$gv['back_goods_number'],
									'price'=>$gv['back_goods_price'],
									'tax_rate'=>0
								);	
							if($gv['product_id']>0){
								$order_line_detail['goods_attribute_external_id'] = $gv['product_id'];
							}
						$data['order_line_ids'][]=array(
								$mode,$gv['rec_id'],$order_line_detail
							);
					}
			}
			//elseif($back_type == 4){
				//$goodssql = "SELECT rec_id,goods_name,goods_id,product_id,goods_number,goods_price FROM ". $GLOBALS['ecs']->table('order_goods') ."  WHERE  order_id=".$v['order_id'];
				   //$goodsInfo = $GLOBALS['db']->getAll($goodssql);
						
					//foreach($goodsInfo as $gv){
						//$order_line_detail = array();
						//$order_line_detail = array(
								//'external_id'=>$gv['rec_id'],
								//'goods_external_id'=>$gv['goods_id'],
								//'quantity'=>$gv['back_goods_number'],
								//'price'=>$gv['back_goods_price'],
								//'tax_rate'=>0
							//);	
						//if($gv['product_id']>0){
							//$order_line_detail['goods_attribute_external_id'] = $gv['product_id'];
						//}
						//$data['order_line_ids'][]=array(
							//0,$gv['rec_id'],array(
								//'external_id'=>$gv['rec_id'],
								//'goods_external_id'=>$gv['goods_id'],
								//'quantity'=>$gv['goods_number'],
								//'price'=>$gv['goods_price'],
								//'tax_rate'=>0
							//)	
						//);
					//}
			//}
			$sync_data[]=$data;
		}
		return $sync_data;
	}


	//同步单个会员到erp系统
	public function syncUserByUseridFromQueue($userid){
		//会员等级列表
		$rank_list_all = $GLOBALS['db']->getAll("select rank_id,rank_name,min_points,max_points from " . $GLOBALS['ecs']->table('user_rank'));
		$rank_list = array();
		foreach($rank_list_all as $key=>$val) {
			$rank_list[$val['rank_id']] = $val;
		}
		$userInfo = $GLOBALS['db']->getRow("SELECT user_id,aite_id,user_name,mobile_phone,user_rank,real_name,rank_points FROM ". $GLOBALS['ecs']->table('users') ." WHERE user_id=".$userid);
		$sync_data=array(
			"external_id"=>$userInfo['user_id'],
			"code"=>"shop_".$userInfo['user_id'],
			"name"=>$userInfo['user_name']." / ".$userInfo['real_name'],
			"main_mobile"=>$userInfo['mobile_phone'],
		);
		if($userInfo['user_rank']){
			$sync_data['c_category_id']=array($userInfo['user_rank'],$rank_list[$userInfo['user_rank']]['rank_name']);
		}else{
			foreach($rank_list_all as $kr=>$vr) {
				$min_point = $vr['min_points'];
				$max_point = $vr['max_points'];
				if($userInfo['rank_points'] <= $max_point && $userInfo['rank_points'] >= $min_point)
				{
					$sync_data['c_category_id']=array($vr['rank_id'],$vr['rank_name']);
					break;
				}
			}
		}
		$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"partner","sync_customer",array($sync_data),array());
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET sync_erp = 1,sync_result = '同步成功' WHERE user_id = ".$userid;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE user_id = ".$userid;
					$GLOBALS['db']->query($sql);
				}
		return $res;	
	}


	//同步店铺订单和平台订单到erp后台
	//参数 需要同步的订单ordersn 
	public function syncOrderByOrdersnsFromQueue($ordersns){
		$str = "";
		foreach($ordersns as $v){
			$str .= "'".$v."',";
		}
		$str = substr($str,0,-1);
		$sql = "SELECT a.order_id,a.order_sn,a.supplier_id,a.user_id,a.add_time,b.sync_erp,b.erp_id,a.consignee,a.address,a.mobile,c.region_name as province_name,d.region_name as city_name,e.region_name as district_name FROM ". $GLOBALS['ecs']->table('order_info') ."  a LEFT JOIN ". $GLOBALS['ecs']->table('supplier') ." b ON a.supplier_id=b.supplier_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." c ON a.province=c.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." d ON a.city=d.region_id  LEFT JOIN ". $GLOBALS['ecs']->table('region') ." e ON a.district=e.region_id WHERE  a.order_sn IN (".$str.")";
		$orderInfo = $GLOBALS['db']->getAll($sql);
		$sync_data = $this->getOrderlistByOrdersns($orderInfo);
      
       @file_put_contents("sync_order.txt", date('Y-m-d H:i:s',local_gettime())."同步的订单信息:".json_encode($sync_data).PHP_EOL, FILE_APPEND);
		if(isset($sync_data) && !empty($sync_data)){
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"sell.order","sync_sell_order",array($sync_data),array());
          @file_put_contents("sync_order.txt", "同步的订单返回信息:".json_encode($res).PHP_EOL, FILE_APPEND);
				$str_orderids = "";
				foreach($sync_data as $v){
					$str_orderids .= "'".$v['external_id']."',";
				}
				$str_orderids = substr($str_orderids,0,-1);
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET sync_erp = 1,sync_result = '同步成功' WHERE order_id in (".$str_orderids.")";
					$GLOBALS['db']->query($sql);
				}else{
                  $res['faultString'] = '同步失败';
					$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET sync_result = '同步失败' WHERE order_id in (".$str_orderids.")";
					$GLOBALS['db']->query($sql);
				}
			
			return $res;
		}
	}

	//同步店铺和平台退货单 mode 0新增 2删除 1更新(业务上用不到)  back_type 4为退货退款 1为退款  //用户更新退货物流时调用更新接口
	public function syncRefundOrderByOrdersnFromQueue($back_id,$mode,$back_type){
      $sql = "SELECT a.back_id,a.order_sn,a.order_id,a.shipping_name,a.invoice_no,a.supplier_id,a.user_id,a.add_time,b.sync_erp,b.erp_id,a.consignee,a.address,a.mobile,c.region_name as province_name,d.region_name as city_name,e.region_name as district_name FROM ". $GLOBALS['ecs']->table('back_order') ."  a LEFT JOIN ". $GLOBALS['ecs']->table('supplier') ." b ON a.supplier_id=b.supplier_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." c ON a.province=c.region_id LEFT JOIN ". $GLOBALS['ecs']->table('region') ." d ON a.city=d.region_id  LEFT JOIN ". $GLOBALS['ecs']->table('region') ." e ON a.district=e.region_id WHERE  a.back_id='".$back_id."'";
		$backInfo = $GLOBALS['db']->getRow($sql);
		if($back_type == 1){//退货流程
			$sync_data = $this->getBackByBackInfo(array($backInfo),$mode,$back_type);
			if(isset($sync_data) && !empty($sync_data)){
              
				$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"sell.order","sync_sell_order",array($sync_data),array());
				
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_erp = 1,sync_result = '同步成功' WHERE back_id = ".$back_id;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE back_id = ".$back_id;
					$GLOBALS['db']->query($sql);
				}
				return $res;
			}
		}elseif($back_type == 4){//退款流程
			$res = $this->sync_sell_order_refund(array($backInfo));
				if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_erp = 1,sync_result = '同步成功' WHERE back_id = ".$back_id;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " SET sync_result = '".get_magic_quotes_gpc($res['faultString'])."' WHERE back_id = ".$back_id;
					$GLOBALS['db']->query($sql);
				}
			return $res;
		}
	}
	
	//同步删除退货单
	public function syncRefundCancel($backInfo){
		
		if(!empty($backInfo)){
			$back_ids = array_column($backInfo,'back_id');
			@file_put_contents("sync_cancel.txt", "退货单移除:".json_encode($back_ids).PHP_EOL, FILE_APPEND);
			$res = $this->erpObj->execute_kw(ODOO_ERP_DB, $this->odooUid, ODOO_ERP_PSD,"sell.order","sync_sell_order_unlink",array('return',$back_ids),array());
			@file_put_contents("sync_cancel.txt", "退货单移除结果:".json_encode($res).PHP_EOL, FILE_APPEND);
		}
		//退款单删除同步更新odooerp订单状态状态
		
	}
}
//获取odooerp实例
//if(ODOO_ERP){
	//$odooErpObj = OdooErp::getInstance();
	//$GLOBALS['odooErpObj'] = $odooErpObj;		
//}

//echo "<pre>";print_r($GLOBALS['odooErpObj']);
?>