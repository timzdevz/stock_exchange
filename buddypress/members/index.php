<?php
/**
 * BuddyPress - Members
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

/**
 * Fires at the top of the members directory template file.
 *
 * @since 1.5.0
 */
do_action( 'bp_before_directory_members_page' ); ?>

<div id="buddypress">
    <div class="bp-custom-component shop-list">
	<?php

	/**
	 * Fires before the display of the members.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_members' ); ?>

	<?php

	/**
	 * Fires before the display of the members content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_members_content' ); ?>

	<?php /* Backward compatibility for inline search form. Use template part instead. ?>
	<?php if ( has_filter( 'bp_directory_members_search_form' ) ) : ?>

		<div id="members-dir-search" class="dir-search" role="search">
			<?php bp_directory_members_search_form(); ?>
		</div><!-- #members-dir-search -->


	<?php else: ?>

		<?php bp_get_template_part( 'common/search/dir-search-form' ); ?>



	<?php endif;  */?>


        <a href="<?php echo get_sell_nims_url(); ?>" class="sell-nims-btn woo-sc-button teal">Продать нимы</a>

        <div id="template-notices" role="alert" aria-atomic="true">
		    <?php do_action( 'template_notices' ); ?>
        </div>

        <div class="stock-exchange-rate stock-stats-block">
            <?php $marketplace_stats = get_marketplace_stats(); ?>
            <h3>курс за 1к <span class="nim-coin-icon"></span></h3>
            <span class="stock-exchange-rate-min">мин. <?php echo $marketplace_stats['min'] ?> &#8381;</span>
            <span class="stock-exchange-rate-avg">средн. <?php echo $marketplace_stats['avg'] ?> &#8381;</span>
            <span class="stock-exchange-rate-max">макс. <?php echo $marketplace_stats['max'] ?> &#8381;</span>
            Всего на продажу: <span class="stock-volume-nim-count nim-coin-icon"><?php echo $marketplace_stats['nim_count_sum'] ?></span>
        </div>

		<div class="item-list-tabs" id="subnav" aria-label="<?php esc_attr_e( 'Members directory secondary navigation', 'buddypress' ); ?>" role="navigation">
			<ul>
				<?php

				/**
				 * Fires inside the members directory member sub-types.
				 *
				 * @since 1.5.0
				 */
				do_action( 'bp_members_directory_member_sub_types' ); ?>

				<li id="members-order-select" class="last filter">
					<label for="members-order-by">Сортировать: </label>
					<select id="members-order-by">

                        <option value="active">По активности</option>
                        <option value="newest">Сначала новые</option>

						<option value="price">По цене</option>
						<option value="transfer-time">По времени передачи</option>
						<option value="nim-amount">Нимов на продажу</option>
						<option value="total-nim-sold">Всего продано нимов</option>
						<option value="rating">По рейтингу</option>
						<option value="reviews">По количеству отзывов</option>
						<option value="transactions">Количество успешных сделок</option>

						<?php /*if ( bp_is_active( 'xprofile' ) ) : ?>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ); ?></option>
						<?php endif; */ ?>

						<?php

						/**
						 * Fires inside the members directory member order options.
						 *
						 * @since 1.2.0
						 */
//						do_action( 'bp_members_directory_order_options' ); ?>
					</select>
				</li>
			</ul>
		</div>

		<h2 class="bp-screen-reader-text">Купить нимы</h2>

		<div id="members-dir-list" class="members dir-list">
			<?php bp_get_template_part( 'members/members-loop' ); ?>
		</div><!-- #members-dir-list -->

		<?php

		/**
		 * Fires and displays the members content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_directory_members_content' ); ?>

		<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ); ?>

		<?php

		/**
		 * Fires after the display of the members content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_after_directory_members_content' ); ?>

	</form><!-- #members-directory-form -->

	<?php

	/**
	 * Fires after the display of the members.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_members' ); ?>

    </div>
</div><!-- #buddypress -->

<?php

/**
 * Fires at the bottom of the members directory template file.
 *
 * @since 1.5.0
 */
do_action( 'bp_after_directory_members_page' );
