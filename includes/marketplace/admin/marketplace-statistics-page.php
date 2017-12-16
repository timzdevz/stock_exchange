<?php
function stock_statistics_page() {
    global $NMS_Balance;
	$total_in           = count_transactions_total( null, 'in', false );
    $total_out          = count_transactions_total( null, 'out', false );
    $fk_wallet_balance  = FreeKassa::getInstance()->get_fk_wallet_balance();
    $kassa_balance      = FreeKassa::getInstance()->get_fk_kassa_balance(false);
    $total_withdrawn = get_total_withdrawn_money();
    $balance_should_be = $total_in - $total_out - $total_withdrawn; ?>

	<?php if ( $_GET['success'] ) : ?>
        <div class="notice notice-success">
            <p>Транзакция #<?php echo esc_html( $_GET['success'] ) ?> успешно создана.</p>
        </div>
	<?php endif; ?>

	<?php if ( $_SESSION['admin_fk_result'] ) : ?>
        <div class="notice notice-info">
            <p><?php echo esc_html( $_SESSION['admin_fk_result'] ); unset( $_SESSION['admin_fk_result']) ?></p>
        </div>
	<?php endif; ?>

    <div class="wrap">
        <h1>Финансы</h1>

        <table class="form-table">

            <tr valign="top">
                <th>Заработано за сегодня</th>
                <td><?php echo format_balance( get_earned_money_today(), true ) ?></td>
            </tr>


            <tr valign="top">
                <th>Заработано за эту неделю</th>
                <td><?php echo format_balance( get_earned_money_week(), true ) ?></td>
            </tr>


            <tr valign="top">
                <th>Заработано за этот месяц</th>
                <td><?php echo format_balance( get_earned_money_month(), true ) ?></td>
            </tr>


            <tr valign="top">
                <th>Всего заработано</th>
                <td><?php echo format_balance( get_earned_money(), true ); ?></td>
            </tr>

            <tr>
                <th>Баланс stockexchange.com: </th>
                <td><?php echo format_balance( $NMS_Balance->marketplace_balance(), true ); ?> (<a href="<?php echo admin_url() . 'options.php#_marketplace_balance'; ?>">редактировать</a>)</td>
            </tr>

        </table>


        <h1>Вывод денег</h1>


        <table class="form-table">

            <tr valign="top">
                <th>Баланс FK Wallet / FreeKassa: </th>
                <td><form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                 <?php echo is_numeric( $fk_wallet_balance ) ?
                     format_balance( $fk_wallet_balance, true ) : $fk_wallet_balance; ?> /

	                <?php echo is_numeric( $kassa_balance ) ?
                        format_balance( $kassa_balance, true ) : $kassa_balance; ?>

                    <button type="submit">Перевести на FK Wallet</button>

                    <input type="hidden" name="action" value="fk_from_kassa_to_wallet">
                    <input type="hidden" name="amount" value="<?php echo $kassa_balance; ?>">
                    <input type="hidden" name="redirect_url" value="<?php echo admin_url( 'admin.php?page=stock-statistics' ) ?>">
                </form></td>
            </tr>

            <tr valign="top">
                <th>Итого баланс:</th>
                <td><?php echo format_balance( $total_balance = $fk_wallet_balance + $kassa_balance, true ) ?></td>
            </tr>

            <tr valign="top">
                <th>Должно быть на балансе:</th>
                <td><?php echo format_balance( $balance_should_be, true ) ?> /
                    Разрыв: <?php echo format_balance( $total_balance - $balance_should_be, true ) ; ?></td>
            </tr>

            <tr valign="top">
                <th>Всего выведено:</th>
                <td><?php echo format_balance( $total_withdrawn, true ) ?></td>
            </tr>


            <tr valign="top">
                <th>Сумма доступная для вывода:</th>
                <td><?php echo format_balance( $avail_withdraw_amount = get_available_withdraw_amount(), true ) ?></td>
            </tr>
        </table>

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <table class="form-table">

                <tr valign="top">
                    <th>Сумма на вывод:</th>
                    <td><input name="stock_withdraw_sum" value="<?php echo $avail_withdraw_amount; ?>" placeholder="Сумма на вывод" size="15"> ₽
                    </td>
                </tr>

                <tr valign="top">
                    <th>Кошелек Яндекс:</th>
                    <td><input name="stock_wallet" value="<?php echo get_option('stock_wallet'); ?>" placeholder="Кошелек Яндекс.Деньги" size="20">
                    </td>
                </tr>

<!--                <tr valign="top">
                    <th>Дата:</th>
                    <td><input id="stock_withdraw_date" type="datetime-local" name="stock_withdraw_date"
                               value="<?php /*echo current_time( 'Y-m-d\TH:i' ) */?>"> <label for="stock_withdraw_date">(формат <?php /*echo current_time( 'Y-m-d\TH:i' ) */?>
                            )</label></td>
                </tr>-->

            </table>

            <!-- 45 is Yandex.Dengi -->
            <input type="hidden" name="stock_withdraw_curr_id" value="45">
            <input type="hidden" name="redirect_url"
                   value="<?php echo admin_url( 'admin.php?page=stock-statistics' ) ?>">
            <input type="hidden" name="action" value="admin_stock_withdraw">

            <p><input type="submit" value="Вывести деньги" class="button button-primary"></p>
        </form>

        <h1>Сделки, пользователи</h1>
        <table class="form-table">

            <tr valign="top">
                <th>Зарегистрировано сегодня</th>
                <td><?php echo get_users_registered(current_time('Y-m-d')); ?> пользовател.</td>
            </tr>

            <tr valign="top">
                <th>Зарегистрировано за месяц</th>
                <td><?php echo get_users_registered(current_time('Y-m-01')); ?> пользовател.</td>
            </tr>


            <tr valign="top">
                <th>Юзеров онлайн</th>
                <td><?php echo bp_count_online_users(); ?> пользовател.</td>
            </tr>

            <tr valign="top">
                <th>Сделок завершено:</th>

                <td>
                    <?php $purchase_num_ending = array("сделка", "сделки", "сделок"); ?>
                    сегодня: <?php echo getNumEnding(get_orders_done('today'), $purchase_num_ending); ?><br>
                    неделя: <?php echo getNumEnding(get_orders_done('week'), $purchase_num_ending); ?><br>
                    месяц: <?php echo getNumEnding(get_orders_done('month'), $purchase_num_ending); ?><br>
                    <b>всего</b>: <?php echo getNumEnding(get_orders_done(null), $purchase_num_ending); ?>
                </td>
            </tr>


            <tr valign="top">
                <th>Статистика баланса пользователей:</th>

                <td>
                    <p>Пополнено всего: <?php echo format_balance( $total_in, true ) ?> |
                        Выведено всего: <?php echo format_balance( $total_out, true ) ?></p>

                    <p>Заработано всего: <?php echo count_total_revenue()?> |
                        Оплачено всего: <?php echo count_total_revenue( null, null, false )?>
                </td>
            </tr>


            <tr valign="top">
                <th>Нимов продано:</th>

                <td>
	                <?php $nims_sold_num_ending = array("ним", "нима", "нимов"); ?>
                    сегодня - <?php echo getNumEnding(get_total_nims_sold('today'), $nims_sold_num_ending); ?><br>
                    неделя - <?php echo getNumEnding(get_total_nims_sold('week'), $nims_sold_num_ending); ?><br>
                    месяц - <?php echo getNumEnding(get_total_nims_sold('month'), $nims_sold_num_ending); ?><br>
                    всего - <?php echo getNumEnding(get_total_nims_sold(), $nims_sold_num_ending); ?>
                </td>
            </tr>

        </table>

    </div>
	<?php
}
