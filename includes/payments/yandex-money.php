<?php
use \YandexMoney\API;

class YandexMoneyPayments {
	const CLIENT_ID = 'HERE_GOES_SECRET_CLIENT_ID';
	const CLIENT_SECRET = 'HERE_GOES_SECRET';

	private $redirect_uri = 'http://stockexchange.com/endpoint';
	private $scope = 'account-info operation-history operation-details payment-p2p.limit(1,100000)';

	private $access_token = null;
	public static $_INSTANCE = null;

	function __construct() {
		$this->access_token = get_option( 'yandex-access-token' );
		add_action( 'admin_init', array( $this, 'payment_actions' ) );
		self::$_INSTANCE = $this;
	}

	function request_token() {
		$auth_url = API::buildObtainTokenUrl( YandexMoneyPayments::CLIENT_ID, $this->redirect_uri, $this->scope );
		header( "Location: $auth_url" );
		exit();
	}

	function get_access_token() {
		$code = $_GET['code'];

		$access_token_response = API::getAccessToken( YandexMoneyPayments::CLIENT_ID, $code, $this->redirect_uri, YandexMoneyPayments::CLIENT_SECRET );
		if ( property_exists( $access_token_response, "error" ) ) {
			// process error
		}

		return $access_token_response->access_token;
	}

	function get_account_info() {
		$api = new API( $this->access_token );
		return $api->accountInfo();
	}

	function payment_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['get_new_token'] ) && $_POST['get_new_token'] == 'yandex_auth_token' ) {
			$this->request_token();
		} elseif ( isset( $_GET['code'] ) ) {
			$access_token = $this->get_access_token();
			if ( ! empty( $access_token ) ) {
				update_option( 'yandex-access-token', $access_token );
			}
		}
	}

	// todo: add logging system
	function pay_to( $ya_wallet_id, $amount, $message, $comment ) {
		$api = new API( $this->access_token );

		// make request payment
		$request_args = array(
			"pattern_id" => "p2p",
			"to"         => $ya_wallet_id,
			"amount"     => $amount,
			"message"    => $message, // Комментарий к переводу, отображается получателю.
			"comment"    => $comment,
		);

		if ( defined( 'DEV_ENV' ) ) {
			$request_args = array_merge($request_args, array(
				'test_payment' => "true",
				'test_result'  => "success"
			));
		}

		$request_payment = $api->requestPayment($request_args);

		if ( $request_payment->status == "success" ) {

			$process_payment_args =  array(
				"request_id" => $request_payment->request_id,
			);

			if ( defined( 'DEV_ENV' ) ) {
				$process_payment_args = array_merge($process_payment_args, array(
					'test_payment' => "true",
					'test_result'  => "success"
				));
			}

			$process_payment = $api->processPayment($process_payment_args);

			$process_payment->operation_type = 'process';
			$process_payment->request_id = $request_payment->request_id;

			return $process_payment;
		}

		if ( ! $request_payment ) {
			return false;
		}

		$request_payment->operation_type = 'request';
		return $request_payment;
	}

	public static function get_error_desc($payment_result) {
		return $payment_result->operation_type == 'request' ?
			self::get_request_payment_error_description( $payment_result->error ) :
			self::get_process_payment_error_description( $payment_result->error );

	}
	private static function get_request_payment_error_description( $error_code ) {
		switch ( $error_code ) {
			case 'illegal_params':
				$error_desc = 'Обязательные параметры платежа отсутствуют или имеют недопустимые значения.';
				break;
			case 'illegal_param_label':
				$error_desc = 'Недопустимое значение параметра label.';
				break;
			case 'illegal_param_to':
				$error_desc = 'Недопустимое значение параметра to.';
				break;
			case 'illegal_param_amount':
				$error_desc = 'Недопустимое значение параметра amount.';
				break;
			case 'illegal_param_amount_due':
				$error_desc = 'Недопустимое значение параметра amount_due.';
				break;
			case 'illegal_param_comment':
				$error_desc = 'Недопустимое значение параметра comment.';
				break;
			case 'illegal_param_message':
				$error_desc = 'Недопустимое значение параметра message.';
				break;
			case 'illegal_param_expire_period':
				$error_desc = 'Недопустимое значение параметра expire_period.';
				break;
			case 'not_enough_funds':
				$error_desc = 'На счете плательщика недостаточно средств. Необходимо пополнить счет и провести новый платеж.';
				break;
			case 'payment_refused':
				$error_desc = 'Магазин отказал в приеме платежа (например, пользователь попробовал заплатить за товар, которого нет в магазине).';
				break;
			case 'payee_not_found':
				$error_desc = 'Получатель перевода не найден. Указанный счет не существует или указан номер телефона/email, не связанный со счетом пользователя или получателя платежа.';
				break;
			case 'authorization_reject':
				$error_desc = 'В авторизации платежа отказано. Возможные причины:
транзакция с текущими параметрами запрещена для данного пользователя;
пользователь не принял Соглашение об использовании сервиса Яндекс.Деньги.';

				break;
			case 'limit_exceeded':
				$error_desc = 'Превышен один из лимитов на операции:
на сумму операции для выданного токена авторизации;
сумму операции за период времени для выданного токена авторизации;
ограничений Яндекс.Денег для различных видов операций.';

				break;
			case 'account_blocked':
				$error_desc = 'Счет пользователя заблокирован. Для разблокировки счета необходимо отправить пользователя по адресу, указанному в поле account_unblock_uri.';

				break;
			case 'ext_action_required':
				$error_desc = 'В настоящее время данный тип платежа не может быть проведен. Для получения возможности проведения таких платежей пользователю необходимо перейти на страницу по адресу ext_action_uri и следовать инструкции на данной странице. Это могут быть следующие действия:
ввести идентификационные данные, принять оферту, выполнить иные действия согласно инструкции.';
				break;

			default:
				$error_desc = 'Техническая ошибка, повторите вызов операции позднее.';

		}

		return $error_desc;
	}
	private static function get_process_payment_error_description( $error_code ) {
		switch ( $error_code ) {
			case 'contract_not_found':
				$error_desc = 'Отсутствует созданный (но не подтвержденный) платеж с заданным request_id.';
				break;
			case 'not_enough_funds':
				$error_desc = 'Недостаточно средств на счете плательщика. Необходимо пополнить счет и провести новый платеж.';
				break;
			case 'limit_exceeded':
				$error_desc = 'Превышен один из лимитов на операции:
на сумму операции для выданного токена авторизации;
сумму операции за период времени для выданного токена авторизации;
ограничений Яндекс.Денег для различных видов операций.';
				break;
			case 'money_source_not_available':
				$error_desc = 'Запрошенный метод платежа (money_source) недоступен для данного платежа.';
				break;
			case 'illegal_param_csc':
				$error_desc = 'Отсутствует или указано недопустимое значение параметра csc.';
				break;
			case 'payment_refused':
				$error_desc = 'В платеже отказано. Возможные причины:
магазин отказал в приеме платежа(запрос checkOrder);
перевод пользователю Яндекс.Денег невозможен(например, превышен лимит остатка кошелька получателя).';
				break;
			case 'authorization_reject':
				$error_desc = 'В авторизации платежа отказано. Возможные причины:
истек срок действия банковской карты;
банк-эмитент отклонил транзакцию по карте;
превышен лимит для этого пользователя;
транзакция с текущими параметрами запрещена для данного пользователя;
пользователь не принял Соглашение об использовании сервиса «Яндекс.Деньги».
';
				break;
			case 'account_blocked':
				$error_desc = 'Счет пользователя заблокирован. Для разблокировки счета необходимо отправить пользователя по адресу, указанному в поле account_unblock_uri.';
				break;
			case 'illegal_param_ext_auth_success_uri':
				$error_desc = 'Отсутствует или указано недопустимое значение параметра ext_auth_success_uri.';
				break;
			case 'illegal_param_ext_auth_fail_uri':
				$error_desc = 'Отсутствует или указано недопустимое значение параметра ext_auth_fail_uri.';
				break;

			default:
				$error_desc = 'В авторизации платежа отказано. Приложению следует провести новый платеж спустя некоторое время.';

		}

		return $error_desc;
	}

}

new YandexMoneyPayments();