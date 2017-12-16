<?php
$is_seller = get_current_user_id() == $seller->ID ;
$is_buyer = get_current_user_id() == $buyer->ID;
$is_admin = is_super_admin();
if ( $order_status == 'closed' || $order_status == 'done' || $order_status == 'cancelled' ) {
	return;
} else if ( ( $is_seller && ( $order_status != 'new' && $order_status != 'pending' ) ) ) {
    return;
} ?>

<form id="user-order-panel" action="" method="post">
	<input type="hidden" name="user-order-panel">
    <?php wp_nonce_field('oup_action_' . get_current_user_id() . 'nonce', 'user-panel-nonce'); ?>
	<?php
	/*
	 * ПАНЕЛЬ ПРОДАВЦА
	 */
	?>
	<?php
    if ( $is_admin && $order_status == 'dispute' ) { ?>
        <h3>Панель администратора</h3>
        <input type="hidden" name="admin-order-panel">
        <p>
            <b>В чью сторону решить спор?</b><br>

            <label><input type="radio" name="party-wins" value="seller">
                Продавец (<?php echo $seller->display_name; ?>)</label>

            <label><input type="radio" name="party-wins" value="buyer">
                Покупатель (<?php echo $buyer->display_name; ?>)</label>

            <label><input type="radio" name="party-wins" value="order-success">
                Спор завершился обоюдно (завершить заказ)</label>
        </p>
        <input type="submit" class="woo-sc-button teal">

    <?php } elseif ( $is_seller ) { ?>

		<h3>Панель продавца</h3>
		<?php if ( $order_status == 'new' ) {
		    $order_transfer_time = get_order_transfer_time( $post->ID );
			$max_deadline          = get_order_deadline( $post->ID );
			$order_garant_deadline = get_order_deadline_from_minutes( $post->ID, false,  $order_transfer_time); ?>

			<p>
				<b>Вы перевели нимы покупателю?</b> <small>ответить строго <b>ПОСЛЕ</b> передачи нимов</small>
				<button name="transfer-complete" value="yes">Да</button>
				<button name="transfer-complete" value="no" onclick="document.querySelector('.no-transfer-reason').style.display = 'block'; return false;">Нет (нет возможности)</button>

            </p>

			<p class="no-transfer-reason" style="display:none">
				Выберите причину:<br>
				<label><input type="radio" name="no_transfer_reason" value="out-of-stock" checked>
					Нет нимов в наличии (закрыть заказ)</label><br>

				<label><input type="radio" name="no_transfer_reason" value="account-banned">
					Мой аккаунт продавца забанили (закрыть заказ)</label><br>

                <?php if (order_closed_reason($post->ID) != 'more-details-required') : ?>
				<label><input type="radio" name="no_transfer_reason" value="more-details-required">
					От покупателя требуется <span rel="tooltip" title="в комментарии к заказу необходимо указать какую доп. информацию Вы хотите узнать от покупателя для перевода нимов">больше информации</span> для перевода нимов (отправить уведомление администратору, <u>не закрывать заказ</u>)</label>
                <?php endif; ?>

				<?php /* <br><label><input type="radio" name="no_transfer_reason" value="other-reason">
					Другая причина <input type="text" name="other_reason_text"> (закрыть заказ)</label> */ ?>

 <br><br><input type="submit" name="no-transfer-reason-submit" value="Отправить" class="woo-sc-button red"><br>
				<small>Если вы закроете заказ, Ваш рейтинг stockexchange продавца будет уменьшен, а отзыв о закрытии заказа с причиной отказа будет оставлен нашим stockexchange ботом в Вашем Личном кабинете.</small>
			</p>

            <?php if ($order_transfer_time == NMS_Shop_Settings::DEFAULT_MAX_TRANSFER_TIME) { ?>

            <p>Осталось времени на перевод нимов: <?php echo $order_garant_deadline['diff_lbl']; ?>
                (передать покупателю нимы до <?php echo $order_garant_deadline['final_date']->format( "d.m.Y H:i" ); ?> по МСК)
                <br>
                <small>В случае если вы не дадите ответ в указанное время, заказ автоматически закроется (будет
                    отменен), см. <a href="<?php echo home_url( '/rules/' ); ?>" target="_blank">правила stockexchange.com</a>.
                </small>
            </p>

            <?php } elseif ( $order_garant_deadline['diff_obj']->invert == 0 ) { ?>

            <p>Осталось <span rel="tooltip" title="гарант времени передачи нимов">основного времени</span> на
                перевод нимов: <?php echo $order_garant_deadline['diff_lbl']; ?>
                (передать покупателю нимы до <?php echo $order_garant_deadline['final_date']->format( "d.m.Y H:i" ); ?> по МСК)
                <br>
                <small>В случае если вы не дадите ответ в указанное время, покупатель сможет закрыть заказ в любое время, а время заказа будет продлено до <?php echo NMS_Shop_Settings::DEFAULT_MAX_TRANSFER_HOURS ?> часов, см. <a href="<?php echo home_url( '/rules/' ); ?>" target="_blank">правила stockexchange.com</a>.
                </small>
            </p>

            <?php } else { ?>

            <p>Осталось дополнительного времени на перевод нимов: <?php echo $max_deadline['diff_lbl']; ?>
                (передать покупателю нимы до <?php echo $max_deadline['final_date']->format( "d.m.Y H:i" ); ?> по МСК)
                <br>
                <small><b>Внимание: в дополнительное время покупатель может отменить заказ в любое время!</b>
                    <br>В случае если вы не дадите ответ в указанное время, заказ автоматически закроется (будет отменен),
                    см. <a href="<?php echo home_url( '/rules/' ); ?>" target="_blank">правила stockexchange.com</a>.
                </small>
            </p>
            <?php } ?>

	    <?php } elseif ( $order_status == 'pending' ) {
		    $reply_deadline = get_order_deadline( $post->ID, true ); ?>

            <p>Время для покупателя на подтверждение перевода нимов : <?php echo $reply_deadline['diff_lbl']; ?>
                (до <?php echo $reply_deadline['final_date']->format("d.m.Y H:i"); ?> по МСК)
                <br><small>В случае если покупатель не даст ответ в указанное время, заказ автоматически завершится в вашу пользу.</small>
            </p>
        <?php } else { ?>
            <p>Здесь будут отображаться действия необходимые совершить продавцу (Вам)</p>
		<?php }
		/*
		 * ПАНЕЛЬ ПОКУПАТЕЛЯ
		 */
		 } elseif ( $is_buyer || $is_admin ) { ?>

        <h3>Панель покупателя</h3>
        <?php if ( $order_status == 'new' ) {
            global $NMS_Shop_Settings;
            $order_transfer_time = get_order_transfer_time( $post->ID );
		    $max_transfer_deadline = get_order_deadline( $post->ID );
            if ($order_transfer_time == $NMS_Shop_Settings::DEFAULT_MAX_TRANSFER_TIME) { ?>

                <p>Время для продавца на перевод нимов : <?php echo $max_transfer_deadline['diff_lbl']; ?>
                    (до <?php echo $max_transfer_deadline['final_date']->format("d.m.Y H:i"); ?> по МСК)
                    <br><small>В случае если продавец не подтвердит передачу нимов в указанное время, заказ автоматически будет отменен, а деньги возвращены покупателю.</small>
                </p>

            <?php } else {
	            $transfer_deadline = get_order_deadline_from_minutes( $post->ID, false, $order_transfer_time );

	            if ( $transfer_deadline['diff_obj']->invert == 0 ) { ?>
                    <p><span rel="tooltip" title="Гарант времени сделки">Основное время</span> для продавца на перевод нимов : <?php echo $transfer_deadline['diff_lbl']; ?>
                        (до <?php echo $transfer_deadline['final_date']->format( "d.m.Y H:i" ); ?> по МСК)

	            <?php } else { ?>

                    <input type="hidden" name="buyer-cancel-order" value="true">
                    <p><b><span rel="tooltip" title="Гарант времени сделки">Основное время</span></b> на перевод нимов для продавца <b>истекло</b>.
                        Вы можете <button class="woo-sc-button red">отменить заказ</button><br>
                        <small>Пожалуйста, не отменяйте заказ если продавец не смог передать Вам нимы в основное время из-за проблем с вашим аккаунтом NIMSES, свяжитесь с <a href="<?php echo home_url('/contact/'); ?>">администрацией</a>.</small></p>

                    <p><u>Дополнительное время</u> для продавца на перевод нимов : <?php echo $max_transfer_deadline['diff_lbl']; ?>
                    (до <?php echo $max_transfer_deadline['final_date']->format( "d.m.Y H:i" ); ?> по МСК)
	            <?php } ?>

                    <br>
                    <small>В случае если продавец не подтвердит передачу нимов в основное время, вы сможете отменить
                        заказ или подождать ещё (максимальное время для перевода
                        нимов <?php echo $NMS_Shop_Settings::DEFAULT_MAX_TRANSFER_HOURS ?> часа). После этого заказ
                        автоматически будет отменен, а деньги возвращены покупателю.
                    </small>
                    </p>
            <?php } ?>

	<?php } elseif ( $order_status == 'pending' ) {
		    $max_deadline = get_order_deadline( $post->ID, $time_for_reply = true ); ?>
            <p>Продавец подтвердил передачу Вам нимов и ждёт Вашего подтверждения.</p>
            <p>
                <b>Вы получили нимы от продавца?</b>
                <button name="transfer-received" value="yes">Да</button>
                <button name="transfer-received" value="no">Нет, открыть спор</button>
            </p>

            <p>Осталось времени на ответ: <?php echo $max_deadline['diff_lbl']; ?>
                <br>
                <small>В случае если вы не дадите ответ в указанное время, заказ автоматически будет считаться
                    исполненным, см. <a href="<?php echo home_url( 'rules/' ) ?>" target="_blank">правила stockexchange.com</a>.
                </small>
            </p>
	    <?php } else { ?>
            <p>Здесь будут отображаться действия необходимые совершить покупателю (Вам)</p>
	    <?php } ?>
    <?php } ?>
</form>