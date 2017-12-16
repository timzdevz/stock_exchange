<?php

class NMS_Log {

	static $log_file_format= "error_log.%s.log";

	public static function log_error( $msg, $module = 'payment' ) {
		$date = new DateTime( 'now', new DateTimeZone( 'Europe/Moscow' ) );
		$log_message = "[" . $date->format( 'd.m.Y H:i:s' ) . "]\t";
		$log_message .= "uid: " . get_current_user_id() . "\t";
		$log_message .= $msg . "\r\n";
		error_log( $log_message, 3, self::get_log_path($module, $date->format( 'd.m.Y' ) ) );
	}

	public static function get_log_path( $module, $date ) {

		$log_path   = trailingslashit( dirname( dirname( $_SERVER['DOCUMENT_ROOT'] ) ) );
		$log_path  .= trailingslashit( 'logs' );
		$log_path  .= trailingslashit( $module );

		// create module directory if not exists
		if ( ! file_exists( $log_path ) ) {
			mkdir($log_path, 0755, true);
		}

		$log_path  .= sprintf( self::$log_file_format, $date );

		return $log_path;
	}

};