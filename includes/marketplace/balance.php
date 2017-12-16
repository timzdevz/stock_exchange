<?php

class NMS_Balance {

	const WITHDRAW_NONCE_SUFFIX = 'stockexchange-withdraw-nonce-oq27eGGhf';

	public function __construct() {
		add_action( 'init', array( $this, 'topup' ) );
		add_action( 'init', array( $this, 'withdraw' ) );
		add_action( 'init', array( $this, 'order_success' ) );
	}


	function topup() {
		if ( ! isset( $_REQUEST['MERCHANT_ID'] ) || ! isset( $_REQUEST['SIGN'] ) ||
		     ! isset( $_REQUEST['AMOUNT'] ) || ! isset ( $_REQUEST['MERCHANT_ORDER_ID'] ) ) {
			return;
		}

		$FK = FreeKassa::getInstance();

		if ( ! in_array( get_the_user_ip(), $FK::ALLOWED_IP_ADDRESSES ) ) {
			die( "hacking attempt!" );
		}

		if ( ! $FK->validate_fk_signature(
			$_REQUEST['SIGN'], $_REQUEST['AMOUNT'], $_REQUEST['MERCHANT_ORDER_ID'] ) ) {
			die( "wrong sign" );
		}

		$topup_amount = (float) $_REQUEST['AMOUNT'];
		$user_id      = (int) $_REQUEST['MERCHANT_ORDER_ID'];
		if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
			NimsEmail::admin_notice(
				"Ошибка при зачислении платежа на баланс пользователя (пользватель не найден)",
				"Пользователь MERCHANT_ORDER_ID: " . strip_tags( $_REQUEST['MERCHANT_ORDER_ID'] ) . "\r\n" .
				"Дата: " . current_time( 'H:i:s d.m.Y' ) . "\r\n" .
				"Пользователь: " . $user_id . "\r\n" .
				"Сумма платежа: " . $topup_amount . "\r\n" .
				"Email плательщика: " . $_REQUEST['P_EMAIL'] );
			die( "YES" );
		}

		$fk_operation_id = $_REQUEST['intid'];
		if ( ! fk_transaction_exists( $fk_operation_id ) ) {
			add_user_balance( $user_id, $topup_amount, 'in', 'Пополнение баланса', 'intid#' . $fk_operation_id . ':' . FreeKassa::EX_CURRENCIES[ $_REQUEST['CUR_ID'] ] );
			if ( $topup_amount >= 50 ) {
				FreeKassa::getInstance()->withdraw_fk_kassa_to_wallet( $topup_amount );
			}
		}

		die( "YES" );
	}

	function withdraw() {
		if ( ! isset( $_POST['withdraw-nonce'] ) ) {
			return;
		}

		$user_id         = bp_displayed_user_id();
		$current_user_id = get_current_user_id();

		if ( ! $user_id || ( $current_user_id != $user_id && ! is_super_admin( $current_user_id ) ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['withdraw-nonce'], $user_id . self::WITHDRAW_NONCE_SUFFIX ) ) {
			die( 'Security check' );
		}

		$min_balance_withdraw = min_balance_withdraw();
		$user_balance         = get_user_balance( $user_id );
		$payment_amount       = (float) $_POST['withdraw_amount'];



		$user_wallet_id = isset( $_POST['wallet'] ) ? $_POST['wallet'] : null;
		$user_wallet    = self::get_withdraw_wallets( $user_wallet_id );
		if ( ! $user_wallet ) {
			bp_core_add_message( "Выберите кошелек для вывода", 'error' );
			nims_redirect();
		}

		if ( isset($user_wallet['withdraw_min'] ) ) {
			$min_balance_withdraw = $user_wallet['withdraw_min'];
		}

		if ( $payment_amount < $min_balance_withdraw || $user_balance < $min_balance_withdraw ) {
			bp_core_add_message( "Минимальная сумма для выплаты на {$user_wallet['name']} равна {$min_balance_withdraw} &#8381;", 'error' );
			nims_redirect();
		}

		if ( $user_balance < $payment_amount ) {
			bp_core_add_message(
				"Недостаточно средств на балансе. Доступная сумма для вывода: $user_balance &#8381;", 'error' );
			nims_redirect();
		}

		$user_wallet_val = isset( $_POST[ $user_wallet_id ] ) ? $_POST[ $user_wallet_id ] : false;
		if ( ! $user_wallet_val ||
		     isset( $user_wallet['pattern'] ) && ! preg_match( '#' . $user_wallet['pattern'] . '#i', $user_wallet_val ) ) {

			$err_msg = isset( $user_wallet['pattern_desc'] ) ? $user_wallet['pattern_desc'] : '';
			bp_core_add_message( "Неправильное значение кошелька. " . $err_msg, 'error' );
			nims_redirect();
		}

		$purse = sanitize_text_field( $user_wallet_val );
		self::user_wallets( $user_id, array( $user_wallet_id => $purse ) );
		update_user_meta( $user_id, '_primary_wallet', $user_wallet_id );

		if ( $this->withdraw_user( $user_id, $payment_amount, $user_wallet, $purse ) ) {
			bp_core_add_message( "Заявка на вывод успешно отправлена! Платёж придет в течение 0-12 часов", "success" );
		} else {
			bp_core_add_message( "Произошла ошибка при запросе на вывод. Пожалуйста, " .
			                     "попробуйте снова или обратитесь в поддержку сайта.", "error" );
		}
		nims_redirect();
	}

	function order_success() {
		if ( ! empty( $_POST ) && count( $_POST ) == 1 && isset( $_POST['MERCHANT_ORDER_ID'] ) &&
		     ( $user_id = (int) $_POST['MERCHANT_ORDER_ID'] ) && is_user_logged_in() ) {
			bp_core_add_message( "Вы успешно пополнили баланс!", 'success' );
			wp_safe_redirect( bp_core_get_user_domain( $user_id ) . 'orders/' );
			exit();
		}
	}

	public function withdraw_user( $user_id, $payment_amount, $user_wallet,  $purse ) {
		FreeKassa::getInstance()->withdraw_fk_kassa_to_wallet( $payment_amount, false );

		$currency_id = $user_wallet['currency_id'];
		$commission = $user_wallet['comm'];

		if ( $commission == 0 ) {
			$final_payment_amount = $payment_amount;
		} else {
			$final_payment_amount = round( $payment_amount * ( 1 - ( $commission / 100) ), 2 );
		}

		$payment_desc = "Вывод средств пользователя #" . $user_id;
		$payment_id   = FreeKassa::getInstance()->withdraw_from_fk_wallet(
			$purse, $final_payment_amount, $currency_id, $payment_desc );

		if ( ! is_int( $payment_id ) ) {
			return false;
		}

		$currency_name = FreeKassa::EX_CURRENCIES[ $currency_id ];
		$method = 'withdrawId#' . $payment_id . ':' . $currency_name;

		subtract_user_balance( $user_id, $payment_amount, 'out', "Вывод средств " . $purse, $method );

		// logging all payments with $final payment amount
		//	NMS_Log::log_error( "вывод средств на $currency_name, запрос вывода $payment_amount р.," .
		// " с комиссией $final_payment_amount р., комиссия - $commission%, кошелек $purse" );

		return true;
	}

	public function withdraw_stock( $payment_amount, $currency_id,  $purse ) {

		$payment_id   = FreeKassa::getInstance()->withdraw_from_fk_wallet(
			$purse, $payment_amount, $currency_id, "Вывод коммиссионных" );

		if ( ! is_int( $payment_id ) ) {
			return false;
		}

		$transaction_id =
			add_transaction( 1, 'commission_out', $payment_amount, "Вывод коммиссионных #$payment_id", null );


		// logging all payments with $final payment amount
		//	NMS_Log::log_error( "вывод средств на $currency_name, запрос вывода $payment_amount р.," .
		// " с комиссией $final_payment_amount р., комиссия - $commission%, кошелек $purse" );

		return $transaction_id;
	}



	public static function get_withdraw_wallets( $wallet_id = null ) {
		$wallets = array(
			'yandex_money' => array(
				'currency_id' => 45,
				'name'        => 'Яндекс.Деньги',
				'comm'        => 0,
				'attr'        => array( 'placeholder' => '410011729822xxx' )
			),

			'qiwi' => array(
				'currency_id'  => 63,
				'name'         => 'Qiwi',
				'comm'         => 5,
				'withdraw_min' => 106,
				'pattern'      => $qiwi_pattern = '^\+\d{8,}$',
				'pattern_desc' => 'Только цифры и знак +. Минимум 8 цифр.',
				'attr'         => array(
					'placeholder'             => '+7910123xxxx',
					'data-parsley-pattern-if' => $qiwi_pattern,
				)
			),

			'wmr' => array(
				'currency_id'  => 1,
				'name'         => 'Webmoney (WMR)',
				'comm'         => 4.5,
				'pattern'      => $wmr_pattern = '^R\d{12}$',
				'pattern_desc' => 'Формат кошелька должен быть буква R и 12 цифр',
				'attr'         => array(
					'placeholder'             => 'R12345678xxxx',
					'data-parsley-pattern-if' => $wmr_pattern
				)
			)
		);

		if ( $wallet_id != null ) {
			return isset( $wallets[ $wallet_id ] ) ? $wallets[ $wallet_id ] : false;
		}

		return $wallets;
	}


	public static function user_wallets( $uid, $wallets = array() ) {
		if ( empty( $wallets ) ) {
			return (array) get_user_meta( $uid, 'user_wallets', true );
		}

		if ( ! is_array( $wallets ) ) {
			throw new InvalidArgumentException( "wallets param should be array" );
		}

		$existing_wallets = self::user_wallets( $uid );

		return update_user_meta( $uid, 'user_wallets', array_merge( $existing_wallets, $wallets ) );
	}

	function marketplace_balance( $set_amount = null ) {
		if ( $set_amount !== null ) {
			return update_option( '_marketplace_balance', $set_amount );
		}

		return get_option( '_marketplace_balance', 0 );
	}

	function marketplace_balance_operation( $amount, $operation = '+' ) {
		$current_balance = $this->marketplace_balance();
		$final_balance   = $operation == '+' ?
			$current_balance + $amount :
			$current_balance - $amount;

		$this->marketplace_balance( $final_balance );
	}

	function get_min_topup_balance() {
		return get_option( 'min_topup_balance', 10 );
	}
}

global $NMS_Balance;
$NMS_Balance = new NMS_Balance();