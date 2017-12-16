<?php
if ( ! class_exists( 'AC_Column' ) ) {
	return;
}
add_action( 'acp/column_types', 'ac_register_pro_columns' );
function ac_register_pro_columns( AC_ListScreen $list_screen ) {
	// Use the type: 'post', 'user', 'comment', 'media' or 'taxonomy'.
	if ( 'post' === $list_screen->get_group() ) {
		$list_screen->register_column_type( new AC_Column_Order_Status );
		$list_screen->register_column_type( new AC_Column_Nim_Count );
		$list_screen->register_column_type( new AC_Column_Order_Sum );
		/* $list_screen->register_column_type( new AC_Column_Lot_Sum );
		$list_screen->register_column_type( new AC_Column_Lot_Activated );
		$list_screen->register_column_type( new AC_Column_Transaction );*/
	}

	if ( 'user' === $list_screen->get_group() ) {
		$list_screen->register_column_type( new AC_Column_User_Payment_Action );
		$list_screen->register_column_type( new AC_Column_User_Balance );
		$list_screen->register_column_type( new AC_Column_User_Payment_Address );
	}
}


class AC_Column_Nim_Count extends AC_Column {
	public function __construct() {
		$this->set_type( 'column-Nim_Count' );
		$this->set_label( 'Сумма нимов' );
	}

	public function get_value( $post_id ) {
		return '<span class="nim-coin-icon">' . number_format( $this->get_raw_value( $post_id ), 0, null, ' ') . "</span>";
	}

	public function get_raw_value( $post_id ) {
		return get_order_nims_amount(  $post_id );
	}

	public function is_valid() {
		if ( $this->get_post_type() != 'order' ) {
			return false;
		}

		return true;
	}
}

class AC_Column_Order_Status extends AC_Column implements
	ACP_Column_EditingInterface, ACP_Column_FilteringInterface {

	public function __construct() {
		$this->set_type( 'column-Order_Status' );
		$this->set_label( 'Статус заказа' );
	}

	public function get_raw_value( $post_id ) {
		return get_order_status( $post_id );
	}

	public function get_value( $id ) {
		$statuses = $GLOBALS['ORDER_STATUSES'];
		return $statuses[$this->get_raw_value( $id ) ];
	}


	public function is_valid() {

		if ($this->get_post_type() == 'order') {
			return true;
		}

		return false;
	}

	/**
	 * Return the editing model for this column
	 *
	 * @return ACP_Editing_Model
	 */
	public function editing() {
		return new ACP_Editing_Model_Order_Status( $this );
	}

	/**
	 * Return the filtering model for this column
	 *
	 * @return ACP_Filtering_ModelInterface|ACP_Filtering_Model
	 */
	public function filtering() {
		return new ACP_Filtering_Model_Order_Status( $this );
	}
}

class ACP_Filtering_Model_Order_Status extends ACP_Filtering_Model {


	public function filter_join_by_order_status( $join ) {
		global $wpdb;
		$join .= "LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id ";

		return $join;
	}

	public function filter_where_by_order_status( $where, &$wp_query  ) {
		global $wpdb;

		$meta_key = '_order_status';
		$where .= $wpdb->prepare( " AND ($wpdb->postmeta.meta_key = '{$meta_key}' AND $wpdb->postmeta.meta_value = '%s') ", $this->get_filter_value());

		return $where;
	}

	public function get_filtering_vars( $vars ) {
		add_filter( 'posts_join', array( $this, 'filter_join_by_order_status' ) );
		add_filter( 'posts_where', array( $this, 'filter_where_by_order_status' ), 10, 2 );

		return $vars;
	}

	public function get_filtering_data() {
		$data['options'] = array();
		$count = 0;
		$statuses = $GLOBALS['ORDER_STATUSES'];

		foreach ( $statuses as $key => $status ) {
			$data['options'][$key] = $count . ". " . $status;
			$count++;
		}

		return $data;
	}
}

class ACP_Editing_Model_Order_Status extends ACP_Editing_Model {
	/**
	 * Editing view settings
	 *
	 * @return array Editable settings
	 */
	public function get_view_settings() {
		// available types: text, textarea, media, float, togglable, select, select2_dropdown and select2_tags
		$settings = array(
			'type' => 'select2_dropdown',
		);
		// (Optional) Only applies to type: togglable, select, select2_dropdown and select2_tags

		$settings['options'] = $GLOBALS['ORDER_STATUSES'];

		// (Optional) If a selector is provided, editable will be delegated to the specified targets
		// $settings['js']['selector'] = 'a.my-class';
		// (Optional) Only applies to the type 'select2_dropdown'. Populates the available select2 dropdown values through ajax.
		// Ajax callback used is 'get_editable_ajax_options()'.
		// $settings['ajax_populate'] = true;
		return $settings;
	}

	public function save( $id, $value ) {
		set_order_status( $id, $value );
	}
}



class AC_Column_Transaction extends AC_Column {
	public function __construct() {
		$this->set_type( 'column-Transaction' );
		$this->set_label( 'ID транзакции' );
	}

	public function get_raw_value( $post_id ) {
		$transaction_id = get_lot_purchase_transaction_id( $post_id );
		if ( $transaction_id ) {
			return "<a href=" . nims_get_post_edit_url( $transaction_id ) . ">$transaction_id</a>";
		}
	}

	public function is_valid() {
		if ( $this->get_post_type() != 'lot_purchase' ) {
			return false;
		}

		return true;
	}
}

class AC_Column_Order_Sum extends AC_Column implements ACP_Column_SortingInterface {
	public function __construct() {
		$this->set_type( 'column-Order_Sum' );
		$this->set_label( 'Сумма заказа' );
	}

	public function get_value( $post_id ) {
		return format_balance( $this->get_raw_value( $post_id ), true );
	}

	public function get_raw_value( $post_id ) {
		return get_order_total( $post_id );
	}

	public function is_valid() {
		return $this->get_post_type() == 'order';
	}

	public function sorting() {
		return new ACP_Sorting_Model( $this );
	}

}

class AC_Column_User_Balance extends AC_Column {
	public function __construct() {
		$this->set_type( 'column-User_Balance' );
		$this->set_label( 'Баланс' );
	}

	public function get_value( $id ) {
		return format_balance( $this->get_raw_value( $id ), true ) . ' (' . format_balance( get_withdraw_amount( $id ), true ) . ')';
	}


	public function get_raw_value( $user_id ) {
		if ( $balance = get_user_meta( $user_id, '_user_balance', true ) ) {
			return $balance;
		}
	}
}

class AC_Column_User_Payment_Address extends AC_Column {
	public function __construct() {
		$this->set_type( 'column-User_Payment_Address' );
		$this->set_label( 'Кошелек Яндекс.Деньги' );
	}

	public function get_raw_value( $user_id ) {
		if ( $payment_yandex = user_payment_address( $user_id ) ) {
			return $payment_yandex;
		}
	}
}

class AC_Column_User_Payment_Action extends AC_Column {
	public function __construct() {
		$this->set_type( 'column-User_Payment_Action' );
		$this->set_label( 'Выплатить' );
	}

	public function get_raw_value( $user_id ) {
		if ( user_payment_address( $user_id ) && get_user_balance( $user_id ) >= min_balance_withdraw() ) {
			$paged = $_GET['paged'] ? $_GET['paged'] : 1;

			// todo fix double payment
			return '
			<button name="balance_withdraw" class="payment-withraw-user-admin" value="' . $user_id . '">Сделать выплату</button>';
		}
	}
}

function users_bulk_actions( $actions ) {
	$actions['payment'] = 'Сделать выплату';

	return $actions;
}

//add_filter( 'bulk_actions-users', 'users_bulk_actions' );