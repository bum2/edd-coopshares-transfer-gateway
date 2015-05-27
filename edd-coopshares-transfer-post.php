<?php

function cst_text_callback ( $args, $post_id ) {
	$value = get_post_meta( $post_id, $args['id'], true );
	if ( $value != "" ) {
		$value = get_post_meta( $post_id, $args['id'], true );
	}else{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$output = "<tr valign='top'> \n".
		" <th scope='row'> " . $args['name'] . " </th> \n" .
		" <td><input type='text' class='regular-text' id='" . $args['id'] . "'" .
		" name='" . $args['id'] . "' value='" .  $value   . "' />\n" .
		" <label for='" . $args['name'] . "'> " . $args['desc'] . "</label>" .
		"</td></tr>";

	return $output;
}

function cst_rich_editor_callback ( $args, $post_id ) {
	$value = get_post_meta( $post_id, $args['id'], true );
	if ( $value != "" ) {
		$value = get_post_meta( $post_id, $args['id'], true );
	}else{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
	$output = "<tr valign='top'> \n".
		" <th scope='row'> " . $args['name'] . " </th> \n" .
		" <td>";
		ob_start();
		wp_editor( stripslashes( $value ) , $args['id'], array( 'textarea_name' => $args['id'] ) );
	$output .= ob_get_clean();

	$output .= " <label for='" . $args['name'] . "'> " . $args['desc'] . "</label>" .
		"</td></tr>\n";

	return $output;
}


/**
 * Updates when saving post
 *
 */
function coopshares_transfer_post_save( $post_id ) {

	if ( ! isset( $_POST['post_type']) || 'download' !== $_POST['post_type'] ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

	$fields = cs_transfer_edd_post_fields();

	foreach ($fields as $field) {
		update_post_meta( $post_id, $field['id'],  $_REQUEST[$field['id']] );
	}
}
add_action( 'save_post', 'coopshares_transfer_post_save' );


/**
 * Display sidebar metabox in saving post
 *
 */
function cs_transfer_print_meta_box ( $post ) {

	if ( get_post_type( $post->ID ) != 'download' ) return;

	?>
	<div class="wrap">
		<div id="tab_container">
			<table class="form-table">
				<?php
					$fields = cs_transfer_edd_post_fields();
					foreach ($fields as $field) {
						if ( $field['type'] == 'text'){
							echo cst_text_callback( $field, $post->ID );
						}elseif ( $field['type'] == 'rich_editor' ) {
							echo cst_rich_editor_callback( $field, $post->ID ) ;
						}
					}
				?>

			</table>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
}

function cs_transfer_show_post_fields ( $post) {

	add_meta_box( 'coopshares_transfer_'.$post->ID, __( "Coopshares-Mixed Transfer Settings", 'edd-coopshares-transfer'), "cs_transfer_print_meta_box", 'download', 'normal', 'high');

}
add_action( 'submitpost_box', 'cs_transfer_show_post_fields' );

function cs_transfer_edd_post_fields () {

	$cs_transfer_gateway_post_settings = array(
		array(
			'id' => 'cs_transfer_post_IBAN',
			'name' => __( 'cs_platform_iban', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_platform_iban_desc', 'edd-coopshares-transfer' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cs_transfer_post_BIN',
			'name' => __( 'cs_platform_bin', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_platform_bin_desc', 'edd-coopshares-transfer' ),
			'type' => 'text',
			'size' => 'regular'
		),
		// bumbum
		array(
			'id' => 'cs_transfer_post_receipt',
			'name' => __( 'cs_transfer_receipt', 'edd-coopshares-transfer' ),
			'desc' => __('cs_transfer_receipt_desc', 'edd-coopshares-transfer'),// . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor'
		),
		//
		array(
			'id' => 'cs_transfer_post_from_email',
			'name' => __( 'cs_from_email', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_from_email_desc', 'edd-coopshares-transfer' ),
			'type' => 'text',
			'size' => 'regular',
			'std'  => get_bloginfo( 'admin_email' )
		),
		array(
			'id' => 'cs_transfer_post_subject_mail',
			'name' => __( 'cs_subject_mail', 'edd-coopshares-transfer' ),
			'desc' => __( 'cs_subject_mail_desc', 'edd-coopshares-transfer' ),//  . '<br/>' . edd_get_emails_tags_list(),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cs_transfer_post_body_mail',
			'name' => __( 'cs_body_mail', 'edd-coopshares-transfer' ),
			'desc' => __('cs_body_mail_desc', 'edd-coopshares-transfer') . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor'
		)
	);

	return $cs_transfer_gateway_post_settings;
};

