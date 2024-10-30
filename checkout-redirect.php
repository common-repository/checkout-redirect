<?php
/**
 * Plugin Name: Checkout Redirect
 * Plugin URI: https://www.highriskshop.com/payment-gateway/woocommerce-payment-redirect-to-other-woocommerce-website-api/
 * Description: Redirect your customers to another website or a custom URL during checkout or after add to cart. Redirect checkout and set a custom redirect URL for each WooCommerce product.
 * Version: 1.0.0
 * Author: HighRiskShop.COM
 * Author URI: https://www.highriskshop.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Add custom field to product edit page
add_action('woocommerce_product_options_advanced', 'highriskshop_custom_atcr_add_to_cart_redirect_field');
function highriskshop_custom_atcr_add_to_cart_redirect_field() {
    woocommerce_wp_text_input(
        array(
            'id'          => 'highriskshop_custom_add_to_cart_redirect',
            'label'       => __('Custom Add to Cart Redirect URL', 'custom-add-to-cart-redirect'),
            'placeholder' => __('Enter custom URL', 'custom-add-to-cart-redirect'),
            'desc_tip'    => true,
            'description' => __('Enter the custom URL to redirect the add to cart button to.', 'custom-add-to-cart-redirect'),
        )
    );
// Add nonce field
    echo '<input type="hidden" name="highriskshop_custom_atcr_nonce" value="' . esc_attr(wp_create_nonce('highriskshop_custom_atcr_nonce_action')) . '" />';	
}

// Save custom field value with nested sanitization and validation
add_action('woocommerce_process_product_meta', 'highriskshop_custom_atcr_save_add_to_cart_redirect_field');
function highriskshop_custom_atcr_save_add_to_cart_redirect_field($post_id) {
    // Check nonce
    if ( ! isset( $_POST['highriskshop_custom_atcr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['highriskshop_custom_atcr_nonce'])), 'highriskshop_custom_atcr_nonce_action' ) ) {
        return;
    }

    // Check user permission
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $highriskshop_atcr_custom_redirect_url = isset($_POST['highriskshop_custom_add_to_cart_redirect']) ? esc_url(sanitize_url($_POST['highriskshop_custom_add_to_cart_redirect'])) : '';

    // Validate the URL
    if (filter_var($highriskshop_atcr_custom_redirect_url, FILTER_VALIDATE_URL) !== false) {
        update_post_meta($post_id, 'highriskshop_custom_add_to_cart_redirect', $highriskshop_atcr_custom_redirect_url);
    } else {
        echo '<script>alert("' . esc_js("Error: Invalid URL. Please enter a valid URL.") . '");</script>';
    }
}

add_action('wp_footer', 'highriskshop_custom_atcr_add_to_cart_redirect_js');
function highriskshop_custom_atcr_add_to_cart_redirect_js() {
    global $post;

    // Check if we're on an archive page or a single product page
    if (is_archive()) {
        $highriskshop_products_query = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => -1 // Retrieve all products
        ));

        if ($highriskshop_products_query->have_posts()) {
            while ($highriskshop_products_query->have_posts()) {
                $highriskshop_products_query->the_post();
                $highriskshop_product_id = get_the_ID();
                $highriskshop_custom_redirect_url = esc_url(get_post_meta($highriskshop_product_id, 'highriskshop_custom_add_to_cart_redirect', true));
                ?>
                <script>
                    jQuery(function($){
                        $(document).on('click', '[data-product_id="<?php echo esc_attr($highriskshop_product_id); ?>"]', function(e){
                            e.preventDefault();
                            var HighRiskcustomRedirectUrl = '<?php echo esc_js($highriskshop_custom_redirect_url); ?>';
                            if (HighRiskcustomRedirectUrl) {
                                window.location.href = HighRiskcustomRedirectUrl;
                            } else {
                                // Proceed with default add to cart behavior if no custom URL is set
                                $(this).closest('form').submit();
                            }
                        });
                    });
                </script>
                <?php
            }
            wp_reset_postdata();
        }
    } elseif (is_product()) {
        $highriskshop_product_id = $post->ID;
        $highriskshop_custom_redirect_url = esc_url(get_post_meta($highriskshop_product_id, 'highriskshop_custom_add_to_cart_redirect', true));
        ?>
        <script>
            jQuery(function($){
                $(document).on('click', '.single_add_to_cart_button', function(e){
                    e.preventDefault();
                    var HighRiskcustomRedirectUrl = '<?php echo esc_js($highriskshop_custom_redirect_url); ?>';
                    if (HighRiskcustomRedirectUrl) {
                        window.location.href = HighRiskcustomRedirectUrl;
                    } else {
                        // Proceed with default add to cart behavior if no custom URL is set
                        $(this).closest('form').submit();
                    }
                });

                // Loop for related products
                <?php
                $highriskshop_related_products = wc_get_related_products($post->ID);
                $highriskshop_related_products_query = new WP_Query(array(
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'post__in' => $highriskshop_related_products
                ));
                while ($highriskshop_related_products_query->have_posts()) {
                    $highriskshop_related_products_query->the_post();
                    $highriskshop_related_product_id = get_the_ID();
                    $highriskshop_related_custom_redirect_url = esc_url(get_post_meta($highriskshop_related_product_id, 'highriskshop_custom_add_to_cart_redirect', true));
                    ?>
                    $(document).on('click', '[data-product_id="<?php echo esc_attr($highriskshop_related_product_id); ?>"]', function(e){
                        e.preventDefault();
                        var HighRiskcustomRedirectUrl = '<?php echo esc_js($highriskshop_related_custom_redirect_url); ?>';
                        if (HighRiskcustomRedirectUrl) {
                            window.location.href = HighRiskcustomRedirectUrl;
                        } else {
                            // Proceed with default add to cart behavior if no custom URL is set
                            $(this).closest('form').submit();
                        }
                    });
                    <?php
                }
                wp_reset_postdata();
                ?>
            });
        </script>
        <?php
    }
}

// Add custom admin notice with URL
function highriskshopcr_custom_admin_notice_with_url() {
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>';
    echo esc_html__('You have installed the free version of the payment redirect plugin with limited functions, to get the full premium version with full payment redirect/cloaking that allows your customer to pay through a payment gateway installed on another WooCommerce site please purchase the premium plugin at ', 'high-risk-payment-redirect');
    echo '<a href="'.esc_url('https://www.highriskshop.com').'">'.esc_html('HighRiskShop.COM').'</a>';
    echo esc_html('.');
    echo '</p>';
    echo '</div>';
}
add_action('admin_notices', 'highriskshopcr_custom_admin_notice_with_url');
