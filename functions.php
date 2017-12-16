<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*-----------------------------------------------------------------------------------*/
/* Start WooThemes Functions - Please refrain from editing this section */
/*-----------------------------------------------------------------------------------*/
add_filter( 'wf_disable_generator_tags', '__return_true');

// WooFramework init
require_once( get_template_directory() . '/functions/admin-init.php' );

/*-----------------------------------------------------------------------------------*/
/* Load the theme-specific files, with support for overriding via a child theme.
/*-----------------------------------------------------------------------------------*/

$includes = array(
	'includes/theme-options.php',            // Options panel settings and custom settings
	'includes/theme-functions.php',        // Custom theme functions
	'includes/theme-actions.php',            // Theme actions & user defined hooks
	'includes/theme-comments.php',            // Custom comments/pingback loop
	'includes/theme-js.php',                // Load JavaScript via wp_enqueue_script
	'includes/sidebar-init.php',            // Initialize widgetized areas
	'includes/theme-widgets.php',            // Theme widgets
	'includes/theme-plugin-integrations.php'    // Plugin integrations
);

// Allow child themes/plugins to add widgets to be loaded.
$includes = apply_filters( 'woo_includes', $includes );

foreach ( $includes as $i ) {
	locate_template( $i, true );
}

if ( is_woocommerce_activated() ) {
	locate_template( 'includes/theme-woocommerce.php', true );
}

/*-----------------------------------------------------------------------------------*/
/* You can add custom functions below */
/*-----------------------------------------------------------------------------------*/
//register_nav_menus( array( 'top-menu (right)' => __( 'Top Menu Right', 'woothemes' ) ) );

//setlocale(LC_ALL, 'ru_RU.UTF-8');


add_action('init', 'stockexchange_session_start', 1);
add_action('wp_logout', 'stockexchange_session_end' );
add_action('wp_login', 'stockexchange_session_end' );

function stockexchange_session_start() {
	if(!session_id()) {
		session_start();
	}
}

function stockexchange_session_end() {
	session_destroy();
}


// logging system
require_once( 'includes/marketplace/log.php' );

require_once( 'includes/buddypress/buddypress.php' );
require_once( 'includes/marketplace/marketplace-actions.php' );
require_once( 'includes/marketplace/admin/marketplace-settings.php' );
require_once( 'includes/marketplace/admin/marketplace-transactions.php' );

// main modules
require_once( 'includes/marketplace/notifications.php' );
require_once( 'includes/marketplace/order-review.php' );
require_once( 'includes/marketplace/order.php' );
require_once( 'order-comment.php' );
require_once( 'includes/marketplace/transaction.php' );
require_once( 'includes/marketplace/user.php' );
require_once( 'includes/marketplace/bot.php' );
require_once( 'includes/marketplace/shop-settings.php' );
require_once( 'includes/marketplace/balance.php' );

require_once( 'includes/marketplace/ajax.php' );
require_once( 'includes/marketplace/cron.php' );
require_once( 'includes/acp/admin-columns-custom.php' );

require_once 'includes/payments/freekassa.php';
// yandex money
/*require_once 'includes/payments/yandex-money-sdk/lib/api.php';
require_once 'includes/payments/yandex-money-sdk/lib/external_payment.php';
require_once 'includes/payments/yandex-money.php';*/

// freekassa

//require_once 'includes/migrate/sql-queries.php';

add_action('init', function() {
	unregister_post_type( 'slide' );
});

add_filter( 'wf_add_blog_name_to_title', 'stockexchange_seo_title' );
function stockexchange_seo_title( $site_title  ) {
	if ( is_front_page() ) {
		return "Купить нимы - покупка и продажа нимов на бирже stockexchange.com";
	}

	return "Покупка и продажа нимов на бирже stockexchange.com";
}


function get_marketplace_commission( $for_calc = true ) {
	$commission = get_option( 'stock_commission', 10);

	if ( $for_calc ) {
		return 1 - $commission / 100;
	}

	return $commission;
}

function dateDifference( $date_1, $date_2, $retformat = '%a' ) {
	$datetime1 = date_create( $date_1 );
	$datetime2 = date_create( $date_2 );

	$interval = date_diff( $datetime1, $datetime2 );

	return $interval->format( $retformat );
}

function do_pagination( $custom_query = null, $args = array() ) {
	if ( ! $custom_query ) {
		global $wp_query;
		$custom_query = $wp_query;
	}

	$default_args = array(
		'base'      => str_replace( 999999999, '%#%', get_pagenum_link( 999999999 ) ),
		'format'    => "/page/%#%",
		'total'     => $custom_query->max_num_pages,
		'current'   => ( get_query_var( "paged" ) ) ? get_query_var( "paged" ) : 1,
		'mid_size'  => 4,
		'end_size'  => 1,
		'prev_text' => __( '« сюда' ),
		'next_text' => __( 'туда »' ),
		'type'      => "list"
	);

	$args = wp_parse_args( $default_args, $args );

	$links = paginate_links( $args );

	if ( $links ) {
		$links = str_replace( 'class=\'page-numbers\'', 'class=\'pagination\'', $links );
		echo '<div class="pagination-centered">';
		echo $links . '</div>';
	}

	$custom_query = null;
}


function bbp_pagination( $custom_query = null) {
	ob_start();
	do_pagination( $custom_query );
	$pagination = ob_get_contents();
	ob_get_clean();

	$pagination = preg_replace( '#\/page\/(\d+)[\/]?#suix', '/?upage=$1', $pagination );
	$pagination = preg_replace( '#(\?upage\=\d+)\?upage\=\d+#suix', '$1', $pagination );

	echo $pagination;
}


function nims_redirect() {
	wp_redirect( bp_get_requested_url() );
	exit();
}

function nims_get_post_edit_url( $post_id, $activate_lot = false ) {
    $url = admin_url( 'post.php?post=' . $post_id ) . '&action=edit';
	if ( $activate_lot ) {
		$url .= '&lot_activate=true';
	}
	return $url;
}

function get_marketplace_stats() {
    global $NMS_Shop_Settings;
	$sellers = get_users(wp_parse_args( 'meta_key=user_sells_nims&meta_value=1' ));

	if ( ! empty ( $sellers ) ) {
		$prices = array();
		$nim_count = array();

		foreach ( $sellers as $seller ) {
			$prices[] = $NMS_Shop_Settings->get_user_shop_nim_price( $seller->ID );
			$nim_count[] = $NMS_Shop_Settings->get_user_sell_nim_amount( $seller->ID );
		}

		$nim_count_sum = number_format( array_sum( $nim_count ), 0, '.', ' ' );

		$min = format_balance( min( $prices ) );
		$avg = format_balance( array_sum( $prices ) / count($prices) );
		$max = format_balance( max( $prices ) );


		// count completed purchases today
		return array(
			'min'             => $min,
			'avg'             => $avg,
			'max'             => $max,
			'nim_count_sum'   => $nim_count_sum,
//          'completed_today' => $completed_today
        );
	} else {
		return 0;
	}
}

/**
 * Restrict access to the administration screens.
 */
add_action( 'admin_init', 'restrict_admin_with_redirect', 1 );
function restrict_admin_with_redirect() {

	if ( ! current_user_can( 'manage_options' ) && ( ! wp_doing_ajax() ) ) {
		wp_redirect( site_url() );
		exit;
	}
}

add_action( 'after_setup_theme', 'remove_admin_bar' );
function remove_admin_bar() {
	if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
		show_admin_bar( false );
	}
}

add_action( 'wp_logout', 'redirect_to_home_logout' );
function redirect_to_home_logout() {
	$redirect_to = home_url();
	wp_redirect( $redirect_to );
	exit();
}


add_action( 'wp_footer', 'nims_scripts_footer' );

function nims_scripts_footer() { ?>
    <script src="<?php echo get_template_directory_uri(); ?>/includes/js/stock.js?v=1.363" defer></script>
	<?php
}

//add_action( 'wp_enqueue_scripts', 'enq_styles', 10 );
function enq_styles() {
	wp_dequeue_style('theme-stylesheet');
	wp_deregister_style( 'theme-stylesheet' );


	wp_register_style( 'theme-stylesheet', get_stylesheet_directory_uri() . '/style.css', false, '1.0.0' );
	wp_enqueue_style( 'theme-stylesheet' );
}

add_action( 'wp_head', 'nims_scripts_header' );
function nims_scripts_header() {
    ?>
    <!-- Chatra {literal} -->
    <script>
        (function(d, w, c) {
            w.ChatraID = 'xwh2BiYXi88Rz48dm';
            var s = d.createElement('script');
            w[c] = w[c] || function() {
                    (w[c].q = w[c].q || []).push(arguments);
                };
            s.async = true;
            s.src = (d.location.protocol === 'https:' ? 'https:': 'http:')
                + '//call.chatra.io/chatra.js';
            if (d.head) d.head.appendChild(s);
        })(document, window, 'Chatra');
    </script>
    <!-- /Chatra {/literal} -->
	<?php
}

//add_action( 'admin_bar_menu', 'stock_admin_bar_btns', 100 );
function stock_admin_bar_btns( $wp_admin_bar ) {
	$title              = 'Лоты на проверку';
	$total_lots_process = user_total_lots( null, true, array(
		'meta_key'   => '_lot_status_select',
		'meta_value' => 'process'
	) )->post_count;
	if ( $total_lots_process > 0 ) {
		$title .= ' (' . $total_lots_process . ')';
	}
	$args = array(
		'id'    => 'lots-in-process-btn',
		'title' => $title,
		'href'  => admin_url( 'edit.php?s&post_status=all&post_type=lot&acp_filter%5B595a554b35b8e%5D=cHJvY2Vzcw%3D%3D' ),

		'meta' => array(
			'target' => '_blank',
			'class'  => 'lots-in-process-btn'
		)
	);
	$wp_admin_bar->add_node( $args );

	$paid_purchase_lot = get_orders(array(
	        'meta_key' => '_lot_purchase_status_select',
            'meta_value' => 'paid',
            'no_found_rows' => true,
    ));

	if ( $paid_purchase_lot->post_count > 0) {
		$purchase_paid_title = "Передать нимы оплаченным лотам ($paid_purchase_lot->post_count)";
		$wp_admin_bar->add_node( array(
			'id'    => 'paid-lot-purchases',
			'title' => $purchase_paid_title,
			'href'  => admin_url( 'edit.php?s&post_status=all&post_type=lot_purchase&acp_filter%5B595b52fdd8ead%5D=cGFpZA%3D%3D' ),

			'meta' => array(
				'target' => '_blank',
				'class'  => 'purchases'
			)
        ) );
	}

}


function isValidTimeStamp($timestamp)
{
	return ((string) (int) $timestamp === $timestamp)
	       && ($timestamp <= PHP_INT_MAX)
	       && ($timestamp >= ~PHP_INT_MAX);
}

function getNumEnding( $number, $endingArray, $inc_number = true ) {
	$input_number = (float) $number;
	$number       = $number % 100;
	if ( $number >= 11 && $number <= 19 ) {
		$ending = $endingArray[2];
	} else {
		$i = $number % 10;
		switch ( $i ) {
			case ( 1 ):
				$ending = $endingArray[0];
				break;
			case ( 2 ):
			case ( 3 ):
			case ( 4 ):
				$ending = $endingArray[1];
				break;
			default:
				$ending = $endingArray[2];
		}
	}
	if ( $inc_number ) {
		return $input_number . ' ' . $ending;
	}

	return $ending;
}

function get_sell_nims_url() {
	$shop_settings_url = bp_core_get_user_domain(get_current_user_id()) . 'shop-settings/';
	$sell_nims_url = is_user_logged_in() ? $shop_settings_url : wp_login_url( '/shop/' ) . '&sell_needs_auth=true';
	return $sell_nims_url;
}

add_action( 'init', 'disable_admin_new_user_notification', 120 );
function disable_admin_new_user_notification() {
	remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
	add_action( 'register_new_user', 'wpse236122_send_new_user_notifications', 999, 2 );
};

function wpse236122_send_new_user_notifications(  $user_id, $notify = 'user' )
{
	wp_send_new_user_notifications( $user_id, $notify );
}

//add_action( 'init', 'enable_desktop_site' );
function enable_desktop_site() {
    if (isset($_GET['desktop_site'])) {
        $_COOKIE['desktop_ver'] = 1;
    } elseif ( isset( $_GET['mobile_site'] ) ) {
        $_COOKIE['desktop_ver'] = 0;
    }
}

function stockexchange_remove_version() {
	return '';
}
add_filter( 'the_generator', 'stockexchange_remove_version' );


function get_the_user_ip() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}


add_filter( 'comments_number', 'russify_comments_number' );

function russify_comments_number( $zero = false, $one = false, $more = false, $deprecated = '' ) {
	global $id;
	$number = get_comments_number( $id );

	if ( $number == 0 ) {
		$output = 'Комментариев нет';
	} elseif ( $number == 1 ) {
		$output = 'Один комментарий';
	} elseif ( ( $number > 20 ) && ( ( $number % 10 ) == 1 ) ) {
		$output = str_replace( '%', $number, '% комментарий' );
	} elseif ( ( ( $number >= 2 ) && ( $number <= 4 ) ) || ( ( ( $number % 10 ) >= 2 ) && ( ( $number % 10 ) <= 4 ) ) && ( $number > 20 ) ) {
		$output = str_replace( '%', $number, '% комментария' );
	} else {
		$output = str_replace( '%', $number, '% комментариев' );
	}
	echo apply_filters( 'russify_comments_number', $output, $number );
}



function br2nl($string)
{
	return preg_replace('/\<br(\s*)?\/?\>/i', "\r\n", $string);
}

/**
 * Get current requested URL for putting in the link
 * @return string
 */
function get_current_url() {
	$url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	return  htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
}

function stockexchange_meta_fields() {
    global $NMS_Shop_Settings;
	$post_id = get_the_ID();
	$site_desc = false;

	if ( get_current_url() == str_replace(array('http:', 'https:'), '', home_url( '/blog/' ) ) ) {
		$post_id = 291;
	}

	if ( ! $post_id && bp_get_requested_url() == home_url('/shop/') ) {
        $post_id = 49;
	}

	if ( $post_id ) {
		$site_desc = get_post_meta( $post_id, 'site_description', true );
	}

/*	if ( bp_is_user_profile() ) {
		$site_desc = $NMS_Shop_Settings->get_user_shop_description( bp_displayed_user_id() );
	}*/

	if ( is_tag() || bp_is_user() ) {
	    ?>
        <meta name="robots" content="noindex" />
        <?php
    }

	if ( is_category() && function_exists('wp_get_terms_meta') )
	{
		$category = get_category( get_query_var( 'cat' ) );
		$cat_id = $category->cat_ID;

		$cat_meta = wp_get_terms_meta($cat_id, 'cat_meta_description', true);
		if ( $cat_meta ) {
			$site_desc = $cat_meta;
		}
	}

	if ( $site_desc ) { ?>
        <meta name="description"
              content="<?php echo esc_attr( mb_strlen($site_desc) > 300 ? substr($site_desc, 0, 300) . "..." : $site_desc ); ?>" >
	<?php  }
}
/*-----------------------------------------------------------------------------------*/
/* Don't add any code below here or the sky will fall down */
/*-----------------------------------------------------------------------------------*/

add_action('admin_head', 'my_custom_fonts');

function my_custom_fonts() { ?>
<style>
.nim-coin-icon:before {
content: '';
display: inline-block;
background: url('<?php echo trailingslashit(get_template_directory_uri()) ?>images/nims-coin.png') no-repeat;
vertical-align: middle;
width: 20px;
height: 22px;
padding-right: 3px;
}

.nim-coin-icon-small:before {
background-size: contain;
width: 15px;
height: 16px;
}

table.form-table.stock-earnings-by-day {
    width: auto;
}

.stock-earnings-by-day th {
    font-weight: normal !important;
}

.stock-earnings-by-day th,
.stock-earnings-by-day td {
    border: 1px solid black;
    padding: 10px;
}
  </style>
<?php }


function custom_excerpt_length( $length ) {
	return 20;
}


?>