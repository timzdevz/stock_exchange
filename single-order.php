<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

global $NMS_Shop_Settings;
$order_confirmed = false;
?>

    <div id="content">

        <div class="col-full" id="buddypress">

			<?php woo_main_before(); ?>

            <section id="main" class="col-left">

				<?php
				if ( have_posts() ) {
					$count = 0;
					while ( have_posts() ) {
						the_post();
						global $post;
						$count ++;
						$seller       = get_userdata( get_order_seller_id( $post->ID ) );
						$buyer        = get_userdata( $post->post_author );
						$order_status = get_order_status( $post->ID );
						$order_total  = get_order_total( $post->ID );

						$transfer_time = $NMS_Shop_Settings->transfer_minutes_to_hours_minutes( get_order_transfer_time( $post->ID ) );
						$order_confirmed = $order_status != 'unconfirmed';
						if ( $order_confirmed ) : ?>
                            <article <?php post_class(); ?>>

                                <header>
                                    <h2>
                                        <a href="<?php the_permalink(); ?>" rel="bookmark"
                                           title="<?php the_title_attribute(); ?>">
											<?php the_title(); ?></a>
                                    </h2>

                                    <div id="template-notices" role="alert" aria-atomic="true">
		                                <?php do_action( 'template_notices' ); ?>
                                    </div>

                                    <h3>Статус заказа:
                                        <span><?php echo $GLOBALS['ORDER_STATUSES'][ get_order_status( $post->ID ) ] ?></span></h3>
                                </header>

                                <section class="entry fix">

                                    <h3>Детали заказа: </h3>
                                    <div class="order-details">
                                        <table>
                                            <tr>
                                                <td>Дата заказа:</td>
                                                <td><?php echo get_the_date( 'd.m.Y H:i', $post->ID ); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Продавец:</td>
                                                <td><b><a href="<?php echo bp_core_get_user_domain( $seller->ID ); ?>"
                                                          target="_blank">
															<?php echo $seller->display_name ?></a></b></td>
                                            </tr>

                                            <tr><td><span rel="tooltip" title="Если продавец не переведет нимы в течение указанного времени, покупатель сможет отменить заказ и купить нимы у другого продавца, при этом рейтинг текущего продавца понизится.">Гарант времени передачи нимов:</span></td>
                                                <td class="<?php echo isset( $data_changed['transfer_time']) ? 'data-changed' : '';?>">
			                                        <?php echo $transfer_time['h'] ?
				                                        getNumEnding($transfer_time['h'], array('час', 'часа', 'часов'), true) : ''; ?>

			                                        <?php echo $transfer_time['m'] ?
				                                        getNumEnding($transfer_time['m'], array('минута', 'минуты', 'минут'), true) : ''; ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Покупатель:</td>
                                                <td><b><a href="<?php echo bp_core_get_user_domain( $buyer->ID ); ?>"
                                                          target="_blank">
															<?php echo $buyer->display_name ?></a></b></td>
                                            </tr>

                                            <tr>
                                                <td><b>Количество нимов:</b></td>
                                                <td><b>
                                                    <span class="nim-coin-icon"><?php echo number_format( get_order_nims_amount( $post->ID ), 0, '.', ' ' ); ?></span></b>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Цена:</td>
                                                <td><?php echo format_balance( get_order_price( $post->ID ), true ); ?></b></td>
                                            </tr>

                                            <tr>
                                                <td>Итого:</td>
                                                <td><?php echo format_balance( $order_total, true ); ?></b></td>
                                            </tr>

                                            <tr>
                                                <td><b>Логин в NIMSES для передачи:</b></td>
                                                <td><?php echo get_order_nimses_login( $post->ID ); ?></td>
                                            </tr>

                                            <tr>
                                                <td><b>Дополнительная информация для продавца:</b></td>
                                                <td><?php echo nl2br( esc_html( get_order_description( $post->ID ) ) ); ?></td>
                                            </tr>

                                            <?php
                                            $closed_reason = order_closed_reason( $post->ID );
                                            if ( $closed_reason && $closed_reason != 'more-details-required' ) { ?>

                                                <tr>
                                                    <td><b>Причина отказа:</b></td>
                                                    <td><?php echo nl2br( esc_html( $closed_reason ) ); ?></td>
                                                </tr>

                                            <?php } ?>

                                        </table>

                                    </div>

                                </section>

                            </article><!-- .post -->

                            <?php require_once 'single-order-user-panel.php'; ?>

						<?php else : require_once 'single-order-unconfirmed.php'; endif; ?>
					<?php } ?>

				<?php } else { ?>
                    <article <?php post_class(); ?>>
                        <p><?php _e( 'Sorry, no posts matched your criteria.', 'woothemes' ); ?></p>
                    </article><!-- .post -->
				<?php } ?>

            </section><!-- #main -->

			<?php woo_main_after(); ?>

        </div><!-- /.col-full -->

    </div><!-- #content -->


<?php if ( $order_confirmed ) : ?>
    <div id="full-single-comments-area">
        <div class="col-full">
			<?php comments_template( '/order-comments.php' ); ?>
        </div>
    </div>
<?php endif; ?>


<?php get_footer(); ?>