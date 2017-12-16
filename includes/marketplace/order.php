<?php

$GLOBALS['ORDER_STATUSES'] = array(
	'unconfirmed' => 'Неподтвержден. Ждет оплаты',
	'new'         => 'Оплачен. Ждет передачи нимов покупателю',
	'pending'     => 'Нимы отправлены покупателю. Ждёт подтверждения',
	'done'        => 'Завершен',
	'dispute'     => 'Открыт спор. Ждёт разрешения ситуации',
	'closed'      => 'Закрыт',
	'cancelled'   => 'Отменен'
);

define( 'MIN_NIMS_AMOUNT_ERR', 'MIN_NIMS_AMOUNT_ERR' );
define( 'SELLER_PRICE_NOT_AVAILABLE_ERR', 'SELLER_NOT_AVAILABLE_ERR' );
define( 'NIMS_AMOUNT_NOT_AVAILABLE_ERR', 'NIMS_AMOUNT_NOT_AVAILABLE_ERR' );
define( 'BUYER_REPLY_TIMEOUT', 'buyer_reply_timeout' );

add_action( 'order_status_change', 'on_order_status_change', 10, 3 );
function on_order_status_change( $order_id, $old_status, $new_status ) {
	$action  = $old_status . '_to_' . $new_status;
	$message = "Заказ #" . $order_id . ". \r\n";

	switch ( $action ) {
		case 'unconfirmed_to_new':
			$order = get_order( $order_id );

			global $NMS_Shop_Settings;
			$NMS_Shop_Settings->seller_subtract_nims( $order_id );

			from_balance_to_buffer( $order['buyer_id'], $order['total'],
				"В буфер: оплата заказа #{$order_id}" );
			add_user_buffer_balance( $order['seller_id'], $order['total'],
				"В буфер: покупатель внёс оплату за заказ #{$order_id}" );

			stockexchangeBot::getInstance()->order_status_change(
				$order_id, 'new', "Покупатель оплатил заказ и ожидает передачи нимов." );


			// send notification to seller with order
			$subject     = "У вас новый заказ #$order_id. Заказ оплачен и ждёт передачи нимов";
			$deadline    = get_order_deadline( $order_id );
			$deadline    = $deadline['final_date'];
			$order_price = format_balance( $order['price'], true );
			$order_total = format_balance( $order['total'], true );
			$nims_amount = number_format( $order['nims_amount'], 0, null, ' ' );
			$message     =
				"У вас новый заказ на нимы. Детали заказа #$order_id:\r\n" .
				"Время заказа: {$order['datetime']}
Количество нимов: {$nims_amount}
Цена: {$order_price}
Всего: {$order_total}
Логин для передачи: {$order['nimses_login']}
Дополнительная информация для продавца: {$order['description']}
Срок передачи заказа: до {$deadline->format('d.m.Y H:i')}\r\n" .
				"Нимы в размере $nims_amount будут удержаны для продажи с вашего магазина до момента завершения заказа. \r\n\r\nПосмотреть заказ: " . get_the_permalink( $order_id );

			send_notification_to_user( $order['seller_id'], $subject, $message, true );


			break;
		case 'new_to_pending':
			stockexchangeBot::getInstance()->order_status_change(
				$order_id, $new_status, "Продавец подтвердил перевод нимов покупателю." );

			$message .= "Продавец перевел Вам нимы. Пожалуйста, проверьте нимы и обязательно подтвердите передачу нимов на странице заказа: " . get_the_permalink( $order_id );
			send_notification_to_user( get_order_buyer_id( $order_id ),
				"Заказ #$order_id. Продавец перевел Вам нимы. Требуется подтверждение.", $message, true );
			break;
		case 'pending_to_done':

			$seller_id           = get_order_seller_id( $order_id );
			$buyer_id            = get_order_buyer_id( $order_id );
			$buyer_reply_timeout = order_closed_reason( $order_id ) == BUYER_REPLY_TIMEOUT;

			// topup seller balance on success
			reward_seller( $order_id );
			add_stockexchange_rating( $seller_id );

			$bot_msg = $buyer_reply_timeout ?
				"Время ожидания ответа покупателя о получении нимов истекло. Заказ автоматически завершен." :
				"Покупатель подтвердил передачу нимов. <br> Вознаграждение за заказ зачислено на счёт продавца.";
			stockexchangeBot::getInstance()->order_status_change( $order_id, 'done', $bot_msg );

			$reward = format_balance( get_order_total_reward( $order_id ), true );

			$subject = 'Заказ #' . $order_id . ' успешно завершен.';
			$message = 'Покупатель подтвердил перевод нимов. Заказ успешно завершен. ';

			if ( $buyer_reply_timeout ) {
				$message = 'Время ожидания ответа от покупателя истекло. Заказ автоматически завершен. ';

			}

			$message .= "\r\nВознаграждение в размере {$reward} зачислено на Ваш баланс.\r\n" .
			            "Вы можете оставить свой отзыв о покупателе здесь: " .
			            get_add_order_review_link( $buyer_id, $order_id );


			send_notification_to_user( $seller_id, $subject, $message, true );

			// ask buyer to leave review
			$review_seller_link = get_add_order_review_link( $seller_id, $order_id );
			$buyer_msg          = $buyer_subject = "Заказ #$order_id успешно завершен. ";

			if ( $buyer_reply_timeout ) {
				$buyer_subject = "Заказ #$order_id автоматически завершен.";
				$buyer_msg     = "Время ожидания Вашего ответа о получении нимов истекло. " .
				                 "Заказ #$order_id автоматически завершен.";
			}
			$buyer_msg .= "\r\nПожалуйста, оставьте свой отзыв о продавце: {$review_seller_link}";

			send_notification_to_user( $buyer_id, $buyer_subject, $buyer_msg, true );

			break;

		case 'pending_to_dispute':
			$order_permalink = get_the_permalink( $order_id );

			stockexchangeBot::getInstance()->order_status_change(
				$order_id, 'dispute', "Покупатель не подтвердил передачу нимов и открыл спор." );

			NimsEmail::admin_notice( "Заказ $order_id. Открыт спор", "Покупатель не подтвердил передачу нимов и открыл спор.\r\nСсылка на заказ: " . $order_permalink );

			send_notification_to_user( get_order_seller_id( $order_id ),
				"Заказ #$order_id. Покупатель не подтвердил передачу нимов и открыл спор.",
				"Покупатель не подтвердил передачу нимов и открыл спор. В ближайшее время администрация свяжется с участниками для разрешения спора. Ссылка на заказ: " . $order_permalink,
				true );
			break;

		case 'new_to_closed':
			$order_total = get_order_total( $order_id );
			$buyer_id    = get_order_buyer_id( $order_id );
			$seller_id   = get_order_seller_id( $order_id );

			// user closed order (garant time expired)
			if ( isset( $_POST['buyer-cancel-order'] ) ) {
				global $NMS_Shop_Settings;

				from_buffer_to_balance( $buyer_id, $order_total, "Из буфера: отмена заказа #" . $order_id );
				subtract_from_user_buffer_balance( $seller_id, $order_total, "Из буфера: отмена заказа." );
				$NMS_Shop_Settings->seller_return_nims( $order_id );

				$order_status_change_msg = "Покупатель отменил заказ. Истекло время гаранта передачи нимов.";
				$notification_msg = "Покупатель отменил заказ #$order_id. Истекло время гаранта передачи нимов.";
				$subject = "Покупатель отменил заказ #$order_id";
				send_notification_to_user( $seller_id, $subject, $notification_msg, true);

				stockexchangeBot::getInstance()->add_user_review( $buyer_id, $seller_id, $order_id,
					"Продавец не перевел нимы в обещанное время гаранта. Покупатель отменил сделку.", 2 );
				stockexchangeBot::getInstance()->order_status_change( $order_id, 'closed', $order_status_change_msg );

				if ( get_order_transfer_time( $order_id ) > 30) {
					subtract_stockexchange_rating( $seller_id, 2 );
				} else {
					subtract_stockexchange_rating( $seller_id, 1 );
				}

			// seller closed order
			} else {

				// deal with balance
				$refund_message = "Из буфера: возврат средств на счёт покупателя за заказ #$order_id";
				from_buffer_to_balance( $buyer_id, $order_total, $refund_message );
				subtract_from_user_buffer_balance( get_order_seller_id( $order_id ), $order_total, $refund_message );

				$closed_reason = esc_html( order_closed_reason( $order_id ) );
				$message       = 'Продавец отказался от сделки. <br>';
				$message       .= 'Причина: ' . $closed_reason . '. ';
				$message       .= 'Рейтинг продавца понижен.<br>';
				$message       .= 'Сумма заказа возвращена на аккаунт покупателя.';


				send_notification_to_user( $buyer_id, "Продавец отказался от сделки #$order_id", br2nl( $message ), true );
				stockexchangeBot::getInstance()->order_status_change( $order_id, 'closed', $message );
				stockexchangeBot::getInstance()->add_user_review( $buyer_id, $seller_id, $order_id,
					"Продавец отказался от сделки. Причина: " . $closed_reason . ".", 2 );
				subtract_stockexchange_rating( $seller_id );
			}
			break;

		case 'new_to_cancelled':
			global $NMS_Shop_Settings;
			$order_total = get_order_total( $order_id );
			$buyer_id    = get_order_buyer_id( $order_id );
			$seller_id   = get_order_seller_id( $order_id );


			from_buffer_to_balance( $buyer_id, $order_total, "Из буфера: отмена заказа #" . $order_id );
			subtract_from_user_buffer_balance( $seller_id, $order_total, "Из буфера: отмена заказа." );
			$NMS_Shop_Settings->seller_return_nims( $order_id );


			$order_status_change_msg = 'Администрация отменила заказ. Причина: см. детали заказа.';
			$notification_msg = "Администрация отменила заказ #$order_id. Причина: см. детали заказа";
			$subject = "Администрация отменила заказ #$order_id.";
			send_notification_to_user( $seller_id, $subject, $notification_msg, true);
			send_notification_to_user( $buyer_id, $subject, $notification_msg, true);

			stockexchangeBot::getInstance()->order_status_change( $order_id, 'cancelled', $order_status_change_msg );

			break;
		// when seller wins or order succeeds
		case 'dispute_to_done':
			global $win_party, $win_party_lbl;

			if ( ! $win_party ) {
				stockexchangeBot::getInstance()->order_status_change( $order_id, 'done',
					'Арбитраж завершил заказ (спор решился обоюдно).' );

				reward_seller( $order_id );
				$dispute_done_msg = "Заказ #{$order_id} был завершен.";

				send_notification_to_user( get_order_buyer_id( $order_id ), false, $dispute_done_msg );
				send_notification_to_user( get_order_seller_id( $order_id ), false, $dispute_done_msg );

			} else {
				stockexchangeBot::getInstance()->order_status_change( $order_id, 'done',
					'Арбитраж закрыл заказ в пользу ' . $win_party_lbl . '.' );

				$seller_id = get_order_seller_id( $order_id );
				$buyer_id = get_order_buyer_id( $order_id );

				stockexchangeBot::getInstance()->add_user_review($seller_id, $buyer_id, $order_id,
					"Арбитраж: покупатель открыл спор, но не выполнил условия сделки. Заказ закрыт в пользу продавца.", 1 );
			}

			add_stockexchange_rating( get_order_seller_id( $order_id ) );

			break;

		// when buyer wins
		case 'dispute_to_closed':
			global $NMS_Shop_Settings, $win_party, $win_party_lbl;
			if ( $win_party == 'buyer' ) {
				$NMS_Shop_Settings->seller_return_nims( $order_id );
				stockexchangeBot::getInstance()->add_user_review( get_order_buyer_id($order_id), get_order_seller_id( $order_id ), $order_id, "Арбитраж: продавец не выполнил условия сделки. Заказ закрыт в пользу покупателя.", 2 );
			}

			stockexchangeBot::getInstance()->order_status_change( $order_id, 'closed',
				'Арбитраж закрыл заказ в пользу ' . $win_party_lbl . '.' );

			subtract_stockexchange_rating( get_order_seller_id( $order_id ) );
			break;
	}
}

add_action( 'init', 'order_cpt_init' );
function order_cpt_init() {

	$order_labels = array(
		'name'               => 'Заказы',
		'singular_name'      => 'Заказ',
		'menu_name'          => 'Заказы',
		'name_admin_bar'     => 'Заказ',
		'add_new'            => 'Добавить новый',
		'add_new_item'       => 'Добавить новый заказ',
		'new_item'           => 'Новый заказ',
		'edit_item'          => 'Редактировать заказ',
		'view_item'          => 'Посмотреть заказ',
		'all_items'          => 'Все заказы',
		'search_items'       => 'Искать заказы',
		'not_found'          => 'Заказы не найдены',
		'not_found_in_trash' => 'Заказы не найдены в корзине',
	);

	$order_args = array(
		'labels'              => $order_labels,
		'public'              => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 20,
		'menu_icon'           => 'dashicons-products',
		'query_var'           => '_order',
		'rewrite'             => array( 'slug' => 'order' ),
		'capability_type'     => 'post',
		'has_archive'         => false,
		'hierarchical'        => false,
		'supports'            => array( 'title', 'author', 'custom-fields', 'comments' )
	);

	register_post_type( 'order', $order_args );
}

add_filter( 'rwmb_meta_boxes', 'order_metabox_init' );
function order_metabox_init( $meta_boxes ) {
	global $ORDER_STATUSES, $BP_Member_Reviews;
	$prefix       = '_';
	$meta_boxes[] = array(
		'id'         => '',
		'title'      => 'Данные заказа',
		'post_types' => array( 'order' ),
		'context'    => 'side',
		'priority'   => 'high',
		'autosave'   => false,
		'fields'     => array(
			array(
				'id'          => $prefix . 'order_status',
				'name'        => 'Статус заказа',
				'type'        => 'select',
				'placeholder' => 'Выберите статус',
				'options'     => $ORDER_STATUSES,
				'std'         => 'unconfirmed',
			),
			array(
				'id'         => $prefix . 'seller_id',
				'type'       => 'user',
				'name'       => 'Продавец',
				'field_type' => 'select'
			),
			array(
				'id'   => $prefix . 'nims_amount',
				'type' => 'text',
				'name' => 'Количество нимов'
			),
			array(
				'id'   => $prefix . 'price',
				'type' => 'text',
				'name' => 'Цена'
			),
			array(
				'id'   => $prefix . 'total',
				'type' => 'text',
				'name' => 'Сумма заказа'
			),
			array(
				'id'   => $prefix . 'commission',
				'type' => 'text',
				'name' => 'Комиссия сервиса'
			),
			array(
				'id'   => $prefix . 'order_description',
				'type' => 'textarea',
				'name' => 'Дополнительная информация по заказу'
			),
			array(
				'id'   => $prefix . 'nimses_login',
				'type' => 'text',
				'name' => 'Логин в NIMSES для перевода нимов'
			),
			array(
				'id'   => $prefix . 'order_transfer_time',
				'type' => 'text',
				'name' => 'Гарант времени передачи нимов (минуты)'
			),
			array(
				'id'   => $prefix . 'order_closed_reason',
				'type' => 'text',
				'name' => 'Причина отказа продавца'
			),
			array(
				'id'   => $prefix . 'reviewed_by_seller',
				'name' => 'Отзыв продавца',
				'type' => 'text',
			),
			array(
				'id'   => $prefix . 'reviewed_by_buyer',
				'name' => 'Отзыв покупателя',
				'type' => 'text',
			),
		),
	);

	return $meta_boxes;
}

function add_order( $seller_id, $nims_amount ) {
	global $NMS_Shop_Settings;

	$buyer_id = get_current_user_id();
	if ( ! $buyer_id ) {
		return false;
	}

	$seller_id = (int) $seller_id;

	if ( ! $NMS_Shop_Settings->user_sells_nims( $seller_id ) ) {
		return false;
	}

	if ( $buyer_id === $seller_id ) {
		return false;
	}

	if ( $nims_amount < $NMS_Shop_Settings->get_min_nims_amount() ) {
		return MIN_NIMS_AMOUNT_ERR;
	}

	if ( $nims_amount > $NMS_Shop_Settings->get_user_sell_nim_amount( $seller_id ) ) {
		return NIMS_AMOUNT_NOT_AVAILABLE_ERR;
	}

	$price = $NMS_Shop_Settings->get_user_shop_nim_price_for_amount( $seller_id, $nims_amount );
	if ( ! $price ) {
		return SELLER_PRICE_NOT_AVAILABLE_ERR;
	}

	$total = round( $nims_amount * $price / 1000 );

	$order_post_args = array(
		'post_title'  => '',
		'post_status' => 'pending',
		'post_type'   => 'order',
		'post_author' => $buyer_id,
		'meta_input'  => array(
			'_order_status'        => 'unconfirmed',
			'_order_transfer_time' => $NMS_Shop_Settings->get_user_transfer_time( $seller_id ),
			'_seller_id'           => $seller_id,
			'_nims_amount'         => $nims_amount,
			'_price'               => $price,
			'_total'               => $total,
			'_commission'          => get_marketplace_commission()
		)
	);

	$order_id = wp_insert_post( $order_post_args );
	if ( is_wp_error( $order_id ) ) {
		return false;
	}

	$post_title       = "Заказ #$order_id";
	$update_post_args = array(
		'ID'          => $order_id,
		'post_title'  => $post_title,
		'post_status' => 'publish'
	);

	wp_update_post( $update_post_args );

	return $order_id;
}

function get_order( $order_id ) {
	return array(
		'order_id'     => $order_id,
		'datetime'     => get_the_date( 'd.m.Y H:i', $order_id ),
		'buyer_id'     => get_order_buyer_id( $order_id ),
		'seller_id'    => get_order_seller_id( $order_id ),
		'nims_amount'  => get_order_nims_amount( $order_id ),
		'price'        => get_order_price( $order_id ),
		'total'        => get_order_total( $order_id ),
		'nimses_login' => get_order_nimses_login( $order_id ),
		'description'  => get_order_description( $order_id ),
		'commission'   => get_order_commission( $order_id )
	);
}

function get_orders( $custom_args = array() ) {

	$args = array(
		'post_type'      => 'order',
		'post_status'    => 'publish',
		'posts_per_page' => - 1
	);

	if ( ! empty( $custom_args ) ) {
		$args = array_merge( $args, $custom_args );
	}

	return new WP_Query( $args );
}

function get_order_transfer_time( $order_id ) {
	$transfer_time =  get_post_meta( $order_id, '_order_transfer_time', true );
	if ( ! $transfer_time ) {
		$transfer_time = NMS_Shop_Settings::DEFAULT_MAX_TRANSFER_TIME;
	}

	return $transfer_time;
}

function get_order_deadline( $order_id, $time_for_reply = false, DateInterval $deadlineInterval = null ) {
	global $NMS_Shop_Settings;
	$timezone = new DateTimeZone( 'Europe/Moscow' );
	if ( ! $deadlineInterval ) {
		$deadlineInterval = new DateInterval( "PT" . $NMS_Shop_Settings::DEFAULT_MAX_TRANSFER_HOURS . "H" );
	}

	if ( $time_for_reply ) {
		$date = get_order_status_date( $order_id, 'pending' );
	} else {
		$date = get_the_date( 'Y-m-d H:i', $order_id );
	}

	$final_datetime = new DateTime( $date, $timezone );
	$final_datetime->add( $deadlineInterval );

	$diff      = ( new DateTime( current_time( 'mysql' ), $timezone ) )->diff( $final_datetime, false );
	$time_diff = array(
		'final_date' => $final_datetime,
		'diff_obj'   => $diff,
		'diff_lbl'   => sprintf( '%01d', $diff->h + ( $diff->days * 24 ) ) . " ч. " .
		                sprintf( '%01d', $diff->i ) . " мин."
	);

	return $time_diff;
}

function get_order_deadline_from_minutes($order_id, $time_for_reply = false, $minutes) {
	global $NMS_Shop_Settings;
	$hours_minutes = $NMS_Shop_Settings->transfer_minutes_to_hours_minutes( $minutes );

	return get_order_deadline( $order_id, $time_for_reply,
		new DateInterval( "PT" . $hours_minutes['h'] . "H" . $hours_minutes['m'] . "M" ) );
}

function get_orders_done( $period, $select = '', $join = '', $where = '' ) {
	global $wpdb;
	$date_query = ' AND DATE(`c`.comment_date) ';
	switch ( $period ) {
		case 'today':
			$today      = date( 'Y-m-d', strtotime( 'today midnight' ) );
			$date_query .= " = '{$today}'";
			break;
		case 'week':
			$week       = date( 'Y-m-d', strtotime( 'last monday', strtotime( 'tomorrow' ) ) );
			$date_query .= " >= '{$week}'";
			break;

		case 'month':
			$month      = date( 'Y-m-01', current_time( 'timestamp' ) );
			$date_query .= " >= '{$month}'";
			break;

		default:
			if ( is_array( $period ) && isset( $period['start'] ) && isset( $period['end'] ) ) {
				$start      = esc_sql( $period['start'] );
				$end        = esc_sql( $period['end'] );
				$date_query .= " BETWEEN '{$start}' AND '{$end}'";
			} else {
				$date_query = '';
			}
	}


	$select = $select ? $select : "count(DISTINCT p.ID) orders_done ";
	$join   = $join ? $join : '';
	$where  = $where ? $where : '';

	$query =
		"SELECT {$select} FROM {$wpdb->prefix}posts p 
	{$join}
	INNER JOIN {$wpdb->prefix}comments c ON c.comment_post_ID = p.ID
	INNER JOIN {$wpdb->prefix}commentmeta cm ON c.comment_ID = cm.comment_id
WHERE 
	cm.meta_key = 'order_status' AND cm.meta_value = 'done' AND c.comment_approved = 1
	{$date_query}
	{$where}";

	return $wpdb->get_var( $query );
}

function get_total_nims_sold( $period = '' ) {
	global $wpdb;

	return get_orders_done( $period,
		' SUM(`pm`.meta_value) nim_count ',
		" INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id ",
		" AND pm.meta_key = '_nims_amount' " );
}

function get_order_status_date( $order_id, $status ) {
	$comments_query = new WP_Comment_Query;
	$status_comment = $comments_query->query( array(
		'post_id'    => $order_id,
		'user_id'    => stockexchangeBot::getInstance()->bot()->ID,
		'meta_key'   => 'order_status',
		'meta_value' => $status
	) );

	if ( ! empty( $status_comment ) ) {
		$status_comment = current( $status_comment );

		return $status_comment->comment_date;
	}

	return false;
}


function get_order_seller_id( $order_id ) {
	return (int) get_post_meta( $order_id, '_seller_id', true );
}

function get_order_buyer_id( $order_id ) {
	return (int) get_post_field( 'post_author', $order_id );
}

function get_order_status( $order_id ) {
	return get_post_meta( $order_id, '_order_status', true );
}

function get_order_nims_amount( $order_id, $format = false ) {
	$nims_amount = get_post_meta( $order_id, '_nims_amount', true );
	if ( $format && $nims_amount ) {
		$nims_amount = number_format( $nims_amount, 0, null, ' ' );
	}

	return $nims_amount;
}

function get_order_price( $order_id ) {
	return get_post_meta( $order_id, '_price', true );
}

function get_order_total( $order_id ) {
	return get_post_meta( $order_id, '_total', true );
}

function get_order_total_reward( $order_id ) {
	return round( get_order_total( $order_id ) * get_order_commission( $order_id ), 2 );
}

function get_order_nimses_login( $order_id ) {
	return get_post_meta( $order_id, '_nimses_login', true );
}

function get_order_description( $order_id ) {
	return get_post_meta( $order_id, '_order_description', true );
}

function get_order_commission( $order_id ) {
	return get_post_meta( $order_id, '_commission', true );
}

function get_order_human_date( $order_id ) {
	$order_date = get_the_date( 'Y-m-d H:i:s', $order_id );
	$today      = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
	$days       = dateDifference( $order_date, $today );

	if ( $days == 0 ) {
		$order_date = human_time_diff( get_the_date( 'U', $order_id ),
				current_time( 'timestamp' ) ) . " назад";
	} else {
		$order_date = get_the_time( 'd.m.y G:i', $order_id );
	}

	return $order_date;
}

function order_closed_reason( $order_id, $closed_reason = null ) {
	if ( $closed_reason ) {
		return update_post_meta( $order_id, '_order_closed_reason', $closed_reason );
	}

	return get_post_meta( $order_id, '_order_closed_reason', true );
}

function set_order_nimses_login( $order_id, $nimses_login ) {
	return update_post_meta( $order_id, '_nimses_login', $nimses_login );
}

function set_order_description( $order_id, $order_description ) {
	return update_post_meta( $order_id, '_order_description', $order_description );
}

function set_order_status( $order_id, $order_status ) {
	if ( in_array( $order_status, array_keys( $GLOBALS['ORDER_STATUSES'] ) ) ) {
		return update_post_meta( $order_id, '_order_status', $order_status );
	}

	return false;
}

/*
 * Uncomment in case "Order reservation feature"
 *

function user_has_lot( $user_id, $lot_id ) {
	return user_total_lots( $user_id, true, array(
			'post__in' => array( $lot_id )
		) )->post_count > 0;
}
function lot_purchase_time_left( $purchase_id ) {
	$lot_max_pay_time  = get_option( 'lot_max_pay_time' );
	$purchase_date     = get_post_field( 'post_date', $purchase_id );
	$payment_time_left = $lot_max_pay_time - ( current_time( 'timestamp' ) - strtotime( $purchase_date ) ) / 60;

	$cancelled = false;
	$mins      = sprintf( "%02d", $payment_time_left );
	$secs      = sprintf( "%02d", ( $payment_time_left - floor( $payment_time_left ) ) * 60 );

	if ( $mins < 0 ) {
		$mins      = $secs = "00";
		$cancelled = true;
	}

	return array(
		'mins'      => $mins,
		'secs'      => $secs,
		'cancelled' => $cancelled
	);
}

function user_already_has_pending_purchase( $user_id ) {
	$args = array(
		'author'         => $user_id,
		'post_type'      => 'lot_purchase',
		'no_found_rows'  => true,
		'posts_per_page' => - 1,
		'meta_key'       => '_lot_purchase_status_select',
		'meta_value'     => 'new'
	);

	$posts = new WP_Query( $args );

	return $posts->post_count > 0;
}*/

add_action( 'update_post_meta', 'nims_on_update_order_status', 10, 4 );
function nims_on_update_order_status( $meta_id, $post_id, $meta_key, $_meta_value ) {
	if ( $meta_key != '_order_status' ) {
		return;
	}

	$prev_value = get_order_status( $post_id );
	add_action( 'updated_post_meta', $updated_post_meta_callback = function ( $meta_id, $object_id, $meta_key, $new_meta_value ) use ( $prev_value, &$updated_post_meta_callback ) {

		do_action( 'order_status_change', $object_id, $prev_value, $new_meta_value );


		/*		do_action( 'order_status_change_from_' . $prev_value . '_to_' . $new_meta_value,
					$object_id, $prev_value, $new_meta_value );

				do_action( 'order_status_change_to_' . $new_meta_value, $object_id, $new_meta_value );*/

		remove_action( 'updated_post_meta', $updated_post_meta_callback, 10, 4 );
	}, 10, 4 );
}

add_action( 'template_redirect', 'restrict_order_access' );
function restrict_order_access() {
	if ( ! is_singular( 'order' ) ) {
		return;
	}

	$current_user_id = get_current_user_id();
	if ( ! $current_user_id ) {
		wp_redirect( home_url() );
		exit();
	}

	// allow administrators to view orders
	if ( is_super_admin() ) {
		return;
	}

	$order         = get_post();
	$allowed_users = array( $order->post_author );
	if ( get_order_status( $order->ID ) != 'unconfirmed' ) {
		$allowed_users[] = get_order_seller_id( $order->ID );
	}

	if ( ! in_array( $current_user_id, $allowed_users ) ) {
		wp_redirect( home_url() );
		exit();
	}
}

add_filter( 'woo_title', 'modify_order_unconfirmed_title', 50, 3 );
function modify_order_unconfirmed_title( $title, $sep, $raw_title ) {
	if ( ! is_singular( 'order' ) ) {
		return $title;
	}

	global $post;

	if ( get_order_status( $post->ID ) == 'unconfirmed' ) {
		$title = str_replace( "Заказ #", "Подтверждение заказа #", $title );
	}

	return $title;
}

add_filter( 'comments_open', 'close_comments_order_cancelled', 10, 2 );
function close_comments_order_cancelled( $open, $post_id ) {
	if ( get_post_type( $post_id ) !== 'order' ) {
		return $open;
	}

	$order_status = get_order_status( $post_id );

	if ( in_array( $order_status, array( 'unconfirmed', 'done', 'cancelled', 'closed' ) ) ) {
		$open = false;
	}

	return $open;
}

// remove nickname class in comments for anonymity
add_filter( /**
 * @param $classes array
 * @param $class
 * @param $comment_id
 * @param $comment WP_Comment
 * @param $post_id
 *
 * @return mixed
 */
	'comment_class', function ( $classes, $class, $comment_id, $comment, $post_id ) {
	if ( get_post_type( $post_id ) ) {
		$tmp_classes = $classes;
		foreach ( $tmp_classes as $key => $comment_class ) {
			if ( in_array( $comment_class, array( 'odd', 'even', 'thread-odd', 'thread-even', 'bypostauthor' ) ) ) {
				unset( $classes[ $key ] );
			}
			if ( preg_match( '/comment-author-/', $comment_class ) ) {
				unset( $classes[ $key ] );
			}
		}
	}

	if ( stockexchangeBot::getInstance()->is_bot( $comment->user_id ) ) {
		$classes[] = 'bypostauthor';
	}

	return $classes;
}, 10, 5 );


add_action( 'wp_insert_comment', 'order_comments_insert_action', 10, 2 );
/**
 * @param $comment_id int
 * @param $comment_object WP_Comment
 */
function order_comments_insert_action( $comment_id, $comment_object ) {
	$post_type = get_post_field( 'post_type', $comment_object->comment_post_ID );
	if ( ! $post_type || $post_type != 'order' ) {
		return;
	}

	$comment                         = array();
	$comment['comment_ID']           = $comment_id;
	$comment['comment_type']         = 'order_comment';
	$comment['comment_author_email'] = strtolower( $comment_object->comment_author_email );
	$seller_id                       = get_order_seller_id( $comment_object->comment_post_ID );
	$buyer_id                        = get_order_buyer_id( $comment_object->comment_post_ID );
	$commentator_super_admin         = is_super_admin( $comment_object->user_id );

	$is_commentator_seller = $seller_id == $comment_object->user_id;
	$is_commentator_buyer  = $buyer_id == $comment_object->user_id;

	if ( ! $comment_object->comment_approved ) {
		if ( ! $is_commentator_seller && ! $is_commentator_buyer && ! $commentator_super_admin ) {
			return;
		}

		$comment['comment_approved'] = 1;
	}

	wp_update_comment( $comment );

	// send email notifications
	if ( $comment_object->user_id == stockexchangeBot::getInstance()->bot()->ID ) {
		return;
	}

	$order_id        = $comment_object->comment_post_ID;
	$comment_link    = get_comment_link( $comment_object );
	$comment_excerpt = get_comment_excerpt( $comment_object );

	$subject = "В заказе #$order_id пользователь %s оставил новый комментарий.";
	$message =
		"В заказе #$order_id пользователь %s оставил новый комментарий:
{$comment_excerpt}

Посмотреть комментарий полностью можно на странице заказа: {$comment_link}";

	$order_status  = get_order_status( $order_id );
	$recipient_ids = array();
	if ( $commentator_super_admin ) {
		$recipient_ids = [ $seller_id, $buyer_id ];

	} else {
		$recipient_ids[] = $is_commentator_seller ? $buyer_id : $seller_id;

		if ( $order_status == 'dispute' ) {
			$recipient_ids[] = 1; // todo: user get_super_admins();
		}
	}

	$commentator_display_name = get_userdata( $comment_object->user_id )->display_name;
	$subject                  = sprintf( $subject, $commentator_display_name );
	$message                  = sprintf( $message, $commentator_display_name );

	foreach ( $recipient_ids as $recipient_id ) {
		$tmp_subject = $subject;

		if ( is_super_admin( $recipient_id ) && $commentator_super_admin ) {
			continue;
		}

		if ( is_super_admin( $recipient_id ) && $order_status == 'dispute' ) {

			$subject = " [СПОР] " . $subject;
		}

		send_notification_to_user( $recipient_id, $subject, $message, true );
		$subject = $tmp_subject;
	}
}

add_filter( 'wp_update_comment_data', 'allow_bot_br_tags', 10, 3 );
function allow_bot_br_tags( $data, $comment, $commentarr ) {
	if ( $comment['user_id'] != stockexchangeBot::getInstance()->bot()->ID ) {
		return $data;
	}

	$data['comment_content'] = $comment['comment_content'];

	return $data;
}

add_action( 'template_redirect', 'order_actions' );
function order_actions() {
	global $post;

	if ( ! isset( $_POST['user-order-panel'] ) ||  ! isset( $_POST['user-panel-nonce'] ) ) {
		return;
	}

	$user_id = get_current_user_id();
	if ( ! wp_verify_nonce( $_POST['user-panel-nonce'], 'oup_action_' . $user_id . 'nonce' ) ) {
		die( 'Security check' );
	}

	if ( ! $post || $post->post_type != 'order' || $post->post_status != 'publish' || ! $user_id ) {
		return;
	}

	$order_id     = $post->ID;
	$order_status = get_order_status( $order_id );

	$seller_id = get_order_seller_id( $order_id );
	$buyer_id  = get_order_buyer_id( $order_id );


	switch ( $order_status ) {
		case 'new':

			if ( isset( $_POST['buyer-cancel-order'] ) && $user_id == $buyer_id ) {
				$order_deadline = get_order_deadline_from_minutes( $order_id, false, get_order_transfer_time( $order_id) );

				// check if over deadline
				if ( $order_deadline['diff_obj']->invert == 1 ) {

					order_closed_reason( $order_id,  "Время гаранта передачи нимов истекло" );
					set_order_status( $order_id, 'closed' );
					bp_core_add_message( "Вы отменили заказ. Рейтинг продавца понижен. Сумма заказа возвращена на баланс.",
						"notice" );
				}

				nims_redirect();

			} elseif ( isset( $_POST['transfer-complete'] ) && $_POST['transfer-complete'] === 'yes' && $user_id == $seller_id  ) {
				set_order_status( $order_id, 'pending' );
				bp_core_add_message( "Вы подтвердили передачу нимов покупателю. В течение 24 часов покупатель должен проверить и подтвердить передачу нимов", "success" );
				nims_redirect();

				// seller didn't transfer nims
			} elseif ( isset( $_POST['no-transfer-reason-submit'] ) && $user_id == $seller_id ) {

				$reason = $_POST['no_transfer_reason'];

				switch ( $reason ) {
					case 'out-of-stock':
					case 'account-banned':
						$closed_reason =
							$reason == 'out-of-stock' ? "Закончились нимы" : "Аккаунт продавца забанен";

						order_closed_reason( $order_id, $closed_reason );
						set_order_status( $order_id, 'closed' );
						bp_core_add_message( "Вы закрыли заказ. Ваш рейтинг stockexchange продавца понижен, " .
						                     "а в личном профиле автоматически оставлен отзыв.", "notice" );

						nims_redirect();
						break;

					case 'more-details-required':
						$closed_reason = order_closed_reason( $order_id );
						if ( $closed_reason == "more-details-required" ) {
							nims_redirect();
						}

						order_closed_reason( $order_id, "more-details-required" );
						NimsEmail::admin_notice( "Продавец запросил у покупателя доп. информацию #$order_id",
							"Открыть заказ #{$order_id}: " . get_the_permalink( $order_id ) );

						send_notification_to_user( $buyer_id,
							"Продавец запросил у Вас дополнительную информацию для перевода нимов. Заказ #$order_id",
							"Продавец Вашего заказа #{$order_id} запросил дополнительую информацию для перевода нимов. " .
							"Посмотреть запрос в комментариях к заказу: " . get_the_permalink( $order_id ) . '#comments', false );

						bp_core_add_message( "Администратор и продавец были оповещены о запросе.", "notice" );
						stockexchangeBot::getInstance()->add_order_message( $order_id,
							"Продавец запросил дополнительную информацию о передаче нимов от покупателя" );

						nims_redirect();
						break;
					default:
						bp_core_add_message( "Выберите одну из причин отказа перевода нимов" );
						nims_redirect();
				}
			}

			break;
		case 'pending':
			if ( $user_id != $buyer_id || ! isset( $_POST['transfer-received'] ) ) {
				nims_redirect();
			}

			if ( $_POST['transfer-received'] === 'yes' ) {
				set_order_status( $order_id, 'done' );

				$link = "<a href='" . get_add_order_review_link( $seller_id, $order_id ) . "'>" .
				        "оставьте свой отзыв о продавце</a>";
				bp_core_add_message( "Вы подтвердили передачу нимов. Заказ завершен. " .
				                     "Пожалуйста, <b>{$link}</b>.", 'success' );
			} elseif ( $_POST['transfer-received'] === 'no' ) {
				set_order_status( $order_id, 'dispute' );
				bp_core_add_message( "Вы успешно открыли спор." .
				                     " Скоро с Вами свяжется администратор для уточнения деталей.", 'success' );
			}

			nims_redirect();

			break;
		case 'dispute':
			// todo: prob make adjustable sum to parties depending on nims transfer amount
			if ( ! is_super_admin( $user_id ) || ! isset( $_POST['admin-order-panel'] ) ) {
				nims_redirect();
			}

			$win_party = $_POST['party-wins'];
			if ( ! $win_party || ! in_array( $win_party, array( 'buyer', 'seller', 'order-success' ) ) ) {
				bp_core_add_message( "Пожалуйста, выберите сторону, которая выиграла", 'error' );
				nims_redirect();
			}

			if ( $win_party == 'order-success' ) {
				bp_core_add_message( "Вы успешно завершили заказ (спор решился обоюдно)." );
				set_order_status( $order_id, 'done' );
				nims_redirect();
			}

			if ( $win_party == 'seller' ) {
				$winner_id = get_order_seller_id( $order_id );
				$looser_id = get_order_buyer_id( $order_id );
			} else {
				$winner_id = get_order_buyer_id( $order_id );
				$looser_id = get_order_seller_id( $order_id );
			}

			$order_total = get_order_total( $order_id );
			if ( $win_party == 'seller' ) {
				reward_seller( $order_id, "Из буфера: арбитраж, победившая сторона", "Из буфера: абитраж." );
			} else {
				from_buffer_to_balance( $winner_id, $order_total, "Из буфера: арбитраж, победившая сторона" );
				subtract_from_user_buffer_balance( $looser_id, $order_total, "Абитраж, из буфера." );
			}

			// send message to parties
			$winner_subject = "Сделка #$order_id завершилась в Вашу пользу.";
			$winner_message = "Заказ #$order_id. Сделка закрылась в Вашу пользу." .
			                  " Сумма заказа переведена на баланс. Ссылка на заказ: " . get_the_permalink( $order_id );
			send_notification_to_user( $winner_id, $winner_subject, $winner_message, true );

			$looser_subject = "Сделка #$order_id завершилась не в Вашу пользу.";
			$looser_message = "Заказ #$order_id. Сделка закрылась не в Вашу пользу." .
			                  " Сумма заказа списана с баланса. Ссылка на заказ: " . get_the_permalink( $order_id );
			send_notification_to_user( $looser_id, $looser_subject, $looser_message, true );

			$win_party_lbl = $win_party == 'seller' ? 'продавца' : 'покупателя';
			bp_core_add_message( "Вы успешно закрыли заказ в пользу <b>" . $win_party_lbl . "</b>." );
			$GLOBALS['win_party']     = $win_party;
			$GLOBALS['win_party_lbl'] = $win_party_lbl;

			set_order_status( $order_id, $win_party == 'seller' ? 'done' : 'closed' );

			nims_redirect();
			break;
	}
}


add_action( 'template_redirect', 'confirm_order' );
function confirm_order() {
	if ( ! isset( $_POST['order-confirmation'] ) ) {
		return;
	}

	global $post;
	if ( ! $post ) {
		nims_redirect();
	}

	if ( ! is_singular( 'order' ) || get_order_status( $post->ID ) != 'unconfirmed' ) {
		nims_redirect();
	}

	$user_id = get_current_user_id();
	if ( $post->post_author != $user_id && ! is_super_admin() ) {
		nims_redirect();
	}

	if ( is_super_admin() ) {
		$user_id = $post->post_author;
	}

	// end of general validation

	if ( isset( $_POST['cancel-order'] ) ) {
		set_order_status( $post->ID, 'cancelled' );
		bp_core_add_message( "Заказ успешно отменен", "notice" );
		nims_redirect();
	}

	// validate user input
	$nimses_login = sanitize_text_field( $_POST['nimses_login'] );
	if ( ! $nimses_login || mb_strlen( $nimses_login ) > 50 ) {
		bp_core_add_message( "Пожалуйста, укажите валиданый логин в NIMSES", "error" );
		nims_redirect();
	}

	set_order_nimses_login( $post->ID, $nimses_login );

	$order_description = sanitize_textarea_field( $_POST['order_description'] );
	if ( mb_strlen( $order_description ) > 500 ) {
		bp_core_add_message( "Допустимая длина дополнительной информации по заказу 500 символов", "error" );
		nims_redirect();
	}

	set_order_description( $post->ID, $order_description );


	// end if user input validation


	// validate order details, that no is changed so far
	global $NMS_Shop_Settings;
	$seller_id          = get_order_seller_id( $post->ID );
	$order_data_changed = array();

	/*	if ( $seller_nims_amount < $NMS_Shop_Settings->get_min_nims_amount() ) {
			bp_core_add_message( "Продавец больше не продает нимы. Попробуйте снова." );
			nims_redirect();
		}*/

	// this should be auto set to false if seller nims amount < min nims amount
	if ( ! $NMS_Shop_Settings->user_sells_nims( $seller_id ) ) {
		bp_core_add_message( "Продавец больше не продает нимы. Пожалуйста, создайте другой заказ.", "error" );
		nims_redirect();
	}

	$order_nims_amount  = get_order_nims_amount( $post->ID );
	$seller_nims_amount = $NMS_Shop_Settings->get_user_sell_nim_amount( $seller_id );
	// buyer tries to buy more then seller can handle
	if ( $order_nims_amount > $seller_nims_amount ) {
		$order_data_changed['nims_amount'] = 'количество нимов';
		$order_nims_amount                 = $seller_nims_amount;
	}

	$seller_price = $NMS_Shop_Settings->get_user_shop_nim_price_for_amount( $seller_id, $order_nims_amount );
	$order_price  = get_order_price( $post->ID );
	if ( $order_price != $seller_price ) {
		$order_data_changed['price'] = 'цена продавца';
		$order_price                 = $seller_price;
	}

	$order_transfer_time = get_order_transfer_time( $post->ID );
	$seller_transfer_time = $NMS_Shop_Settings->get_user_transfer_time( $seller_id );
	if ( $order_transfer_time < $seller_transfer_time ) {
		$order_data_changed['transfer_time'] = 'увеличился гарант времени передачи нимов';
		$order_transfer_time                 = $seller_transfer_time;
	}

	$order_total = get_order_total( $post->ID );
	if ( ! empty( $order_data_changed ) ) {

		if ( isset ( $order_data_changed['nims_amount'] ) || isset( $order_data_changed['price'] ) ) {
			$order_data_changed['total'] = 'общая сумма заказа';
			$order_total                 = round( $order_nims_amount * $order_price / 1000 );
			update_post_meta( $post->ID, '_nims_amount', $order_nims_amount );
			update_post_meta( $post->ID, '_price', $order_price );
			update_post_meta( $post->ID, '_total', $order_total );
		}

		update_post_meta( $post->ID, '_order_transfer_time', $order_transfer_time );

		$order_data_changed_lbl = implode( ', ', $order_data_changed );

		$_SESSION['order_data_changed'] = $order_data_changed;

		bp_core_add_message(
			"<b>Внимание:</b> при подтверждение следующие данные изменились: <b>{$order_data_changed_lbl}</b>.<br>" .
			"Пожалуйста, заного проверьте данные заказа и подтвердите заказ.", 'error' );
		nims_redirect();
	}

	if ( get_user_balance( $user_id ) < $order_total ) {
		bp_core_add_message(
			"Не хватает суммы на балансе", "error" );
		nims_redirect();
	}

	// confirm order success
	set_order_status( $post->ID, 'new' );

	// update order date
	$time = current_time( 'mysql' );
	wp_update_post( array(
		'ID'            => $post->ID, // ID of the post to update
		'post_date'     => $time,
		'post_date_gmt' => get_gmt_from_date( $time )
	) );

	ob_start();
	echo $NMS_Shop_Settings->transfer_minutes_to_hours_minutes($order_transfer_time, true);
	$order_transfer_time_lbl = ob_get_clean();
	bp_core_add_message(
		"Ваш заказ был успешно оплачен! В течение " . $order_transfer_time_lbl . " продавец должен передать вам нимы" );
	nims_redirect();
}


add_action( 'init', 'add_order_post_request' );
function add_order_post_request() {
	if ( ! isset( $_POST['user-buy-nims'] ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/login/' ) . '?purchase_needs_auth=true' );
		exit();
	}

	$shop_nim_amount = (int) $_POST['shop_nim_amount'];
	$seller_id       = (int) $_POST['seller_id'];

	if ( ! $shop_nim_amount || ! $seller_id ) {
		return;
	}

	$add_order_result = add_order( $seller_id, $shop_nim_amount );
	switch ( $add_order_result ) {
		case MIN_NIMS_AMOUNT_ERR:
			bp_core_add_message( 'Минимальное количество нимов для покупки: ' .
			                     $GLOBALS['NMS_Shop_Settings']->get_min_nims_amount(), 'error' );
			break;
		case SELLER_PRICE_NOT_AVAILABLE_ERR:
			bp_core_add_message( 'Цена продавца недоступна. Попробуйте снова.', 'error' );
			break;
		case NIMS_AMOUNT_NOT_AVAILABLE_ERR:
			bp_core_add_message( 'Указанное количество нимов больше недоступно. Попробуйте снова.', 'error' );
			break;
		case false:
			bp_core_add_message( "Произошла ошибка, попробуйте снова или обратитесь к администратору.", 'error' );
			break;
		default:
			wp_redirect( get_permalink( $add_order_result ) );
			exit();
	}

	nims_redirect();
}

add_filter( 'tml_action_template_message', 'add_login_purchase_message', 10, 2 );
function add_login_purchase_message( $message, $action ) {
	if ( $action == 'login' && isset( $_GET['purchase_needs_auth'] ) ) {
		return "Для покупки нимов, пожалуйста, войдите в аккаунт или <a href=" . wp_registration_url() . ">зарегистрируйтесь</a>!";
	} elseif ( $action == 'login' && isset( $_GET['sell_needs_auth'] ) ) {
		return "Для продажи нимов, пожалуйста, войдите в аккаунт или <a href=" . wp_registration_url() . ">зарегистрируйтесь</a>!";
	}
}