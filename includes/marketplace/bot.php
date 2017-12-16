<?php

class stockexchangeBot {

	const stockexchange_BOT_LOGIN = 'stockexchange_bot';
	const stockexchange_BOT_DISPLAY = 'stockexchange.com';
	const stockexchange_BOT_PASS = '3S;kEKU&8';

	private static $_instance = null;

	private $bot;

	private function __construct() {
		$this->bot();
		add_action( 'after_switch_theme', array( $this, 'create_bot' ) );
	}

	function create_bot() {
		if ( $this->bot() ) {
			return false;
		}

		$bot = wp_insert_user( array(
			'user_login'   => self::stockexchange_BOT_LOGIN,
			'display_name' => self::stockexchange_BOT_DISPLAY,
			'user_email'   => 'bot@stockexchange.com',
			'user_pass'    => wp_hash_password( self::stockexchange_BOT_PASS ),
		) );

		if ( is_wp_error( $bot ) ) {
			echo "Error creating stockexchange bot: " . $bot->get_error_message();

			return false;
		}

		return true;
	}

	function bot() {
		if ( ! $this->bot ) {
			$this->bot = get_user_by( 'login', self::stockexchange_BOT_LOGIN );
		}

		return $this->bot;
	}

	function add_order_message( $order_id, $message, $comment_meta = array() ) {
		$defaults = array(
			'user_id'          => $this->bot()->ID,
			'comment_post_ID'  => $order_id,
			'comment_content'  => $message,
			'comment_approved' => 1
		);

//		$insert_comment_args = wp_parse_args( $comment_args, $defaults );

		$comment_id = wp_insert_comment( $defaults );
		if ( ! $comment_id ) {
			return false;
		}

		if ( ! empty( $comment_meta ) ) {

			foreach ( $comment_meta as $meta_key => $meta_value ) {
				update_comment_meta( $comment_id, $meta_key, $meta_value );
				NMS_Log::log_error( "oid: $order_id, message: $message, comment_id = {$comment_id}; key = {$meta_key}; value = {$meta_value};\r\n", 'comment' );
			}
		}

		return $comment_id;
	}

	function order_status_change( $order_id, $status, $extra_message = "" ) {
		$message = "<br><br>Статус заказа изменен на:<br> <b><i>{$GLOBALS['ORDER_STATUSES'][$status]}</i></b>";
		if ( $extra_message ) {
			$message = $extra_message . "<br>" . $message;
		}

		$this->add_order_message( $order_id, $message, array( 'order_status' => $status ) );
	}

	function add_user_review( $reviewer_id, $user_id, $order_id, $message, $rating ) {
		global /** @var BP_Member_Reviews $BP_Member_Reviews */
		$BP_Member_Reviews;

		$rating = (int) $rating;

		$post = array(
			'post_type'   => $BP_Member_Reviews->post_type,
			'post_status' => 'publish',
			'post_author' => $reviewer_id,
		);

		$stars       = $BP_Member_Reviews->settings['stars'];
		$review_meta = array(
			'user_id'  => $user_id,
			'stars'    => $stars,
			'type'     => 'single',
			'guest'    => false,
			'average'  => ( $rating / $stars ) * 100,
			'order_id' => $order_id,
			'review'   => esc_textarea( $message ),
			'by_bot'   => true
		);

		$review_id = wp_insert_post( $post );

		foreach ( $review_meta as $key => $value ) {
			if ( is_string( $value ) ) {
				$value = trim( $value );
			}
			update_post_meta( $review_id, $key, $value );
		}
	}


	function is_bot( $user_id ) {
		return $this->bot()->ID == $user_id;
	}


	/**
	 * @return stockexchangeBot|null
	 */
	static public function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

// run immediately to create instance and attach action hooks
stockexchangeBot::getInstance();