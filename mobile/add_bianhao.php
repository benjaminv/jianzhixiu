<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
$_POST = json_decode(file_get_contents('php://input'),1);
//@file_put_contents("add_bianhao.txt", "同步的二维码:".json_encode($_POST).PHP_EOL, FILE_APPEND);
//{"data":{"pici":"20190603094135","list":[{"bianhao":"SN75195156376681","zd1":"1"}]}}
$data = $_POST['data'];
if(isset($data['pici']) && !empty($data['pici']) && isset($data['list']) && !empty($data['list'])){
	$batch_number = trim($data['pici']);
	$count = $db->getOne('select IFNULL(COUNT(id),0) from ecs_security_codes where batch_number="'.trim($data['pici']).'"');
	if($count == 0){ // 表示没有此编号
		$time = gmtime();
        $sql = 'INSERT INTO ecs_security_codes (batch_number,code_number,addtime,points,product) VALUES';
		foreach($data['list'] as $v){
			$sql .= '("'.$batch_number.'","'.$v['bianhao'].'","'. $time.'","'.$v['zd1'].'","'.$v['product'].'"),';
		}
        $sql = substr($sql,0, -1);
		if($db->query($sql)){
			echo json_encode(array('code'=>200,'msg'=>'success'));
		}else{
			echo json_encode(array('code'=>400,'msg'=>'database error'));
		}
	}else{
		echo json_encode(array('code'=>401,'msg'=>'already exists'));
	}
}else{
	echo json_encode(array('code'=>402,'msg'=>'param error'));
}