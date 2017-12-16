<?php
function marketplace_transactions_page() {
	?>
    <style type="text/css">
        ul.pagination {
            display: block;
            margin-left: -0.3125rem;
            min-height: 1.5rem
        }

        ul.pagination li {
            color: #222;
            font-size: 1.2em;
            margin-left: 0.3125rem
        }

        ul.pagination li a,ul.pagination li button {
            border-radius: 3px;
            transition: background-color 300ms ease-out;
            background: none;
            color: #999;
            display: block;
            font-size: 1em;
            font-weight: normal;
            line-height: inherit;
            padding: 0.0625rem 0.625rem 0.0625rem
        }

        ul.pagination li:hover a,ul.pagination li a:focus,ul.pagination li:hover button,ul.pagination li button:focus {
            background: #e6e6e6
        }

        ul.pagination li.unavailable a,ul.pagination li.unavailable button {
            cursor: default;
            color: #999;
            pointer-events: none
        }

        ul.pagination li.unavailable:hover a,ul.pagination li.unavailable a:focus,ul.pagination li.unavailable:hover button,ul.pagination li.unavailable button:focus {
            background: transparent
        }

        ul.pagination li.current a,ul.pagination li.current button {
            background: #008CBA;
            color: #fff;
            cursor: default;
            font-weight: bold
        }

        ul.pagination li.current a:hover,ul.pagination li.current a:focus,ul.pagination li.current button:hover,ul.pagination li.current button:focus {
            background: #008CBA
        }

        ul.pagination li {
            display: block;
            float: left
        }

        .pagination-centered {
            text-align: center
        }

        .pagination-centered ul.pagination li {
            display: inline-block;
            float: none
        }

    </style>
	<div class="wrap">
		<h2>Все транзакции</h2>

		<?php
		$page = ( isset( $_GET['paged'] ) ) ? $_GET['paged'] : 1;
		$transactions_per_page = 20 ;
		$transactions = get_transactions( null, $page, $transactions_per_page,
            $_REQUEST['transaction_type'] ? $_REQUEST['transaction_type'] : null );
		?>

		<div class="bp-custom-component">

			<div class="clearfix"></div>

			<div class="user-transactions-container">

				<?php if ($transactions['found_rows'] > 0) { ?>

                    <div class="bbp-pagination">
						<?php
						global $wp_query; $wp_query_tmp = $wp_query;

						$wp_query->set( 'paged', $page );
						$wp_query->max_num_pages = ceil($transactions['found_rows'] / $transactions_per_page);
						ob_start(); bbp_pagination(); $pag_output = ob_get_clean();
						$pag_output = str_replace( '#038;', '', $pag_output );
						$pag_output = preg_replace( '#(transaction_type=.+?)transaction_type#', 'transaction_type', $pag_output );
						echo $pag_output;
						$wp_query = null; $wp_query = $wp_query_tmp;
						?>
                    </div>

                <?php } ?>

                    <form action="<?php echo get_current_url();?>">
                        <input type="hidden" name="page" value="marketplace-transactions">
                    <label for="transact_type">Фильтр:</label>
                        <select name="transaction_type" id="transact_type">
                        <option value="0">Все типы транзакций</option>
                    <?php foreach ( $GLOBALS['TRANSACTIONS_TYPES'] as $key => $type ) {
                        $selected = $key == $_REQUEST['transaction_type']; ?>
                        <option value="<?php echo $key; ?>" <?php echo $selected ? 'selected' : ''; ?>><?php echo $type; ?></option>
                    <?php } ?>
                    </select>
                        <input type="submit" value="Go">
                    </form>
					<table class="wp-list-table widefat fixed striped posts">
						<thead>
						<tr class="header">

							<th>Дата</th>
							<th>Пользователь</th>
							<th width="40%">Описание</th>
							<th>Сумма</th>
							<th>Доступно</th>
							<th>Буфер</th>
						</tr>
						</thead>
						<tbody>

						<?php foreach ( $transactions['transactions'] as $transaction ) {
						    $userdata = get_userdata($transaction['user_id']); ?>
							<tr>
								<td data-label="Дата"><?php echo date( 'd.m.Y H:i', strtotime( $transaction['date'] ) )?>
                                <td data-label="Пользователь">
                                    <a href="<?php echo bp_core_get_user_domain($userdata->ID)?>"><?php echo get_userdata($transaction['user_id'])->user_login?></a></td>

								<td data-label="Описание"><?php echo esc_html( $transaction['comment'] ); ?></td>

								<td data-label="Сумма"><?php echo format_balance( esc_html( $transaction['amount'] ), true )?></td>

								<td data-label="Доступно"><?php echo format_balance( esc_html( $transaction['balance'] ), true ) ?></td>

								<td data-label="Буфер"><?php echo format_balance( esc_html( $transaction['buffer'] ), true )  ?></td>
							</tr>

						<?php } ?>
						</tbody>
					</table>

				<?php if ($transactions['found_rows'] > 0) { ?>
					<div class="bbp-pagination">
						<?php echo $pag_output; ?>
					</div>

				<?php } ?>


			</div>
		</div>
	<?php
}