<?php
/**
 * BuddyPress - Users Profile
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddypress' ); ?>" role="navigation">
	<ul>
		<?php bp_get_options_nav(); ?>
	</ul>
</div><!-- .item-list-tabs -->

<?php

/**
 * Fires before the display of member profile content.
 *
 * @since 1.1.0
 */
do_action( 'bp_before_profile_content' ); ?>

<div class="profile">

<?php switch ( bp_current_action() ) :

	// Edit
	case 'edit'   :
		bp_get_template_part( 'members/single/profile/edit' );
		break;

	// Change Avatar
	case 'change-avatar' :
		bp_get_template_part( 'members/single/profile/change-avatar' );
		break;

	// Change Cover Image
	case 'change-cover-image' :
		bp_get_template_part( 'members/single/profile/change-cover-image' );
		break;

	// Compose
	case 'public' :
        global $NMS_Shop_Settings; $uid = bp_displayed_user_id();
		$shop_desc = $NMS_Shop_Settings->get_user_shop_description( $uid );
		$verified = $NMS_Shop_Settings->user_verified_status( $uid );
	    if ($verified === "1") { ?>
            <h3>Информация о продавце</h3>
            <ul class="user-shop-stats">
                <li><b><span rel="tooltip" title="Продавец обещает перевести нимы в течение указанного времени, в противном случае Вы сможете отменить заказ.">Гарант времени:</span></b> <?php echo $NMS_Shop_Settings->get_user_transfer_time( $uid, true, true ) ?></li>
                <li><b>Количество сделок:
                        <span rel="tooltip" title="законченные / проваленные">
                            <span class="orders-success"><?php echo get_user_orders( $uid ); ?></span> /
                            <span class="orders-fail">
                                <?php echo get_user_orders($uid, 'seller', 'closed'); ?></span></b>
                </li>
                <li><b>Всего нимов продано:</b>
                    <span class="nim-coin-icon"><?php echo count_sold_nims($uid, true) ?></span>
                </li>

                <li><b>Отзывы от покупателей:</b> <a href="<?php echo bp_core_get_user_domain( $uid ) ?>reviews/?type=buyer">
                                      <?php echo get_user_review_count( $uid, true ); ?></a></li>

                <?php /* <div><b>Средняя время передачи нимов (по мнению покупателей):</b> ~20 минут</div> */ ?>

            </ul>
            <div class="user-shop-description"><?php echo esc_html($shop_desc); ?></div>
	    <?php }

//		// Display XProfile
//		if ( bp_is_active( 'xprofile' ) )
//			bp_get_template_part( 'members/single/profile/profile-loop' );
//
//		// Display WordPress profile (fallback)
//		else
//			bp_get_template_part( 'members/single/profile/profile-wp' );

		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch; ?>
</div><!-- .profile -->

<?php

/**
 * Fires after the display of member profile content.
 *
 * @since 1.1.0
 */
do_action( 'bp_after_profile_content' ); ?>
