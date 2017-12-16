<?php
function stock_statistics_days_page() {
	if ( isset( $_POST['statistics_days'] ) ) {
		$date_from = $_POST['date_from'];
		$date_to = $_POST['date_to'];
		$earnings = get_earnings_for_period( $date_from, $date_to );
		if ( $earnings['error'] ) {
            echo '<div style="color: red; font-weight: bold; padding: 20px 0;">' . $earnings['error'] . '</div>';
			unset( $earnings );
		}
	}

	$month_begin = date( 'Y-m-01' );
	$today = date( 'Y-m-d', strtotime( 'today midnight' ) );
    ?>

    <div class="wrap">
        <h1>Статистика доходов по дням</h1>

        <form action="" method="post">
            <h3>Выберите период для отчета</h3>
            с: <input type="date" name="date_from" required value="<?php echo isset($date_from) ? $date_from : $month_begin; ?>"> по: <input type="date" name="date_to" required value="<?php echo isset($date_to) ? $date_to : $today; ?>">
            <input type="submit" value="Поехали">
            <input type="hidden" name="statistics_days" value="true">
        </form>

        <?php if ( isset( $earnings ) ) : ?>
            <h3>Доход с <b><?php echo date('d/m/Y', strtotime($date_from) ) ?></b> по
            <b><?php echo date('d/m/Y', strtotime($date_to) ) ?></b></h3>

        <table class="form-table stock-earnings-by-day">

            <?php foreach ($earnings as $earning) { ?>
			<tr valign="top">
                <th><?php echo $earning['date'] ?></th>
                <td><?php echo format_balance( $earning['value'], true ) ?></td>
            </tr>
            <?php } ?>
        </table>

        <?php endif; ?>

    </div>
	<?php
}
