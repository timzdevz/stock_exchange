<?php
add_action( 'bp_actions', function () {
	remove_action( 'template_notices', 'bp_core_render_message' );
	add_action( 'template_notices', 'nims_bp_core_render_message' );
}, 6 );

function send_notification_to_user( $user_id, $subject = false, $message, $mail = false, $mail_html = false ) {

	$notification_id = bp_notifications_add_notification( array(
		'user_id'          => $user_id,
		'item_id'          => $user_id,
		'component_name'   => 'custom',
		'component_action' => 'custom_message',
		'date_notified'    => bp_core_current_time(),
		'is_new'           => 1,
		'allow_duplicate'  => true
	) );

	if ( $notification_id ) {
		bp_notifications_add_meta( $notification_id, 'custom_message', $message );

		if ( ! $mail ) {
			return $notification_id;
		}

		if ( ! $subject ) {
			$subject = 'Новое уведомление на сайте';
		}

		$message .= "\r\n" .
                    'Перейдите на сайт для прочтения уведомления ' . bp_get_notifications_permalink( $user_id );
		NimsEmail::user_notice( $user_id, $subject, $message, $mail_html );

		return $notification_id;
	}

	return false;
}

add_action( 'bp_notification_before_delete', 'delete_notification_custom_meta' );
function delete_notification_custom_meta( $args ) {
	bp_notifications_delete_meta( $args['id'], 'custom_message' );
}

// this gets the saved item id, compiles some data and then displays the notification
add_filter( 'bp_notifications_get_notifications_for_user', 'custom_format_buddypress_notifications', 10, 8 );
function custom_format_buddypress_notifications(
	$action, $item_id, $secondary_item_id, $total_items,
	$format = 'string', $action_name, $component_name, $notification_id
) {
	if ( $action == 'custom_message' ) {
		return bp_notifications_get_meta( $notification_id, 'custom_message', true );
	}
}

function nims_bp_core_render_message() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( ! empty( $bp->template_message ) ) :
		switch ( $bp->template_message_type ) {
			case 'success':
				$type = 'updated';
				break;
			case 'notice':
				$type = 'notice';
				break;
			default:
				$type = 'error';
		}

		remove_filter( 'bp_core_render_message_content', 'wp_kses_data', 5 );
		$content = apply_filters( 'bp_core_render_message_content', $bp->template_message, $type );
		add_filter( 'bp_core_render_message_content', 'wp_kses_data', 5 ); ?>

        <div id="message" class="bp-template-notice <?php echo esc_attr( $type ); ?>">

			<?php echo $content; ?>

        </div>

		<?php
		do_action( 'bp_core_render_message' );

	endif;
}

// this is to add a fake component to BuddyPress. A registered component is needed to add notifications
function custom_filter_notifications_get_registered_components( $component_names = array() ) {

	// Force $component_names to be an array
	if ( ! is_array( $component_names ) ) {
		$component_names = array();
	}

	// Add 'custom' component to registered components array
	array_push( $component_names, 'custom' );

	// Return component's with 'custom' appended
	return $component_names;
}

add_filter( 'bp_notifications_get_registered_components', 'custom_filter_notifications_get_registered_components' );

add_filter( 'wp_mail_from_name', 'custom_wp_mail_from_name' );
function custom_wp_mail_from_name( $original_email_from ) {
	return get_bloginfo( 'name' );
}

add_filter( 'wp_mail_from', 'custom_wp_mail_from' );
function custom_wp_mail_from( $original_email_address ) {
	return 'contact@stockexchange.com';
}

class NimsEmail {

	static $html_headers = array( 'Content-Type: text/html; charset=UTF-8' );

	static function admin_notice( $subject, $message ) {
		wp_mail( get_option( 'admin_email' ), $subject, $message );

	}

	static function admin_cancel_nims( $lot_id ) {

		$nimses_amount = get_lot_total_nims( $lot_id );
		$nimses_login  = get_lot_nimses_login( $lot_id );
		$lot_activated_date = get_lot_activated_date( $lot_id );
		$lot_activated_date = $lot_activated_date ? date( 'd.m.Y H:i', $lot_activated_date ) : 'N/A';
		$nimses_amount = number_format( $nimses_amount, 0, ".", ' ' );

		$message = "
Заявка на возврат нимов.\r\n 
Логин в NIMSES: $nimses_login\r\n
Дата активации лота: $lot_activated_date\r\n
Кол-во: $nimses_amount\r\n\r\n 
Ссылка на отмену лота: " . nims_get_post_edit_url( $lot_id ) . "&lot_cancel=true \r\n
Ссылка на лот: " . nims_get_post_edit_url( $lot_id );

		self::admin_notice( 'Заявка на отмену лота #' . $lot_id,  $message );
	}

	static function user_notice( $user_id, $subject, $message, $html = false ) {
		$br      = $html ? "<br>" : "\r\n";
		$message = "Здравствуйте,{$br}{$br}" . $message . "{$br}{$br}Администрация торговой площадки нимов stockexchange.com";
		wp_mail(
			bp_core_get_user_email( $user_id ),
			$subject,
            $message,
			$html ? self::$html_headers : null );
	}

	static function maybeSendBuyerReminder( $order_id ) {
		global $NMS_Shop_Settings;
	    $buyer_id = get_order_buyer_id( $order_id );
		$buyer_orders_count = get_user_orders( $buyer_id, 'buyer', array( 'compare' => 'NOT IN', 'unconfirmed' ) );
		// if user started to sell nims or made another confirmed orders, return
		if ( $NMS_Shop_Settings->user_sells_nims( $buyer_id ) || $buyer_orders_count ) {
		    return;
        }

        // we already sent reminder to buyer, cancel
		if ( get_post_meta( $order_id, '_buyer_reminder_sent', true ) ) {
		    return;
		}

		$buyer_display_name = get_userdata( $buyer_id )->display_name;
		$seller_display_name = get_userdata( get_order_seller_id( $order_id ) )->display_name;

		$nims_amount = get_order_nims_amount( $order_id, true );
		$nims_price = get_order_price( $order_id );
		$order_total = format_balance( get_order_total( $order_id ), true );
		$post_permalink = get_post_permalink( $order_id );

		$message = "$buyer_display_name, " .
		             "мы заметили, что Вы не подтвердили свой заказ #$order_id. Детали заказа:<br>" .
		             "<ul><li>цена: $nims_price</li>" .
		             "<li>количество нимов: $nims_amount</li>" .
		             "<li>сумма заказа:  $order_total</li>" .
		             "<li>продавец: $seller_display_name</li>" .
		             "<li>cсылка на ваш заказ: <a href='" . $post_permalink . "'>$post_permalink</a></li></ul>" .
		             "В течение суток вы можете оплатить и подтвердить ваш заказ," .
		             " либо отменить его и заказать нимы у другого продавца.<br>" .

		             "В случае если Вы не совершили покупку по каким-либо причинам" .
		             " (например связанным с работой нашего сервиса или продавца), " .
		             "<b>просим Вам сообщить нам о них в ответном письме!</b> Спасибо.<br>";

		if ( send_notification_to_user( $buyer_id, "У вас есть не подтвержденный заказ", $message, true, true ) ) {
			update_post_meta( $order_id, '_buyer_reminder_sent', true );
		}
    }
}

