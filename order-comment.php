<?php function order_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	$user_bot           = stockexchangeBot::getInstance()->is_bot( $comment->user_id );
	?>

<li <?php comment_class( array('comment', $user_bot ? 'comment-bot' : null ) ); ?>>

    <?php if (is_super_admin()) { ?>
    <p>
    Order status: <?php echo get_comment_meta( $comment->comment_ID, 'order_status', true ); ?>
	</p>
    <?php } ?>
	<a name="comment-<?php comment_ID() ?>"></a>

	<?php if ( ! $user_bot ) { ?>
		<div class="comment-avatar"><?php the_commenter_avatar( array( 'avatar_size' => 80 ) ); ?></div>
	<?php } ?>

	<?php if ( ! $user_bot ) : ?>
		<div id="li-comment-<?php comment_ID() ?>" class="comment-container">

			<div class="comment-entry" id="comment-<?php comment_ID(); ?>">
				<?php comment_text(); ?>
			</div><!-- /comment-entry -->

			<div class="comment-meta" id="comment-meta-<?php comment_ID(); ?>">

				<span class="name"><?php echo get_comment_author( get_comment_ID() );//the_commenter_link(); ?></span>
				<span class="date"><?php echo get_comment_date( get_option( 'date_format' ) ); ?> в <?php echo get_comment_time( get_option( 'time_format' ) ); ?></span>

				<span class="edit"><?php edit_comment_link( __( 'Edit', 'woothemes' ), '', '' ); ?></span>

			</div><!-- /.comment-meta -->

		</div><!-- /.comment-container -->
	<?php else : ?>
		<div id="li-comment-<?php comment_ID() ?>" class="comment-container comment-bot-container">

			<div class="comment-meta" id="comment-meta-<?php comment_ID(); ?>">

				<p>
					<span class="date bot-date"><?php echo get_comment_date( get_option( 'date_format' ) ); ?> в <?php echo get_comment_time( get_option( 'time_format' ) ); ?></span>

					<span class="edit"><?php edit_comment_link( __( 'Edit', 'woothemes' ), '', '' ); ?></span></p>

			</div><!-- /.comment-meta -->

			<div class="comment-entry" id="comment-<?php comment_ID(); ?>">
				<?php comment_text(); ?>
			</div><!-- /comment-entry -->


		</div><!-- /.comment-container -->
	<?php endif; ?>

<?php } ?>