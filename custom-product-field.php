<?php 
/**
 * Plugin Name: WooCommerce Custom Product Field
 * Description: Adds a custom field to WooCommerce products and shows it on the product page, cart, and checkout.
 * Version: 1.0
 * Author: Silpa TA
 * Author Url: https://github.com/SilpaTA/custom-product-field-plugin
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook to add the custom field to the product general options tab
add_action('woocommerce_product_options_general_product_data', 'wc_add_custom_field');

// Save the custom field value
add_action('woocommerce_process_product_meta', 'wc_save_custom_field');

/**
 * Add the custom field to the product general options tab
 */
function wc_add_custom_field() {
    woocommerce_wp_text_input(array(
        'id' => '_custom_product_field',
        'label' => __('Additional Product Field', 'woocommerce'),
        'placeholder' => 'Enter additional data/text for the product',
        'desc_tip' => 'true',
        'description' => __('Enter the value for the additional product field.', 'woocommerce')
    ));
    woocommerce_wp_checkbox(array(
        'id' => '_gift_product_field',
        'label' => __('Send as Gift', 'woocommerce'),
        'description' => __('Check this box if this product can be sent as a gift.', 'woocommerce')
    ));

    woocommerce_wp_textarea_input(array(
        'id' => '_gift_message_field',
        'label' => __('Gift Message', 'woocommerce'),
        'placeholder' => 'Enter gift message',
        'description' => __('Enter a message to include with the gift.', 'woocommerce'),
        'desc_tip' => 'true'
    ));
}

/**
 * Save the custom field value
 */
function wc_save_custom_fields($post_id) {
    $custom_field_value = isset($_POST['_custom_product_field']) ? sanitize_text_field($_POST['_custom_product_field']) : '';
    update_post_meta($post_id, '_custom_product_field', $custom_field_value);
    
    $gift_product_field = isset($_POST['_gift_product_field']) ? 'yes' : 'no';
    update_post_meta($post_id, '_gift_product_field', $gift_product_field);

    $gift_message_field = isset($_POST['_gift_message_field']) ? sanitize_textarea_field($_POST['_gift_message_field']) : '';
    update_post_meta($post_id, '_gift_message_field', $gift_message_field);
}
?>