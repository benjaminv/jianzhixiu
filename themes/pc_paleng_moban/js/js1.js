// JavaScript Document

/*购物指南选项卡*/
function nTabs(thisObj,Num){
        if(thisObj.className == "curr")return;
        var tabObj = thisObj.parentNode.id;
        var tabList = document.getElementById(tabObj).getElementsByTagName("span");
        for(i=0; i <tabList.length; i++)
        {
        if (i == Num)
        {
           thisObj.className = "curr"; 
              document.getElementById(tabObj+"_Content"+i).style.display = "block";
        }else{
           tabList[i].className = "normal"; 
           document.getElementById(tabObj+"_Content"+i).style.display = "none";
        }
        } 
        }

	
	
			
/*购物指南下拉*/
function activity_category(id) 
{
var conf = {'show':'themes/xaphp/images/wen.gif','hide':'themes/xaphp/images/wen2.gif'};
var obody = document.getElementById('activity_category_'+id);
var obj=document.getElementById('activity_categorytop_'+id);
if(obody.style.display == 'none') 
{
obody.style.display = '';
obj.style.background="url("+conf.show+")";
obj.style.color="#FFFFFF";
} else {
obody.style.display = 'none';
obj.style.background="url("+conf.hide+")";
obj.style.color="#646464";
}
}


/*早市下拉*/
function activity_category1(id) 
{
var conf = {'show':'images/up.gif','hide':'images/up.gif'};
var obody = document.getElementById('activity_category_'+id);
var obj=document.getElementById('activity_categorytop_'+id);
if(obody.style.display == 'none') 
{
obody.style.display = '';
obj.style.background="url("+conf.show+") no-repeat 80px 5px;";
} else {
obody.style.display = 'none';
obj.style.background="url("+conf.hide+") no-repeat 80px 5px;";
}
}





(function(){var a=function(){var t1 , c=DOM.offset(".mallFpCon").top;var b=(DOM.viewportWidth()-1883)/2;if(S.UA&&S.UA.ie&&S.UA.ie<=7){t1=2}else{t1=0}DOM.css("#J_FlashBg",{top:(c-t1)+"px"});DOM.css("#J_FlashBgCon",{marginLeft:b+"px"});DOM.show("#J_FlashBg")};a();Event.on(window,"resize",function(){a()})})();




  //焦点图切换JS
var n=0;
var showNum = document.getElementById("num");
function Mea(value){
	n=value;
	setBg(value);
	plays(value);
	}
function setBg(value){
	for(var i=0;i<5;i++){
	   if(value==i){
	     document.getElementById("a"+value).className='act';      
			}	else{	
			 document.getElementById("a"+i).className='nor';
			}  
	} 
}
function plays(value){ 
		 for(i=0;i<5;i++){
			  if(i==value){			  
			  	document.getElementById("pc_"+value).style.display="block";
			  }else{
			    document.getElementById("pc_"+i).style.display="none";			    
			  }			
		}	
}

function clearAuto(){clearInterval(autoStart)}
function setAuto(){autoStart=setInterval("auto(n)", 3000)}
function auto(){
	n++;
	if(n>4)n=0;
	Mea(n);
} 
function sub(){
	n--;
	if(n<0)n=4;
	Mea(n);
} 
setAuto();






/*详情页小图大图切换*/
function $(id) { return document.getElementById(id); }

<!-- 注意：：注意：：实现上一张，下一张的代码在最下面的地方。注意：：此版本为带定时器自动切换图片版本。2000毫秒换一次图。 -->

<!-- 另外：后面代码有更改，图片切换方法已封装成统一的接口changeimg(),可节省文件所占空间。 -->

<!--此函数的目的应该是如果window.onload事件不等于function，也就是如果window.onload事件没有指定，就把window.onload事件事件指定为传进去的func函数（而在后面很显然传进去的是(iFocusChange)这一个函数）。最终的目的是把窗口的window.onload事件事件指定为func，然后让窗口在加载时执行func。-->
<!-- 否则，就把window.onload事件指定为匿名函数function(){ oldonload();func();} -->
<!-- 不过搞不清楚传进去的那个(iFocusChange)函数有啥作用，还有那个匿名函数的目的。-->
<!-- 其实此处我也不太理解这个函数的目的，只是猜测一下而已。。 -->

function addLoadEvent(func){                <!-- 此函数愣是没有看懂他的功能 请说明一下 （oldonload func根本就没有使用？？）-->
 var oldonload = window.onload;
 if (typeof window.onload != 'function') {   <!--窗口加载时，它的类型不等于function就将其变成func？？？-->
  window.onload = func;
 } else {
  window.onload = function(){
   oldonload();
   func();
  }
 }
}

<!-- 这个函数通过传入当前得到焦点的小图片元素还有x，y之类的来切换图片的。实现的话，这个太复杂了，经过复杂的数学计算得出的吧，看不太懂。只知道作用。。 -->
function moveElement(elementID,final_x,final_y,interval) { <!--这一段明显示来移动图片的，看到了一些坐标轴的变换，但是它这里到底是如何实现其移动的？ -->
  if (!document.getElementById) return false;
  if (!document.getElementById(elementID)) return false;
  var elem = document.getElementById(elementID);
  if (elem.movement) {
    clearTimeout(elem.movement);
  }
  if (!elem.style.left) {
    elem.style.left = "0px";
  }
  if (!elem.style.top) {
    elem.style.top = "0px";
  }
  var xpos = parseInt(elem.style.left);
  var ypos = parseInt(elem.style.top);
  if (xpos == final_x && ypos == final_y) {
  return true;
  }
  if (xpos < final_x) {
    var dist = Math.ceil((final_x - xpos)/10);
    xpos = xpos + dist;
  }
  if (xpos > final_x) {
    var dist = Math.ceil((xpos - final_x)/10);
    xpos = xpos - dist;
  }
  if (ypos < final_y) {
    var dist = Math.ceil((final_y - ypos)/10);
    ypos = ypos + dist;
  }
  if (ypos > final_y) {
    var dist = Math.ceil((ypos - final_y)/10);
    ypos = ypos - dist;
  }
  elem.style.left = xpos + "px";
  elem.style.top = ypos + "px";
  var repeat = "moveElement('"+elementID+"',"+final_x+","+final_y+","+interval+")";
  elem.movement = setTimeout(repeat,interval);
}

<!-- 设置所有的小图片样式还有图片说明的样式 -->
function classNormal(iFocusBtnID){
 var iFocusBtns= $(iFocusBtnID).getElementsByTagName('li');  <!--.getElementsByTagName -->
 for(var i=0; i<iFocusBtns.length; i++) {
  iFocusBtns[i].className='normal';
 }
}

<!-- 设置鼠标放在上面的小图片还有图片说明的样式（也就是获得焦点的小图片的样式与图片说明文字的样式。） -->
function classCurrent(iFocusBtnID,n){
 var iFocusBtns= $(iFocusBtnID).getElementsByTagName('li');
 iFocusBtns[n].className='current';
}

var focusImage=new Array();

<!-- 把每一个小图片的onmouseover事件都设置为相应的切换图片的匿名函数。例如第0张图片，把切换函数设定为moveElement('ifocus_piclist',0,0,5);然后再设置当前获得焦点的小图片0的CSS样式和其他失去焦点的小图片的CSS样式等。 -->
function iFocusChange() {
 if(!$('ifocus')) return false;
 $('ifocus').onmouseover = function(){atuokey = true}; <!-- autokey -->
 $('ifocus').onmouseout = function(){atuokey = false};
 var iFocusBtns = $('ifocus_btn').getElementsByTagName('li');<!-- 获得焦点的小图片 -->
 var listLength = iFocusBtns.length;<!-- 获得获得焦点的图片的索引？？ -->

<!-- 如果鼠标放在第0张图片上则把第0张小图片和说明样式设置为classCurrent等等、、 -->
 iFocusBtns[0].onmouseover = function() {
  changeimg(0);
  $('image_0').innerHTML=focusImage[0];
 }

<!-- 如果鼠标放在第1张图片上则把第1张小图片和说明样式设置为classCurrent等等、、 -->
 if (listLength>=2) {
  iFocusBtns[1].onmouseover = function() {
   changeimg(1);
    $('image_1').innerHTML=focusImage[1];
  }
 }

<!-- 如果鼠标放在第2张图片上则把第2张小图片和说明样式设置为classCurrent等等、、 -->
 if (listLength>=3) {
  iFocusBtns[2].onmouseover = function() {
   changeimg(2);
    $('image_2').innerHTML=focusImage[2];
  }
 }

<!-- 如果鼠标放在第3张图片上则把第3张小图片和说明样式设置为classCurrent等等、、 -->
 if (listLength>=4) {
  iFocusBtns[3].onmouseover = function() {
   changeimg(3);
    $('image_3').innerHTML=focusImage[3];
  }
 }
 
<!-- 如果鼠标放在第4张图片上则把第4张小图片和说明样式设置为classCurrent等等、、 -->
  if (listLength>=5) {
  iFocusBtns[4].onmouseover = function() {
   changeimg(4);
   $('image_4').innerHTML=focusImage[4];
  }
 }
 
}
var interval = setInterval('autoiFocus()',4000);
var atuokey = false;

<!-- 目的是实现自动切换图片。每2000毫秒所触发的事件。 -->
function autoiFocus() {
 if(!$('ifocus')) return false;
 if(atuokey) return false;
 var focusBtnList = $('ifocus_btn').getElementsByTagName('li');
 var listLength = focusBtnList.length;
 for(var i=0; i<listLength; i++) {
  if (focusBtnList[i].className == 'current') var currentNum = i;
$('image_'+i).innerHTML=focusImage[i];<!-------这一行代码注释掉就只加载小图，点击小图才加载大图；不注释就先加载小图，再加载大图。---->
 }
 if (currentNum==0&&listLength!=1 ){
  changeimg(1);
 }
 if (currentNum==1&&listLength!=2 ){
  changeimg(2);
 }
 if (currentNum==2&&listLength!=3 ){
  changeimg(3);
 }
 
 if (currentNum==3&&listLength!=4 ){
  changeimg(4);
 }
 
 if (currentNum==4 ){
  changeimg(0);
 }
 if (currentNum==1&&listLength==2 ){
  changeimg(0);
 }
 if (currentNum==2&&listLength==3 ){
  changeimg(0);
 }
 
  if (currentNum==3&&listLength==4 ){
  changeimg(0);
 }
}

addLoadEvent(iFocusChange);<!-- 这里就是我所说的把window.onload函数指定为iFocusChange。iFocusChange这个函数的作用是当鼠标移动到另一张图片时执行。。 -->

function hidden(id){
	for(var i = 0 ; i < 7 ; i ++){
		document.getElementById('hrefTitle'+(i+1)).className='a2';
		document.getElementById('href'+(i+1)).style.display='none';
		
	}
	document.getElementById('hrefTitle'+id).className='a1';
	document.getElementById('href'+id).style.display='';
	
}
function hidden2(id){
	for(var i = 0 ; i < 3 ; i ++){
		document.getElementById('href2Title'+(i+1)).className='a3';
		document.getElementById('href2'+(i+1)).style.display='none';
		
	}
	document.getElementById('href2Title'+id).className='a4';
	document.getElementById('href2'+id).style.display='';
	
}
function hidden3(id){
	for(var i = 0 ; i < 5 ; i ++){
		document.getElementById('href3Title'+(i+1)).className='a6';
		document.getElementById('href3'+(i+1)).style.display='none';
		
	}
	document.getElementById('href3Title'+id).className='a5';
	document.getElementById('href3'+id).style.display='';
	
}



<!-- 注意：：：此处实现上一张，下一张！！！！！！！！ -->


<!-- 这下面全是实现上一张，下一张，以及点击切换任意一张图片的代码 -->


<!-- 这里是一些我从原代码里提取出来的一些图片切换代码，封装成可以直接切换到任意一张图片的函数。 -->
<!-- 切换图片的代码只是那么三个函数的调用。。 -->
<!-- 如果要加入上一张，下一张的功能，可以设置一个全局变量之类的来存放当前显示的图片索引，然后在点击下一张就把索引加1，然后再根据索引来调用不同的图片切换函数。如当前显示的图片为第0张，我设当前索引为0，然后点击下一张，把所以加1，那么新的索引值就为1，那么我就应该调用显示第一张图片的切换函数。 -->
<!-- 其实我感觉只要把关键的一些代码提取出来，然后自己再按不同的思路再去设计可能比修改更好一些。因为修改的话最麻烦的是难理解原作者的思路。 -->

<!-- 这里切换不同的图片根本的不同是moveElement('ifocus_piclist',0,X,5);这个函数的第三个参数（也就是X，每一幅图片相差375个单位）。 -->
<!-- 另外补充一句：这里的实现可能比较拘泥于5张图片，如果有更多的图片，此一大段代码只能作为参考。具体的实现比较麻烦。。 -->

<!----------------------------------------------- 这里是无用的代码，图片切换已封装成统一的方法（仅供参考，可删除）--------------------------------->
<!-- 这是直接切换到第0张图片的函数 -->

<!----------------------------------------------- 多余代码到此结束，此段代码可删除，节省空间。。----------------------------------------------------------------->


<!-- 注意：此处已将各个图片的不同切换方法封装成统一的方法，仍然是通过小图片索引去切换图片。直接通过要显示的小图片的索引去切换大图。 -->
function changeimg(Index){
	var imgs = document.getElementById('ifocus_btn').getElementsByTagName("li");
  moveElement('ifocus_piclist',0,-(330*Index),imgs.length);
  classNormal('ifocus_btn');
  classCurrent('ifocus_btn',Index);
<!-- 每一次通过鼠标移到小图片上或者按下按钮切换图片时，把定时器清除掉然后重新设置，等于重新计时。防止计时器自动切换图片与手动切换图片冲突。 -->
  clearInterval(interval); 
  interval = setInterval('autoiFocus()',4000);
}


<!-- 获取当前在显示区域里的大图对应的小图片的索引 -->
function getcurrentindex(){
	var imgs = document.getElementById('ifocus_btn').getElementsByTagName("li");
	for(var i=0;i<imgs.length;i++){
		if(imgs[i].className == 'current')
			return i;
	}
}

<!-- 这里是点击上一张，或者下一张，然后通过获取当前显示的图片的索引去切换到上一张或者下一张。注意：choice为0时则是切换到下一张的功能，1为上一张。 -->
function nextlastimg(choice){
	var imgs = document.getElementById('ifocus_btn').getElementsByTagName("li");
	if(choice == 0)<!-- 注意：choice为0时则是切换到下一张的功能。通过小图片索引加1或者减1实现。 -->
{
	var nextindex = getcurrentindex() + 1;
	if(nextindex == imgs.length){ nextindex = 0;}
}
else
{
	var nextindex = getcurrentindex() - 1;
	if(nextindex == -1){ nextindex = imgs.length-1;}
}
	changeimg(nextindex);
}


