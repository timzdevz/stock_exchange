<?php
global $NMS_Balance;

$topup = 0;
if ( isset( $_GET['topup'] ) ) {
	$topup = (float) $_GET['topup'];
}
$FK = FreeKassa::getInstance();
$order_id = bp_displayed_user_id();
?>
<div class="bp-custom-component topup-balance-component">

    <form action="<?php echo FreeKassa::CASH_URL; ?>" data-parsley-validate id="form-topup">
        <h3>Введите сумму пополнения</h3>

        <input type="hidden" name="m" value="<?php echo $FK->merchant_id; ?>">
        <input type="hidden" name="o" value="<?php echo $order_id; ?>">
        <input type="hidden" name="s" value="<?php echo $FK->get_fk_signature( $topup, $order_id ); ?>" id="topup-fk-signature">
        <input type="hidden" name="em"
               value="<?php echo get_userdata( bp_displayed_user_id() )->user_email ?>">

        <p>
            <input type="number" name="oa" id="topup-amount" step="0.01" min="<?php echo  ( $min_topup_balance = $NMS_Balance->get_min_topup_balance() ) ?>" placeholder="мин <?php echo $min_topup_balance; ?>"
                   value="<?php echo $topup ? $topup : '' ?>" required> &#8381; <br>
            <button type="submit" id="topup-submit" class="woo-sc-button teal" title="Перейти к выбору способа оплаты">Оплатить</button><br>
            <small>На следующей странице вы сможете выбрать способ оплаты</small>
        </p>

    </form>

</div>