<?php
function csp_select_bsite($order, $select_rule) {
    $list_json = get_option('csp_bsite_list', '[]');
    $list = json_decode($list_json, true);
    if (!is_array($list) || count($list) == 0) return [null, null, null];
    switch ($select_rule) {
        case 'manual':
            return [$list[0]['api'], $list[0]['token'], $list[0]['name']];
        case 'random':
            $sel = $list[array_rand($list)];
            return [$sel['api'], $sel['token'], $sel['name']];
        case 'by_product':
            $items = $order->get_items();
            $product_id = reset($items)->get_product_id();
            $idx = $product_id % count($list);
            $sel = $list[$idx];
            return [$sel['api'], $sel['token'], $sel['name']];
        default:
            return [$list[0]['api'], $list[0]['token'], $list[0]['name']];
    }
}

function csp_sign_order($order_data, $secret) {
    return hash_hmac('sha256', json_encode($order_data, JSON_UNESCAPED_UNICODE), $secret);
}

function csp_create_order_on_bsite($order_data, $bsite_api, $api_token) {
    if (!$bsite_api || !$api_token) return false;
    $args = [
        'body'    => json_encode($order_data, JSON_UNESCAPED_UNICODE),
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_token,
        ],
        'timeout' => 15,
    ];
    $response = wp_remote_post($bsite_api, $args);
    if (is_wp_error($response)) return false;
    $result = json_decode(wp_remote_retrieve_body($response), true);
    return $result && isset($result['pay_url']) ? $result['pay_url'] : false;
}
