<?php
/*
 * Custom functions to override default woocommerce and dokan plugins
 * child-functions.php
 */


// WooCommerce custom functions

// display an 'SOLD' label on out of stock products

add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_stock', 10 );

function woocommerce_template_loop_stock() {

	global $product;
	$availability = $product->get_availability();

	if ( $availability['availability'] == 'Out of stock') {
			echo '<span class="sold-ribbon-wrapper"><span class="sold_ribbon">Sold</span></span>';
	}
}


// Display seller name/link on products page(spare parts) and product single page

function display_seller_name_on_product() {
	global $product;
	$author = get_user_by( 'id', $product->post->post_author );
	$store_info = dokan_get_store_info( $author->ID );

	if ( !empty( $store_info['store_name'] ) ) { ?>
    <h5 class="details">
      Seller: <?php printf( '<a href="%s">%s</a>', dokan_get_store_url( $author->ID ), $author->display_name ); ?>
    </h5>
  <?php 
  }

}

add_action( 'woocommerce_after_shop_loop_item_title','display_seller_name_on_product');
add_action( 'woocommerce_before_add_to_cart_form','display_seller_name_on_product');


// Function to validate add to cart functionality to only allow same seller products into cart

function validate_add_cart_item_based_seller( $passed, $product_id, $quantity, $variation_id = '', $variations= '' ) {

	global $woocommerce;
	$cart = $woocommerce->cart->get_cart_contents_count();

	// Check if cart is not empty
	if ( $cart != 0 ) { 

		// Fetch cart items
		$items = $woocommerce->cart->get_cart();

			foreach($items as $item => $values) { 
					
				$cart_product    = get_post( $values['product_id'] );
				$cart_product_seller = $cart_product->post_author; 
				$cart_seller = get_user_by( 'id', $cart_product_seller );
				$cart_seller_id = $cart_seller->ID;
				$cart_seller_display_name = $cart_seller->display_name;
			} 

			// Get product details
			$product = get_post( $product_id );
			$product_seller = $product->post_author; 
			$seller = get_user_by( 'id', $product_seller );
			$seller_id = $seller->ID;

			//Display error if cart product seller and current product seller are not equal
			if($cart_seller_id != $seller_id) {
				$passed = false;
				wc_add_notice( __( "You can only add products from '$cart_seller_display_name' or delete previous item", 'textdomain' ), 'error' );
			}
	}
	return $passed;

}
add_filter( 'woocommerce_add_to_cart_validation', 'validate_add_cart_item_based_seller', 10, 5 );



/**
 * Apply a 'FM Member GST' for 'FM Member' user role and 'TRADE Member GST' on 'Trade Member'
 */
function wc_diff_rate_for_user( $tax_class, $product ) {
	
	if ( is_page( 'cart' ) || is_cart() ) {

		global $woocommerce;
    $items = $woocommerce->cart->get_cart();

			foreach($items as $item => $values) { 
					
				$post_obj    = get_post( $values['product_id'] );
				$post_author = $post_obj->post_author; 
				$user = get_user_by( 'id', $post_author );
			
				if ( $user->roles[1]=='5378f25c7c001' ) {
					$tax_class = 'FM Member GST';
				
				} elseif ( $user->roles[1]=='5378f27c63e76' ) {
					$tax_class = 'TRADE Member GST';
					
				}
			} 

		return $tax_class;
	}
}
add_filter( 'woocommerce_product_tax_class', 'wc_diff_rate_for_user', 1, 2 );

// WooCommerce custom functions end

// Custom recent-video-posts shortcode to display post's video on spare-parts page's sidebar
// use [recent-video-posts] shortcode

function my_recent_posts_shortcode($atts) {
	$q = new WP_Query(
	  array( 'orderby' => 'date', 'posts_per_page' => '1')
	);

	$post_types = array(
		'audio',
		'video',
		'quote',
		'gallery',
	);

   $list = '<div class="recent-posts col-md-12">';
   
   while($q->have_posts()) : $q->the_post();

   $id = get_the_ID();
   $post_pod_type = get_post_meta($id, 'post_pod_type', true);

   $post_type_values = get_post_meta( get_the_ID(), 'post_type_values', true );
	$source_url = $post_type_values['video'];
	$cover_image = isset($post_type_values['video_cover_image']) ? $post_type_values['video_cover_image'] : '';
	$cover_image_on_mobiles = isset($post_type_values['video_cover_image_on_mobiles']) ? (int) $post_type_values['video_cover_image_on_mobiles'] : 0;
	
	$attributes = array();

	if ($cover_image) {
		$attributes[] = 'cover_image="' . esc_url($cover_image) . '"';
	
		if ($cover_image_on_mobiles) {
			$attributes[] = 'cover_image_on_mobiles="' . $cover_image_on_mobiles . '"';
		}
	
	} else {
		$attributes[] = 'cover_id="' . $post->ID . '"';
	}

	if (!empty($source_url)) {

	$list .= '<article id="custom-post-'.$id.'" class="custom-entry row custom-post-'.$id.'">
	<a href="' . get_permalink() . '">' . get_the_title() . '</a><br>';
	$list .= '<span class="post-date">'.get_the_date().'</span>';

	$list .='<div class="custom-entry-image custom-widget">'
		.do_shortcode('[tmm_video ' . implode(' ', $attributes) . ']' . esc_url($source_url) . '[/tmm_video]').
	'</div>';
	}
	$list .= '</article>';
   endwhile;
   
   wp_reset_postdata();

   return $list . '</div>';
   
   }
   
   add_shortcode('recent-video-posts', 'my_recent_posts_shortcode');