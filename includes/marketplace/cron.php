<?php
add_filter( 'cron_schedules', 'stockexchange_cron_schedules' );
function stockexchange_cron_schedules( $schedules ) {
	if ( ! isset( $schedules["30min"] ) ) {
		$schedules["30min"] = array(
			'interval' => 60 * 30,
			'display'  => __( 'Every 30 minutes' )
		);
	}

	if ( ! isset( $schedules["1min"] ) ) {
		$schedules["1min"] = array(
			'interval' => 60,
			'display'  => __( 'Every 1 minute' )
		);
	}

	if ( ! isset( $schedules["24h"] ) ) {
		$schedules["24h"] = array(
			'interval' => 60 * 60 * 24,
			'display'  => __( 'Every day (24 hours)' )
		);
	}

	return $schedules;
}

add_action( 'init', 'init_stockexchange_cron_job' );
function init_stockexchange_cron_job() {

	// Make sure this event hasn't been scheduled
/*	wp_clear_scheduled_hook( 'cron_job_every_24_hours' );
	$timestamp = wp_next_scheduled('cron_job_every_24_hours');
	wp_unschedule_event($timestamp,'cron_job_every_24_hours');*/

	if ( ! wp_next_scheduled( 'cron_job_hourly' ) ) {
		wp_schedule_event( time(), 'hourly', 'cron_job_hourly' );
	}


	if ( ! wp_next_scheduled( 'cron_job_daily' ) ) {
		wp_schedule_event( time(), 'daily', 'cron_job_daily' );
	}

	if ( ! wp_next_scheduled( 'cron_job_every_1_min' ) ) {
		wp_schedule_event( time(), '1min', 'cron_job_every_1_min' );
	}
}

add_action( 'cron_job_hourly', 'delete_unconfirmed_orders' );
function delete_unconfirmed_orders() {
	$orders = get_orders( array( 'meta_key' => '_order_status', 'meta_value' => 'unconfirmed', 'fields' => 'ids' ) );

	if ( ! $orders->have_posts() ) {
		return;
	}

	foreach ( $orders->posts as $order_id ) {
		$unconfirmed_deadline = get_order_deadline( $order_id );
		$now = new DateTime( current_time('mysql'), new DateTimeZone('Europe/Moscow') );
		if ( $now  >= $unconfirmed_deadline['final_date'] ) {
			wp_delete_post( $order_id, true );
		} elseif ( $unconfirmed_deadline['diff_obj']->h >= 2 && $unconfirmed_deadline['diff_obj']->invert == 0 ) {
			NimsEmail::maybeSendBuyerReminder($order_id);
		}
	}
}


add_action( 'cron_job_every_1_min', 'close_expired_new_orders' );
function close_expired_new_orders() {
	$orders = get_orders( array( 'meta_key' => '_order_status', 'meta_value' => 'new', 'fields' => 'ids' ) );

	if ( ! $orders->have_posts() ) {
		return;
	}

	foreach ( $orders->posts as $new_order_id ) {
		$reply_deadline = get_order_deadline( $new_order_id );
		$now = new DateTime( current_time('mysql'), new DateTimeZone('Europe/Moscow') );
		if ( $now  >= $reply_deadline['final_date'] ) {
			order_closed_reason( $new_order_id, "Истекло время ожидания передачи нимов от продавца" );
			set_order_status( $new_order_id, 'closed' );
		}
	}
}

add_action( 'cron_job_every_1_min', 'close_expired_pending_orders' );
function close_expired_pending_orders() {
	$orders = get_orders( array( 'meta_key' => '_order_status', 'meta_value' => 'pending', 'fields' => 'ids' ) );

	if ( $orders->have_posts() ) {
		foreach ( $orders->posts as $new_order_id ) {
			$transfer_deadline = get_order_deadline( $new_order_id, true );
			$now = new DateTime( current_time('mysql'), new DateTimeZone('Europe/Moscow'));
			if ( $now  >= $transfer_deadline['final_date'] ) {
				order_closed_reason( $new_order_id, BUYER_REPLY_TIMEOUT );
				set_order_status( $new_order_id, 'done' );
			}
		}
	}
}

add_action( 'cron_job_daily', 'disable_expired_sellers' );
function disable_expired_sellers() {
	$sellers = get_users( wp_parse_args( 'meta_key=user_sells_nims&meta_value=1' ) );

	if ( ! empty ( $sellers ) ) {

		foreach ( $sellers as $seller ) {
			$last_activity = bp_get_user_last_activity( $seller->ID );
			if ( ! $last_activity ) {
				continue;
			}

			$lastActivityDateTime = new DateTime( $last_activity,  new DateTimeZone('Europe/Moscow') );
			$lastActivityDateTime->add( new DateInterval( 'P10D' ) );

			$now = new DateTime( current_time('mysql'), new DateTimeZone('Europe/Moscow'));

			if ( $now >= $lastActivityDateTime ) {
				update_user_meta( $seller->ID, 'user_sells_nims', 0 );
			}

		}

	}
}