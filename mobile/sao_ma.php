<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_pay_fencheng.php');

// 检测是否登录吧
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] >0){

    $bianhao  = isset($_REQUEST['bianhao'])?$_REQUEST['bianhao']:'0';
    if($bianhao){

		$is_have_saoma = $db->getAll('select * from ecs_security_codes where code_number="'.$bianhao.'"');

		if($is_have_saoma[0]){
            // 表示未扫码
			if(!$is_have_saoma[0]['is_lottery']){ 

				$jifen = trim($_REQUEST['jifen']);
				$zengsong = 'block';
                $time = gmtime();


		        $time = date('Y-m-d',$time);
			    $id = $is_have_saoma[0]['dzp_id'];
		        if(! $id){

		        	$zengsong = 'none';
		        	$message = '抽奖id不存在，请确保有抽奖活动';
		        }else{

					$password = $bianhao; // 防伪码
			        $time = $is_have_saoma[0]['addtime'];   //时间
			        $md = md5($bianhao.$time.'code_number'); // key
		        }

		        
			}else{ // 表示这个商品已经被别人扫过

				$zengsong = 'none';
                $time = gmtime();
				$up = $db->query('update ecs_security_codes set scan_num=scan_num+1,update_scantime=\''.$time.'\' where code_number=\''.$bianhao.'\'');

				$message = '该商品已经被查询过第'.$is_have_saoma[0]['scan_num'].'次,谨防假冒';
			}

			
		}else{ // 表示没有此防伪码


			$jifen = $_REQUEST['jifen'];
			$zengsong = 'none';	

			$message = '该二维码不存在商城系统中';
		} 
	}else{
		$jifen = 0;
		$zengsong = 'none';

		$message = '携带参数错误，请重新加载';
	}
}else{

	$jifen = 0;
	$zengsong = 'none';

	$message = '您未登录，请先登录后再进行抽奖';
}

?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
	<style type="text/css">
	    *{
	    	margin: 0;
	    	padding:0;
	    	font-size: 43px;
	    }
		.zhe_zhao{
			z-index: 99;
			position: absolute;
			width:100%;
			height: 100%;
			margin-top: -300px;
			background-color: #353232;
		}
		.button{
			list-style: none;
			width: 200px;
			height: 80px;
			position: relative;
			left:50%;
			margin-left: -100px;
            top: 30%;
            margin-top: 60px;
			
		}
		.buten{
			background-color: #02a1c1;
			border:none;
			margin-top: 30px;
			text-decoration: none;
			float: left;
            margin-left: 270px;
			font-size: 43px;
			color: #fff;
			padding: 30px;
			padding-left:20px;
			padding-right: 10px;

			border-radius: 10px;

		}
	</style>
</head>
<body style="background-color: #29AC78;color: #fff;">
	<div class="zhe_zhao" style="display: <?php  if(isset($_SESSION['user_id']) && $_SESSION['user_id']){echo 'none';}else{echo 'block';}?>">
		<a href="./user.php?teshu_tiao_zhuan=tiaozhaunhuilai&bianhao=<?php echo $_REQUEST['bianhao'];?>&jifen=<?php echo $_REQUEST['jifen'];?>" style="text-decoration: none;">
		    

		    <h3 style="color:#fff;text-align: center;margin-top:260px;font-size: 33px;">您还没有登录，请先登入后再进行操作！！！</h3>
			<button  class="button" style="font-size: 33px;">立即登录</button>

		</a>
	</div> 
	<div style="font-size:18px;margin-top:100px;text-align: center;margin-top: 160px; display:<?php echo $zengsong; ?>">

		<header style="margin-top: 20px;">第<i>1</i>次扫码，<?php if(isset($is_have_saoma[0]['product'])){echo $is_have_saoma[0]['product'];}?>商品为正品</header>

    	<div style="margin-top: 60px;margin-bottom: 20px;">抽奖送大礼，礼品送不停</div>
        <div style="margin-top: 40px;">
    		<a href="./dzp.php?id=<?php if(isset($id)){echo $id;}?>&password=<?php if(isset($password)){echo $password;}?>&time=<?php if(isset($time)){echo $time;}?>&md=<?php if(isset($md)){echo $md;}?>" style="margin-top: 60px;text-decoration: none;margin-left: 00px;">
    		<button  class="buten" style="width: 400px;">立即抽奖</button>
    	</a>
        </div>
    	
		
	</div> 
    <div style="clear: both;"></div>
	<div style="text-align: center;margin-top: 300px;color:red;font-size:50px;display:<?php if ($zengsong == 'block') {echo 'none';}else{ echo 'block';}?> ">
		<span><?php if(isset($message)){ echo $message;}?></span>

        <a href="./index.php">
        	<button  class="buten" style="margin-top: 300px;margin-left: 360px;">返回首页</button>
        </a>
	</div>
    
</body>
</html>