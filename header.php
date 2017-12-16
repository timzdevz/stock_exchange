<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Header Template
 *
 * Here we setup all logic and XHTML that is required for the header section of all screens.
 *
 * @package WooFramework
 * @subpackage Template
 */
 
 global $woo_options, $woocommerce;

 $settings = array(
	'header_content' => ''
 );
	
 $settings = woo_get_dynamic_values( $settings );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="telderi" content="c4bcf1012282de3faa79d50b62194231" />
<title><?php woo_title(); ?></title>
<?php stockexchange_meta_fields(); ?>
<?php woo_meta(); ?>
<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>" />
<?php
wp_head();
woo_head();
?>
</head>
<body <?php body_class(); ?>>
<?php woo_top(); ?>
<?php if (preg_match( '/(?i)msie [4-8]/i', @$_SERVER['HTTP_USER_AGENT'] ) ) { ?>
<!--[if lt IE 9]>
<p class="browserupgrade">Вы используете <strong>устаревший</strong> браузер.  Некоторые функции сайта могут работать неверно. Пожалуйста, <a href="http://browsehappy.com/" rel="external nofollow" target="_blank">обновите Ваш браузер</a> для полной совместимости с сайтом.</p>
<![endif]-->
<?php } ?>
<div id="wrapper">
    
    <?php woo_header_before(); ?>

	<header id="header">
		<div class="col-full">
		
			<?php woo_header_inside(); ?>
	    	
	    	<div id="hgroup">
				<div class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></div><sup>[beta]</sup>
				<div class="site-description"><?php bloginfo( 'description' ); ?></div>
			</div>
			
			<?php if ( $settings['header_content'] != '' ) { ?>
				<p><?php echo do_shortcode( stripslashes( $settings['header_content'] ) ); ?></p>
			<?php } ?>
			
			<?php if ( is_woocommerce_activated() && isset( $woo_options['woocommerce_header_cart_link'] ) && 'true' == $woo_options['woocommerce_header_cart_link'] ) { ?>
		    	<ul class="nav cart fr">
		    		<li>		<a class="cart-contents" href="<?php echo $woocommerce->cart->get_cart_url(); ?>" title="<?php _e('View your shopping cart', 'woothemes'); ?>"><?php echo $woocommerce->cart->get_cart_total(); ?> <span class="count"><?php echo sprintf( _n('%d item', '%d items', $woocommerce->cart->get_cart_contents_count(), 'woothemes' ), $woocommerce->cart->get_cart_contents_count() );?></span></a>
</li>
		   		</ul>
	    	<?php } ?>
	        
		</div>

        <?php if (is_front_page()) {
            $buy_nims_url = home_url('shop/');
            $sell_nims_url = get_sell_nims_url();
            ?>

            <div class="header-btn-container">
                <a href="<?php echo $buy_nims_url; ?>" class="header-btn">Купить нимы</a>
                <a href="<?php echo $sell_nims_url; ?>" class="header-btn header-btn-sell">Продать нимы</a>
            </div>

        <?php } ?>

        <?php if ( ! is_front_page() ) : ?>

        <?php endif; ?>

    </header><!-- /#header -->

	<?php woo_content_before(); ?>
