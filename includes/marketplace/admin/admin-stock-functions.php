<?php

function get_all_publish_posts_ids( $post_type, $meta_query, $custom_args = array() ) {
	$query_args = array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => '-1',
		'fields'         => 'ids',
		'meta_query'     => $meta_query
	);

	if ( ! empty( $custom_args ) ) {
		$query_args = array_merge( $query_args, $custom_args );
	}

	return new WP_Query( $query_args );
}


// note: another option is to do count via commission transactions

function get_earned_money( $profit_period = null ) {
	$where = " WHERE `type` = 'commission' ";
	if ( $profit_period ) {
		$where .= " AND " . $profit_period;
	}

	$money_today = get_transactions_query( "SUM(`amount`)", $where );

	return $money_today;
}

function get_earned_money_today() {

	$today = date( 'Y-m-d', strtotime( 'today midnight' ) );

	return get_earned_money( " DATE(`date`) = '{$today}'  " );
}

function get_earned_money_week() {
	$start_date = date( 'Y-m-d', strtotime( 'monday this week' ) );
	$end_date   = date( 'Y-m-d', strtotime( 'sunday this week' ) );

	return get_earned_money( " ( DATE(`date`) BETWEEN '{$start_date}' AND '{$end_date}' ) " );
}

function get_earned_money_month() {
	$start_date = date( 'Y-m-01' );
	$end_date   = date( 'Y-m-t' );

	return get_earned_money( " ( DATE(`date`) BETWEEN '{$start_date}' AND '{$end_date}' ) " );
}

function get_earnings_for_period($date_from, $date_to) {
	$earnings = array();

	$dt_date_from = new DateTime($date_from);
	$dt_date_to = new DateTime( $date_to );
	$dt_date_to->add( new DateInterval( 'P1D' ) );

	if ( $dt_date_to < $dt_date_from ) {
		$earnings['error'] = "Неверно указан период";

		return $earnings;
	}

	$period = new DatePeriod(
		$dt_date_from,
		new DateInterval('P1D'),
		$dt_date_to
	);

	/** @var DateTime $date */
	$counter = 0;
	foreach ( $period as $date ) {

		$earnings[$counter]['date'] = $date->format( 'd/m/Y' );
		$earned_money =  get_earned_money( " DATE(`date`) = '{$date->format('Y-m-d')}'  " );
		$earnings[$counter]['value'] = $earned_money ? $earned_money : 0;
		$counter++;
	}


	return $earnings;
}

function get_total_withdrawn_money() {
	$withdrawn_money = get_transactions_query( "SUM(`amount`)", " WHERE `type` = 'commission_out' " );

	return $withdrawn_money;
}

function get_available_withdraw_amount() {
	return get_earned_money() - get_total_withdrawn_money();
}


add_action( 'admin_post_admin_stock_withdraw', 'admin_marketplace_withdraw' );
function admin_marketplace_withdraw() {
	global $NMS_Balance;

	$amount = (float) str_replace( ',', '.', $_POST['stock_withdraw_sum'] );
	$redirect_url = $_POST['redirect_url'];

	if ( ! current_user_can( 'manage_options' ) || $amount == 0 ) {
		wp_safe_redirect( $redirect_url );
	}

	/*	$date      = $_POST['stock_withdraw_date'];
		$date_time = new DateTime( $date ? $date : 'now', new DateTimeZone( 'Europe/Moscow' ) );
		$timestamp = $date_time->getTimestamp();*/

	$transaction_id = $NMS_Balance->withdraw_stock( $amount, $_POST['stock_withdraw_curr_id'], $_POST['stock_wallet'] );


	update_option( 'stock_wallet', sanitize_text_field( $_POST['stock_wallet'] ) );

	if ( is_int( $transaction_id ) ) {
		$redirect_url .= "&success=$transaction_id";
	} else {
		$redirect_url .= "&error=true";

	}

	wp_safe_redirect( $redirect_url );
	exit();
}


add_action( 'admin_post_fk_from_kassa_to_wallet', 'admin_fk_from_kassa_to_wallet' );
function admin_fk_from_kassa_to_wallet() {
	$amount = (float) $_POST['amount'];

	if ( ! is_super_admin() || $amount == 0) {
		$_SESSION['admin_fk_result'] = "Cумма перевода не может равняться 0";
		wp_safe_redirect( $_POST['redirect_url']);
	}

	$result = FreeKassa::getInstance()->withdraw_fk_kassa_to_wallet( $amount );
	$_SESSION['admin_fk_result'] = $result;
	wp_safe_redirect( $_POST['redirect_url']);

	exit();
}

function get_users_registered( $date ) {
	global $wpdb;
	$sql = $wpdb->prepare(
		"SELECT count(ID) FROM {$wpdb->users} WHERE {$wpdb->users}.user_registered >= '%s' ",
		$date
	);

	return $wpdb->get_var( $sql );
}


add_action( 'admin_bar_menu', 'add_maretplace_menu_to_admin_bar', 100 );
function add_maretplace_menu_to_admin_bar( $wp_admin_bar ) {


	$marketplace_stockexchange_stats = "Маркетплейс stockexchange";
	$wp_admin_bar->add_node( array(
		'id'    => 'marketplace-stockexchange-stats',
		'title' => $marketplace_stockexchange_stats,
		'href'  => admin_url( 'admin.php?page=stock-statistics' ),

		'meta' => array(
			'title' => 'Статистика stockexchange',
			'target' => '_blank',
			'class'  => 'marketplace-stockexchange-stats'
		)
	) );

}
