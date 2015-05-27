<?php
/*
Plugin Name: Easy Digital Downloads - CoopShares-FEHIF Transfers Gateway
Plugin URL: https://github/bum2/edd-coopshares-transfer-gateway
Description: Another EDD gateway for the CoopShares-FEHIF (Faircoin-Euro Hybrid Investment Fund) option in getfaircoin.net, forked from the edd-manual-gateway (a fork of the manual_edd_wp_plugin).
Version: 0.5
Author: Bumbum
Author URI: https://getfaircoin.net
*/

//Language
load_plugin_textdomain( 'edd-coopshares-transfer', false,  dirname(plugin_basename(__FILE__)));// . '/languages/' );

//Load post fields management
require_once ( __DIR__ . '/edd-coopshares-transfer-post.php');

//Registers the gateway
function coopshares_transfer_edd_register_gateway( $gateways ) {
	$gateways['coopshares_transfer'] = array( 'admin_label' => 'Coopshares-Mixed Transfer', 'checkout_label' => __( 'Coopshares-Mixed Transfer', 'edd-coopshares-transfer' ) );
	return $gateways;
}
add_filter( 'edd_payment_gateways', 'coopshares_transfer_edd_register_gateway' );

//Pre purchase form
function edd_coopshares_transfer_gateway_cc_form() {

	$output = '<div>';

		global $edd_options;
		$output .= $edd_options['cs_transfer_checkout_info'];

	$output .= "</div>";

	echo $output;

}
add_action('edd_coopshares_transfer_cc_form', 'edd_coopshares_transfer_gateway_cc_form');


// processes the payment
function coopshares_transfer_edd_process_payment( $purchase_data ) {
    
    global $edd_options;
    
	// check for any stored errors
	$errors = edd_get_errors();
	if ( ! $errors ) {

		$purchase_summary = edd_get_purchase_summary( $purchase_data );

		$payment = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => $edd_options['currency'],
			'downloads'    => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info'    => $purchase_data['user_info'],
			'status'       => 'pending'
		);

		// record the pending payment
		$payment = edd_insert_payment( $payment );

		// send email with payment info
		coopshares_transfer_email_purchase_order( $payment );

		edd_send_to_success_page();

	} else {
		$fail = true; // errors were detected
	}

	if ( $fail !== false ) {
		// if errors are present, send the user back to the purchase page so they can be corrected
		//edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		edd_send_back_to_checkout( '/checkout' );
	}
}
add_action( 'edd_gateway_coopshares_transfer', 'coopshares_transfer_edd_process_payment' );


// adds the settings to the Payment Gateways section
function coopshares_transfer_add_settings ( $settings ) {

	$coopshares_transfer_gateway_settings = array(
		array(
			'id' => 'coopshares_transfer_gateway_settings',
			'name' => '<strong>' . __( 'Coopshares-Mixed Transfer Settings', 'edd-coopshares-transfer' ) . '</strong>',
			'desc' => __( 'Settings to manage the Coopshares-Mixed payment gateway via wire transfer', 'edd-coopshares-transfer' ),
			'type' => 'header'
		),
		array(
			'id' => 'cs_transfer_platform_IBAN',
			'name' => __( 'cs_platform_IBAN', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_platform_iban_desc', 'edd-coopshares-transfer' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cs_transfer_platform_BIC',
			'name' => __( 'cs_platform_bin', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_platform_bin_desc', 'edd-coopshares-transfer' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cs_transfer_one_or_multiple_IBAN',
			'name' => __( 'cs_one_multiple_accounts', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_one_multiple_accounts_desc', 'edd-coopshares-transfer' ),
			'type' => 'select',
			'options' => array(1 => 'ONE', 2 => 'MULTIPLE'),
			'std'  => 1
		),
		array(
			'id' => 'cs_transfer_checkout_info',
			'name' => __( 'cst_checkout_info', 'edd-coopshares-transfer' ),
			'desc' => __( 'cst_checkout_info_desc', 'edd-coopshares-transfer' ),
			'type' => 'rich_editor'
		),
		array(
			'id' => 'cs_transfer_from_email',
			'name' => __( 'cst_from_email', 'edd-coopshares-transfer' ),
			'desc' => __( 'cst_from_email_desc', 'edd-coopshares-transfer' ),
			'type' => 'text',
			'size' => 'regular',
			'std'  => get_bloginfo( 'admin_email' )
		),
		array(
			'id' => 'cs_transfer_subject_mail',
			'name' => __( 'cst_subject_mail', 'edd-coopshares-transfer' ),
			'desc' => __( 'cst_subject_mail_desc', 'edd-coopshares-transfer' )  . '<br/>' . edd_get_emails_tags_list(),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cs_transfer_body_mail',
			'name' => __( 'cst_body_mail', 'edd-coopshares-transfer' ),
			'desc' => __('cst_body_mail_desc', 'edd-coopshares-transfer') . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor',
		),

	);

	return array_merge( $settings, $coopshares_transfer_gateway_settings );
}
add_filter( 'edd_settings_gateways', 'coopshares_transfer_add_settings' );


function coopshares_email_tag_IBAN( $payment_id ) {

	global $edd_options;
	if ( $edd_options['cs_transfer_one_or_multiple_IBAN'] == 1 ) {
		$IBAN = $edd_options['cs_transfer_platform_IBAN'];
	} else {
		$downloads = edd_get_payment_meta_cart_details( $payment_id );
		$post_id = $downloads[0]['id'];
		$IBAN = get_post_meta( $post_id, 'cs_transfer_post_IBAN', true );
	}
	return $IBAN;

}
function coopshares_email_tag_BIC( $payment_id ) {

	global $edd_options;
	if ( $edd_options['cs_transfer_one_or_multiple_IBAN'] == 1 ) {
		$BIC = $edd_options['cs_transfer_platform_BIC'];
	} else {
		$downloads = edd_get_payment_meta_cart_details( $payment_id );
		$post_id = $downloads[0]['id'];
		$BIC = get_post_meta( $post_id, 'cs_transfer_post_BIN', true );
	}
	return $BIC;

}

function coopshares_transfer_setup_email_tags() {

	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'cs_IBAN',
			'description' => __( 'Coopshares-Mixed Transfer IBAN account number', 'edd-coopshares-transfer' ),
			'function'    => 'coopshares_email_tag_IBAN'
		),
		array(
			'tag'         => 'cs_BIC',
			'description' => __( 'Coopshares-Mixed Transfer BIC of the bank', 'edd-coopshares-transfer' ),
			'function'    => 'coopshares_email_tag_BIC'
		)
	);

	// Apply edd_email_tags filter
	$email_tags = apply_filters( 'edd_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		edd_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
	}

}
add_action( 'edd_add_email_tags', 'coopshares_transfer_setup_email_tags' );


//Sent transfer instructions
function coopshares_transfer_email_purchase_order ( $payment_id, $admin_notice = true ) {

	global $edd_options;

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_id      = edd_get_payment_user_id( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );
	$to           = edd_get_payment_user_email( $payment_id );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $email;
	}

	$message = edd_get_email_body_header();


	if ( $edd_options['cs_transfer_one_or_multiple_IBAN'] == 1 ) {
		$email = stripslashes( $edd_options['cs_transfer_body_mail'] );
		$from_email = isset( $edd_options['cs_transfer_from_email'] ) ? $edd_options['cs_transfer_from_email'] : get_option('admin_email');
		$subject = wp_strip_all_tags( $edd_options['cs_transfer_subject_mail'], true );
	} else {
		$downloads = edd_get_payment_meta_cart_details( $payment_id );
		$post_id = $downloads[0]['id'];
		$email = stripslashes (get_post_meta( $post_id, 'cs_transfer_post_body_mail', true ));
		$from_email = get_post_meta( $post_id, 'cs_transfer_post_from_email', true );
		$subject = wp_strip_all_tags(get_post_meta( $post_id, 'cs_transfer_post_subject_mail', true ));
	}


	$message .= edd_do_email_tags( $email, $payment_id );
	$message .= edd_get_email_body_footer();

	$from_name = get_bloginfo('name');

	$subject = edd_do_email_tags( $subject, $payment_id );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers = apply_filters( 'edd_receipt_headers', $headers, $payment_id, $payment_data );

	if ( apply_filters( 'edd_email_purchase_receipt', true ) ) {
		wp_mail( $to, $subject, $message, $headers);//, $attachments );
	}

	if ( $admin_notice && ! edd_admin_notices_disabled( $payment_id ) ) {
		do_action( 'edd_admin_sale_notice', $payment_id, $payment_data );
	}
}


function coopshares_transfer_payment_receipt_after($payment){
  if( edd_get_payment_gateway( $payment->ID ) == 'coopshares_transfer'){
    $payment_data = edd_get_payment_meta( $payment->ID );
    $downloads = edd_get_payment_meta_cart_details( $payment->ID );
    $post_id = $downloads[0]['id'];
    $message = stripslashes ( get_post_meta( $post_id, 'cs_transfer_post_receipt', true ));
    $message = edd_do_email_tags( $message, $payment->ID );
    //$message = edd_get_payment_gateway( $payment->ID );
    echo $message;
  }
}
add_action('edd_payment_receipt_after_table', 'coopshares_transfer_payment_receipt_after');
