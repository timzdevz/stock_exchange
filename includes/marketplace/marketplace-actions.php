<?php

// status change actions
add_action( 'lot_status_change_from_pending_to_done', 'nims_update_user_balance', 10, 3 );
function nims_update_user_balance( $lot_id, $old_status, $new_status ) {
	$total_nims         = get_post_meta( $lot_id, '_total_nims', true );
	$price_per_thousand = get_post_meta( $lot_id, '_price_per_thousand', true );

	$total_amount = (float) number_format( $total_nims * $price_per_thousand / 1000, 2, '.', '' );

	$post_author_id = get_post_field( 'post_author', $lot_id );

	$user_balance = (float) get_user_balance( $post_author_id );
	$user_balance += $total_amount;
	set_user_balance( $post_author_id, $user_balance );
}

add_action( 'wp_head', function () {
	if ( ( $unread_notif_count = bp_notifications_get_unread_notification_count( get_current_user_id() ) ) > 0 ) { ?>
        <style>
            #top-nav .bp-profile-nav > a:before,
            #menu-bp > #notifications-personal-li a:before {
                content: '<?php echo $unread_notif_count ?>'
            }
        </style>
		<?php
	}
} );

add_action( 'template_notices', 'limit_new_lots_notice', 99 );
function limit_new_lots_notice() {
	if ( ! bp_is_my_profile() ) {
		return;
	}


	if ( ! get_user_meta( $user_id = bp_displayed_user_id(), 'welcome_message_viewed', true ) ) {
		?>
        <div id="message" class="bp-template-notice success">
            <p>Добро пожаловать на торговую площадку нимов stockexchange.com! Обязательно ознакомьтесь с <a
                        href="<?php echo home_url( '/rules/' ) ?>" target="_blank">правилами работы stockexchange.com</a>. Вы можете продать свои нимы в разделе
                <a href="<?php echo get_sell_nims_url(); ?>">Продажа нимов</a> или
                <a href="<?php echo home_url('/shop/'); ?>">купить нимы</a> на рынке нимов.</p>
        </div>
		<?php update_user_meta( $user_id, 'welcome_message_viewed', true );
	}
}


//add_action( 'bp_core_general_settings_after_save', 'nims_save_custom_bp_settings' );
function nims_save_custom_bp_settings() {

	//sanitize input data
	$payment_yandex = sanitize_text_field( $_POST['payment_yandex'] );

	if ( ! empty( $payment_yandex ) ) {
		if ( ! preg_match( "/[0-9]+/", $payment_yandex ) ) {
			bp_core_add_message( 'Введите данные Яндекс.Кошелька в правильном формате', 'error' );

			return;
		} elseif ( user_payment_address( bp_displayed_user_id(), $payment_yandex ) ) {
			bp_core_add_message( 'Вы успешно обновили личные данные.', 'success' );
		}
	}

	$default_nimses_login = sanitize_text_field( $_POST['default_nimses_login'] );
	user_default_nimses_login( get_current_user_id(), $default_nimses_login );
}


// show actual balance instead of label "Баланс"
add_filter( 'nav_menu_item_title', 'stockexchange_balance_nav_menu_item', 10, 4 );
function stockexchange_balance_nav_menu_item( $title, $item, $args, $depth ) {
	if ( $title != "Баланс" || $item->post_name != "balans" ) {
		return $title;
	}

	$title = format_balance( get_user_balance( get_current_user_id() ) ) . " ₽";

	return $title;
}

// show correct links of sub balance items
add_filter( 'wp_get_nav_menu_items', 'fix_balance_sub_items_links', 10, 3 );
function fix_balance_sub_items_links( $items, $menu, $args ) {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return $items;
	}

	foreach ( $items as $item ) {
		switch ( $item->post_name ) {
			case "popolnit-balans":
				$item->url = bp_core_get_user_domain( $uid ) . "balance/";
				break;
			case "vyvesti-sredstva":
				$item->url = bp_core_get_user_domain( $uid ) . "balance/withdraw-funds/";
				break;
			case "istoriya-operatsij":
				$item->url = bp_core_get_user_domain( $uid ) . "balance/transactions/";
				break;
		}
	}

	return $items;
}


// fix css classes for shop list and profile page
add_filter( 'nav_menu_css_class', 'order_menu_nav_classes', 10, 2 );
function order_menu_nav_classes( $classes, $item ) {
	$shop_url = get_option( 'stockexchange_shop_url', home_url( 'shop/' ) );
	if ( is_singular( 'order' ) ) {
		$classes = array_diff( $classes, array( 'current_page_parent' ) );
	} elseif ( bp_is_my_profile() ) {
		if ( $item->url === $shop_url ) {
			$classes = array_diff( $classes, array( 'current_page_item' ) );

		} elseif ( preg_match( "#$shop_url(?:[a-z_\-0-9]+)/profile/#i", $item->url ) ) {
			$classes[] = 'current_page_parent';

		} elseif ( preg_match( "#$shop_url(?:[a-z_\-0-9]+)/balance/#i", $item->url ) ) {
			$classes = array_diff( $classes, array( 'current_page_item', 'current-menu-ancestor' ) );
		}
    }

	return $classes;
}

add_action( 'template_redirect', 'stockexchange_disable_author_archive' );
function stockexchange_disable_author_archive() {
    if ( is_author() ) {
		wp_redirect( home_url(), 301 );
		exit();
	}
}
