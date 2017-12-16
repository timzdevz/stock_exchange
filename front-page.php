<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Page Template
 *
 * This template is the default page template. It is used to display content when someone is viewing a
 * singular view of a page ('page' post_type) unless another page template overrules this one.
 * @link http://codex.wordpress.org/Pages
 *
 * @package WooFramework
 * @subpackage Template
 */
	get_header();
	global $woo_options;
?>
       
    <div id="content" class="page">
    
    	<div class="col-full">
    
    		<?php woo_main_before(); ?>
    		
			<section id="main" class="col-left"> 			
			
        	<?php
        		if ( have_posts() ) { $count = 0;
        			while ( have_posts() ) { the_post(); $count++;
        	?>                                                           
        	    <article <?php post_class(); ?>>

					<?php if (!is_front_page()): ?>
                    <header>
				    	<h1><?php the_title(); ?></h1>
					</header>
                    <?php endif; ?>
					
        	        <section class="entry">
        	        	<?php the_content(); ?>
			
						<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'woothemes' ), 'after' => '</div>' ) ); ?>
        	       	</section><!-- /.entry -->
			
					<?php edit_post_link( __( '{ Edit }', 'woothemes' ), '<span class="small">', '</span>' ); ?>
        	        
        	    </article><!-- /.post -->

                        <h3 class="last-nimses-news-title">Последние статьи и новости NIMSES</h3>


                        <?php $articles = get_posts('cat=24&posts_per_page=3');
                        add_filter( 'excerpt_length', 'custom_excerpt_length', 999 ); ?>
                            <div class="row">
				        <?php foreach ( $articles as $article ) {
					        setup_postdata( $article );?>
                            <div class="col col-4">
                                <article class="news-article">
                            <a href="<?php the_permalink($article); ?>">
                                <h2><?php echo get_the_title($article); ?></h2></a>

	                            <?php if (has_post_thumbnail($article) ) {
	                                echo get_the_post_thumbnail( $article, array(300));
	                            } ?>


                                <p><?php echo get_the_excerpt($article); ?></p>
                                </article>
                            </div>
				        <?php }
				        wp_reset_postdata(); ?>

                            </div>


                        <h3 class="last-nimses-news-title">Последние новости биржи stockexchange.com</h3>


                        <?php $articles = get_posts('cat=21&posts_per_page=3'); ?>
                            <div class="row">
				        <?php foreach ( $articles as $article ) {
					        setup_postdata( $article );?>
                            <div class="col col-4">
                                <article class="news-article">
                            <a href="<?php the_permalink($article); ?>">
                                <h2><?php echo get_the_title($article); ?></h2></a>
                                    <span><?php the_date("H:i d.m.Y"); ?></span>

	                            <?php if (has_post_thumbnail($article) ) {
	                                echo get_the_post_thumbnail( $article, array(300));
	                            } ?>


                                <p><?php echo get_the_excerpt($article); ?></p>
                                </article>
                            </div>
				        <?php }
				        wp_reset_postdata(); ?>
                            </div>

        	    <?php
					} // End WHILE Loop
				} else {
			?>
				<article <?php post_class(); ?>>
        	    	<p><?php _e( 'Sorry, no posts matched your criteria.', 'woothemes' ); ?></p>
        	    </article><!-- /.post -->
        	<?php } // End IF Statement ?>  
        	
			</section><!-- /#main -->
			
			<?php woo_main_after(); ?>
			
        	<?php get_sidebar(); ?>
        
        </div><!-- /.col-full -->

    </div><!-- /#content -->
		
<?php get_footer(); ?>