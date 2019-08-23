<?php
$getcom = $_GET["expressid"];
$getNu = $_GET["expressno"];
$phone = $_GET['expressphone'];

//echo $typeCom.'<br/>' ;
//echo $getNu ;
define('IN_ECS', true);

require('./init.php');

switch ($getcom){
    case "EMS"://ecshop后台中显示的快递公司名称
        $postcom = 'ems';//快递公司代码
        break;
    case "中国邮政":
        $postcom = 'ems';
        break;
    case "申通快递":
        $postcom = 'shentong';
        break;
    case "圆通速递":
        $postcom = 'yuantong';
        break;
    case "顺丰速运":
        $postcom = 'shunfeng';
        break;
    case "天天快递":
        $postcom = 'tiantian';
        break;
    case "韵达快递":
        $postcom = 'yunda';
        break;
    case "中通速递":
        $postcom = 'zhongtong';
        break;
    case "龙邦物流":
        $postcom = 'longbanwuliu';
        break;
    case "宅急送":
        $postcom = 'zhaijisong';
        break;
    case "全一快递":
        $postcom = 'quanyikuaidi';
        break;
    case "汇通速递":
        $postcom = 'huitongkuaidi';
        break;  
    case "民航快递":
        $postcom = 'minghangkuaidi';
        break;  
    case "亚风速递":
        $postcom = 'yafengsudi';
        break;  
    case "快捷速递":
        $postcom = 'kuaijiesudi';
        break;  
    case "华宇物流":
        $postcom = 'tiandihuayu';
        break;  
    case "中铁快运":
        $postcom = 'zhongtiewuliu';
        break;      
        /* 修改 by www.68ecshop.com start */
    case "百世汇通":
        $postcom = 'huitongkuaidi';
        break;
    case "全峰快递":
        $postcom = 'quanfengkuaidi';
        break;
    case "德邦":
        $postcom = 'debangwuliu';
        break;
        /* 修改 by www.68ecshop.com end */
    case "FedEx":
        $postcom = 'fedex';
        break;      
    case "UPS":
        $postcom = 'ups';
        break;      
    case "DHL":
        $postcom = 'dhl';
        break;      
    default:
        $postcom = '';
}
// echo $postcom;
// exit;
if($getcom && $getNu &&($phone)){
  
    //参数设置
    $post_data = array();
    $post_data["customer"] = $db->getOne('select value from ecs_shop_config where code="kuaidi100"');
    $key = $db->getOne('select value from ecs_shop_config where code="kuaidi100_api"');
    
    //$post_data["customer"] = '134525D0CF685DD0D5418A323B3B6AD1'; // 快递公司接口的customer
   // $key= 'bjIiztwW8037' ; // 快递公司给的 key
    $post_data["param"] = '{"com":"'.$postcom.'","num":"'.$getNu.'","phone":"'.$phone.'"}';

    $url='http://poll.kuaidi100.com/poll/query.do';
    $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
    $post_data["sign"] = strtoupper($post_data["sign"]);
    $o="";
    foreach ($post_data as $k=>$v)
    {
        $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
    }
    $post_data=substr($o,0,-1);
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        $data = str_replace("\"",'"',$result );
        $data = json_decode($result,true);
     
	
}else{
	echo '查询失败，请重试';
}
exit();




