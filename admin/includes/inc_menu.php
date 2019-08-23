<?php

/**
 * ECSHOP 管理中心菜单数组
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: inc_menu.php 17217 2011-01-19 06:29:08Z liubo $
 */
if(! defined('IN_ECS'))
{
	die('Hacking attempt');
}

$modules['02_cat_and_goods']['01_goods_list'] = 'goods.php?act=list'; // 商品列表
$modules['02_cat_and_goods']['02_supplier_goods_list'] = 'goods.php?act=list&supp=1'; // 供货商商品列表
$modules['02_cat_and_goods']['03_goods_add'] = 'goods.php?act=add'; // 添加商品
$modules['02_cat_and_goods']['04_category_list'] = 'category.php?act=list';
$modules['02_cat_and_goods']['05_comment_manage'] = 'comment_manage.php?act=list';
// 代码增加 评论详情
$modules['02_cat_and_goods']['05_order_comment'] = 'order_comment.php?act=list';
/* 晒单插件 增加 by www.dm299.com */
$modules['02_cat_and_goods']['05_shaidan_manage'] = 'shaidan.php?act=list';
$modules['02_cat_and_goods']['05_goods_tags'] = 'goods_tags.php?act=list';
/* 晒单插件 增加 by www.dm299.com */
// $modules['02_cat_and_goods']['05_question_manage'] =
// 'question_manage.php?act=list';
$modules['02_cat_and_goods']['06_goods_brand_list'] = 'brand.php?act=list';
$modules['02_cat_and_goods']['08_goods_type'] = 'goods_type.php?act=manage';
$modules['02_cat_and_goods']['11_goods_trash'] = 'goods.php?act=trash'; // 商品回收站
$modules['02_cat_and_goods']['12_batch_pic'] = 'picture_batch.php';
$modules['02_cat_and_goods']['13_batch_add'] = 'goods_batch.php?act=add'; // 商品批量上传
$modules['02_cat_and_goods']['14_goods_export'] = 'goods_export.php?act=goods_export';
$modules['02_cat_and_goods']['15_batch_edit'] = 'goods_batch.php?act=select'; // 商品批量修改
$modules['02_cat_and_goods']['16_goods_script'] = 'gen_goods_script.php?act=setup';
$modules['02_cat_and_goods']['17_tag_manage'] = 'tag_manage.php?act=list';
/*
 * $modules['02_cat_and_goods']['50_virtual_card_list'] =
 * 'goods.php?act=list&extension_code=virtual_card';
 * $modules['02_cat_and_goods']['51_virtual_card_add'] =
 * 'goods.php?act=add&extension_code=virtual_card';
 * $modules['02_cat_and_goods']['52_virtual_card_change'] =
 * 'virtual_card.php?act=change';
 */
$modules['02_cat_and_goods']['goods_auto'] = 'goods_auto.php?act=list';
$modules['02_cat_and_goods']['scan_store'] = 'scan.php?act=insert';

/* $modules['03_promotion']['02_snatch_list'] = 'snatch.php?act=list'; */
$modules['03_promotion']['04_bonustype_list'] = 'bonus.php?act=list';
// $modules['03_promotion']['06_pack_list'] = 'pack.php?act=list';
// $modules['03_promotion']['07_card_list'] = 'card.php?act=list';
//$modules['03_promotion']['08_group_buy'] = 'group_buy.php?act=list';
$modules['03_promotion']['09_topic'] = 'topic.php?act=list';
$modules['03_promotion']['10_auction'] = 'auction.php?act=list';
$modules['03_promotion']['12_favourable'] = 'favourable.php?act=list';
// $modules['03_promotion']['13_wholesale'] = 'wholesale.php?act=list';
$modules['03_promotion']['14_package_list'] = 'package.php?act=list';
// $modules['03_promotion']['ebao_commend'] = 'ebao_commend.php?act=list';
$modules['03_promotion']['15_exchange_goods'] = 'exchange_goods.php?act=list';
$modules['03_promotion']['25_pre_sale_list'] = 'pre_sale.php?act=list'; // 预售
$modules['03_promotion']['26_cut']       = 'cut.php?act=list';//
$modules['03_promotion']['28_extpintuan']       = 'extpintuan.php?act=list';//新版拼团
$modules['03_promotion']['30_lucky_buy']       = 'lucky_buy.php?act=list';//云购


$modules['03_promotion']['26_seckill']		 = 'skill.php?act=seckill_list';    // 秒杀

$modules['03_promotion']['31_dzp']		 = 'dzp.php?act=list';    //大转盘

$modules['04_order']['01_order_list'] = 'order.php?act=list';
$modules['04_order']['02_supplier_order'] = 'order.php?act=list&supp=1';
$modules['04_order']['03_order_query'] = 'order.php?act=order_query';
$modules['04_order']['04_merge_order'] = 'order.php?act=merge';
$modules['04_order']['05_edit_order_print'] = 'order.php?act=templates';
$modules['04_order']['06_undispose_booking'] = 'goods_booking.php?act=list_all';
// $modules['04_order']['07_repay_application'] = 'repay.php?act=list_all';
$modules['04_order']['08_add_order'] = 'order.php?act=add';
$modules['04_order']['09_delivery_order'] = 'order.php?act=delivery_list';
// $modules['04_order']['10_back_order'] = 'order.php?act=back_list';
$modules['04_order']['10_back_order'] = 'back.php?act=back_list'; // 代码修改
                                                                                // By
                                                                                // www.dm299.com
$modules['04_order']['11_supplier_back_order'] = 'back.php?act=back_list&supp=1'; // 代码修改
                                                                                       // By
                                                                                       // www.dm299.com
                                                                                       // ecshop
                                                                                       // add
                                                                                       // start
$modules['04_order']['12_order_excel'] = 'excel.php?act=order_excel';
$modules['04_order']['11_kuaidi_order'] = 'kuaidi_order.php?act=list';
$modules['04_order']['12_kuaidi_order2'] = 'kuaidi_order.php?act=list&order_status=4&is_finish=1';
// ecshop add end
/* 增值税发票_添加_START_www.dm299.com */
$modules['04_order']['12_invoice_list'] = 'order.php?act=invoice_list';
/* 增值税发票_添加_START_www.dm299.com */

/* 虚拟卷订单列表_添加_START_www.dm299.com */
$modules['18_virtual']['virtual_goods_add'] = 'virtual_goods.php?act=add&extension_code=virtual_good'; // 添加虚拟商品
$modules['18_virtual']['virtual_goods_sup'] = 'virtual_goods.php?act=list&extension_code=virtual_good&supp=1'; // 虚拟商品列表
$modules['18_virtual']['virtual_goods_list'] = 'virtual_goods.php?act=list&extension_code=virtual_good'; // 虚拟商品列表
$modules['18_virtual']['virtual_card_list'] = 'virtual_goods_card.php?act=all_card';
$modules['18_virtual']['virtual_validate'] = 'virtual_goods_card.php?act=verification_info';
$modules['18_virtual']['virtual_category'] = 'category.php?act=virtual_list'; // 虚拟商品分类管理
$modules['18_virtual']['virtual_district'] = 'virtual_goods.php?act=district'; // 虚拟商品商圈管理
/* 虚拟卷订单列表_添加_START_www.dm299.com */

$modules['05_banner']['ad_position'] = 'ad_position.php?act=list';
$modules['05_banner']['ad_list'] = 'ads.php?act=list';

//$modules['06_stats']['flow_stats'] = 'flow_stats.php?act=view';
/* 代码添加_START By www.dm299.com */
$modules['06_stats']['keyword'] = 'keyword.php?act=list'; // 客户搜索记录
/* 代码添加_SEND By www.dm299.com */
//$modules['06_stats']['searchengine_stats'] = 'searchengine_stats.php?act=view';
//$modules['06_stats']['z_clicks_stats'] = 'adsense.php?act=list';
$modules['06_stats']['report_guest'] = 'guest_stats.php?act=list'; // 客户统计
//$modules['06_stats']['report_order'] = 'order_stats.php?act=list';
//$modules['06_stats']['report_sell'] = 'sale_general.php?act=list';
//$modules['06_stats']['sale_list'] = 'sale_list.php?act=list';
//$modules['06_stats']['sell_stats'] = 'sale_order.php?act=goods_num';
//$modules['06_stats']['report_users'] = 'users_order.php?act=order_num';
//$modules['06_stats']['visit_buy_per'] = 'visit_sold.php?act=list';
/* 代码增加 By  www.dm299.com Start */
$modules['06_stats']['industry_stats'] = 'industry_scale_stats.php?act=list'; // 行业分析
$modules['06_stats']['users_stats'] = 'user_added_stats.php?act=list'; // 会员统计
$modules['06_stats']['shops_stats'] = 'shop_added_stats.php?act=list'; // 店铺统计
$modules['06_stats']['orders_stats'] = 'order_stats.php?act=list'; // 订单统计
$modules['06_stats']['goods_stats'] = 'goods_stats.php?act=list'; // 商品分析
$modules['06_stats']['sells_stats'] = 'sell_stats.php?act=list'; // 销售报告
$modules['06_stats']['after_sells_stats'] = 'refund_stats.php?act=list'; // 售后统计
/* 代码增加 By  www.dm299.com End */

$modules['07_content']['03_article_list'] = 'article.php?act=list';
$modules['07_content']['02_articlecat_list'] = 'articlecat.php?act=list';
$modules['07_content']['vote_list'] = 'vote.php?act=list';
$modules['07_content']['article_auto'] = 'article_auto.php?act=list';
// $modules['07_content']['shop_help'] = 'shophelp.php?act=list_cat';
// $modules['07_content']['shop_info'] = 'shopinfo.php?act=list';

$modules['08_members']['03_users_list'] = 'users.php?act=list';
$modules['08_members']['03_users_virtual_list'] = 'users_virtual.php?act=list';
$modules['08_members']['04_users_export'] = 'users_export.php'; // 代码增加
                                                                            // By
                                                                            // www.dm299.com

$modules['08_members']['04_users_add'] = 'users.php?act=add';
$modules['08_members']['05_user_rank_list'] = 'user_rank.php?act=list';
$modules['08_members']['06_list_integrate'] = 'integrate.php?act=list';
$modules['08_members']['08_unreply_msg'] = 'user_msg.php?act=list_all';
$modules['08_members']['09_user_account'] = 'user_account.php?act=list';
$modules['08_members']['10_user_account_manage'] = 'user_account_manage.php?act=list';
$modules['08_members']['09_postman_list'] = 'postman.php?act=list';

$modules['10_priv_admin']['admin_logs'] = 'admin_logs.php?act=list';
$modules['10_priv_admin']['admin_list'] = 'privilege.php?act=list';
$modules['10_priv_admin']['admin_role'] = 'role.php?act=list';
$modules['10_priv_admin']['agency_list'] = 'agency.php?act=list';
$modules['10_priv_admin']['suppliers_list'] = 'suppliers.php?act=list'; // 供货商

$modules['11_system']['01_shop_config'] = 'shop_config.php?act=list_edit';
//$modules['11_system']['shop_authorized'] = 'license.php?act=list_edit';//授权证书
$modules['11_system']['02_payment_list'] = 'payment.php?act=list';
$modules['11_system']['03_shipping_list'] = 'shipping.php?act=list';
$modules['11_system']['04_mail_settings'] = 'shop_config.php?act=mail_settings';
$modules['11_system']['05_area_list'] = 'area_manage.php?act=list';
// $modules['11_system']['06_plugins'] = 'plugins.php?act=list';
$modules['11_system']['07_cron_schcron'] = 'cron.php?act=list';
$modules['11_system']['08_friendlink_list'] = 'friend_link.php?act=list';
$modules['11_system']['sitemap'] = 'sitemap.php';
$modules['11_system']['check_file_priv'] = 'check_file_priv.php?act=check';
$modules['11_system']['captcha_manage'] = 'captcha_manage.php?act=main';
$modules['11_system']['ucenter_setup'] = 'integrate.php?act=setup&code=ucenter';
$modules['11_system']['flashplay'] = 'flashplay.php?act=list';
$modules['11_system']['navigator'] = 'navigator.php?act=list';
$modules['11_system']['file_check'] = 'filecheck.php';
// $modules['11_system']['fckfile_manage'] = 'fckfile_manage.php?act=list';
$modules['11_system']['021_reg_fields'] = 'reg_fields.php?act=list';

$modules['12_template']['02_template_select'] = 'template.php?act=list';
$modules['12_template']['03_template_setup'] = 'template.php?act=setup';
$modules['12_template']['04_template_library'] = 'template.php?act=library';
$modules['12_template']['05_edit_languages'] = 'edit_languages.php?act=list';
$modules['12_template']['06_template_backup'] = 'template.php?act=backup_setting';
$modules['12_template']['mail_template_manage'] = 'mail_template.php?act=list';

$modules['13_backup']['02_db_manage'] = 'database.php?act=backup';
$modules['13_backup']['03_db_optimize'] = 'database.php?act=optimize';
$modules['13_backup']['04_sql_query'] = 'sql.php?act=main';
$modules['13_backup']['clear_demo'] = 'clear_demo.php?act=start';
// $modules['13_backup']['05_synchronous'] = 'integrate.php?act=sync';
$modules['13_backup']['convert'] = 'convert.php?act=main';

// $modules['14_sms']['02_sms_my_info'] = 'sms.php?act=display_my_info';
// $modules['14_sms']['03_sms_send'] = 'sms.php?act=display_send_ui';
// $modules['14_sms']['04_sms_charge'] = 'sms.php?act=display_charge_ui';
// $modules['14_sms']['05_sms_send_history'] ='sms.php?act=display_send_history_ui';
// $modules['14_sms']['06_sms_charge_history'] ='sms.php?act=display_charge_history_ui';

$modules['15_rec']['affiliate'] = 'affiliate.php?act=list';
$modules['15_rec']['affiliate_ck'] = 'affiliate_ck.php?act=list';

$modules['16_email_manage']['email_list'] = 'email_list.php?act=list';
$modules['16_email_manage']['magazine_list'] = 'magazine_list.php?act=list';
$modules['16_email_manage']['attention_list'] = 'attention_list.php?act=list';
$modules['16_email_manage']['view_sendlist'] = 'view_sendlist.php?act=list';

/* 代码增加_start By www.dm299.com */
$modules['02_supplier']['06_supplier_tag'] = 'supplier_tag.php?act=list';
$modules['02_supplier']['05_supplier_rank'] = 'supplier_rank.php?act=list';
$modules['02_supplier']['01_supplier_reg'] = 'supplier.php?act=list';
$modules['02_supplier']['02_supplier_list'] = 'supplier.php?act=list&status=1';
$modules['02_supplier']['03_rebate_nopay'] = 'supplier_rebate.php?act=list';
//$modules['02_supplier']['03_rebate_pay'] = 'supplier_rebate.php?act=list&is_pay_ok=1';
$modules['02_supplier']['04_shop_category'] = 'supplier_street_category.php?act=list';
$modules['02_supplier']['05_shop_street'] = 'supplier_street.php?act=list';
/* 代码增加_end By www.dm299.com */

// 微信权限
// $modules['17_weixin_manage']['weixin_config'] = 'weixin.php?act=config';
// $modules['17_weixin_manage']['weixin_addconfig'] =
// 'weixin.php?act=addconfig';
// $modules['17_weixin_manage']['weixin_menu'] = 'weixin.php?act=menu';
// $modules['17_weixin_manage']['weixin_notice'] = 'weixin.php?act=notice';
// $modules['17_weixin_manage']['weixin_keywords'] = 'weixin.php?act=keywords';
// $modules['17_weixin_manage']['weixin_fans'] = 'weixin.php?act=fans';
// $modules['17_weixin_manage']['weixin_news'] = 'weixin.php?act=news';
// $modules['17_weixin_manage']['weixin_addqcode'] = 'weixin.php?act=addqcode';
// $modules['17_weixin_manage']['weixin_qcode'] = 'weixin.php?act=qcode';
// $modules['17_weixin_manage']['weixin_reg'] = 'weixin.php?act=reg';
// 活动管理
// $modules['17_weixin_manage']['weixin_act'] = 'weixin_egg.php?act=list';
// $modules['17_weixin_manage']['weixin_award'] = 'weixin_egg.php?act=log';
// $modules['17_weixin_manage']['weixin_oauth'] = 'weixin.php?act=oauth';
// $modules['17_weixin_manage']['weixin_qiandao'] = 'weixin.php?act=qiandao';
// $modules['17_weixin_manage']['weixin_addkey'] = 'weixin.php?act=addkey';
$modules['11_system']['website'] = 'website.php?act=list';
$modules['03_promotion']['16_takegoods_list'] = 'takegoods.php?act=list';
$modules['03_promotion']['16_takegoods_order'] = 'takegoods.php?act=order_list';
$modules['16_email_manage']['sendmail'] = 'sendmail.php?act=sendmail';
$modules['02_cat_and_goods']['pricecut'] = 'pricecut.php?act=list&status=-1';
$purview['pricecut'] = 'goods_manage';
$_LANG['pricecut'] = '降价通知列表';
$modules['17_pickup_point_manage']['pickup_point_list'] = 'pickup_point.php?act=list';
$modules['17_pickup_point_manage']['pickup_point_add'] = 'pickup_point.php?act=add';
$modules['17_pickup_point_manage']['pickup_point_batch_add'] = 'pickup_point.php?act=batch_add';
$modules['03_promotion']['19_valuecard_list'] = 'valuecard.php?act=list';
$_LANG['19_valuecard_list'] = '储值卡管理';
$purview['19_valuecard_list'] = 'bonus_manage';
$modules['11_system']['website'] = 'website.php?act=list';
/* 代码增加_end By www.dm299.com */

//$modules['20_chat']['chat_settings'] = 'chat_settings.php'; // 聊天服务设置
//$modules['20_chat']['customer'] = 'customer.php?act=list'; // 客服管理
$modules['20_chat']['third_customer'] = 'third_customer.php?act=list'; // 三方客服


$modules['21_queue']['queue_msg'] = 'queue_msg.php?act=list'; // 队列列表


$modules['02_cat_and_goods']['19_jilu'] = 'jilu.php';

/*
$menu_top['dm299_scan'] = '18_dm299_scan';
$modules['18_dm299_scan']['dm299_scan'] = 'dm299_scan.php';
$_LANG['18_dm299_scan']	     = '木马扫描';
$_LANG['dm299_scan']     = '木马扫描';
$purview['18_dm299_scan'] = 'dm299_scan';
$purview['dm299_scan'] = 'dm299_scan';
*/


//手机端功能菜单----微信设置  Add By 月梦网络
$modules['06_wechat_setting']['01_weixin_config'] = 'weixin.php?act=config'; //微信设置//
$modules['06_wechat_setting']['02_weixin_oauth'] = 'weixin.php?act=oauth'; //OAuth设置//
$modules['06_wechat_setting']['03_weixin_menu'] = 'weixin.php?act=menu'; //自定义菜单//
$modules['06_wechat_setting']['04_weixin_addkey'] = 'weixin.php?act=addkey'; //关键字回复//
$modules['06_wechat_setting']['05_weixin_keywords'] = 'weixin.php?act=keywords'; //功能变量//
$modules['06_wechat_setting']['06_article_danpin'] = 'article_danpin.php?act=list'; //单品推广设置//
$modules['06_wechat_setting']['07_weixin_autoreg'] = 'weixin.php?act=autoreg'; //微信注册设置//
$modules['06_wechat_setting']['08_weixin_qiandao'] = 'weixin.php?act=qiandao'; //签到设置//
$modules['06_wechat_setting']['09_weixin_egg'] = 'weixin_egg.php?act=list'; //活动管理//
$modules['06_wechat_setting']['10_weixin_log'] = 'weixin_egg.php?act=log'; //中奖管理//
$modules['06_wechat_setting']['11_weixin_qcode'] = 'weixin.php?act=qcode'; //管理二维码//
$modules['06_wechat_setting']['12_weixin_addqcode'] = 'weixin.php?act=addqcode'; //生成二维码//
$modules['06_wechat_setting']['13_weixin_notice'] = 'weixin.php?act=notice'; //分享设置//
$modules['06_wechat_setting']['14_weixin_remind'] = 'weixin.php?act=remind'; //消息提醒设置//
$modules['06_wechat_setting']['15_weixin_news'] = 'weixin.php?act=news'; //消息推送//
$modules['06_wechat_setting']['16_weixin_fans'] = 'weixin.php?act=fans'; //粉丝管理//
$modules['06_wechat_setting']['17_weixin_share'] = 'weixin_share.php?act=list'; //分享记录//



//手机端功能菜单----系统设置  Add By 月梦网络
$modules['08_mobile_setting']['01_shop_config'] = 'mobile_shop_config.php?act=list_edit'; // 商店设置
$modules['08_mobile_setting']['02_menu_list'] = 'menu.php?act=list'; // 自定义菜单
$modules['08_mobile_setting']['03_ad_position'] = 'mobile_ad_position.php?act=list'; // 广告位置
$modules['08_mobile_setting']['04_ads_list'] = 'mobile_ads.php?act=list'; // 广告列表
$modules['08_mobile_setting']['06_template_setup'] = 'mobile_template.php?act=setup'; // 设置模板
$modules['08_mobile_setting']['07_mobile_website'] = 'mobile_website.php?act=list'; // 手机授权登录

//手机端功能菜单----分销管理 Add By 月梦网络


$modules['07_distrib_setting']['01_affiliate_list']		= 'mobile_affiliate.php?act=list';		// 分销设置
$modules['07_distrib_setting']['02_distributor_list']	 = 'distributor.php?act=list';			// 分销商列表
$modules['07_distrib_setting']['03_distrib_goods_list'] = 'distrib_goods.php?act=list';			// 商品设置
$modules['07_distrib_setting']['04_affiliate_ck']		= 'mobile_affiliate_ck.php?act=list';	// 订单管理
$modules['07_distrib_setting']['05_distrib_sort']		= 'distrib_sort.php?act=list';			// 排行榜
$modules['07_distrib_setting']['06_deposit_list']		= 'deposit_list.php?act=list';			// 提现申请列表


//yhy新增menu
//$modules['000_dm299']['00_wxadoc'] = '../dm299/admin';
$modules['22_erweima']['er_wei_ma'] = 'er_wei_ma.php';
$modules['22_erweima']['pi_ci__number'] = 'pi_ci__number.php';
$modules['22_erweima']['total_code_number'] = 'total_code_number.php';
?>
