<?php
/*
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: jiekou.php 17217 2011-01-19 06:29:08Z liubo $

详述: 参见概述 PS: 强烈建议修改默认接口密码!!!
使用: 将本文件放在网站根目录下, 存放路径示例: D:\Htdoc\ECShop_V2.7.3_UTF8_release1106\jiekou.php

必填字段: title
附件字段: file
默认使用字段: title  content author add_time
欲使用其它字段信息, 请参考以下示例数据

示例数据
$_POST = array(
	'title' => '测试文章-'. date('Y-m-d H:i:s'), //标题
	'article_cat' => 2, //文章分类ID
	'article_type' => 1, //文章重要性 0普通 1置顶
	'is_open' => '1', //是否显示 0不显示 1显示
	'author' => '今天优品', //文章作者
	'author_email' => 'hanbing@gmail.com', //作者email
	'keywords' => '关键字1,关键字2,关键字3', //SEO-关键字
	'content' => '测试文章内容-'. date('Y-m-d H:i:s'), //文章内容
	'file_url' => 'http://baidu.com/demo.jpg', //文件网址
	'link_url' => 'http://locoy.com/', //外部链接
	'description' => 'SEO-描述', //网页描述
	'add_time' => '2013/9/25 16:55:08', //添加时间
	'click_count' => '阅读量',
);

*/
header('Content-type:text/html;charset=UTF-8');

//!!! 接口密码验证,在此修改接口密码 !!!
if($_REQUEST['password'] != 'jtyp880511') jiekou_msg(0, '接口密码错误!');

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');

//获取分类下拉菜单
if(!empty($_REQUEST['cattree'])) get_cat_tree();

/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';

if(!isset($_POST['title']) || $_POST['title'] === '') jiekou_msg(0, '文章标题不能为空!');
if(!isset($_POST['content']) || $_POST['content'] === '') jiekou_msg(0, '文章内容不能为空!');

$_POST['is_open'] = 1; //是否显示

/*检查是否重复*/
$is_exist = $db->getone("select * from ". $ecs->table('article') ." where title ='". $_POST['title'] ."' and cat_id = '". $_POST['article_cat'] ."'");
if ($is_exist)
{
	jiekou_msg(0, '已存在的文章标题: '. stripslashes($_POST['title']));
}

/* 取得文件地址 */
$file_url = '';
if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none'))
{
    // 检查文件格式
    if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types))
    {
    	jiekou_msg(0, '不允许的附件类型!');
    }

    // 复制文件
    $res = upload_article_file($_FILES['file']);
    if ($res != false)
    {
        $file_url = $res;
    }
}

if ($file_url == '')
{
    $file_url = $_POST['file_url'];
}

/* 计算文章打开方式 */
if ($file_url == '')
{
    $open_type = 0;
}
else
{
    $open_type = $_POST['content'] == '' ? 1 : 2;
}

/*插入数据*/
$add_time = !empty($_POST['add_time']) ? strtotime($_POST['add_time']) : gmtime();

if (empty($_POST['cat_id']))
{
    $_POST['cat_id'] = 0;
}
$sql = "INSERT INTO ".$ecs->table('article')."(title, cat_id, article_type, is_open, author, ".
            "author_email, keywords, content, add_time, file_url, open_type, link, description ,click_count) ".
        "VALUES ('$_POST[title]', '$_POST[article_cat]', '$_POST[article_type]', '$_POST[is_open]', ".
            "'$_POST[author]', '$_POST[author_email]', '$_POST[keywords]', '$_POST[content]', ".
            "'$_POST[add_time]', '$_POST[file_url]', '$_POST[open_type]', '$_POST[link_url]', '$_POST[description]', '$_POST[click_count]')";
$db->query($sql);

clear_cache_files(); // 清除相关的缓存文件

jiekou_msg(1, '操作完成!');

//上传文件
function upload_article_file($upload)
{
    if (!make_dir("./" . DATA_DIR . "/article"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path     = ROOT_PATH. DATA_DIR . "/article/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/article/" . $filename;
    }
    else
    {
        return false;
    }
}

//获取分类下拉菜单
function get_cat_tree() {
	exit('<select>'. article_cat_list() .'</select>');
}

//接口提示消息
function jiekou_msg($is_success, $notice) {
	$msg = '';
	if($is_success) {
		$msg .= '成功! ';
	} else {
		$msg .= 	'失败! ';
	}
	$msg .= $notice;
	exit($msg);
}
?>