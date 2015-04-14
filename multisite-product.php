<?php
/*
Plugin Name: WooCommerce Multisite As A Product
Plugin URI: http://sethrubenstein.info/plugins
Description: Description
Version: 0.1
Author: Seth Rubenstein
Author URI: http://sethrubenstein.info
*/

/**
 * Copyright (c) `date "+%Y"` . All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

function add_multisite_product_type( $types ){
    $types[ 'mutlisite_product' ] = __( 'Multisite product' );
    return $types;
}
add_filter( 'product_type_selector', 'add_multisite_product_type' );

function create_multisite_product_type(){
    // declare the product class
    class WC_Product_Multisite extends WC_Product{
        public function __construct( $product ) {
            $this->product_type = 'mutlisite_product';
            parent::__construct( $product );
            // add additional functions here
        }
    }
}
add_action( 'plugins_loaded', 'create_multisite_product_type' );

function multisite_product_options_start_buffer(){
    ob_start();
}

function multisite_product_options_end_buffer(){
    // Get value of buffering so far
    $getContent = ob_get_contents();

    // Stop buffering
    ob_end_clean();

    $getContent = str_replace('options_group pricing show_if_simple show_if_external', 'options_group pricing show_if_simple show_if_external show_if_multisite_product', $getContent);
    echo $getContent;
}

add_action('woocommerce_product_options_sku', 'multisite_product_options_start_buffer');
add_action('woocommerce_product_options_pricing', 'multisite_product_options_end_buffer');
