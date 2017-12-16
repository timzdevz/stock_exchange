<?php
define( 'TRANSACTIONS_TABLE_NAME', 'nms_transactions' );
$GLOBALS['TRANSACTIONS_TYPES'] = array(
	'in'             => 'Пополнение счёта', // только пополнение
	'out'            => 'Вывод средств', // НУЖНО для подсчета сколько народу всего вывело!
	// Использовать при выводе средств
	'commission'     => 'Комиссия сервиса',
	'commission_out' => 'Вывод коммиссионых',
	'buffer_in'      => 'В буфер', // при оплате заказа и выводе средств
	'buffer_out'     => 'Из буфера' // оплата заказа для покупателя,
	// вознаграждение для продавца, а вывод средств также через 'out'
);

add_action( 'after_switch_theme', 'create_transaction_table_on_theme_activate' );
function create_transaction_table_on_theme_activate() {
	global $wpdb;

	$table_name = $wpdb->prefix . "nms_transactions";
	if ( $wpdb->get_var( 'SHOW TABLES LIKE ' . $table_name ) != $table_name ) {

		$sql = "CREATE TABLE {$table_name} (
        id int NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(255) not null,
        amount decimal(15, 2) not null,
        balance decimal(15, 2) not null,
        buffer decimal(15, 2) not null,
        `comment` varchar(255) not null,
        method varchar(255) null,
        `date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address varchar(15) null,
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID)
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( "stockexchange_db_version", "1.0" );
	}
}


function add_transaction( $user_id, $type, $amount, $comment = null, $method = null, $timestamp = null ) {
	global $wpdb, $NMS_Balance;

	if ( ! in_array( $type, array_keys( $GLOBALS['TRANSACTIONS_TYPES'] ) ) ) {
		throw new InvalidArgumentException( "Invalid transaction type" );
	}

	if ( $comment == null ) {
		$comment = $GLOBALS['TRANSACTIONS_TYPES'][ $type ];
		switch ( $type ) {
			case 'in':
				$comment .= " на " . format_balance( $amount, true );
				break;
			default:
				$comment .= " " . format_balance( $amount, true );
		}
	}

	// add to marketplace balance if commission, maybe move to do_action in future
	if ( $type == 'commission' ) {
		$NMS_Balance->marketplace_balance_operation( $amount, '+' );
	} elseif ( $type == 'commission_out' ) {
		$NMS_Balance->marketplace_balance_operation( $amount, '-' );
	}

	$wpdb->insert( $wpdb->prefix . TRANSACTIONS_TABLE_NAME, array(
		'user_id'    => $user_id,
		'type'       => $type,
		'amount'     => $amount,
		'balance'    => get_user_balance( $user_id ),
		'buffer'     => get_user_buffer_balance( $user_id ),
		'comment'    => $comment,
		'method'     => $method,
		'date'       => $timestamp ? $timestamp : current_time( 'mysql' ),
		'ip_address' => get_the_user_ip()
	), array( '%d', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', ) );

	$last_id = $wpdb->insert_id;

	return $last_id;
}

function get_transaction( $transaction_id ) {
	global $wpdb;

	$table_name = $wpdb->prefix . TRANSACTIONS_TABLE_NAME;

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM $table_name WHERE `id` = %d", $transaction_id ) );
}

function get_transactions( $user_id = null, $page_offset, $transactions_per_page, $type = null ) {
	global $wpdb;

	$offset     = ( $page_offset - 1 ) * $transactions_per_page;
	$table_name = $wpdb->prefix . TRANSACTIONS_TABLE_NAME;
	$where      = array();
	if ( $user_id ) {
		$where[] = sprintf( ' user_id = %d ', $user_id );
	}

	if ( $type ) {
		$where[] = sprintf( ' type = \'%s\' ', esc_sql( $type ) );
	}

	$where_sql = ! empty( $where ) ? ' WHERE 1=1 AND ' . implode( ' AND ', $where) : '';

	$results                 = array();
	$results['transactions'] = $wpdb->get_results( $wpdb->prepare(
		"SELECT SQL_CALC_FOUND_ROWS * FROM $table_name {$where_sql} ORDER BY `date` DESC LIMIT %d, %d",
		$offset, $transactions_per_page ), 'ARRAY_A' );
	$results['found_rows']   = $wpdb->get_var( "SELECT FOUND_ROWS()" );

	return $results;
}

function get_transactions_query( $fields = array( '*' ), $where, $wpdb_func = 'get_var' ) {
	global $wpdb;
	$table_name = $wpdb->prefix . TRANSACTIONS_TABLE_NAME;
	if ( ! is_array( $fields ) ) {
		$fields = (array) $fields;
	}

	$fields  = implode( ',', $fields );
	$results = call_user_func_array(
		array( $wpdb, $wpdb_func ),
		array( "SELECT {$fields} FROM $table_name {$where}" ) );

	return $results;
}

function get_user_transactions_by_type( $user_id = null, $type, $fields = 'all' ) {
	global $wpdb;
	$table_name = $wpdb->prefix . TRANSACTIONS_TABLE_NAME;

	$select = "select *";
	if ( $fields != 'all' ) {
		$fields = (array) $fields;
		$fields = array_map( function ( $field ) {
			return esc_sql( $field );
		}, $fields );
		$select = "select " . implode( ',', $fields );
	}

	$extra_where = '';
	$user_id     = (int) $user_id;
	if ( $user_id ) {
		$extra_where .= " AND user_id = '{$user_id}' ";
	}

	return $wpdb->get_results( $wpdb->prepare(
		"{$select} from $table_name WHERE type = %s {$extra_where}", $type ), 'ARRAY_A' );
}

function fk_transaction_exists( $fk_operation_id ) {
	global $wpdb;

	$table_name = $wpdb->prefix . TRANSACTIONS_TABLE_NAME;

	$results = $wpdb->get_col( $wpdb->prepare(
		"select id from {$table_name} WHERE method LIKE %s", 'intid#' . $wpdb->esc_like( $fk_operation_id ) . '%' ) );

	return count( $results ) > 0;
}