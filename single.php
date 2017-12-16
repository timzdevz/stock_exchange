<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Single Post Template
 *
 * This template is the default page template. It is used to display content when someone is viewing a
 * singular view of a post ('post' post_type).
 * @link http://codex.wordpress.org/Post_Types#Post
 *
 * @package WooFramework
 * @subpackage Template
 */
	get_header();
	global $woo_options;
	
/**
 * The Variables
 *
 * Setup default variables, overriding them if the "Theme Options" have been saved.
 */
	
	$settings = array(
					'thumb_single' => 'false', 
					'single_w' => 200, 
					'single_h' => 200, 
					'thumb_single_align' => 'alignright',
					'connect' => 'false'
					);
					
	$settings = woo_get_dynamic_values( $settings );
	$single_layout = get_post_meta($post->ID, '_layout', true); // Get single page layout
?>
       
    <div id="content">
    	
    	<div class="col-full">
    	
    		<?php woo_main_before(); ?>
    		
			<section id="main" class="col-left">
			           
        	<?php
        		if ( have_posts() ) { $count = 0;
        			while ( have_posts() ) { the_post(); $count++;
        	?>
				<article <?php post_class(); ?>>
			
					<?php echo woo_embed( 'width=580' ); ?>
        	        <?php if ( $settings['thumb_single'] == 'true' && ! woo_embed( '' ) ) { woo_image( 'width=' . $settings['single_w'] . '&height=' . $settings['single_h'] . '&class=thumbnail ' . $settings['thumb_single_align'] ); } ?>
			
        	        <header>
						<h1><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
						<span class="post-date-author"><strong class="post-date"><?php the_time( get_option( 'date_format' ) ); ?></strong> <?php _e('by', 'woothemes'); ?> <strong class="post-author"><?php the_author_posts_link(); ?></strong></span>
						<span class="post-category"><?php the_category(' ') ?></span>
					</header>
        	        
        	        <section class="entry fix">
        	        	<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'woothemes' ), 'after' => '</div>' ) ); ?>
					</section>
										
					<?php the_tags( '<p class="tags">'.__( 'Tags: ', 'woothemes' ), ', ', '</p>' ); ?>
                    <script type="text/javascript">(function() {
                            if (window.pluso)if (typeof window.pluso.start == "function") return;
                            if (window.ifpluso==undefined) { window.ifpluso = 1;
                                var d = document, s = d.createElement('script'), g = 'getElementsByTagName';
                                s.type = 'text/javascript'; s.charset='UTF-8'; s.async = true;
                                s.src = ('https:' == window.location.protocol ? 'https' : 'http')  + '://share.pluso.ru/pluso-like.js';
                                var h=d[g]('body')[0];
                                h.appendChild(s);
                            }})();</script>
                    <div class="pluso" data-background="transparent" data-options="big,square,line,horizontal,counter,theme=08" data-services="vkontakte,odnoklassniki,facebook,twitter,google,moimir"></div>
        	                        
        	    </article><!-- .post -->
        	    
        	   <?php } ?>
	    	   
	    	   <?php if ( $single_layout == 'layout-full' )  { // If full width layout ?>
	    	    
	    	    	<nav id="post-entries" class="fix">
	    	    	    <div class="nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav">&larr;</span> %title' ); ?></div>
	    	    	    <div class="nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav">&rarr;</span>' ); ?></div>
	    	    	</nav><!-- #post-entries -->
	    	    	
	    	   <?php } ?>
	    	    	
	    	    <?php if ( $single_layout != 'layout-full' )  { // If NOT full width layout ?>
	    	    	
	    	    	<?php if ( isset( $woo_options['woo_post_author'] ) && $woo_options['woo_post_author'] == 'true' ) { ?>
						<aside id="post-author" class="fix<?php if ( $settings[ 'connect' ] == "true" ) { echo " fl"; } ?>">
						    <div class="profile-image"><?php echo get_avatar( get_the_author_meta( 'ID' ), '70' ); ?></div>
						    <div class="profile-content">
						    	<h3 class="title"><?php printf( esc_attr__( 'About %s', 'woothemes' ), get_the_author() ); ?></h3>
						    	<?php the_author_meta( 'description' ); ?>
						    	<div class="profile-link">
						    		<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
						    			<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'woothemes' ), get_the_author() ); ?>
						    		</a>
						    	</div><!-- #profile-link	-->
						    </div><!-- .post-entries -->
						</aside><!-- .post-author-box -->
					<?php } ?>
					
					<?php //woo_subscribe_connect(); ?>


					
	    	    	<nav id="post-entries" class="fix">
	    	    	    <div class="nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav">&larr;</span> %title' ); ?></div>
	    	    	    <div class="nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav">&rarr;</span>' ); ?></div>
	    	    	</nav><!-- #post-entries -->
	    	    	
	    	    	 <?php
        	    	// Determine wether or not to display comments here, based on "Theme Options".
        	    	if ( isset( $woo_options['woo_comments'] ) && in_array( $woo_options['woo_comments'], array( 'post', 'both' ) ) ) {
        	    		comments_template();
        	    	}
			
					}
				?>
	    	    
        	<?php } else { ?>
				<article <?php post_class(); ?>>
        	    	<p><?php _e( 'Sorry, no posts matched your criteria.', 'woothemes' ); ?></p>
				</article><!-- .post -->             
       		<?php } ?>  
        	
			</section><!-- #main -->
			
			<?php woo_main_after(); ?>
			
        	<?php get_sidebar(); ?>
	
		</div><!-- /.col-full -->
		
    </div><!-- #content -->
    
    <?php if ( $single_layout == 'layout-full' )  { // If full width layout ?>
    
    	<div id="full-single-comments-area">
    		<div class="col-full">
    			<?php if ( isset( $woo_options['woo_post_author'] ) && $woo_options['woo_post_author'] == 'true' ) { ?>
				    <aside id="post-author" class="fix<?php if ( $settings[ 'connect' ] == "true" ) { echo " fl"; } ?>">
				        <div class="profile-image"><?php echo get_avatar( get_the_author_meta( 'ID' ), '70' ); ?></div>
				        <div class="profile-content">
				        	<h3 class="title"><?php printf( esc_attr__( 'About %s', 'woothemes' ), get_the_author() ); ?></h3>
				        	<?php the_author_meta( 'description' ); ?>
				        	<div class="profile-link">
				        		<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
				        			<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'woothemes' ), get_the_author() ); ?>
				        		</a>
				        	</div><!-- #profile-link	-->
				        </div><!-- .post-entries -->
				    </aside><!-- .post-author-box -->
				<?php } ?>
				
				<?php woo_subscribe_connect(); ?>
	    	    
	    	     <?php
        	    // Determine wether or not to display comments here, based on "Theme Options".
        	    if ( isset( $woo_options['woo_comments'] ) && in_array( $woo_options['woo_comments'], array( 'post', 'both' ) ) ) {
        	        comments_template();
        	    } ?>
				
				</div>
				</div>
				
				<?php } ?>
		
	<?php get_footer(); ?>
	
</div>