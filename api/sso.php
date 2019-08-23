<?php
define('IN_ECS', true);
require('./init.php');
//error_reporting(E_ALL);
$actionList = array("poster",'all','order','commission','goods_collect','store_collect','collect','collect_del');
$action = $_GET['action'];
if(!in_array($action,$actionList)){
	echo json_encode(array("result"=>"fail","code"=>401,"msg"=>"非法操作"));exit;
}

$function_name = 'api_action_' . $action;
if(! function_exists($function_name)){
	echo json_encode(array("result"=>"fail","code"=>401,"msg"=>"非法操作"));exit;
}
call_user_func($function_name);

function api_action_all(){
	
}

//商品收藏
function api_action_goods_collect(){
	$portal_id = intval($_GET['user_id']);
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$page = intval($_GET['page'])?intval($_GET['page']):1;
	$pagesize = 10;
	$member = $db->getRow("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$portal_id, true);
	if(!isset($member) || empty($member)){
		echo json_encode(array("code"=>401,"msg"=>"非法操作"));exit;
	}
	$goods_collect = get_goods_collect($member['user_id'],$page,$pagesize);
	echo json_encode($goods_collect);
}

function api_action_collect_del(){
	$portal_id = intval($_GET['user_id']);
	$type = $_GET['type'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$member = $db->getRow("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$portal_id, true);
	if(!isset($member) || empty($member)){
		echo json_encode(array("code"=>401,"msg"=>"非法操作"));exit;
	}
	$user_id = $member['user_id'];
	$collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : 0;
	if($type == 'goods'){
		if($collection_id > 0)
		{
			$goods_collect = $db->getRow("SELECT rec_id FROM ". $ecs->table('collect_goods'). " WHERE rec_id='$collection_id' AND user_id ='$user_id'");
			if(!isset($goods_collect) || empty($goods_collect)){
				echo json_encode(array("code"=>401,"msg"=>"不存在的数据"));exit;
			}
			$res = $db->query('DELETE FROM ' . $ecs->table('collect_goods') . " WHERE rec_id='$collection_id' AND user_id ='$user_id'");
			if($res){
				echo json_encode(array("code"=>200,"msg"=>"删除成功!"));exit;
			}else{
				echo json_encode(array("code"=>401,"msg"=>"删除失败!"));exit;
			}
		}
	}elseif($type == 'store'){

		if($collection_id)
		{
			$store_collect = $db->getRow("SELECT id FROM ". $ecs->table('supplier_guanzhu'). " WHERE id='$collection_id' AND userid ='$user_id'");
			if(!isset($store_collect) || empty($store_collect)){
				echo json_encode(array("code"=>401,"msg"=>"不存在的数据"));exit;
			}
			$res = $db->query('DELETE FROM ' . $ecs->table('supplier_guanzhu') . " WHERE id='$collection_id' AND userid ='$user_id'");
		}
        if($res){
            echo json_encode(array("code"=>200,"msg"=>"删除成功!"));exit;
        }else{
            echo json_encode(array("code"=>401,"msg"=>"删除失败!"));exit;
        }
	}else{
		echo json_encode(array("code"=>401,"msg"=>"非法操作"));exit;
	}
}

//店铺收藏
function api_action_store_collect(){
	$portal_id = intval($_GET['user_id']);
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	$page = intval($_GET['page'])?intval($_GET['page']):1;
	$pagesize = 10;
	$member = $db->getRow("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$portal_id, true);
	if(!isset($member) || empty($member)){
		echo json_encode(array("code"=>401,"msg"=>"非法操作"));exit;
	}
	$store_collect = get_store_collect($member['user_id'],$page,$pagesize);
	echo json_encode($store_collect);
}

function api_action_collect(){
	$portal_id = intval($_GET['user_id']);
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$page = intval($_GET['page'])?intval($_GET['page']):1;
	$pagesize = 10;
	$member = $db->getRow("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$portal_id, true);
	if(!isset($member) || empty($member)){
		echo json_encode(array("code"=>401,"msg"=>"非法操作"));exit;
	}
	$goods_collect = get_goods_collect($member['user_id'],$page,$pagesize);
	$store_collect = get_store_collect($member['user_id'],$page,$pagesize);
	//echo "<pre>";print_r($store_collect);exit;
	echo json_encode(array('code'=>200,'goods_collect'=>$goods_collect['list'],'store_collect'=>$store_collect['list']));
}

function get_goods_collect($user_id,$page,$pagesize){
	$start = ($page-1)*$pagesize;
	$result = get_collection_goods($user_id,$pagesize,$start);
	foreach($result as $k=>$v){
		$result[$k]['goods_thumb'] = LIANMEI_SHOP . substr($v['goods_thumb'],2);
		$result[$k]['url'] = LIANMEI_SHOP . '/mobile/'.$v['url'];
	}
	return array('code'=>200,'list'=>$result);
}

function get_store_collect($user_id,$page,$pagesize){
	$start = ($page-1)*$pagesize;
	$result = get_follow_shops($user_id,$pagesize,$start);
	foreach($result as $k=>$v){
		$result[$k]['shop_logo'] = LIANMEI_SHOP ."/". $v['shop_logo'];
		$result[$k]['url'] = LIANMEI_SHOP . '/mobile/'.$v['url'];
	}
	return array('code'=>200,'list'=>$result);
}


function api_action_poster(){
	$portal_id = intval($_GET['user_id']);
	$result = getUserPoster($portal_id);
	echo json_encode($result);
}

function api_action_order(){
	$parent_portal_id = intval($_GET['parent_id']);
	$user_portal_id = intval($_GET['user_id']);
	$page = intval($_GET['page'])?intval($_GET['page']):1;
	$pagesize = 10;
	//根据分销来获取订单
	$result = getUserOrder($parent_portal_id,$user_portal_id,$page,$pagesize);
	echo json_encode($result);
}

function api_action_commission(){
	$portal_id = intval($_GET['user_id']);
	$result = getUserCommission($portal_id);
	echo json_encode($result);
}

function getUserOrder($parent_portal_id,$user_portal_id,$page,$pagesize){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$start = ($page-1)*$pagesize;
	$parent_id = $db->getOne("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$parent_portal_id, true);
	
	$user_id = $db->getOne("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$user_portal_id, true);
	if(!$parent_id || !$user_id){
		return array("code"=>401,"msg"=>"非法操作");exit;
	}
	
	$sql = "SELECT a.money,b.order_sn,b.pay_name,b.goods_amount,b.shipping_fee,b.pay_time FROM ".$ecs->table('affiliate_log'). " a LEFT JOIN ".$ecs->table('order_info')." b ON a.order_id=b.order_id WHERE b.user_id=".$user_id.' AND a.separate_type=0 AND  a.user_id='.$parent_id." ORDER BY a.`time` DESC LIMIT $start,$pagesize";
	$list = $db->getAll($sql);
	foreach($list as $k=>$v){
		$list[$k]['pay_time'] = date("Y-m-d H:i:s",$v['pay_time']);
	}
	return array("code"=>200,"list"=>$list);exit;
}

function getUserPoster($portal_id){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$member = $db->getRow("SELECT `user_id`,`headimg` as avatar,`user_name` as nickname FROM " . $ecs->table('users') . " WHERE `portal_id`=".$portal_id, true);
	
	if(!isset($member) || empty($member)){
		return array("result"=>"fail","code"=>401,"msg"=>"非法操作");exit;
	}
	$user_id = $member['user_id'];
	//是否生成过二维码
	//$sql = "SELECT code FROM " . $ecs->table('user_qrcode') . " where user_id='$user_id'";
	//$qrcode = $db->getOne($sql);
	$dirpath = $_SERVER['DOCUMENT_ROOT']."/mobile/images/qrcode/";
	if(!file_exists($dirpath)){
		mkdir($dirpath, 0777);
	}
	$qrcodeDir = $dirpath."poster_".$user_id.'.png';
	$poster = $dirpath."1_".$user_id.'_ok.png';
	//error_reporting(E_all);
	if(!file_exists($poster)){
		set_time_limit(80); 
		ignore_user_abort(true); 
		//生成推广海报
		$qrcode_left = 473;
		$qrcode_top = 733;
		$avatar_left = 22;
		$avatar_top =  698;
		$nickname_left =  210;
		$nickname_top = 728;
		$nickname_fontsize =  24;
		/* 背景图片 */   
		$bgimg = $_SERVER['DOCUMENT_ROOT']."/mobile/images/posterbg.jpg";
		$urlstr = LIANMEI_SHOP . "/mobile";
		$selfUrl = OSS_LOGIN.'/?dest='.urlencode($urlstr).'&uid='.$portal_id;
		$qrcodeObj = new QRcode();
		$qrcodeObj->png($selfUrl,$qrcodeDir,"L",4,3);
		if(!file_exists($qrcodeDir)){
			return array("code"=>400,"msg"=>"二维码生成失败");exit;
		}
		
		/* 合成二维码 */
		mergeImg($bgimg, $qrcodeDir, $dirpath, "1_".$user_id.".png", $qrcode_left, $qrcode_top);
		
		/* 合成头像 */
		if(empty($member['avatar'])){
			$avatar = LIANMEI_SHOP."/mobile/images/default_avatar.jpg";
		}else{
			$avatar = $member['avatar'];
		}
		saveImage($avatar, $dirpath."1_".$user_id."_","avatar.png");
		mergeImg($dirpath."1_".$user_id.".png", $dirpath."1_".$user_id."_avatar.png", $dirpath, "1_".$user_id.".png", $avatar_left, $avatar_top);	
		
		$file = $dirpath."1_".$user_id.".png";
		$imageinfo = @getimagesize($file);
		$image = imageCreateFromExt($file,$imageinfo[2]);
		/* 合成昵称 */
		/* 设置字体的路径 */
		$font = $_SERVER['DOCUMENT_ROOT']."/mobile/ttf/yahei.ttf";  
		$font_color['r'] = $font_color['g'] = $font_color['b'] = 255;
		/* 设置字体颜色和透明度 */
		$color = imagecolorallocatealpha($image, $font_color['r'], $font_color['g'], $font_color['b'], 0);
		imagettftext($image, $nickname_fontsize, 0, $nickname_left, $nickname_top, $color, $font, $member['nickname']);
		/* 保存图片 */
		$okfield = $dirpath."1_".$user_id."_ok.png";
		imagepng($image, $okfield);  
		/*销毁图片*/  
		imagedestroy($image);
		/* 删除多余文件 */
		@unlink($dirpath."1_".$user_id.".png");
		@unlink($dirpath."poster_".$user_id.".png");
		@unlink($dirpath."1_".$user_id."_avatar.png");
	}
	$qrcode = LIANMEI_SHOP . "/mobile/images/qrcode/1_".$user_id.'_ok.png';
	return array('code'=>200,'poster'=>$qrcode);
}

function getUserCommission($portal_id){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$member = $db->getRow("SELECT `user_id` FROM " . $ecs->table('users') . " WHERE `portal_id`=".$portal_id, true);
	if(!isset($member) || empty($member)){
		return array("code"=>401,"msg"=>"非法操作");exit;
	}
	$commission = get_total_money_by_user_id($member['user_id'],1);
	return array("code"=>200,"commission"=>$commission);exit;
}

function mergeImg($image,$merge, $savepath = null, $savename = null, $x, $y, $alpha = 100){
	$savefile = $savepath.$savename;
	
	$imageinfo = @getimagesize($image);
	$imageObj = imageCreateFromExt($image,$imageinfo[2]);

	$waterinfo = @getimagesize($merge);
	$mergeObj = imageCreateFromExt($merge,$waterinfo[2]);
	
	@imagecopymerge($imageObj, $mergeObj, $x, $y, 0, 0, $waterinfo[0], $waterinfo[1], $alpha);
	@imagepng($imageObj, $savefile);
	@imagedestroy($imageObj);
	@imagedestroy($mergeObj);
}

function imageCreateFromExt($image,$type){
	switch ($type) {
		case 1:
			$im = @imagecreatefromgif($image);
				break;
		case 2:
				$im = @imagecreatefromjpeg($image);
				break;
		case 3:
				$im = @imagecreatefrompng($image);
				break;
	}
	return $im;
}
function hexTorgb($hexColor){
		$color = str_replace('#', '', $hexColor);
		if (strlen($color) > 3) {
			$rgb = array('r' => hexdec(substr($color, 0, 2)), 'g' => hexdec(substr($color, 2, 2)), 'b' => hexdec(substr($color, 4, 2)));
		} else {
			$color = $hexColor;
			$r = substr($color, 0, 1) . substr($color, 0, 1);
			$g = substr($color, 1, 1) . substr($color, 1, 1);
			$b = substr($color, 2, 1) . substr($color, 2, 1);
			$rgb = array('r' => hexdec($r), 'g' => hexdec($g), 'b' => hexdec($b));
		}
		return $rgb;
}

function saveImage($path, $file_dir, $image_name){
		if (!preg_match('/\/([^\/]+\.[a-z]{3,4})$/i', $path)) {
			//die('获取用户头像失败，请检查系统是否正常获取粉丝头像');
		}
		$ch = curl_init();
		$fp = fopen($file_dir . $image_name, 'wb');
		//exit;
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
}

