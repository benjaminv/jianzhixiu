<?php

/**
 * ECSHOP 管理中心众筹商品语言文件
 * ============================================================================
 * * 版权所有 2008-2015 甜心100网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.we10.cn；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: group_buy.php 17217 2011-01-19 06:29:08Z derek $
*/

/* 当前页面标题及可用链接名称 */
$_LANG['pre_sale_list'] = '众筹活动列表';
$_LANG['add_pre_sale'] = '添加众筹活动';
$_LANG['edit_pre_sale'] = '编辑众筹活动';

/* 活动列表页 */
$_LANG['goods_name'] = '商品名称';
$_LANG['start_date'] = '开始时间';
$_LANG['end_date'] = '结束时间';
$_LANG['deposit'] = '保证金';
$_LANG['restrict_amount'] = '限购';
$_LANG['gift_integral'] = '赠送积分';
$_LANG['valid_order'] = '订单';
$_LANG['valid_goods'] = '订购商品';
$_LANG['current_price'] = '众筹单价';
$_LANG['initial_price'] = '原始单价';
$_LANG['current_status'] = '状态';
$_LANG['view_order'] = '查看订单';
$_LANG['get_price'] = '已筹金额';
$_LANG['raise_days'] = '已筹天数';
$_LANG['price_rate'] = '筹款达成率';
$_LANG['back_money_success'] = '退款成功';
$_LANG['back_money_fail'] = '退款失败，该众筹活动已经退款';


/* 添加/编辑活动页 */
$_LANG['goods_cat'] = '商品分类';
$_LANG['total_price'] = '目标金额';
$_LANG['notice_total_price'] = '目标金额不能为空';
$_LANG['all_cat'] = '所有分类';
$_LANG['goods_brand'] = '商品品牌';
$_LANG['all_brand'] = '所有品牌';

$_LANG['label_goods_name'] = '众筹商品：';
$_LANG['notice_goods_name'] = '请先搜索商品,在此生成选项列表...';
$_LANG['label_start_date'] = '活动开始时间：';
$_LANG['label_end_date'] = '活动结束时间：';
$_LANG['notice_datetime'] = '（年月日－时）';
$_LANG['label_sale_price'] = '众筹价格：';
$_LANG['notice_retainage_start'] = '尾款开始支付时间不得早于众筹活动结束时间';
$_LANG['notice_retainage_end'] = '建议留给用户支付尾款时间至少72小时以上';
$_LANG['label_deliver_goods'] = '开始发货时间描述：';
$_LANG['act_name'] = '活动名称：';
$_LANG['act_desc'] = '活动描述：';
$_LANG['notice_deliver_goods'] = '格式建议：1.预计 yyyy-MM-dd HH 时前发货。2.付款后 N 天内发货。';
$_LANG['notice_simple_goods'] = '内容建议：商品名称及简要特征，不超过15字';
$_LANG['notice_detail_goods'] = '内容建议：商品的详细特征，不超过30字';
$_LANG['label_price_ladder'] = '价格阶梯：';
$_LANG['notice_number_amount'] = '数量';
$_LANG['notice_ladder_price'] = '价格';
$_LANG['notice_ladder_desc'] = '该价格档位描述';
$_LANG['notice_limit_num'] = '允许购买数量';
$_LANG['label_desc'] = '活动说明：';
$_LANG['label_status'] = '活动当前状态：';
$_LANG['zc'][ZC_PRE_START] = '未开始';
$_LANG['zc'][ZC_UNDER_WAY] = '众筹中';
$_LANG['zc'][ZC_FINISHED] = '众筹结束';
$_LANG['zc'][ZC_SUCCEED] = '众筹成功';
$_LANG['zc'][ZC_FAIL] = '众筹失败';
$_LANG['bm'][0] = '已退款';
$_LANG['bm'][1] = '未退款';
$_LANG['label_order_qty'] = '订单数 / 有效订单数：';
$_LANG['label_goods_qty'] = '商品数 / 有效商品数：';
$_LANG['label_cur_price'] = '当前价：';
$_LANG['label_end_price'] = '最终价：';
$_LANG['label_handler'] = '操作：';
$_LANG['error_group_buy'] = '您要操作的众筹活动不存在';
$_LANG['error_status'] = '当前状态不能执行该操作！';
$_LANG['button_finish'] = '结束活动';
$_LANG['notice_finish'] = '（修改活动结束时间为当前时间）';
$_LANG['button_succeed'] = '活动成功';
$_LANG['notice_succeed'] = '（更新订单价格）';
$_LANG['button_fail'] = '活动失败';
$_LANG['notice_initial_price'] = '原始单价为商品的正式开售的价格，不能为空';
$_LANG['notice_fail'] = '（取消订单，保证金退回帐户余额，失败原因可以写到活动说明中）';
$_LANG['cancel_order_reason'] = '众筹失败';
$_LANG['pre_sale_order_refund'] = '众筹成功，重新计算订单金额，退回用户多交的钱';
$_LANG['js_languages']['succeed_confirm'] = '此操作不可逆，您确定要设置该众筹活动成功吗？';
$_LANG['js_languages']['fail_confirm'] = '此操作不可逆，您确定要设置该众筹活动失败吗？';
$_LANG['button_mail'] = '发送邮件';
$_LANG['notice_mail'] = '（通知客户付清余款，以便发货）';
$_LANG['mail_result'] = '该众筹活动共有 %s 个有效订单，成功发送了 %s 封邮件。';
$_LANG['invalid_time'] = '您输入了一个无效的众筹时间。';

$_LANG['add_success'] = '添加众筹活动成功。';
$_LANG['edit_success'] = '编辑众筹活动成功。';
$_LANG['back_list'] = '返回众筹活动列表。';
$_LANG['continue_add'] = '继续添加众筹活动。';


/* 添加/编辑活动提交 */
$_LANG['error_goods_null'] = '您没有选择众筹商品！';
$_LANG['error_goods_exist'] = '您选择的商品目前有一个众筹活动正在进行！';
$_LANG['error_price_ladder'] = '您没有输入有效的价格阶梯！';
$_LANG['error_restrict_amount'] = '限购数量不能小于价格阶梯中的最大数量';
$_LANG['error_deposit'] = '定金不能大于阶梯价格';

$_LANG['js_languages']['error_goods_null'] = '您没有选择众筹商品！';
$_LANG['js_languages']['error_deposit'] = '您输入的保证金不是数字！';
$_LANG['js_languages']['error_restrict_amount'] = '您输入的限购数量不是整数！';
$_LANG['js_languages']['error_gift_integral'] = '您输入的赠送积分数不是整数！';
$_LANG['js_languages']['search_is_null'] = '没有搜索到任何商品，请重新搜索';

/* 删除众筹活动 */
$_LANG['js_languages']['batch_drop_confirm'] = '您确定要删除选定的众筹活动吗？';
$_LANG['error_exist_order'] = '该众筹活动已经有订单，不能删除！';
$_LANG['batch_drop_success'] = '成功删除了 %s 条众筹活动记录（已经有订单的众筹活动不能删除）。';
$_LANG['no_select_group_buy'] = '您现在没有众筹活动记录！';

/* 操作日志 */
$_LANG['log_action']['pre_sale'] = '众筹商品';

?>