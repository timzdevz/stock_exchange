<?php

add_action( 'bp_setup_nav', 'shop_item_profile_nav' );
function shop_item_profile_nav() {
	bp_core_new_nav_item( array(
		'name'                    => 'Продажа нимов',
		'slug'                    => 'shop-settings',
		'position'                => 20,
		'screen_function'         => 'member_show_shop_settings',
		'show_for_displayed_user' => bp_is_my_profile() || is_super_admin(),
		'default_subnav_slug'     => 'shop-settings',
		'item_css_id'             => 'shop_settings'
	) );

	$seller_active_orders_count = get_user_orders( bp_displayed_user_id(),
		'seller', array('compare' => 'NOT IN', 'done', 'closed', 'unconfirmed', 'cancelled'), true );
	$shop_orders_title = sprintf( 'Заказы магазина <span class="%s" rel="tooltip" title="%s">%d</span>',
		esc_attr( 'active-orders-count' ), esc_attr( 'Активные заказы' ), $seller_active_orders_count );

	bp_core_new_nav_item( array(
		'name'                    => $shop_orders_title,
		'slug'                    => 'shop-orders',
		'position'                => 20,
		'screen_function'         => 'member_show_shop_orders',
		'show_for_displayed_user' => bp_is_my_profile() || is_super_admin(),
		'default_subnav_slug'     => 'shop-orders',
		'item_css_id'             => 'shop_orders'
	) );

	bp_core_new_nav_item( array(
		'name'                    => 'Мои заказы',
		'slug'                    => 'orders',
		'position'                => 20,
		'screen_function'         => 'member_show_orders',
		'show_for_displayed_user' => bp_is_my_profile() || is_super_admin(),
		'default_subnav_slug'     => 'all-orders',
		'item_css_id'             => 'orders'
	) );


	$user_id = bp_displayed_user_id();
	$user_balance = get_user_balance( $user_id );
	$balance_title = sprintf( 'Баланс <span class="%s">%s &#8381;</span>',
		esc_attr( 'balance-count' ), format_balance( $user_balance ) );

	bp_core_new_nav_item( array(
		'name'                    => $balance_title,
		'slug'                    => 'balance',
		'position'                => 20,
		'screen_function'         => 'member_show_balance',
		'show_for_displayed_user' => bp_is_my_profile() || is_super_admin(),
		'default_subnav_slug'     => 'topup',
		'item_css_id'             => 'balance'
	) );


	bp_core_new_subnav_item( array(
		'name'            => 'Пополнить баланс',
		'slug'            => 'topup',
		'parent_slug'     => 'balance',
		'parent_url'      => bp_core_get_user_domain( $user_id ) . 'balance/',
		'user_has_access' => bp_is_my_profile() || is_super_admin(),
		'screen_function' => 'member_show_balance',
	) );

	bp_core_new_subnav_item( array(
		'name'            => 'Вывести средства',
		'slug'            => 'withdraw-funds',
		'parent_slug'     => 'balance',
		'parent_url'      => bp_core_get_user_domain( $user_id ) . 'balance/',
		'user_has_access' => bp_is_my_profile() || is_super_admin(),
		'screen_function' => 'member_show_balance',
		'item_css_id'     => 'withdraw-funds'
	) );


	bp_core_new_subnav_item( array(
		'name'            => 'История операций',
		'slug'            => 'transactions',
		'parent_slug'     => 'balance',
		'parent_url'      => bp_core_get_user_domain( $user_id ) . 'balance/',
		'user_has_access' => bp_is_my_profile() || is_super_admin(),
		'screen_function' => 'member_show_transactions',
		'item_css_id'     => 'transactions'
	) );

}

function member_show_shop_settings() {
	bp_core_load_template( 'members/single/shop-settings' );
}

function member_show_shop_orders() {
	bp_core_load_template( 'members/single/shop-orders' );
}

function member_show_orders() {
	bp_core_load_template( 'members/single/orders' );
}

function member_show_transactions() {
	bp_core_load_template( 'members/single/balance/transactions' );
}

function member_show_balance() {
	bp_core_load_template( 'members/single/balance/balance' );
}

function bp_is_user_shop_settings() {
	if ( bp_is_user() && bp_is_current_component( 'shop-settings' ) ) {
		return true;
	}

	return false;
}

function bp_is_user_orders() {
	if ( bp_is_user() && bp_is_current_component( 'orders' ) ) {
		return true;
	}

	return false;
}

function bp_is_user_shop_orders() {
	if ( bp_is_user() && bp_is_current_component( 'shop-orders' ) ) {
		return true;
	}

	return false;
}

function bp_is_user_transactions() {
	if ( bp_is_user() && bp_is_current_component( 'transactions' ) ) {
		return true;
	}

	return false;
}

function bp_is_user_balance() {
	if ( bp_is_user() && bp_is_current_component( 'balance' ) ) {
		return true;
	}

	return false;
}

function bp_is_user_online ($user_id) {
	//wp-content/plugins/buddypress/bp-core/classes/class-bp-user-query.php
	add_filter( 'bp_user_query_online_interval', function ( $minutes_from_last_activity ) {
		return 1;
	} );
	// Setup args for querying members.
	$members_args = array(
		'user_id'         => $user_id,
		'type'            => 'online',
		'populate_extras' => true,
		'search_terms'    => false,
	);

	return bp_has_members( $members_args );
}


add_filter( 'bp_get_send_message_button_args', 'stockexchange_send_message_button_args' );
function stockexchange_send_message_button_args( $args ) {
	$args['link_text']  = "Личное сообщение";
	$args['link_class'] .= " woo-sc-button teal";

	return $args;
}

// customize bp header meta
add_action( 'template_redirect', function () {
	global $BP_Member_Reviews;
	remove_action( 'bp_profile_header_meta', array( $BP_Member_Reviews, 'embed_rating' ) );
	add_action( 'bp_after_member_header', array( $BP_Member_Reviews, 'embed_rating' ), 20 );
} );

add_action( 'bp_setup_nav', 'lafa_remove_submenu_item', 201 );
function lafa_remove_submenu_item() {
	global $bp;

	unset( $bp->bp_options_nav['profile']['public'] );
	unset( $bp->bp_options_nav['settings']['profile'] );
}

add_filter( 'bp_members_pagination_count', 'stockexchange_bp_members_pagination_count' );
function stockexchange_bp_members_pagination_count( $pag ) {
	$pag = str_replace( 'участника', 'продавца', $pag );
	return preg_replace( '#из (\d+)(?: активных)* пользователей#', 'из <b>$1 продавцов</b>', $pag );
}

add_filter( 'bp_get_search_default_text', 'modify_search_members_text', 10, 2 );
function modify_search_members_text( $default_text, $component ) {
	if ( $component ) {
		$default_text = "Имя продавца...";
	}

	return $default_text;
}

// always query only approved merchants
add_filter( 'bp_legacy_theme_ajax_querystring', 'stockexchange_ajax_querystring', 10, 7 );
function stockexchange_ajax_querystring($query_str, $obj, $object_filter, $object_scope,
								$object_page, $object_search_terms, $object_extras ) {
	if ( $obj != 'members' ) {
		return $query_str;
	}


	$sellers_query_str = "meta_key=user_sells_nims&meta_value=1";
	$query_str = $query_str ? $query_str . '&' . $sellers_query_str : $sellers_query_str;
	return $query_str;
}

add_filter( 'bp_user_query_uid_clauses', 'stockexchange_query_members', 10, 2 );
function stockexchange_query_members( $sql, $BP_User_Query ) {
	global $wpdb;
	$prefix = $wpdb->prefix;

	switch ( $BP_User_Query->query_vars['type'] ) {

		case 'price':
			$sql['select'] .= " LEFT JOIN {$prefix}usermeta umeta ON umeta.user_id = u.ID AND umeta.meta_key = 'min_nim_price' ";
			$sql['orderby'] .= "ORDER BY CAST(umeta.meta_value AS DECIMAL(10, 2))";
			$sql['order'] = "ASC";
			break;

		case 'transfer-time':
			$sql['select'] .= " LEFT JOIN {$prefix}usermeta umeta ON umeta.user_id = u.ID AND umeta.meta_key = 'transfer_time' ";
			$sql['orderby'] .= "ORDER BY (umeta.meta_value IS NULL) ASC, " .
			                   "CAST(umeta.meta_value AS DECIMAL(10, 0)) ASC";
//			$sql['order'] = "ASC";
			break;

		case 'transactions':
			add_action( 'bp_pre_user_query', 'bp_user_query_count_total_sql_calc', 10, 1 );

			$sql['select'] =
"SELECT u.ID, count(posts.ID) c FROM wp_users u
  LEFT JOIN  ( select pm1.meta_value as user_id, p.ID from wp_posts p
                 join wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_order_status' AND pm.meta_value = 'done'
                 join wp_postmeta pm1 ON pm1.post_id = pm.post_id and pm1.meta_key = '_seller_id' AND pm1.meta_value IS NOT NULL
               where p.post_type = 'order' AND p.post_status = 'publish' ) as posts ON posts.user_id = u.ID
			";

			$sql['orderby'] .= "GROUP BY u.ID ORDER BY c";
			$sql['order'] = "DESC";

			break;

		case 'rating':
			$sql['select'] .= " LEFT JOIN {$prefix}usermeta umeta ON umeta.user_id = u.ID AND umeta.meta_key = 'bp-user-reviews-result' ";
			$sql['orderby'] .= "ORDER BY CAST(umeta.meta_value AS DECIMAL(10, 2))";
			$sql['order'] = "DESC";
			break;

		case 'nim-amount':
			$sql['select'] .= " INNER JOIN {$prefix}usermeta umeta ON umeta.user_id = u.ID AND umeta.meta_key = 'nim_amount' ";
			$sql['orderby'] .= "ORDER BY CAST(umeta.meta_value AS DECIMAL(10, 0))";
			$sql['order'] = "DESC";

			break;

		case 'total-nim-sold':
			add_action( 'bp_pre_user_query', 'bp_user_query_count_total_sql_calc', 10, 1 );

			$sql['select'] =
			"SELECT u.ID, sum(posts.nims_sold) c FROM wp_users u
  LEFT JOIN  ( select pm1.meta_value as user_id, pm.meta_value as nims_sold from wp_posts p
                 join wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_nims_amount'
                 join wp_postmeta pm1 ON pm1.post_id = pm.post_id and pm1.meta_key = '_seller_id' AND pm1.meta_value IS NOT NULL
                 join wp_postmeta pm2 ON pm1.post_id = pm2.post_id and pm2.meta_key = '_order_status' AND pm2.meta_value = 'done'
               where p.post_type = 'order' AND p.post_status = 'publish' ) as posts ON posts.user_id = u.ID
			";

			$sql['orderby'] .= "GROUP BY u.ID ORDER BY c";
			$sql['order'] = "DESC";

			break;

		case 'reviews':
			$sql['select'] .= " LEFT JOIN {$prefix}usermeta umeta ON umeta.user_id = u.ID AND umeta.meta_key = 'bp-user-reviews-count' ";
			$sql['orderby'] .= " ORDER BY CAST(umeta.meta_value AS DECIMAL(10, 0)) ";
			$sql['order'] = "DESC";
			break;
	}

	return $sql;
}

function bp_user_query_count_total_sql_calc( $bp_user_query ) {
	$bp_user_query->query_vars['count_total'] = 'sql_calc_found_rows';
}

add_filter( 'bp_found_user_query', 'remove_bp_user_query_sql_calc', 10, 2 );
function remove_bp_user_query_sql_calc($select_found_rows_str, $bp_user_query) {
	if ( $bp_user_query->query_vars['count_total'] == 'sql_calc_found_rows' ) {
		remove_action( 'bp_pre_user_query', 'bp_user_query_count_total_sql_calc');
	}

	return $select_found_rows_str;
}

function get_user_review_count( $user_id, $as_seller = false ) {

	if ( $as_seller ) {
		add_filter( 'posts_clauses', 'stockexchange_reviews_only_buyers', 10, 2 );
	}
	$reviews = new WP_Query(array(
		'post_type'   => 'bp-user-reviews',
		'post_status' => 'publish',
		'posts_per_page'  => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'meta_query' => array(
			array(
				'key'     => 'user_id',
				'value'   => $user_id
			)
		)
	));

	if ( $as_seller ) {
		remove_filter( 'posts_clauses', 'stockexchange_reviews_only_buyers', 10, 2 );
	}

	return $reviews->post_count;
}

/**
 * @param $data BP_XProfile_ProfileData
 * Disable edit field
 */
function stockexchange_xprofile_data_before_save( $data ) {
	if ( $data->field_id == 2 ) {
		$data->value = 'a:1:{i:0;s:70:"Я согласен с правилами работы сервиса.";}';
	}
}
add_action( 'xprofile_data_before_save', 'stockexchange_xprofile_data_before_save' );

function bpfr_hide_profile_edit( $retval ) {
	// remove field from edit tab
	if(  bp_is_user_profile_edit() ) {
		$retval['exclude_fields'] = '2'; // ID's separated by comma
	}
	// allow field on registration page
	if ( bp_is_register_page() ) {
		$retval['include_fields'] = '2'; // ID's separated by comma
	}

	// hide the filed on profile view tab
	if ( $data = bp_get_profile_field_data( 'field=2' ) ) :
		$retval['exclude_fields'] = '2'; // ID's separated by comma
	endif;

	return $retval;
}
add_filter( 'bp_after_has_profile_parse_args', 'bpfr_hide_profile_edit' );


add_filter( 'bp_current_user_can', 'stockexchange_disable_visibility_change', 10, 4 );
function stockexchange_disable_visibility_change( $retval, $capability, $site_id, $args ) {
	if ( $capability == 'bp_xprofile_change_field_visibility' ) {
		return false;
	}

	return $retval;
}

// resend activation email
function get_resend_activation_link( $username = '' ) {
	// Login form not used.
	if ( empty( $username ) ) {
		return false;
	}

	$user = get_user_by( 'login', $username );

	// An existing WP_User with a user_status of 2 is either a legacy
	// signup, or is a user created for backward compatibility. See
	// {@link bp_core_signup_user()} for more details.
	if ( is_a( $user, 'WP_User' ) && 2 == $user->user_status ) {
		$user_login = $user->user_login;

		// If no WP_User is found corresponding to the username, this
		// is a potential signup.
	} elseif ( is_wp_error( $user ) && 'invalid_username' == $user->get_error_code() ) {
		$user_login = $username;

		// This is an activated user, so bail.
	} else {
		return false;
	}

	// Look for the unactivated signup corresponding to the login name.
	$signup = BP_Signup::get( array( 'user_login' => sanitize_user( $user_login ) ) );

	// No signup or more than one, something is wrong. Let's bail.
	if ( empty( $signup['signups'][0] ) || $signup['total'] > 1 ) {
		return false;
	}

	// Unactivated user account found!
	// Set up the feedback message.
	$signup_id = $signup['signups'][0]->signup_id;
	$signup_email = $signup['signups'][0]->user_email;

	$resend_url_params = array(
		'action' => 'bp-resend-activation',
		'id'     => $signup_id,
	);

	$resend_url = wp_nonce_url(
		add_query_arg( $resend_url_params, wp_login_url() ),
		'bp-resend-activation'
	);

	$resend_string = '<p>' . sprintf('Не получили письмо с активацией? <a href="%s">нажмите здесь, чтобы отправить его повторно на %s</a>.</p>', esc_url( $resend_url ), esc_html($signup_email) );

	return $resend_string;
}



function stockexchange_reviews_only_buyers( $clauses, $query_object ) {
	return stockexchange_review_filter( $clauses, 'buyers' );
}

function stockexchange_reviews_only_sellers( $clauses, $query_object ) {
	return stockexchange_review_filter( $clauses, 'sellers' );
}

function stockexchange_review_filter( $clauses, $reviews_from ){
	// Now, let's add your table into the SQL
	$join = &$clauses['join'];
	if (! empty( $join ) ) $join .= ' '; // add a space only if we have to (for bonus marks!)
	$join .=
"INNER JOIN wp_postmeta pm1 ON ( wp_postmeta.post_id = pm1.post_id )
INNER JOIN wp_posts p1 ON ( pm1.meta_value = p1.ID )
INNER JOIN wp_postmeta pm2 ON ( pm2.post_id = p1.ID )";

	// And make sure we add it to our selection criteria
	$where = &$clauses['where'];
	// Regardless, you always start with AND, because there's always a '1=1' statement as the first statement of the WHERE clause that's added in by WP/

	$compare_sign = $reviews_from == 'buyers' ? '<>' : '=';
	$where .= " AND pm1.meta_key = 'order_id' AND pm2.meta_key = '_seller_id' AND pm2.meta_value {$compare_sign}  wp_posts.post_author"; // assuming $leader_id is always (int)

	// And I assume you'll want the posts "grouped" by user id, so let's modify the groupby clause
	/*$groupby = &$clauses['groupby'];
	// We need to prepend, so...
	if (! empty( $groupby ) ) $groupby = ' ' . $groupby; // For the show-offs
	$groupby = "{$wpdb->posts}.post_author" . $groupby;*/

	// Regardless, we need to return our clauses...
	return $clauses;
}

add_action( 'init', 'unread_notifications_actions' );
function unread_notifications_actions() {
	if ( ! isset( $_POST['unread_notification_action'] ) ) {
		return;
	}

	$user_id = bp_displayed_user_id();
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'stockexchange-notification-action' . $user_id ) ) {
		die( 'Security check' );
	}

	if ( isset( $_POST['delete-all-unread'] ) ) {
		BP_Notifications_Notification::delete(array( 'user_id' => $user_id ));
	} elseif ( isset( $_POST['mark-all-read'] ) ) {
		BP_Notifications_Notification::mark_all_for_user( $user_id );
	}
}