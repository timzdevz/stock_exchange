<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>


<script type="text/javascript">
    jQuery(window).load(function() {
        if (window.yaCounter45052823) {
            var yaParams = {URL: document.location.href};
            yaCounter45052823.reachGoal('error404', yaParams)}
    });
</script>
       
    <div id="content">
    
    	<div class="col-full">
    	
    		<?php woo_main_before(); ?>
    		
			<section id="main" class="col-left">
        	                                                                        
        	    <div class="page">
					
					<header>
        	        	<h1>Упс, страница не найдена :( 404</h1>
        	        </header>
        	        <section class="entry">
                        <p>Мы испытываем некоторые сложности с сервером в данный момент, и 404 ошибка может возникать спонтанно. <br><strong>Если вы уверены, что данная страница существует и доступна для Вас, пожалуйста, обновите страницу ещё раз или попробуйте перезайти в аккаунт!</strong> Приносим извинения за временные неудобства.</p>
        	        	<p>К сожалению, то что Вы искали здесь больше не находится. <a href="<?php echo bp_get_requested_url(); ?>">Попробуйте снова</a> или перейти <a href="<?php echo home_url(); ?>">на главную</a>.</p>
        	        </section>
			
        	    </div><!-- /.post -->
        	                                        
        	</section><!-- /#main -->
        	
        	<?php woo_main_after(); ?>
			
        	<?php get_sidebar(); ?>
        
        </div><!-- /.col-full -->

    </div><!-- /#content -->
		
<?php get_footer(); ?>