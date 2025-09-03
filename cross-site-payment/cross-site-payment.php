<?php
/*
Plugin Name: Cross Site Payment
Description: 支持A站下单，B站付款，含多站点、字段联动、安全加密、高级后台配置
Version: 1.1
Author: xiaoyuhaoshuai
*/

if (!defined('ABSPATH')) exit;

// Includes
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/webhook-handler.php';

// WooCommerce check
if (!function_exists('wc_get_order')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Cross Site Payment 插件依赖 WooCommerce，请先安装并启用 WooCommerce。</strong></p></div>';
    });
    return;
}

// 下单后自动对接B站并跳转支付
add_action('woocommerce_thankyou', 'csp_handle_order_after_checkout');
function csp_handle_order_after_checkout($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);

    // 多站点选择
    $select_rule = get_option('csp_bsite_select_rule', 'manual');
    list($bsite_api, $api_token, $bsite_name) = csp_select_bsite($order, $select_rule);

    $extra_fields_json = get_option('csp_extra_fields', '{}');
    $extra_fields = json_decode($extra_fields_json, true);

    // 构造订单参数
    $order_data = [
        'order_id'    => $order->get_id(),
        'amount'      => $order->get_total(),
        'user_email'  => $order->get_billing_email(),
        'user_phone'  => $order->get_billing_phone(),
        'items'       => [],
        'extra'       => $extra_fields,
        'target_site' => $bsite_name
    ];

    foreach($order->get_items() as $item) {
        $order_data['items'][] = [
            'name'       => $item->get_name(),
            'qty'        => $item->get_quantity(),
            'total'      => $item->get_total(),
            'product_id' => $item->get_product_id()
        ];
    }

    // 安全签名
    $webhook_secret = get_option('csp_webhook_secret');
    $order_data['sign'] = csp_sign_order($order_data, $webhook_secret);

    // 调用B站API生成订单并获取支付链接
    $b_pay_url = csp_create_order_on_bsite($order_data, $bsite_api, $api_token);

    // 跳转到B站支付页面
    if ($b_pay_url) {
        wp_redirect($b_pay_url);
        exit;
    }
}
