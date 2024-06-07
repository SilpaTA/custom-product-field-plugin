<?php
/**
 * Plugin Name: WooCommerce Custom Product Field
 * Description: Adds custom fields to WooCommerce products for additional information, including gift options and messages.
 * Version: 1.0
 * Author: Silpa TA
 * Author Url: https://github.com/SilpaTA/custom-product-field-plugin
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook to add the custom fields to the product general options tab
add_action('woocommerce_product_options_general_product_data', 'wc_add_custom_fields');

// Save the custom fields values
add_action('woocommerce_process_product_meta', 'wc_save_custom_fields');

// Display custom fields on the single product page
add_action('woocommerce_single_product_summary', 'wc_display_custom_fields', 25);

// Handle adding custom fields to cart
add_action('woocommerce_before_add_to_cart_button', 'wc_add_gift_options');
add_filter('woocommerce_add_cart_item_data', 'wc_add_custom_fields_to_cart', 10, 2);

// Display custom fields values in the cart and checkout
add_filter('woocommerce_get_item_data', 'wc_display_custom_fields_cart', 10, 2);

// Save custom fields values to the order
add_action('woocommerce_checkout_create_order_line_item', 'wc_save_custom_fields_order', 10, 4);

// Add custom fields column to orders list
add_filter('manage_edit-shop_order_columns', 'wc_custom_order_column');

// Display custom fields values in orders list
add_action('manage_shop_order_posts_custom_column', 'wc_custom_order_column_value', 10, 2);

/**
 * Add the custom fields to the product general options tab
 */
function wc_add_custom_fields() {
    woocommerce_wp_text_input(array(
        'id' => '_custom_product_field',
        'label' => __('Additional Information', 'woocommerce'),
        'placeholder' => 'Please enter a message for the users',
        'desc_tip' => 'true',
        'description' => __('Please enter a message for the users.', 'woocommerce')
    ));
}

/**
 * Save the custom fields values
 */
function wc_save_custom_fields($post_id) {
    $custom_field_value = isset($_POST['_custom_product_field']) ? sanitize_text_field($_POST['_custom_product_field']) : '';
    update_post_meta($post_id, '_custom_product_field', $custom_field_value);
}

/**
 * Display the custom fields on the single product page
 */
function wc_display_custom_fields() {
    global $post;
    $custom_field_value = get_post_meta($post->ID, '_custom_product_field', true);

    if (!empty($custom_field_value)) {
        echo '<div class="woocommerce_custom_field"><strong>' . __('Additional Information:', 'woocommerce') . '</strong> ' . esc_html($custom_field_value) . '</div>';
    }
}

/**
 * Add gift options fields to the single product page
 */
function wc_add_gift_options() {
    echo '<div class="woocommerce_gift_options">
            <label for="gift_product_checkbox">
                <input type="checkbox" id="gift_product_checkbox" name="gift_product_checkbox" value="yes">
                ' . __('Send as Gift', 'woocommerce') . '
            </label>
          </div>
          <div class="woocommerce_gift_message">
            <label for="gift_message_field">' . __('Gift Message', 'woocommerce') . '</label>
            <textarea id="gift_message_field" name="gift_message_field" placeholder="' . __('Enter gift message', 'woocommerce') . '"></textarea>
          </div>';
}

/**
 * Add custom fields values to the cart item
 */
function wc_add_custom_fields_to_cart($cart_item_data, $product_id) {
    $custom_field_value = get_post_meta($product_id, '_custom_product_field', true);
    $gift_product_field = isset($_POST['gift_product_checkbox']) ? 'yes' : 'no';
    $gift_message_field = isset($_POST['gift_message_field']) ? sanitize_textarea_field($_POST['gift_message_field']) : '';

    // if (!empty($custom_field_value)) {
    //     $cart_item_data['custom_product_field'] = $custom_field_value;
    // }

    if ($gift_product_field === 'yes') {
        $cart_item_data['gift_product_field'] = $gift_product_field;
        if (!empty($gift_message_field)) {
            $cart_item_data['gift_message_field'] = $gift_message_field;
        }
    }

    $cart_item_data['unique_key'] = md5(microtime() . rand());
    return $cart_item_data;
}

/**
 * Display custom fields values in the cart and checkout
 */
function wc_display_custom_fields_cart($item_data, $cart_item) {
    // if (isset($cart_item['custom_product_field'])) {
    //     $item_data[] = array(
    //         'name' => __('Additional Information', 'woocommerce'),
    //         'value' => $cart_item['custom_product_field']
    //     );
    // }

    if (isset($cart_item['gift_product_field']) && $cart_item['gift_product_field'] === 'yes') {
        $item_data[] = array(
            'name' => __('Send as Gift', 'woocommerce'),
            'value' => __('Yes', 'woocommerce')
        );
        if (isset($cart_item['gift_message_field'])) {
            $item_data[] = array(
                'name' => __('Gift Message', 'woocommerce'),
                'value' => $cart_item['gift_message_field']
            );
        }
    }

    return $item_data;
}

/**
 * Save custom fields values to the order
 */
function wc_save_custom_fields_order($item, $cart_item_key, $values, $order) {
    // if (isset($values['custom_product_field'])) {
    //     $item->add_meta_data(__('Additional Information', 'woocommerce'), $values['custom_product_field'], true);
    // }

    if (isset($values['gift_product_field']) && $values['gift_product_field'] === 'yes') {
        $item->add_meta_data(__('Send as Gift', 'woocommerce'), __('Yes', 'woocommerce'), true);
        if (isset($values['gift_message_field'])) {
            $item->add_meta_data(__('Gift Message', 'woocommerce'), $values['gift_message_field'], true);
        }
    }
}

/**
 * Add custom fields column to orders list
 */
function wc_custom_order_column($columns) {
    $new_columns = (is_array($columns)) ? $columns : array();
    //$new_columns['custom_product_field'] = __('Additional Information', 'woocommerce');
    $new_columns['gift_product_field'] = __('Send as Gift', 'woocommerce');
    return $new_columns;
}

/**
 * Display custom fields values in orders list
 */
function wc_custom_order_column_value($column, $post_id) {
    // if ($column === 'custom_product_field') {
    //     $order = wc_get_order($post_id);
    //     foreach ($order->get_items() as $item_id => $item) {
    //         if ($custom_field = $item->get_meta('Additional Information')) {
    //             echo esc_html($custom_field);
    //         }
    //     }
    // }

    if ($column === 'gift_product_field') {
        $order = wc_get_order($post_id);
        foreach ($order->get_items() as $item_id => $item) {
            if ($gift_field = $item->get_meta('Send as Gift')) {
                echo esc_html($gift_field);
                if ($gift_message = $item->get_meta('Gift Message')) {
                    echo '<br><strong>' . __('Gift Message:', 'woocommerce') . '</strong> ' . esc_html($gift_message);
                }
            }
        }
    }
}
?>
