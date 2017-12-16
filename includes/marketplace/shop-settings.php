<?php

class NMS_Shop_Settings {

	const DEFAULT_MAX_TRANSFER_HOURS = 24;
	const DEFAULT_MAX_TRANSFER_TIME = self::DEFAULT_MAX_TRANSFER_HOURS * 60;
	const DEFAULT_MIN_TRANSFER_TIME = 10;

	var $pricing_group_table_name;

	function __construct() {
		global $wpdb;

		$this->pricing_group_table_name = $wpdb->prefix . "nms_pricing_group";

		add_action( 'after_switch_theme', array( $this, 'on_stockexchange_theme_activate' ) );
		add_filter( 'update_user_metadata', array( $this, 'on_update_user_verified_meta' ), 10, 5 );
		add_action( 'init', array( $this, 'save_settings' ) );
		add_filter( 'body_class', array( $this, 'add_user_verified_class_to_body' ) );
	}

	function add_user_verified_class_to_body($classes) {
		$used_id = bp_displayed_user_id();
		if ( ! $used_id ) {
			$used_id = bp_loggedin_user_id();
		}

		if ( ! $used_id ) {
			return $classes;
		}

		if ($this->user_verified_status( $used_id ) === "1") {
			$classes = array_merge( $classes, array( 'seller-verified' ) );
		}

		return $classes;
	}

	function on_stockexchange_theme_activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . "nms_pricing_group";
		if ( $wpdb->get_var( 'SHOW TABLES LIKE ' . $table_name ) != $table_name ) {

			$sql = "CREATE TABLE {$table_name} (
        id int NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        min_quantity int not null,
        max_quantity int not null,
        price decimal(15, 2) not null,
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			add_option( "stockexchange_db_version", "1.0" );
		}
	}


	private function save_user_pricing_groups( $user_id ) {
		if ( ! isset( $_POST['price_grouping'] ) ) {
			return;
		}


		// validation for pricing groups
		/*
		doesn't save keys
 		usort($_POST['price_grouping'], function ( $a, $b) {
			return $a['min_quantity']>$b['min_quantity'];
		});*/



		if ( count( $_POST['price_grouping'] ) == 1 ) {
			return false;
		}

		// replace first-val by min && last val by max nims
		reset( $_POST['price_grouping'] );
		$_POST['price_grouping'][ key( $_POST['price_grouping'] ) ]['min_quantity'] = $this->get_min_nims_amount();
		end( $_POST['price_grouping'] );
		$_POST['price_grouping'][ key( $_POST['price_grouping'] ) ]['max_quantity'] = $this->get_max_nims_amount();
		reset( $_POST['price_grouping'] );

		$tmp_min  = $tmp_max = array();
		$last_min = $last_max = null;


		foreach ( $_POST['price_grouping'] as $price_group_id => $price_group ) {
			if ( ! is_numeric( $price_group_id ) && mb_strpos( $price_group_id, 'new_' ) === false ) {
				return false;
			}

			if ( ! ( (float) $price_group['price'] ) ) {
				return false;
			}

			// min quantity should be 5000 min
			if ( $price_group['min_quantity'] < $this->get_min_nims_amount() ) {
				return false;
			}

			// max quantity should be 50 million max
			if ( $price_group['max_quantity'] > $this->get_max_nims_amount() ) {
				return false;
			}


			if ( $price_group['min_quantity'] >= $price_group['max_quantity'] ) {
				return false;
			}

			// check that there are no same min and max quantity
			if ( in_array( $price_group['min_quantity'], $tmp_min ) ||
			     in_array( $price_group['max_quantity'], $tmp_max )
			) {
				return false;
			}

			$tmp_min[] = $price_group['min_quantity'];
			$tmp_max[] = $price_group['max_quantity'];


			if ( $last_min === null ) {
				$last_min = $price_group['min_quantity'];
				$last_max = $price_group['max_quantity'];
			} elseif ( $price_group['min_quantity'] <= $last_max ) {
				return false;
			} elseif ( $price_group['min_quantity'] != $last_max + 1 ) {
				return false;
			} else {
				$last_min = $price_group['min_quantity'];
				$last_max = $price_group['max_quantity'];
			}
		}

		global $wpdb;

		$existing_pricing_groups = $this->get_user_pricing_groups( $user_id, 'ARRAY_A' );
		$update_arr              = $insert_arr = array();
		foreach ( $_POST['price_grouping'] as $price_group_id => $price_group ) {
			if ( mb_strpos( $price_group_id, 'new_' ) === false &&
			     ( $arr_key = array_search( $price_group_id, array_column( $existing_pricing_groups, 'id' ) ) ) !== false
			) {

				$update_arr[]                                  = array(
					'group' => $price_group,
					'id'    => $price_group_id
				);
				$existing_pricing_groups[ $arr_key ]['update'] = true;
			} else {
				$price_group['user_id'] = $user_id;
				$insert_arr[]           = $price_group;
			}
		}

		foreach ( $update_arr as $update_group ) {
			$group = $update_group['group'];


			$wpdb->update( $this->pricing_group_table_name,
				$group,
				array( 'id' => $update_group['id'] ),
				array( '%d', '%d', '%f' ),
				array( '%d' )
			);
		}


		foreach ( $existing_pricing_groups as $pricing_group ) {
			if ( ! isset( $pricing_group['update'] ) ) {
				$wpdb->delete(
					$this->pricing_group_table_name,
					array( 'id' => $pricing_group['id'] ),
					array( '%d' ) );
			}
		}

		$total_pricing_groups = count( $update_arr );
		foreach ( $insert_arr as $insert_group ) {
			if ( $total_pricing_groups >= $this->get_dynamic_pricing_group_limit() ) {
				return;
			}

			$result = $wpdb->insert( $this->pricing_group_table_name, $insert_group, array( '%d', '%d', '%f', '%d' ) );
			if ( $result ) {
				$total_pricing_groups ++;
			}
		}


		$all_groups = $this->get_user_pricing_groups( $user_id, 'ARRAY_A' );

		try {
			$min_nim_price = $all_groups[0]['price'];

			foreach ( $all_groups as $all_group ) {
				$min_nim_price = min( $all_group['price'], $min_nim_price );
			}

			update_user_meta( $user_id, 'min_nim_price', $min_nim_price );
		} finally {}

		return true;
	}

	function save_settings() {
		if ( ! isset( $_POST['save_shop_settings'] ) ) {
			return false;
		}

		$user_id = bp_displayed_user_id();
		if ( ! $user_id ) {
			nims_redirect();
		}

		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ||
		     ( $current_user_id != $user_id && ! is_super_admin( $current_user_id ) )
		) {
			nims_redirect();
		}


		if ( isset( $_POST['verification_admin'] ) && is_super_admin() ) {
			$admin_verification_status = $_POST['verification_status'];
			if ( $admin_verification_status === "1") {
				$admin_verification_settings_updated = update_user_meta( $user_id, 'user_verified', true );
				update_user_meta( $user_id, '_stockexchange_rating', get_option( 'default_stockexchange_rating', 10 ) );
			} else {
				$admin_verified_message = sanitize_text_field( $_POST['admin_verified_message'] );
				update_user_meta( $user_id, 'user_verified_message', $admin_verified_message );
				$admin_verification_settings_updated = update_user_meta( $user_id, 'user_verified', false );
			}

			if ( $admin_verification_settings_updated ) {
				bp_core_add_message( "Настройки верификации сохранены" );
			}

			nims_redirect();
		}

		$user_verified_status = $this->user_verified_status( $user_id );
		if ( $user_verified_status === "pending" ) {
			nims_redirect();
		}

		if ( $user_verified_status === "1" && ! stockexchange_rating_ok( $user_id ) ) {
			bp_core_add_message( "Ваш рейтинг stockexchange продавца слишком низок. Вы не можете продавать нимы", 'error' );
			nims_redirect();
		}

		$nim_amount = (int) $_POST['nim_amount'];
		if ( $nim_amount < ( $min_nims_amount = $this->get_min_nims_amount() ) ) {
			bp_core_add_message( "Минимальное количество нимов на продажу " . $min_nims_amount, 'error' );
			nims_redirect();
		}

		// transfer garant time
		$total_minutes = ((int) $_POST['transfer_hours']) * 60 + ((int)$_POST['transfer_minutes']);
		if ( $total_minutes < self::DEFAULT_MIN_TRANSFER_TIME || $total_minutes > self::DEFAULT_MAX_TRANSFER_TIME ) {
			bp_core_add_message( "Гарант времени не можете быть меньше " . self::DEFAULT_MIN_TRANSFER_TIME .
			                     " минут и больше " . self::DEFAULT_MAX_TRANSFER_HOURS .  " часов", 'error' );
			nims_redirect();
		}

		$this->set_user_transfer_time( $user_id, $total_minutes );


		$nims_price_depends = $_POST['nims_price_depends'] == "1" ? true : false;

		if ( ! $nims_price_depends ) {
			$nim_price = (float) $_POST['nim_price'];
			if ( ! $nim_price || $nim_price < 0.01 ) {
				bp_core_add_message( "Пожалуйста, укажите правильную цену", 'error' );
				nims_redirect();
			}

			$nim_price = sprintf( "%01.2f", $nim_price );
			update_user_meta( $user_id, 'nim_price', $nim_price );
			update_user_meta( $user_id, 'min_nim_price', $nim_price );
		}

		$user_description     = sanitize_text_field( $_POST['shop_description'] );
		$user_description_len = mb_strlen( $user_description );
		if ( $user_description_len < 50 || $user_description > 160 ) {
			bp_core_add_message( "Описание должно быть от 70 до 160 символов", "error" );
			nims_redirect();
		} else {
			update_user_meta( $user_id, 'shop_description', $user_description );
		}

		update_user_meta( $user_id, 'nim_amount', $nim_amount );

		if ( $nims_price_depends ) {
			$result = $this->save_user_pricing_groups( $user_id );
			if ( $result === false ) {
				bp_core_add_message( "Пожалуйста, проверьте настройки ценновых групп", 'error' );
				nims_redirect();
			}
		}

		// update after save_user_pricing_groups
		update_user_meta( $user_id, 'nims_price_depends', $nims_price_depends );

		if ($user_verified_status === "1" ) {
			update_user_meta( $user_id, 'user_sells_nims', isset( $_POST['user_sells_nims'] ) );

			bp_core_add_message( "Все настройки успешно сохранены", "success" );
			nims_redirect();
		} else {
			update_user_meta( $user_id, 'user_sells_nims', false );
			update_user_meta( $user_id, 'user_verified', 'pending' );
			update_user_meta( $user_id, 'user_verified_message', false );

			bp_core_add_message( "Магазин успешно отправлен на проверку. Администратор проверит Ваш магазин в течение 24 часов." );

			nims_redirect();
		}
	}

	function on_update_user_verified_meta( $null, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( $meta_key != 'user_verified' ) {
			return null;
		}

		$user_id = $object_id;
		$user_shop_settings_url = bp_core_get_user_domain( $user_id ) . "shop-settings/";

		if ( $meta_value === true ) {
			update_user_meta( $user_id, 'user_sells_nims', true );
			update_user_meta( $user_id, 'user_verified_message', false );
			send_notification_to_user( $user_id,
				"Администратор верифицировал ваш магазин",
				"Теперь ваш магазин отображается в общем списке магазинов нимов. \r\n" .
				"Ссылка на список магазинов: " . home_url( '/shop/' ),
				true );
		} elseif ( $meta_value == false ) {
			$user_verified_message = get_user_meta( $user_id, 'user_verified_message', true );
			send_notification_to_user( $user_id,
				"Администратор не подтвердил ваш магазин",
				"Причина отказа: " . esc_html($user_verified_message) . "\r\n" .
				"Пожалуйста, измените данные профиля согласно комментарию и отправьте магазин на проверку заного. \r\n" . "Ссылка на настройки магазина: " . $user_shop_settings_url, true);


			update_user_meta( $user_id, 'user_sells_nims', false );

		} elseif ( $meta_value == 'pending' ) {

			NimsEmail::admin_notice( "Продавец " . get_userdata( $user_id)->user_login . " отправил магазин на проверку",
				"Требуется верификация продавца: " . $user_shop_settings_url . " \r\n" .
				"Все ожидающие проверки магазины: " .
				admin_url('users.php?s&acp_filter%5B5979a70ccc909%5D=cGVuZGluZw%3D%3D&acp_filter_action=Filter') );
		}

		return null;
	}

	function get_dynamic_pricing_group_limit() {
		// todo: add option to admin page
		return get_option( 'dynamic_pricing_group_limit', 4 );
	}

	function user_sells_nims( $user_id ) {
		return get_user_meta( $user_id, 'user_sells_nims', true );
	}

	function user_verified_status( $user_id ) {
		return get_user_meta( $user_id, 'user_verified', true );
	}

	function get_verified_message( $user_id ) {
		return get_user_meta( $user_id, 'user_verified_message', true );
	}

	function nims_price_dynamic( $user_id ) {
		return get_user_meta( $user_id, 'nims_price_depends', true );
	}

	function get_user_sell_nim_amount( $user_id ) {
		return get_user_meta( $user_id, 'nim_amount', true );
	}

	function set_seller_nim_amount( $user_id, $nim_amount ) {
		return update_user_meta( $user_id, 'nim_amount', $nim_amount );
	}

	function get_user_sell_nim_price( $user_id ) {
		return get_user_meta( $user_id, 'nim_price', true );
	}




	function get_user_shop_nim_price( $user_id ) {
		if ( $this->nims_price_dynamic( $user_id ) ) {
			return $this->get_user_dynamic_min_price( $user_id );
		} else {
			return $this->get_user_sell_nim_price( $user_id );
		}
	}

	function get_user_shop_nim_price_for_amount( $user_id, $nims_amount ) {
		if ( ! $this->user_sells_nims( $user_id ) ) {
			return false;
		}

		if ( $this->nims_price_dynamic( $user_id ) ) {
			$price = 0;
			$pricing_groups = $this->get_user_pricing_groups( $user_id, 'ARRAY_A' );
			foreach ( $pricing_groups as $pricing_group ) {
				if ( $nims_amount >= $pricing_group['min_quantity'] ) {
					$price = $pricing_group['price'];
				}
			}

			return $price;
		}

		return $this->get_user_sell_nim_price( $user_id );
	}

	function get_user_pricing_groups( $user_id, $output = 'OBJECT' ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * from {$this->pricing_group_table_name} 
WHERE user_id = '%d'
ORDER BY min_quantity ASC", $user_id ), $output
		);
	}

	function get_user_dynamic_min_price( $user_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `price` from {$this->pricing_group_table_name} 
WHERE user_id = '%d'
ORDER BY price ASC", $user_id )
		);

	}


	function get_user_shop_description( $user_id ) {
		return get_user_meta( $user_id, 'shop_description', true );
	}

	function get_user_transfer_time( $user_id, $return_hours_minutes_arr = false, $echo = false ) {
		$transfer_minutes = (int) get_user_meta( $user_id, 'transfer_time', true );
		if ( ! $transfer_minutes ) {
			$transfer_minutes = self::DEFAULT_MAX_TRANSFER_TIME;
		}

		if ( $return_hours_minutes_arr ) {
			return $this->transfer_minutes_to_hours_minutes( $transfer_minutes, $echo );
		}

		return $transfer_minutes;
	}

	function transfer_minutes_to_hours_minutes( $transfer_minutes, $echo = false ) {
		$hours = floor($transfer_minutes / 60);
		$minutes = ($transfer_minutes % 60);
		$formatted_str = array(
			'h' => $hours,
			'm' => $minutes
		);

		if ($echo) {

			$formatted_str = '';
			if ( $hours > 0 ) {
				$formatted_str .= "{$hours} час. ";
			}

			if ( $minutes > 0 ) {
				$formatted_str .= "{$minutes} мин.";
			}
		}

		return $formatted_str;
	}

	function set_user_transfer_time( $user_id, $total_minutes ) {
		return update_user_meta( $user_id, 'transfer_time', $total_minutes );
	}

	function get_min_nims_amount() {
		return get_option( 'min_nims_amount', 10000 );
	}

	function get_max_nims_amount() {
		return get_option( 'max_nims_amount', 50000000 );
	}

	function seller_subtract_nims( $order_id ) {
		$seller_id = get_order_seller_id( $order_id );
		$user_current_nims = $this->get_user_sell_nim_amount( $seller_id );
		$new_nims_amount = $user_current_nims - get_order_nims_amount( $order_id );

		if ( $new_nims_amount < 0 ) {
			$new_nims_amount = 0;
		}

		$this->set_seller_nim_amount( $seller_id, $new_nims_amount );

		if ( $new_nims_amount < $this->get_min_nims_amount() ) {
			update_user_meta( $seller_id, 'user_sells_nims', false );
		}
	}

	function seller_return_nims( $order_id ) {
		$seller_id = get_order_seller_id( $order_id );
		$user_current_nims = $this->get_user_sell_nim_amount( $seller_id );

		if ( $user_current_nims < 0 ) {
			$user_current_nims = 0;
		}

		$new_nims_amount = $user_current_nims + get_order_nims_amount( $order_id );


		$this->set_seller_nim_amount( $seller_id, $new_nims_amount );
	}
}

global $NMS_Shop_Settings;
$NMS_Shop_Settings = new NMS_Shop_Settings();