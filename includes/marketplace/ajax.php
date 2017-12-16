<?php

add_action( 'wp_ajax_update_user_balance', 'ajax_update_user_balance' );

function ajax_update_user_balance() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		die();
	}

	$user_balance =  get_user_balance( $user_id );

	echo json_encode( array(
		'balance' => format_balance( $user_balance, true ),
		'rawBalance' => $user_balance,
	) );

	wp_die();
}


add_action( 'wp_ajax_get_fk_signature', 'ajax_get_fk_signature' );
function ajax_get_fk_signature() {
	global $NMS_Balance;

	if ( ! is_user_logged_in() || ! isset( $_POST['topup_amount'] ) ) {
		exit();
	}

	// check for min topup amount
	$order_amount = (int) $_POST['topup_amount'];
	if ( ( $min_topup = $NMS_Balance->get_min_topup_balance() ) > $order_amount ) {
		echo json_encode( array( 'error' => 'Ошибка! Минимальная сумма платежа равна ' . $min_topup . ' руб.' ) );
		exit();
	}

	$order_id = bp_displayed_user_id();
	$fk_signature = FreeKassa::getInstance()->get_fk_signature( $order_amount, $order_id );

	echo json_encode( array( 'success' => $fk_signature ) );
	exit();
}