<?php
/**
 * BuddyPress - Members Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

/**
 * Fires before the display of the members loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_before_members_loop' ); ?>

<?php if ( bp_get_current_member_type() ) : ?>
    <p class="current-member-type"><?php bp_current_member_type_message() ?></p>
<?php endif; ?>

<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) : ?>

    <div id="pag-top" class="pagination">

        <div class="pag-count" id="member-dir-count-top">

			<?php bp_members_pagination_count(); ?>

        </div>

        <div class="pagination-links" id="member-dir-pag-top">

			<?php bp_members_pagination_links(); ?>

        </div>

    </div>

	<?php

	/**
	 * Fires before the display of the members list.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_members_list' ); ?>

    <ul id="members-list" class="item-list" aria-live="assertive" aria-relevant="all">

		<?php while ( bp_members() ) : bp_the_member();
			$uid = bp_get_member_user_id(); ?>

            <li <?php bp_member_class(); ?>>
                <div class="row">

                    <div class="col col-5">
                        <div class="item-avatar">
                            <a href="<?php bp_member_permalink(); ?>"
                               target="_blank"><?php bp_member_avatar( 'type=full' ); ?></a>
                            <div class="item-meta">
                                активность:<br><span class="activity"
                                                     data-livestamp="<?php bp_core_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ); ?>"><?php bp_member_last_active(); ?></span>
                            </div>
                        </div>

						<?php global $BP_Member_Reviews; ?>
                        <div class="item">
                            <div class="item-title">
                                <a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
                            </div>
                            <div><b>Сделок:
                                    <span rel="tooltip" title="законченные / проваленные"><span class="orders-success"><?php echo get_user_orders($uid); ?></span>
                / <span class="orders-fail"><?php echo get_user_orders($uid, 'seller', 'closed'); ?></span></b></span>
                            </div>

							<?php $BP_Member_Reviews->embed_rating( $uid ) ?>

							<?php
							global $NMS_Shop_Settings;
							$shop_desc = $NMS_Shop_Settings->get_user_shop_description( $uid );
							?>
                            <div class="user-shop-description"><?php echo esc_html( $shop_desc ); ?></div>
                        </div>
                    </div>
                    <div class="col col-4"">
						<?php require( 'single/buy-nims-form.php' ) ?>
                    </div>

                    <div class="col col-3">
                        <div class="user-shop-stats">
                            <div><b><span rel="tooltip" title="Продавец обещает перевести нимы в течение указанного времени, в противном случае Вы сможете отменить заказ.">Гарант времени:</span></b> <?php echo $NMS_Shop_Settings->get_user_transfer_time( $uid, true, true ) ?></div>
                            <div><b>Всего продано:</b>

                                <span class="nim-coin-icon"><?php echo number_format( count_sold_nims( $uid ), 0, '.', ' ') ;?></span>
                            </div>
                            <?php /* <div><b>Среднее время передачи <span class="nim-coin-icon"></span>:</b> ~20 мин.</div> */ ?>
							<?php $review_count = get_user_review_count( $uid, true ); ?>
                            <div><b><span rel="tooltip" title="Количество отзывов от покупателей">Отзывы:</span></b>
								<?php if ( $review_count > 0 ) { ?>
                                    <a href="<?php echo bp_core_get_user_domain( $uid ) ?>reviews/?type=buyer"
                                       target="_blank"><b><?php echo $review_count ?></b></a>
								<?php } else {
									echo 0;
								} ?>
                            </div>
                        </div>
                    </div>
                </div>

            </li>

		<?php endwhile; ?>

    </ul>

	<?php

	/**
	 * Fires after the display of the members list.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_members_list' ); ?>

	<?php bp_member_hidden_fields(); ?>

    <div id="pag-bottom" class="pagination">

        <div class="pag-count" id="member-dir-count-bottom">

			<?php bp_members_pagination_count(); ?>

        </div>

        <div class="pagination-links" id="member-dir-pag-bottom">

			<?php bp_members_pagination_links(); ?>

        </div>

    </div>

<?php else: ?>

    <div id="message" class="info">
        <p>Продавцы не найдены. Вы можете выставить свои нимы на продажу <a href="<?php echo get_sell_nims_url(); ?>">здесь</a>.</p>
    </div>

<?php endif; ?>

<?php

/**
 * Fires after the display of the members loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_after_members_loop' ); ?>
