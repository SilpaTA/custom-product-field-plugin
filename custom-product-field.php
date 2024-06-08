<?php
/**
 * Plugin Name: WooCommerce Custom Product Field
 * Description: Adds custom fields to WooCommerce products for additional information, including gift options and messages.
 * Version: 1.0
 * Author: Silpa TA
 * Author URL: https://github.com/SilpaTA/custom-product-field-plugin
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WC_Custom_Product_Field {

    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save_custom_fields']);
        add_action('woocommerce_single_product_summary', [$this, 'display_custom_fields'], 25);
        add_action('woocommerce_before_add_to_cart_button', [$this, 'add_gift_options']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_custom_fields_to_cart'], 10, 2);
        add_filter('woocommerce_get_item_data', [$this, 'display_custom_fields_cart'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_custom_fields_order'], 10, 4);
        add_filter('manage_edit-shop_order_columns', [$this, 'custom_order_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'custom_order_column_value'], 10, 2);
    }

    /**
     * Add the custom fields to the product general options tab
     */
    public function add_custom_fields() {
        woocommerce_wp_text_input([
            'id' => '_custom_product_field',
            'label' => __('Additional Information', 'woocommerce'),
            'placeholder' => 'Please enter a message for the users',
            'desc_tip' => 'true',
            'description' => __('Please enter a message for the users.', 'woocommerce')
        ]);
    }

    /**
     * Save the custom fields values
     */
    public function save_custom_fields($post_id) {
        $custom_field_value = isset($_POST['_custom_product_field']) ? sanitize_text_field($_POST['_custom_product_field']) : '';
        update_post_meta($post_id, '_custom_product_field', $custom_field_value);
    }

    /**
     * Display the custom fields on the single product page
     */
    public function display_custom_fields() {
        global $post;
        $custom_field_value = get_post_meta($post->ID, '_custom_product_field', true);

        if (!empty($custom_field_value)) {
            echo '<div class="woocommerce_custom_field"><strong>' . __('Additional Information:', 'woocommerce') . '</strong> ' . esc_html($custom_field_value) . '</div>';
        }
    }

    /**
     * Add gift options fields to the single product page
     */
    public function add_gift_options() {
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
    public function add_custom_fields_to_cart($cart_item_data, $product_id) {
        $gift_product_field = isset($_POST['gift_product_checkbox']) ? 'yes' : 'no';
        $gift_message_field = isset($_POST['gift_message_field']) ? sanitize_textarea_field($_POST['gift_message_field']) : '';

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
    public function display_custom_fields_cart($item_data, $cart_item) {
        if (isset($cart_item['gift_product_field']) && $cart_item['gift_product_field'] === 'yes') {
            $item_data[] = [
                'name' => __('Send as Gift', 'woocommerce'),
                'value' => __('Yes', 'woocommerce')
            ];
            if (isset($cart_item['gift_message_field'])) {
                $item_data[] = [
                    'name' => __('Gift Message', 'woocommerce'),
                    'value' => $cart_item['gift_message_field']
                ];
            }
        }

        return $item_data;
    }

    /**
     * Save custom fields values to the order
     */
    public function save_custom_fields_order($item, $cart_item_key, $values, $order) {
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
    public function custom_order_column($columns) {
        $new_columns = (is_array($columns)) ? $columns : [];
        $new_columns['gift_product_field'] = __('Send as Gift', 'woocommerce');
        return $new_columns;
    }

    /**
     * Display custom fields values in orders list
     */
    public function custom_order_column_value($column, $post_id) {
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
}

// Initialize the plugin
new WC_Custom_Product_Field();

?>
