<?php
add_action('admin_menu', function () {
    add_options_page(
        '跨站支付设置',
        '跨站支付',
        'manage_options',
        'cross-site-payment-settings',
        'csp_settings_page'
    );
});

add_action('admin_notices', function () {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        echo '<div class="notice notice-success is-dismissible"><p>设置已保存！</p></div>';
    }
});

function csp_settings_page() {
    ?>
    <div class="wrap csp-admin-wrap">
        <h1>跨站支付插件设置</h1>
        <form method="post" action="options.php">
            <?php settings_fields('csp_settings_group'); ?>
            <?php do_settings_sections('cross-site-payment-settings'); ?>
            <!-- 配置表单内容略，可用前文代码 -->
        </form>
    </div>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../assets/admin-style.css'; ?>">
    <script>
    // JSON校验略
    </script>
    <?php
}

add_action('admin_init', function () {
    register_setting('csp_settings_group', 'csp_bsite_list');
    register_setting('csp_settings_group', 'csp_bsite_select_rule');
    register_setting('csp_settings_group', 'csp_extra_fields');
    register_setting('csp_settings_group', 'csp_webhook_secret');
    register_setting('csp_settings_group', 'csp_notify_email');
});
