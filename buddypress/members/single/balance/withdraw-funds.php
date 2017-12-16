<?php
$uid = bp_displayed_user_id();
$available_balance = get_user_balance( $uid );
$user_wallets = NMS_Balance::user_wallets( $uid );
$primary_wallet = get_user_meta( bp_displayed_user_id(), '_primary_wallet', true );
$mbw_default = $mbw = min_balance_withdraw(); ?>

<h3>Заявка на вывод средств</h3>
<form action="" method="post" data-parsley-validate>
<p class="wallet-select">

    <?php
    foreach ( NMS_Balance::get_withdraw_wallets() as $wallet_id => $payment_wallet ) {
        $selected_wallet = $primary_wallet == $wallet_id;
        $wallet_withdraw_min =
            isset ($payment_wallet['withdraw_min']) ? $payment_wallet['withdraw_min'] : $mbw_default;
        if ($selected_wallet) {
            $mbw = $wallet_withdraw_min;
        } ?>
        <label>
            <input type="radio" name="wallet" value="<?php echo $wallet_id?>"
                   id="<?php echo $wallet_id?>" required
                   data-withdraw-min="<?php echo $wallet_withdraw_min; ?>"
            <?php echo $selected_wallet ? ' checked="checked" ' : ''; ?>>

            <?php echo $payment_wallet['name']; ?>
            <input
                type="text" name="<?php echo $wallet_id?>"
                title="<?php echo isset( $payment_wallet['pattern_desc'] ) ? $payment_wallet['pattern_desc'] : ''; ?>"
                   value="<?php echo isset( $user_wallets[$wallet_id] ) ? esc_attr( $user_wallets[$wallet_id] ) : '' ?>"
                   <?php foreach ($payment_wallet['attr'] as $attr_key => $attr_value) {
                       echo ' ' . $attr_key . '="' . $attr_value . '"' ;} ?>
                   data-parsley-validate-if-empty="true"
                   data-parsley-required-if="#<?php echo $wallet_id; ?>:checked"> (<span rel="tooltip" title="Комиссия на вывод <?php echo $payment_wallet['name']; ?>"><?php echo $payment_wallet['comm']; ?>%</span>)</label>
        <?php } ?>
    <br><small>Яндекс.Деньги снимает свою платежную комиссию 0.5%. <b>Ни в коем случае не отменяйте платеж (счет) Яндекса</b>, мы не сможем вернуть вам деньги (из-за ограничений платежной системы).</small>
</p>

<p>
	<label>Сумма на вывод (доступно <?php echo format_balance( $available_balance, true ); ?>):<br>
		<input type="number" step="0.01" name="withdraw_amount" min="<?php echo $mbw; ?>" <?php if ( $available_balance >= $mbw ) : ?> max="<?php echo $available_balance ?>" <?php endif; ?> required placeholder="мин. <?php echo $mbw; ?>" <?php echo ! $available_balance ? 'disabled="disabled"' : ''?> id="withdraw_amount"> &#8381;</label><br>

</p>

<p>
	<input type="submit" value="Вывести средства" class="woo-sc-button teal" <?php echo ! $available_balance ? 'disabled="disabled"' : ''?>><br>
	<small>Вывод средств будет осуществлен в течение 0-12 часов после подачи заявки</small>
</p>
	<input type="hidden" name="withdraw-nonce"
	       value="<?php echo wp_create_nonce( $uid . NMS_Balance::WITHDRAW_NONCE_SUFFIX ); ?>">
</form>