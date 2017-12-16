<?php

function format_balance( $balance, $show_currency = false ) {
	$balance = number_format( $balance, 2, '.', ' ' );
	if ( $show_currency ) {
		$balance = $balance . ' ₽';
	}

	return $balance;
}

function add_user_balance( $user_id, $topup_amount, $transaction_type = 'in', $comment = null, $method = null ) {
	add_transaction( $user_id, $transaction_type, $topup_amount, $comment, $method );
	set_user_balance( $user_id, get_user_balance( $user_id ) + $topup_amount );
	if ( $transaction_type == 'in' ) {
		$topup_formatted = format_balance( $topup_amount, true );
		send_notification_to_user( $user_id, "Ваш баланс пополнен на {$topup_formatted}",
			"Ваш баланс был успешно пополнен на {$topup_formatted}. Если вы оформляли заказ, вы можете посмотреть его в разделе <a href='" . trailingslashit(
				 bp_core_get_user_domain( $user_id) ). "orders'>Мои заказы</a>", true);
	}
}

function get_user_balance( $user_id, $echo = false ) {
	$balance = get_user_meta( $user_id, '_user_balance', true );
	if ( ! is_numeric( $balance ) ) {
		$balance = 0;
	}

	if ( $echo ) {
		$balance = format_balance( $balance, $echo );
		echo $balance;

		return null;
	}

	return $balance;
}

function set_user_balance( $user_id, $balance ) {
	return update_user_meta( $user_id, '_user_balance', max( 0, $balance ) );
}

function set_user_buffer_balance( $user_id, $balance ) {
	return update_user_meta( $user_id, '_user_buffer_balance', max( 0, $balance ) );
}

function subtract_user_balance( $user_id, $amount,  $transact_type = null, $comment = null, $method = null ) {
	set_user_balance( $user_id, get_user_balance( $user_id ) - $amount );
	if ( $transact_type !== null ) {
		add_transaction( $user_id, $transact_type, $amount, $comment, $method );
	}

}

function get_user_buffer_balance( $user_id ) {
	$balance = get_user_meta( $user_id, '_user_buffer_balance', true );
	if ( ! is_numeric( $balance ) ) {
		$balance = 0;
	}

	return $balance;
}


function add_user_buffer_balance( $user_id, $topup_amount, $comment = null, $method = null) {
	set_user_buffer_balance( $user_id, round( get_user_buffer_balance( $user_id ) + $topup_amount, 2 ) );
	add_transaction( $user_id, 'buffer_in', $topup_amount, $comment, $method );
}

function from_balance_to_buffer( $user_id, $sum, $comment = null, $method = null ) {
	set_user_balance( $user_id, get_user_balance( $user_id ) - $sum );
	add_user_buffer_balance( $user_id, $sum, $comment, $method );
}

function from_buffer_to_balance( $user_id, $sum, $comment = null) {
	set_user_balance( $user_id, get_user_balance( $user_id ) + $sum);
	subtract_from_user_buffer_balance( $user_id, $sum, false );
	add_transaction( $user_id, 'buffer_out', $sum, $comment );
}

function subtract_from_user_buffer_balance( $user_id, $sum, $transaction_and_comment = null ) {
	set_user_buffer_balance( $user_id, round( get_user_buffer_balance( $user_id ) - $sum, 2 ) );

	if ( $transaction_and_comment !== false ) {
		add_transaction( $user_id, 'buffer_out', $sum, $transaction_and_comment );
	}
}

function reward_seller( $order_id, $comment_seller = null, $comment_buyer = null ) {
	$seller_id = get_order_seller_id( $order_id );
	$buyer_id = get_order_buyer_id( $order_id );
	$order_total = get_order_total( $order_id );
	$total_reward = get_order_total_reward( $order_id );
	$order_commission = 100 - get_order_commission( $order_id ) * 100;

	// manage buyer balance
	$comment_buyer = $comment_buyer ? $comment_buyer :
		"Из буфера: заказ #$order_id завершен. Средства были начислены на счёт продавца.";

	subtract_from_user_buffer_balance( $buyer_id, $order_total, $comment_buyer);

	// manager seller balance
	$comment_seller = $comment_seller ? $comment_seller :
		"Из буфера: зачисление вознаграждения за заказ #$order_id";
	from_buffer_to_balance( $seller_id, $total_reward,  $comment_seller);

	$marketplace_commission = $order_total - $total_reward;
	subtract_from_user_buffer_balance( $seller_id, $marketplace_commission, false );
	add_transaction( $seller_id, 'commission', $marketplace_commission, "Комиссия сервиса ($order_commission%) за заказ #$order_id" );
}

function get_withdraw_amount( $user_id ) {
	return get_user_balance( $user_id, false );
}

function min_balance_withdraw() {
	return get_option( 'stock_min_balance', 10 );
}

/**
 * @param null $user_id
 * @param string $user_type
 * @param string|array $status can be array of statues, or specify $status['compare'] for non default compare
 * @param bool $only_count
 * @param null $customArgs
 *
 * @return int|WP_Query
 */
function get_user_orders( $user_id = null, $user_type = 'seller', $status = 'done', $only_count = true, $customArgs = null ) {

	$status_compare = '=';
	if ( isset( $status['compare'] ) ) {
		$status = (array) $status;
		$status_compare = $status['compare'];
		unset ( $status['compare'] );
	} elseif ( is_array( $status ) ) {
		$status_compare = 'IN';
	}

	$args = array(
		'post_type'      => 'order',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'   => '_order_status',
				'value' => $status,
				'compare' => $status_compare
			)
		)
	);

	if ($user_id) {
		if ( $user_type == 'seller' ) {
			$args['meta_query'][] = array(
				'key'   => '_seller_id',
				'value' => $user_id
			);
		} else {
			$args['author'] = $user_id;
		}
	}

	if ( $only_count ) {
		$args['no_found_rows'] = true;
		$args['fields'] = 'ids';
	}

	if ( $customArgs ) {
		$args = wp_parse_args( $args, $customArgs );
	}


	$query = new WP_Query( $args );
	if ($only_count) {
		return $query->post_count;
	}

	return $query;
}

function bp_count_online_users() {
	$i = 0;

	if ( bp_has_members( 'user_id=0&type=online&per_page=9999&populate_extras=0' ) ) :
	while ( bp_members() ) : bp_the_member();
		$i++;
	endwhile;
	endif;

	return $i;
}

function count_sold_nims( $user_id, $format = false ) {
	$done_orders  = get_user_orders( $user_id, 'seller', 'done', false, array( 'fields' => 'ids' ) );
	$total_nims = 0;
	foreach ( $done_orders->posts as $post_id ) {
		$total_nims += (int) get_order_nims_amount( $post_id );
	}

	if ( $format ) {
		return number_format( $total_nims, 0, '.', ' ' );
	}

	return $total_nims;
}

function count_total_revenue( $user_id = null, $user_type = 'seller', $only_reward = true ) {
	$done_orders  = get_user_orders( $user_id, $user_type, 'done', false, array( 'fields' => 'ids' ) );
	$total_user_revenue = 0;
	foreach ( $done_orders->posts as $post_id ) {
		$total_user_revenue += $only_reward ? get_order_total_reward( $post_id ) : get_order_total( $post_id );
	}

	return format_balance( $total_user_revenue, true );
}

function count_transactions_total( $user_id = null, $type, $format = true ) {
	$transactions_total = 0;
	$transactions = get_user_transactions_by_type( $user_id, $type, 'amount' );
	foreach ( $transactions as $transaction ) {
		$transactions_total += $transaction['amount'];
	}

	if ( $format ) {
		return format_balance( $transactions_total, true );
	}

	return $transactions_total;
}

function user_payment_address( $user_id, $new_address = null ) {
	if ( $new_address != null ) {
		return update_user_meta( $user_id, 'payment_yandex', $new_address );
	} else {
		return get_user_meta( $user_id, 'payment_yandex', true );
	}
}

function user_default_nimses_login( $user_id, $default_nimses_login = null ) {
	if ( $default_nimses_login != null ) {
		return update_user_meta( $user_id, 'default_nimses_login', $default_nimses_login );
	} else {
		return get_user_meta( $user_id, 'default_nimses_login', true );
	}
}

function get_stockexchange_rating( $user_id, $format = false ) {
	$stockexchange_rating = (float) get_user_meta( $user_id, '_stockexchange_rating', true );
	if ( $format ) {
		return number_format( $stockexchange_rating, 2, '.', ' ' );
	}

	return $stockexchange_rating;
}

// todo: just another check, remove in the future
add_action('bp_init', function() {
	global $NMS_Shop_Settings;
	$loggedin_user_id = bp_loggedin_user_id();
	if ( $loggedin_user_id && $NMS_Shop_Settings->user_sells_nims( $loggedin_user_id ) && ! stockexchange_rating_ok( $loggedin_user_id ) ) {
		update_user_meta( $loggedin_user_id, 'user_sells_nims', false );
	}
});

function stockexchange_rating_ok( $user_id ) {
	// todo: move to admin settings page
	$rating = get_stockexchange_rating( $user_id );
	$min_rating_allowed = 5;

	return $rating >= $min_rating_allowed;
}

function add_stockexchange_rating( $user_id, $sum = 1 ) {
	$stockexchange_rating = get_stockexchange_rating( $user_id );
	$stockexchange_rating = min( $stockexchange_rating + $sum, 15 );
	update_user_meta( $user_id, '_stockexchange_rating', $stockexchange_rating );
}

function subtract_stockexchange_rating( $user_id, $sum = 2 ) {
	$stockexchange_rating = get_stockexchange_rating( $user_id );
	$stockexchange_rating = max( 0, $stockexchange_rating - $sum );
	update_user_meta( $user_id, '_stockexchange_rating', $stockexchange_rating );
	if ( ! stockexchange_rating_ok( $user_id ) ) {
		update_user_meta( $user_id, 'user_sells_nims', false );
	}
}

/*function withdraw_to_qiwi( $qiwi_wallet ) {
	$user_id        = get_current_user_id();
	$amount         = get_withdraw_amount( $user_id );
	$method_type    = 'qiwi';
	if ( ! preg_match( "/^\+?\d+$/", $qiwi_wallet ) ) {
		bp_core_add_message( 'Пожалуйста, используйте только цифры и + для кошелька', 'error' );
		return false;
	}

	$transaction_id = add_transaction( $user_id, 'withdraw', $amount, $method_type, current_time( 'timestamp' ), $qiwi_wallet, false, "Вывод средств на Qiwi кошелек $qiwi_wallet");

	if ($transaction_id) {
		set_user_balance( $user_id, 0 );
		send_notification_to_user( $user_id, false,
			"Запрос на вывод средств на киви кошелек успешно выполнен. Выплата будет осуществлена в течение 12 часов" );

		bp_core_add_message( 'Запрос на вывод средств на киви кошелек успешно выполнен. Выплата будет осуществлена в течение 12 часов', 'success' );

		NimsEmail::admin_notice( "Запрос на выплату на Qiwi кошелек", "Пользователь запросил вывести $amount руб. на свой Qiwi аккаунт. Детали в транзакции: " . nims_get_post_edit_url($transaction_id));
	}
}

function withdraw_user_balance( $user_id ) {
	$ya_wallet_id    = get_user_meta( $user_id, 'payment_yandex', true );
	$amount          = get_withdraw_amount( $user_id );
	$username        = get_userdata( $user_id )->user_login;
	$payment_message = "Выплата пользователю $username от биржи stockexchange.com";
	$method_type     = 'p2p-outgoing';

	// выплата
	$payment_result = YandexMoneyPayments::$_INSTANCE->pay_to( $ya_wallet_id, $amount, $payment_message, $payment_message );
	if ( ! $payment_result ) {
		return false;
	}

	// обработка результата
	$result_message = 'payment ' . $payment_result->operation_type . ' ' . $payment_result->status;
	switch ( $payment_result->status ) {
		case 'refused':
			$result_message .= ': ' . YandexMoneyPayments::get_error_desc( $payment_result );

			// Кошелек не найден
			if ( $payment_result->error == 'payee_not_found' || $payment_result->error == 'illegal_param_to' ) {
				send_notification_to_user( $user_id, false,
					"Ошибка при выплате баланса: указанный Вами счет Яндекс.Деньги не существует. " .
					"Пожалуйста, обновите номер кошелька в настройках." );
			} else {
				send_notification_to_user( $user_id, false,
					"При выплате произошла ошибка. Пожалуйста, обратитесь в тех. поддержку." );
				$transaction_id = add_transaction( $user_id, 'withdraw', $amount, $method_type, current_time( 'timestamp' ), $ya_wallet_id, false, $result_message );

				NimsEmail::admin_notice(
					"При выплате баланса пользователя произошла ошибка.",
					"Пользователь #$user_id не смог вывести баланс. Сообщение об ошибке: " . $result_message .
					"\r\nСсылка на транзакцию: " . nims_get_post_edit_url( $transaction_id ) );
			}

			break;
		case 'success':
			set_user_balance( $user_id, 0 );
			send_notification_to_user( $user_id, false, "Выплата $amount ₽ пользователю $username на кошелек $ya_wallet_id произведена успешно.", true );
			add_transaction( $user_id, 'withdraw', $amount, $method_type, current_time( 'timestamp' ), $ya_wallet_id );
			break;

		default:
			set_user_balance( $user_id, 0 ); // maybe not set to 0, leave as is
			send_notification_to_user( $user_id, "Запрос на выплату находится в процессе", "Ваш запрос на выплату находится в процессе обработки. Пожалуйста, обратитесь в поддержку для завершения выплаты." );
			$transaction_id = add_transaction( $user_id, 'withdraw', $amount, $method_type, current_time( 'timestamp' ), $ya_wallet_id, false, $payment_result->request_id );

			NimsEmail::admin_notice(
				"При выплате баланса пользователя произошла ошибка.",
				"Пользователь #$user_id не смог вывести баланс. \r\nРезультат: " . $result_message .
				"\r\nYandex Request ID: $payment_result->request_id\r\nСсылка на транзакцию: " . nims_get_post_edit_url( $transaction_id ) );


			break;
	}

	if ( is_admin() ) {
		$notice_status = $payment_result->error ? 'error' : 'updated';
		bp_core_add_admin_notice( $result_message, $notice_status );
	}

	return $payment_result->status;
}


add_action( 'template_redirect', 'withdraw_balance_post_request' );
function withdraw_balance_post_request() {

	if ( ! isset( $_POST['withdraw_user_balance'] ) ) {
		return false;
	}

	if ( is_user_logged_in() && ( $user_id = bp_displayed_user_id() ) ) {
		if ( get_user_unaccepted_withdraw( $user_id ) ) {
			bp_core_add_message( 'У вас есть незаконченный платёж. Пожалуйста, обратитесь в администрацию для завершения платежа.', 'notice' );

			header( "Location: " . get_user_profile_link( $user_id ) );
			exit();
		}

		$amount_to_pay = get_user_balance( $user_id );
		if ( $amount_to_pay < min_balance_withdraw() ) {
			bp_core_add_message( 'Вы не достигли минимальной суммы на балансе для выплаты.', 'error' );

			header( "Location: " . bp_get_requested_url() );
			exit();
		}

		$payment_amount = get_withdraw_amount( $user_id );
		if ( $_POST['withdraw_yandex'] == "true" ) {
			$payment_status = withdraw_user_balance( $user_id );


			if ( $payment_status == 'success' ) {
				bp_core_add_message( 'Выплата на кошелек ' . user_payment_address( $user_id ) . ' в размере ' . $payment_amount . ' руб. прошла успешно!', 'success' );
			} else {
				bp_core_add_message( 'Произошла ошибка.', 'error' );
			}


		} else {
			withdraw_to_qiwi( $_POST['qiwi_wallet'] );
		}

		nims_redirect();
	}
}*/