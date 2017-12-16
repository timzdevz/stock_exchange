<?php

class FreeKassa {

	const CASH_URL = "//www.free-kassa.ru/merchant/cash.php";
	const KASSA_API_URL = "https://www.free-kassa.ru/api.php";
	const WALLET_API_URL = 'https://wallet.free-kassa.ru/api_v1.php';

	public $wallet_id;
	private $wallet_api_key;

	public $merchant_id;
	public $secret_1;
	public $secret_2;

	private static $_instance;

	public function __construct() {
		$this->merchant_id    = get_option( 'fk_merchant_id', 00000 );
		$this->secret_1       = get_option( 'fk_secret_1', 'secret_1' );
		$this->secret_2       = get_option( 'fk_secret_2', 'secret_2' );
		$this->wallet_id      = get_option( 'fk_wallet_id', 'F00000000' );
		$this->wallet_api_key = get_option( 'fk_wallet_api_key', 'WALLET_API_KEY' );
	}

	function get_fk_signature( $order_amount, $order_id, $secret = null ) {
		$secret = $secret === null ? $this->secret_1 : $secret;

		return md5( $this->merchant_id . ':' . $order_amount . ':' . $secret . ':' . $order_id );
	}

	function validate_fk_signature( $signature, $amount, $merchant_order_id ) {
		return $signature == $this->get_fk_signature( $amount, $merchant_order_id, $this->secret_2 );
	}

	function  get_fk_kassa_balance( $format = true ) {
		$xml_response = $this->api_request( self::KASSA_API_URL, array(
			'merchant_id' => $this->merchant_id,
			's'           => md5( $this->merchant_id . $this->secret_2 ),
			'action'      => 'get_balance'
		) );

		try {
			$balance_data = @new SimpleXMLElement( $xml_response );
			if ( ! empty( $balance_data->balance) ) {
				$balance =  current( $balance_data->balance );

				return $format ? format_balance($balance, true ) : $balance;
			} else {
				throw new Exception( $xml_response );
			}

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	function get_fk_wallet_balance() {
		$json_response = $this->api_request( self::WALLET_API_URL, array(
			'wallet_id' => $this->wallet_id,
			'sign'      => md5( $this->wallet_id . $this->wallet_api_key ),
			'action'    => 'get_balance'
		), 'POST' );

		try {
			$response_obj = json_decode( $json_response );

			// example: {"status":"info","desc":"Wallet balance","data":{"RUR":"50.00","USD":"0.00","EUR":"0.00"}}

			if ( $response_obj->data->RUR ) {
				return $response_obj->data->RUR;
			} else {
				throw new Exception( $json_response );
			}

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	public function withdraw_fk_kassa_to_wallet( $payment_amount, $log_error = true ) {
		$xml_response = $this->api_request( self::KASSA_API_URL, array(
			'merchant_id' => $this->merchant_id,
			'currency'    => 'fkw',
			'amount'      => $payment_amount,
			's'           => md5( $this->merchant_id . $this->secret_2 ),
			'action'      => 'payment'
		) );

		try {
			$response = @( new SimpleXMLElement( $xml_response ) );
			if ( $response->answer == 'error' ) {
				throw new Exception( $response->desc );
			}

			$payment_id = (int) $response->PaymentId;
			if ( ! $payment_id ) {
				throw new Exception( "no payment id. Response data: " . $xml_response );
			}

			return true;

		} catch ( Exception $e ) {
			$log_msg = "Вывод с кассы на кошелек $payment_amount Р: " . $e->getMessage() . "," .
			           "response: " . $xml_response;

			if ( $log_error ) {
				NMS_Log::log_error( $log_msg );
			}

			return $log_msg;
		}
	}

	public function withdraw_from_fk_wallet( $purse, $payment_amount, $currency_id, $desc = "" ) {

		$json_response = $this->api_request( self::WALLET_API_URL, array(
			'wallet_id' => $this->wallet_id,
			'purse'     => $purse,
			'amount'    => $payment_amount,
			'desc'      => @iconv("UTF-8", "CP1251", $desc),
			'currency'  => $currency_id,
			'sign'      => md5(
				$this->wallet_id . $currency_id . $payment_amount . $purse . $this->wallet_api_key ),
			'action'    => 'cashout'
		), 'POST' );

		try {
			$response_obj = json_decode( $json_response );

			// {"status":"info","desc":"Payment send","data":{"payment_id":"543273"}}
			if ( (int) $response_obj->data->payment_id ) {
				return (int) $response_obj->data->payment_id;
			} else {
				throw new Exception();
			}

		} catch ( Exception $e ) {
			$currency_name = self::EX_CURRENCIES[ $currency_id ];
			NMS_Log::log_error(
				"Вывод средств: $payment_amount Р, $currency_name, $purse. Ошибка: " . $e->getMessage() .
				", response: " . $json_response );

			return false;
		}
	}


	private function api_request( $api_url, $data, $method = 'GET' ) {
		if ( ! in_array( $method, array( 'GET', 'POST' ) ) ) {
			throw new InvalidArgumentException( "Only GET and POST methods allowed" );
		}

		$ch       = curl_init();
		$curl_url = $api_url;

		if ( $method == 'GET' ) {
			$query_params = http_build_query( $data );
			$curl_url     .= '?' . $query_params;
		} else {
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		}

		curl_setopt( $ch, CURLOPT_URL, $curl_url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );

		$result = trim( curl_exec( $ch ) );
//		$c_errors = curl_error($ch);

		curl_close( $ch );

		return $result;
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new FreeKassa();
		}

		return self::$_instance;
	}

	const ALLOWED_IP_ADDRESSES = array(
		'136.243.38.147',
		'136.243.38.149',
		'136.243.38.150',
		'136.243.38.151',
		'136.243.38.189',
		'88.198.88.98'
	);

	const EX_CURRENCIES = array(
		'1'   => 'WebMoney WMR',
		'2'   => 'WebMoney WMZ',
		'3'   => 'WebMoney WME',
		'45'  => 'Яндекс.Деньги',
		'60'  => 'OKPAY RUB',
		'61'  => 'OKPAY EUR',
		'62'  => 'OKPAY USD',
		'63'  => 'QIWI кошелек',
		'64'  => 'Perfect Money USD',
		'67'  => 'VISA/MASTERCARD UAH',
		'69'  => 'Perfect Money EUR',
		'70'  => 'PayPal',
		'79'  => 'Альфа-банк RUR',
		'80'  => 'Сбербанк RUR',
		'82'  => 'Мобильный Платеж Мегафон',
		'83'  => 'Мобильный Платеж Билайн',
		'84'  => 'Мобильный Платеж МТС',
		'87'  => 'OOOPAY USD',
		'94'  => 'VISA/MASTERCARD RUB',
		'99'  => 'Терминалы России',
		'102' => 'Z-Payment',
		'106' => 'OOOPAY RUR',
		'109' => 'OOOPAY EUR',
		'110' => 'Промсвязьбанк',
		'112' => 'Тинькофф Кредитные Системы',
		'114' => 'PAYEER RUB',
		'116' => 'Bitcoin',
		'117' => 'Денежные переводы',
		'118' => 'Салоны связи',
		'121' => 'WMR',
		'124' => 'VISA/MASTERCARD EUR',
		'130' => 'WMR-bill',
		'131' => 'WMZ-bill',
		'132' => 'Мобильный Платеж Tele2',
		'133' => 'FK WALLET RUB',
		'136' => 'eCoin',
		'137' => 'Мобильный Платеж МегаФон Северо-Западный филиал',
		'138' => 'Мобильный Платеж МегаФон Сибирский филиал',
		'139' => 'Мобильный Платеж МегаФон Кавказский филиал',
		'140' => 'Мобильный Платеж МегаФон Поволжский филиал',
		'141' => 'Мобильный Платеж МегаФон Уральский филиал',
		'142' => 'Мобильный Платеж МегаФон Дальневосточный филиал',
		'143' => 'Мобильный Платеж МегаФон Центральный филиал',
		'147' => 'Litecoin',
		'150' => 'AdvCash',
		'153' => 'VISA/MASTERCARD+ RUB',
		'154' => 'Skin pay',
		'155' => 'QIWI WALLET',
		'156' => 'QIWI RUB',
		'157' => 'VISA UAH CASHOUT',
	);
}