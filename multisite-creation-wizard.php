<?php
/*
Plugin Name: Cliff Michaels Mutlisite Creation Wizard
Plugin URI: http://sethrubenstein.info/plugins
Description: This plugin offers functionality to cliffmichaels.com that would allow for a subscription product for multisite.
Version: 0.1
Author: Seth Rubenstein
Author URI: http://sethrubenstein.info
*/

function get_cliffmichaels_ms_product_id() {
    if (get_field('subscription_product_id','options')) {
        return get_field('subscription_product_id','options');
    } else {
        return 18402;
    }
}


// Once the user has checked out we need to create the new site...
function cliffmichaels_ms_create_site( $order ) {
	global $woocommerce;
    $user_id = get_current_user_id();

    $order = new WC_Order($order);

	// Loop through purchased items in order and add
	if ( sizeof( $order->get_items() ) > 0 ) {
		foreach( $order->get_items() as $item ) {
            $product_id = $item["product_id"];
            if ( $product_id === get_cliffmichaels_ms_product_id() ) {
                echo '<script>console.log("Site Creation Process Begun");</script>';
    			// Here we're adding a custom meta to each user with the product id
                if (!get_user_meta( $user_id, 'site_created_'.$product_id.'', true ) ) {
                    add_user_meta( $user_id, 'site_created_'.$product_id.'', 'purchased' );
                    wpmu_create_blog(  'cliffmichaels.dev',  '/test-site/',  'Test Site',  $user_id );
                    echo '<script>console.log("Authentication of '.$product_id.' Successful");</script>';
                    $notification_markup = '';
                    echo $notification_markup;
                } else {
                    echo '<script>console.log("Registration Already Present for '.$product_id.'");</script>';
                }
            }
		}
	} else {
        echo '<div class="error"><strong>ERROR1001</strong Your site was not create. Please contact support and let them know your error code (1001) to resolve this issue.</div>';
    }
}

function cliffmichaels_ms_initial_site_setup($blog_id){
    switch_to_blog($blog_id);
    switch_theme('cliff-michaels-whitelabel');
    activate_plugin( 'woocommerce/woocommerce.php' );
    restore_current_blog();
}

add_action('wpmu_new_blog', 'cliffmichaels_ms_initial_site_setup');

/**
 * The order items table appears on the thank you page.
 * However, we want to prevent duplicate registrations.
 */
function cliffmichaels_ms_hijack_thankyou() {
	$current_page =  "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$escaped_link = htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8');
	$link = parse_url($escaped_link);
	$page_path = $link['path'];
	if (strpos($page_path,'order-received') !== false) {
		add_action('woocommerce_thankyou', 'cliffmichaels_ms_create_site');
	} else {
		add_action('woocommerce_order_items_table', 'cliffmichaels_ms_create_site');
	}
}
add_action('wp_head','cliffmichaels_ms_hijack_thankyou');
