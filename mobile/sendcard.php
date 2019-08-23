<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_order.php');
error_reporting(E_ALL);
$orderid= $_GET['id'];
send_order_bonus($orderid,0);