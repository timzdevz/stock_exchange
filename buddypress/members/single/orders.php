<?php

$page = ( isset( $_GET['upage'] ) ) ? $_GET['upage'] : 1;

$status = $_GET['status'];
$status_array = null;
$shop_order_statuses = $GLOBALS['ORDER_STATUSES'];
if ( in_array( $status, array_keys( $shop_order_statuses ) ) ) {
	$status_array = array(
		'key'     => '_order_status',
		'value'   => $status,
		'compare' => '=',
	);
}

$args = array(
	'post_type'  => 'order',
	'author'     => bp_displayed_user_id(),
	'paged'      => $page,
	'posts_per_page' => 15,
	'meta_query' => array(
        'relation' => 'AND',
		array(
			'key'     => 'author',
			'value'   => 'anon',
			'compare' => 'NOT EXISTS',
		),
        $status_array
	)
);
query_posts( $args );

?>

<form action="">
    <input type="hidden" name="upage" id="page-num" value="<?php echo $_GET['upage'] ? $_GET['upage'] : 1 ?>" >

    <label>Показывать:
        <select name="status" onchange="this.form.querySelector('#page-num').value = 1; this.form.submit();">
            <option value="all">Все</option>
			<?php $order_statuses = $GLOBALS['ORDER_STATUSES'];
			foreach ( $order_statuses as $order_status_id => $order_status) { ?>
                <option value="<?php echo $order_status_id ?>"
					<?php echo $_GET['status'] == $order_status_id ? 'selected' : ''; ?>><?php echo $order_status ?></option>
			<?php } ?>
        </select></label>
</form>

<div class="bp-custom-component">

    <h2 class="bp-screen-reader-text">Заказы</h2>

    <div class="clearfix"></div>

    <div class="user-orders-container">

		<?php if ( have_posts() ) { ?>

            <table class="table-responsive">
                <thead>
                <tr class="header">

                    <th>ID заказа</th>
                    <th>Дата</th>
                    <th>Продавец</th>
                    <th>Кол-во нимов</th>
                    <th title="Цена за 1000 нимов">Цена</th>
                    <th>Сумма заказа</th>
                    <th>Статус покупки</th>
                    <th>Действия</th>

                </tr>
                </thead>
                <tbody>


                <?php
                while ( have_posts() ) { the_post(); global $post;
	                $order_status = rwmb_meta( '_order_status' );
	                $order_date = get_order_human_date( $post->ID );
	                $seller = get_userdata( get_order_seller_id( $post->ID ) );
                ?>

                <tr class="nim-lot-purchase">
                    <td data-label="ID заказа">#<?php echo $post->ID ?></td>

                    <td data-label="Дата" class="date-cell"><?php echo $order_date; ?></td>

                    <td data-label="Продавец"><a href="<?php echo bp_core_get_user_domain($seller->ID) ?>">
		                <?php echo $seller->display_name ?></td>


                    <td data-label="Кол-во нимов"><span class="nim-coin-icon"><?php echo get_order_nims_amount( $post->ID, true ) ?></span></td>

                    <td data-label="Цена" title="Цена за 1000 нимов"
                        class="nowrap"><?php echo format_balance( get_order_price( $post->ID ), true ) ?></td>


                    <td data-label="Сумма заказа"><?php echo format_balance( get_order_total( $post->ID ), true ) ?></td>

                    <td data-label="Статус" class="text-center status-<?php echo $order_status ?>">
						<?php rwmb_the_value( '_order_status' ) ?></td>

                    <td data-label="Действия" class="text-center">
		                <?php $reviewed_by = order_reviewed_by( $post->ID );
		                if ( $order_status == 'done' && ! $reviewed_by['buyer'] ) : ?>
                            <a href="<?php echo get_add_order_review_link($seller->ID, $post->ID )?>" target="_blank">Оставить отзыв</a> |
		                <?php endif; ?>

                        <a href="<?php echo get_the_permalink( $post ) ?>" target="_blank">Открыть заказ</a>
                    </td>
                </tr>

				<?php } ?>
                </tbody>

            </table>

            <div class="bbp-pagination">
				<?php
				ob_start(); bbp_pagination(); $pag_output = ob_get_clean();
				$pag_output = str_replace( '#038;', '', $pag_output );
				$pag_output = preg_replace( '#(status=.+?)status#', 'status', $pag_output );
				echo $pag_output;?>
            </div>


		<?php } else { ?>

            <div class="panel">
                Заказы не найдены. <a href="<?php echo home_url( 'shop/' ) ?>">Посмотреть и купить нимы на рынке нимов</a>.
            </div>

		<?php }
		wp_reset_query(); ?>


    </div>
</div>


