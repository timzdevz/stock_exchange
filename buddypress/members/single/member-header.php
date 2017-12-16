<?php
/**
 * BuddyPress - Users Header
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */
?>

<?php do_action( 'bp_before_member_header' ); ?>

<div id="item-header-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( 'type=full' ); ?>
	</a>



    <div class="after-member-header">
        <div style="display: block; clear: both; line-height: 1.2; padding-bottom: 3px">
            активность: <span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_user_last_activity( bp_displayed_user_id() ) ); ?>"><?php bp_last_activity( bp_displayed_user_id() ); ?></span>
        </div>

		<?php do_action( 'bp_after_member_header' ); ?>
    </div>

</div><!-- #item-header-avatar -->

<div id="item-header-content">
    <?php require_once('buy-nims-form.php') ?>
</div><!-- #item-header-content -->



<div id="template-notices" role="alert" aria-atomic="true"> <?php do_action( 'template_notices' ); ?> </div>
