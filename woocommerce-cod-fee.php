<?php
/**
 * Plugin Name: WooCommerce Cash on Delivery Fee
 * Plugin URI: https://github.com/yourusername/woocommerce-cod-fee
 * Description: Adds an additional configurable fee when Cash on Delivery is selected.
 * Version: 1.0.0
 * Author: Arif M.
 * License: GPL v2 or later
 * Text Domain: woocommerce-cod-fee
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {

    add_options_page(
        'Cash on Delivery Fee',
        'Cash on Delivery Fee',
        'manage_options',
        'cod-fee-settings',
        'wccf_settings_page'
    );
});
add_action('admin_init', function () {

    register_setting('wccf_settings_group', 'wccf_enable');
    register_setting('wccf_settings_group', 'wccf_fee_amount');
    register_setting('wccf_settings_group', 'wccf_fee_label');
    register_setting('wccf_settings_group', 'wccf_notice');

    add_settings_section(
        'wccf_main_section',
        'Cash on Delivery Fee Settings',
        null,
        'cod-fee-settings'
    );

    add_settings_field(
        'wccf_enable',
        'Enable COD Fee',
        'wccf_enable_field',
        'cod-fee-settings',
        'wccf_main_section'
    );

    add_settings_field(
        'wccf_fee_amount',
        'Fee Amount',
        'wccf_fee_amount_field',
        'cod-fee-settings',
        'wccf_main_section'
    );

    add_settings_field(
        'wccf_fee_label',
        'Fee Label',
        'wccf_fee_label_field',
        'cod-fee-settings',
        'wccf_main_section'
    );

    add_settings_field(
        'wccf_notice',
        'Customer Notice',
        'wccf_notice_field',
        'cod-fee-settings',
        'wccf_main_section'
    );
});

function wccf_enable_field() {
    $value = get_option('wccf_enable', 'yes');
    echo '<input type="checkbox" name="wccf_enable" value="yes" ' . checked('yes', $value, false) . ' />';
}

function wccf_fee_amount_field() {
    $value = get_option('wccf_fee_amount', 200);
    echo '<input type="number" name="wccf_fee_amount" value="' . esc_attr($value) . '" />';
}

function wccf_fee_label_field() {
    $value = get_option('wccf_fee_label', 'Cash on Delivery Fee');
    echo '<input type="text" name="wccf_fee_label" value="' . esc_attr($value) . '" class="regular-text" />';
}

function wccf_notice_field() {
    $value = get_option('wccf_notice', 'An additional fee has been added because you selected Cash on Delivery.');
    echo '<textarea name="wccf_notice" class="large-text" rows="3">' . esc_textarea($value) . '</textarea>';
}

function wccf_settings_page() {
    ?>
    <div class="wrap">
        <h1>Cash on Delivery Fee</h1>

        <form method="post" action="options.php">
            <?php
                settings_fields('wccf_settings_group');
                do_settings_sections('cod-fee-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}
add_action('woocommerce_cart_calculate_fees', function ($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!WC()->session) return;

    if (WC()->session->get('chosen_payment_method') !== 'cod') return;

    if (get_option('wccf_enable') !== 'yes') return;

    $fee = (float) get_option('wccf_fee_amount', 200);
    $label = get_option('wccf_fee_label', 'Cash on Delivery Fee');

    if ($fee <= 0) return;

    $cart->add_fee($label, $fee, false);
});
add_action('woocommerce_review_order_before_payment', function () {

    if (!WC()->session) return;

    if (WC()->session->get('chosen_payment_method') !== 'cod') return;

    if (get_option('wccf_enable') !== 'yes') return;

    $fee = (float) get_option('wccf_fee_amount', 200);
    $notice = get_option('wccf_notice');

    echo '<div class="woocommerce-info">';
    echo esc_html($notice) . ' <strong>' . wc_price($fee) . '</strong>';
    echo '</div>';
});
add_action('wp_footer', function () {

    if (!is_checkout()) return;
    ?>

    <script>
    jQuery(function($){
        $('form.checkout').on('change','input[name=payment_method]',function(){
            $('body').trigger('update_checkout');
        });
    });
    </script>

    <?php
});