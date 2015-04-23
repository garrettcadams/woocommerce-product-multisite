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

function cliffmichales_ms_checkout_fields( $checkout ) {
    global $woocommerce;

    foreach($woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		$_product = $values['data'];

		if( get_cliffmichaels_ms_product_id() == $_product->id ) {
            echo '<div id="cm_ms_site_info"><h2>' . __('Site Name:') . '</h2>';

            woocommerce_form_field( 'cliff_ms_site_name', array(
                'type'          => 'text',
                'class'         => array('my-field-class form-row-wide'),
                'label'         => __('The name you would like to call your site, this will also be shortened into your access url. So Acme University would become cliffmichaels.com/acme-university.'),
                'placeholder'   => __('Acme University'),
            ), $checkout->get_value( 'cliff_ms_site_name' ));

            echo '</div>';
		}
	}

}
add_action( 'woocommerce_after_order_notes', 'cliffmichales_ms_checkout_fields' );

function cliffmichaels_ms_validate_checkout() {
    global $woocommerce;

    foreach($woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		$_product = $values['data'];
        // Check if set, if its not set add an error.
		if( get_cliffmichaels_ms_product_id() == $_product->id && ! $_POST['cliff_ms_site_name']) {
                wc_add_notice( __( 'Please give us a site name.' ), 'error' );
        }
    }
}
add_action( 'woocommerce_checkout_process', 'cliffmichaels_ms_validate_checkout' );

function cliffmichaels_ms_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['cliff_ms_site_name'] ) ) {
        $user_id = get_current_user_id();
        update_post_meta( $order_id, 'cm_ms_site_name', sanitize_text_field( $_POST['cliff_ms_site_name'] ) );

        $site_name = preg_replace("/[^\w]+/", "-", $_POST['cliff_ms_site_name']);
        update_post_meta( $order_id, 'cm_ms_site_url', strtolower($site_name) );
        add_user_meta( $user_id, 'site_url', strtolower($site_name) );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'cliffmichaels_ms_update_order_meta' );

// Once the user has checked out we need to create the new site...
function cliffmichaels_ms_create_site( $order ) {
	global $woocommerce;
    $user_id = get_current_user_id();

    /**
	 * We're checking to see if this is the "thank you page"
	 * if so then we're initiating a new WC_Order class using the order id from $order.
	 */
	$current_page =  "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$escaped_link = htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8');
	$link = parse_url($escaped_link);
	$page_path = $link['path'];
	if (strpos($page_path,'order-received') !== false) {
		$order = new WC_Order($order);
	}

	// Loop through purchased items in order and add
	if ( sizeof( $order->get_items() ) > 0 ) {
		foreach( $order->get_items() as $item ) {
            $product_id = $item["product_id"];
            if ( $product_id === get_cliffmichaels_ms_product_id() ) {
                echo '<script>console.log("Site Creation Process Begun");</script>';
    			// Here we're adding a custom meta to each user with the product id
                if (!get_user_meta( $user_id, 'site_created_'.$product_id.'', true ) ) {
                    add_user_meta( $user_id, 'site_created_'.$product_id.'', 'true' );
                    $user = new WP_User( $user_id );
                    $user->add_cap( 'manage_white_label' );
                    $site_name = get_post_meta($order->id, 'cm_ms_site_name', true);
                    $site_url = get_post_meta($order->id, 'cm_ms_site_url', true);
                    wpmu_create_blog(  'cliffmichaels.dev',  '/'.$site_url.'/',  ''.$site_name.'',  $user_id );
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
    $manager_page = array(
        'post_content'   => 'Manage Users',
        'post_name'      => 'manager-dashboard',
        'post_title'     => 'User Management Dashboard',
        'post_status'    => 'publish',
        'post_type'      => 'page',
        'page_template'  => 'page-manager-dashboard.php'
    );
    wp_insert_post( $manager_page );
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

function cliffmichaels_ms_thankyou_text() {
    $user_id = get_current_user_id();
    $new_site_url = get_user_meta( $user_id, 'site_url', true );
    ?>
    <br>
    <center><a href="<?php bloginfo('url');?>/<?php echo $new_site_url;?>/" class="button">Click Here to Setup Your New Site</a></center>
    <?php
}
add_filter( 'woocommerce_thankyou_order_received_text', 'cliffmichaels_ms_thankyou_text', 2 );

if( function_exists('register_field_group') ):

register_field_group(array (
	'key' => 'group_552f06212ef31',
	'title' => 'White Label Options',
	'fields' => array (
		array (
			'key' => 'field_552f062fb2076',
			'label' => 'Subscription Product ID',
			'name' => 'subscription_product_id',
			'prefix' => '',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => '',
			'max' => '',
			'step' => '',
			'readonly' => 0,
			'disabled' => 0,
		),
	),
	'location' => array (
		array (
			array (
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'acf-options',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
));

endif;
