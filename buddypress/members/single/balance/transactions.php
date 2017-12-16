<?php
$uid = bp_displayed_user_id();
$page = ( isset( $_GET['upage'] ) ) ? $_GET['upage'] : 1;
$transactions_per_page = 20;
$transactions = get_transactions( bp_displayed_user_id(), $page, $transactions_per_page );
?>


<h2 class="bp-screen-reader-text">История операций</h2>

<div class="user-transactions-summary">
<p align="center">Пополнено: <?php echo count_transactions_total( $uid, 'in' ) ?> | Выведено: <?php echo count_transactions_total( $uid, 'out' )?></p>

<p align="center">Заработано: <?php echo count_total_revenue( bp_displayed_user_id() )?> | Оплачено: <?php echo count_total_revenue( bp_displayed_user_id(), 'buyer', false )?></p>
</div>


<div class="clearfix"></div>

<div class="user-transactions-container">

    <?php if ($transactions['found_rows'] > 0) { ?>

        <table class="table-responsive">
            <thead>
            <tr class="header">

                <th>Дата</th>
                <th>Описание</th>
                <th>Сумма</th>
                <th>Доступно</th>
                <th>Буфер</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ( $transactions['transactions'] as $transaction ) { ?>
            <tr>
                <td data-label="Дата"><?php echo date( 'd.m.Y H:i', strtotime( $transaction['date'] ) )?>

                <td data-label="Описание"><?php echo esc_html( $transaction['comment'] ); ?></td>

                <td data-label="Сумма"><?php echo format_balance( esc_html( $transaction['amount'] ), true )?></td>

                <td data-label="Доступно"><?php echo format_balance( esc_html( $transaction['balance'] ), true ) ?></td>

                <td data-label="Буфер"><?php echo format_balance( esc_html( $transaction['buffer'] ), true )  ?></td>
            </tr>

            <?php } ?>
            </tbody>
        </table>

        <div class="bbp-pagination">
            <?php
            global $wp_query;
            $tmp = clone $wp_query;
            $wp_query->set( 'paged', $page );
            $wp_query->max_num_pages = ceil($transactions['found_rows'] / $transactions_per_page);
            bbp_pagination();
            $wp_query = null; $wp_query = $tmp;?>
        </div>

    <?php } else { ?>

            <div class="panel">
                Здесь будет отображаться Ваша история баланса.
            </div>

    <?php } wp_reset_query(); ?>


