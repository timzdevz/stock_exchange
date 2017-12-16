<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Do not delete these lines
if ( ! empty( $_SERVER['SCRIPT_FILENAME'] ) && 'comments.php' == basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
	die ( 'Please do not load this page directly. Thanks!' );
}

$comments_by_type = separate_comments( $comments ); ?>

<?php if ( have_comments() ) { ?>

<div id="comments">

	<?php if ( ! empty( $comments_by_type['order_comment'] ) || ! empty( $comments_by_type['bot_order_comment'] ) ) { ?>
		<span class="heading"><?php the_title(); ?></span>
		<h3><?php comments_number( __( 'No Responses', 'woothemes' ), __( 'One Response', 'woothemes' ), __( '% Responses', 'woothemes' ) ); ?></h3>

		<ol class="commentlist">

			<?php wp_list_comments( 'avatar_size=50&callback=order_comment&type=order_comment' ); ?>

		</ol>

		<nav class="navigation fix">
			<div class="fl"><?php previous_comments_link(); ?></div>
			<div class="fr"><?php next_comments_link(); ?></div>
		</nav><!-- /.navigation -->
	<?php } ?>

	<?php if ( ! empty( $comments_by_type['pings'] ) ) { ?>

        <span class="heading"><?php _e( 'Trackbacks/Pingbacks', 'woothemes' ); ?></span>

        <ol class="pinglist">
            <?php wp_list_comments( 'type=pings&callback=list_pings' ); ?>
        </ol>

	<?php }; ?>

</div> <!-- /#comments_wrap -->

<?php } else { // this is displayed if there are no comments so far ?>


	<?php
		// If there are no comments and comments are closed, let's leave a little note, shall we?
		if ( comments_open() && is_singular() ) { ?>
			<div id="comments">
				<h5 class="nocomments"><?php _e( 'No comments yet.', 'woothemes' ); ?></h5>
			</div>
		<?php } ?>

<?php
	} // End IF Statement

	/* The Respond Form. Uses filters in the theme-functions.php file to customise the form HTML. */
	if ( comments_open() )
		comment_form(array(
            'title_reply' => 'Оставить комментарий к заказу',
//			'label_submit' => 'Оставить комментарий к заказу',
            'logged_in_as' => ''
        ));
?>
