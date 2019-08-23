<?php
/**
 * 积分类
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获取用户积分
 * @param $user_id
 * @return int
 */
function getCustomerAvailablePoints($user_id)
{
    $sql = 'SELECT pay_points FROM ' . $GLOBALS['ecs']->table('users') .
        " WHERE user_id = '$user_id'";

    $result =  $GLOBALS['db']->getRow($sql);

    return $result ? $result['pay_points'] : 0;
}

/**
 * 获取系统积分与货币抵扣比例
 * @return int
 */
function getSystemPointExchange()
{
    $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('shop_config') .
        " WHERE code = 'integral_scale'";

    $result =  $GLOBALS['db']->getRow($sql);

    return $result ? $result['value'] : 0;
}

/**
 * @param $user_id 用户id
 * @param $goods 商品集合
 * @param string $goods_points_field 商品积分支付金额字段
 * @param string $goods_number_field 商品数量字段
 * @return array
 */
function computeCartGoodsPointsAvailable($user_id, &$goods, $goods_points_field = "points_payment_amount", $goods_number_field = 'goods_number')
{
    $customer_available_points = getCustomerAvailablePoints($user_id);
    $customer_available_points_base = $customer_available_points;

    $system_point_exchange = getSystemPointExchange();

    if (intval($system_point_exchange) < 1) {
        return generateResult($customer_available_points_base, 0, 0, $goods);
    }

    if (intval($customer_available_points) < 1) {
        return generateResult($customer_available_points_base, 0, $system_point_exchange, $goods);
    }

    $scale = 100 / $system_point_exchange;

    // 订单最大可用积分
    $orderMaxAvailablePoints = 0;

    // 商品可抵扣总金额
    $goodsDeductionAmount = 0;

    foreach ($goods as $v) {
        $orderMaxAvailablePoints += $v[$goods_number_field] * $v[$goods_points_field] * $scale;
        $goodsDeductionAmount += $v[$goods_number_field] * $v[$goods_points_field];
    }

    // 订单实际可使用的积分
    $orderUsePoints = $customer_available_points >= $orderMaxAvailablePoints ? $orderMaxAvailablePoints : $customer_available_points;

    // 计算单件商品使用的积分
    foreach ($goods as &$v) {
        $v['use_points_by_one'] = $v[$goods_points_field] / $goodsDeductionAmount * $orderUsePoints;
        $v['deduction_amount_by_one'] = $v['use_points_by_one'] / $scale;
        $v['system_point_exchange'] = $system_point_exchange;
    }
    // 订单使用积分抵扣金额
    $orderUsePointsAmount = $orderUsePoints / $scale;

    return generateResult($customer_available_points_base, $orderUsePoints, $system_point_exchange, $goods, $orderUsePointsAmount);
}

/**
 * @param $customer_available_points 客户可用积分
 * @param $order_available_point 订单可用积分
 * @param $system_point_exchange 系统设置积分与货币之间的抵扣比例
 * @param $deductible_amount 订单抵扣金额
 * @param $goods 商品积分抵扣详情
 * @return array
 */
function generateResult($customer_available_points, $order_available_point, $system_point_exchange, $goods, $deductible_amount = 0)
{
    return array(
        'customer_available_points' => $customer_available_points,
        'order_available_point' => $order_available_point,
        'system_point_exchange' => $system_point_exchange,
        'deductible_amount' => $deductible_amount,
        'goods' => $goods
    );
}











