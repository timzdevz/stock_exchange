<?php
/**
 * Sensei Logic
 */

/**
 * Declare that your theme now supports Sensei
 */
add_action( 'after_setup_theme', 'theonepager_sensei_support' );
function theonepager_sensei_support() {
    add_theme_support( 'sensei' );
}

if ( ! function_exists( 'woo_sensei_css' ) ) {
	/**
	 * Sensei css
	 * Enqueues Sensei CSS
	 */
	function woo_sensei_css() {
		wp_register_style( 'woo-sensei-css', get_template_directory_uri() . '/includes/integrations/sensei/css/sensei.css' );
		wp_enqueue_style( 'woo-sensei-css' );
	}
}

if ( ! function_exists( 'woo_sensei_remove_pagination' ) ) {
	/**
	 * Sensei pagination
	 * Removes Sensei pagination
	 */
	function woo_sensei_remove_pagination() {
		global $woothemes_sensei;
		remove_action( 'sensei_pagination', array( $woothemes_sensei->frontend, 'sensei_output_content_pagination' ), 10 );
	}
}

if ( ! function_exists( 'woo_sensei_remove_wrappers' ) ) {
	/**
	 * Sensei wrappers
	 * Removes Sensei wrappers
	 */
	function woo_sensei_remove_wrappers() {
		global $woothemes_sensei;
		remove_action( 'sensei_before_main_content', array( $woothemes_sensei->frontend, 'sensei_output_content_wrapper' ), 10 );
		remove_action( 'sensei_after_main_content', array( $woothemes_sensei->frontend, 'sensei_output_content_wrapper_end' ), 10 );
	}
}