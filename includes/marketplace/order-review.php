<?php

add_filter( 'bp_members_reviews_review_allowed', 'restrict_user_review', 10, 3 );
function restrict_user_review( $allowed, $reviewer_id, $reviewee_id ) {

	if ( ! isset( $_REQUEST['add-review'] ) || ! is_numeric( $_REQUEST['add-review'] ) ) {
		return false;
	}

	$order = get_post( (int) $_REQUEST['add-review'] );
	if ( ! $order || $order->post_type != 'order' || $order->post_status != 'publish' ){
		return false;
	}

	if ( ! in_array( get_order_status( $order->ID ), array( 'done' ) ) ) {
		return false;
	}

	$parties = array(
		'seller_id' => get_order_seller_id( $order->ID ),
		'buyer_id'  => get_order_buyer_id( $order->ID )
	);


	// check that reviewer and reviewee belong to one of the parties
	if ( ! in_array( $reviewer_id, $parties ) || ! in_array( $reviewee_id, $parties ) ) {
		return false;
	}

	$reviewed_by = order_reviewed_by( $order->ID );

	if ( $reviewer_id == $parties['buyer_id'] && ! $reviewed_by['buyer'] ) {
		return true;
	} elseif ( $reviewer_id == $parties['seller_id'] && ! $reviewed_by['seller'] ) {
		return true;
	}

	return false;
}

add_action( 'wp_insert_post', 'on_order_review_add', 10, 3 );
/**
 * @param $post_id
 * @param $post WP_Post
 * @param $update
 */
function on_order_review_add( $post_id, $post, $update ) {
	global $BP_Member_Reviews;
	if ( $update || $post->post_type != $BP_Member_Reviews->post_type ) {
		return;
	}

	if ( ! isset( $_REQUEST['add-review'] ) ) {
		return;
	}

	$order = get_post( (int) $_REQUEST['add-review'] );
	if ( ! $order || ! $order->post_type == 'order' ) {
		return;
	}

	$reviewer = $post->post_author;
	$reviewee = (int) $_POST['user_id'];

	$seller_id = get_order_seller_id( $order->ID );
	$buyer_id = get_order_buyer_id( $order->ID );

	if ( $seller_id == $reviewee ) {
		order_reviewed_by( $order->ID, array( 'buyer' => $post_id ) );
	} elseif ( $buyer_id == $reviewee ) {
		order_reviewed_by( $order->ID, array( 'seller' => $post_id ) );
	} else {
		return;
	}

	// attach order id to review for further display in review list
	update_post_meta( $post_id, 'order_id', $order->ID );
	if ( $post->post_status != 'publish' ) {
		wp_update_post( array(
			'ID' => $post_id,
			'post_status' => 'publish',
		) );
	}

	// send new review notification
	$reviewer_name = get_userdata( $reviewer )->display_name;
	send_notification_to_user( $reviewee,
		"Новый отзыв на сайте", "Пользователь {$reviewer_name} оставил Вам новый отзыв к заказу #{$order->ID}.\r\n" .
		"Посмотреть отзыв в личном кабинете: " . bp_core_get_user_domain( $reviewee) . "reviews/", true );

}

/**
 * @param $order_id
 * @param array $args
 *              $args['seller']
 *              $args['buyer']
 *
 * @return array
 */
function order_reviewed_by( $order_id, $args = array() ) {
	if ( isset( $args['seller'] ) ) {
		$seller_review_id = (int) $args['seller'];
		update_post_meta( $order_id, '_reviewed_by_seller',  $seller_review_id);
	}

	if ( isset( $args['buyer'] ) ) {
		$buyer_review_id = (int) $args['buyer'];
		update_post_meta( $order_id, '_reviewed_by_buyer', $buyer_review_id );
	}

	return array(
		'seller' => get_post_meta( $order_id, '_reviewed_by_seller', true ),
		'buyer'  => get_post_meta( $order_id, '_reviewed_by_buyer', true )
	);
}



function get_add_order_review_link( $user_id, $order_id ) {
	return bp_core_get_user_domain( $user_id) . "reviews/?add-review=" . $order_id;
}

//add_filter( 'bp_members_review_meta', 'calc_seller_review_meta', 10, 2);
function calc_seller_review_meta( $review_meta, &$response ) {
	$order_id = (int) $_REQUEST['add-review'];

	if ( bp_loggedin_user_id() == get_order_buyer_id( $order_id ) ) {
		$hours   = (int) $_POST['transfer-time-hours'];
		$minutes = (int) $_POST['transfer-time-minutes'];

		// convert hours to minutes
		$mins_in_hours = $hours * 60;
		$total_mins = $minutes + $mins_in_hours;
		if ( ! $total_mins ) {
			$response['result'] = false;
			$response['error'][] = "Время перевода нимов не может быть 0";
		}

//		calc_seller_avg_transfer_time();
	}


	return $review_meta;
}