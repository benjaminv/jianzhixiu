<?php
//删除多个cookie，采用遍历数组方式
foreach($_COOKIE as $key=>$value){
    setcookie($key, '', time()-1000);
    setcookie($key, '', time()-1000, '/');
}
