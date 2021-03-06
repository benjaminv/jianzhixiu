<?php define('IN_ECS', true);
		require(dirname(__FILE__) . '/includes/init.php');
		
	?>
<style>
    *{  margin: 0;padding: 0;}
    a{text-decoration: none;}
    li, img, label, input {vertical-align: middle;}
    cite, em, s, i, b {font-style: normal;}
    .hid{display:none;}
    .show{display: block;}

    .g_nav_fix{width: 40px;height: 40px;position: fixed;right: 5%; top: 20%; z-index: 999;}
    .g_nav_btn{width: 40px;height: 40px;background: url(<?php echo LIANMEI_SHOP;?>/mobile/themesmobile/dm299_com/images/img_g_nav_btn.png) no-repeat center;
      -webkit-background-size: 100%;background-size: 100%;}
    .g_nav{ width: 112px; overflow: hidden;float: right;}
    .triangle{
        width: 100%;
      height: 20px;
    }
    .triangle h2{
        width: 0;
        height: 0;
        border-style: solid;
        border-color: transparent transparent #565553 transparent;
        border-width: 10px;
        transition: 0.6s;
        float: right;
        margin-right: 10px;
        padding: 0px;
    }
    .g_nav ul {
        width: 100%;
        overflow: hidden;
        background-color: rgba(27,27,27,0.8);
        border-radius: 2px;
    }
    .g_nav li {
        width: 100%;
        height: 40px;
        border-bottom: 1px solid #626262;
        line-height: 40px;
    }
    .g_nav a{
        color: #f3f1f1;
    }
    .g_nav a i{
      margin-left: 7px; 
    }
    .g_nav li span {
        display: block;
        width: 25px;
        height: 25px;
        float: left;
        margin-top: 5px;
        margin-left: 5px;
    }
    .g_nav .nav_peixun a span{
      background: url(<?php echo LIANMEI_SHOP;?>/mobile/themesmobile/dm299_com/images/img_peixun.png) no-repeat center;
      -webkit-background-size: 80%;
      background-size: 80%;
    }
    .g_nav .nav_shangcheng a span{
      background: url(<?php echo LIANMEI_SHOP;?>/mobile/themesmobile/dm299_com/images/img_shangcheng.png) no-repeat center;
      -webkit-background-size: 80%;
      background-size: 80%;
    }
    .g_nav .nav_luntan a span{
      background: url(<?php echo LIANMEI_SHOP;?>/mobile/themesmobile/dm299_com/images/img_luntan.png) no-repeat center;
      -webkit-background-size: 80%;
      background-size: 80%;
    }
    .g_nav .nav_mendian a span{
      background: url(<?php echo LIANMEI_SHOP;?>/mobile/themesmobile/dm299_com/images/img_mendian.png) no-repeat center;
      -webkit-background-size: 80%;
      background-size: 80%;
    }
</style>

  
	
  <div class="g_nav_fix">
    <div class="g_nav_btn" onclick="show_g_nav();">
    </div>
    <div class="g_nav hid" id="global_nav">
      <div class="triangle" >
        <h2></h2>
      </div>
      <ul>
        <li class="nav_peixun">
          <a href="<?php echo LIANMEI_EDUCATION;?>/app/index.php?i=1&c=entry&do=index&m=fy_lessonv2">
            <span></span>
            <i>在线培训</i>
          </a>
        </li>
        <li class="nav_shangcheng">
          <a href="<?php echo LIANMEI_SHOP;?>/mobile/">
            <span></span>
            <i>美业商城</i>
          </a>
        </li>
        <li class="nav_luntan">
          <a href="<?php echo LIANMEI_CLUB;?>/forum.php?mod=guide&view=digest&mobile=2">
            <span></span>
            <i>经验交流</i>
          </a>
        </li>
        <li class="nav_mendian">
          <a href="<?php echo LIANMEI_MARKET;?>/s/probe">
            <span></span>
            <i>门店获客</i>
          </a>
        </li>
      </ul>
    </div>
  </div>
  
  <script src="<?php echo LIANMEI_SHOP;?>/mobile/themesmobile/dm299_com/js/jquery.js"></script>
  <script>
    //显示菜单
    function show_g_nav() {
      if($('#global_nav').css('display')=='none') {
        $('#global_nav').removeClass('hid');
        $('#global_nav').addClass('show');
      } else {
        $('#global_nav').removeClass('show');
        $('#global_nav').addClass('hid');
      }
     }
     
      $(window).on("scroll", function() {  
        $('#global_nav').removeClass('show');  
        $('#global_nav').addClass('hid');
      });
  </script>
  <script type="text/javascript">
	var _url = window.location.href;
	console.log(_url);
	var peixun_url = "edu.lian-mei.com", luntan_url = "club.lian-mei.com", shangcheng_url = "shop.lian-mei.com", mendian_url = "management.lian-mei.com";
	if (_url.indexOf(peixun_url) != -1) {
		$('.nav_peixun').addClass('hid');
	}
	if (_url.indexOf(luntan_url) != -1) {
		$('.nav_luntan').addClass('hid');
	}
	if (_url.indexOf(shangcheng_url) != -1) {
		$('.nav_shangcheng').addClass('hid');
	}
	if (_url.indexOf(mendian_url) != -1) {
		$('.nav_mendian').addClass('hid');
	}
  </script>