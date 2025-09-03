<?php
add_action('rest_api_init', function () {
    register_rest_route('csp/v1', '/payment_callback', [
        'methods'  => 'POST',
        'callback' => 'csp_payment_callback',
        'permission_callback' => '__return_true'
    ]);
});

function csp_payment_callback($request) {
    $params = $request->get_json_params();
    $order_id = $params['order_id'];
    $status   = $params['status'];
    $extra    = $params['extra'] ?? [];
    $sign     = $params['sign'] ?? '';
    $webhook_secret = get_option('csp_webhook_secret');
    $notify_email = get_option('csp_notify_email');

    // 签名校验
    $valid_sign = hash_hmac('sha256', json_encode($params, JSON_UNESCAPED_UNICODE), $webhook_secret);
    if ($sign !== $valid_sign) {
        if ($notify_email) {
            wp_mail($notify_email, '支付回调签名异常', '订单号: ' . $order_id . '，状态: ' . $status);
        }
        return ['success' => false, 'msg' => '签名校验失败'];
    }

    if ($order_id && $status == 'paid') {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_status('processing', 'B站支付成功自动同步');
            return ['success' => true];
        }
    }
    if ($notify_email) {
        wp_mail($notify_email, '支付回调异常', '订单号: ' . $order_id . '，状态: ' . $status);
    }
    return ['success' => false];
}
