
<?php
global $NMS_Shop_Settings;
$uid = bp_displayed_user_id(); // case inside user profile (shop)
if ( ! $uid ) {
	$uid = bp_get_member_user_id(); // case shop list
}

if ( $NMS_Shop_Settings->user_sells_nims( $uid ) ) {
	$price_depends = $NMS_Shop_Settings->nims_price_dynamic($uid); ?>
    <div class="user-shop <?php echo $price_depends ? 'price-depends' : ''; ?>" data-shop-id="<?php echo $uid ?>">


    <div class="user-shop-title">
        <span class="pretext">Продает</span>
        <span class="nim-coin-icon"><?php echo number_format($nim_amount = $NMS_Shop_Settings->get_user_sell_nim_amount($uid), 0, '.', ' '); ?></span>
        <span class="pretext"><?php echo $price_depends ? 'от' : 'по' ?></span>
		<?php echo $nim_price = $NMS_Shop_Settings->get_user_shop_nim_price($uid) ?> &#8381;
    </div>

	<?php
	/*
	 * Price grouping
	 */
	if ($price_depends) {
		$pricing_groups = $NMS_Shop_Settings->get_user_pricing_groups( $uid, 'ARRAY_A' );
		$pricing_group_count = 1;
		$pricing_groups_total = count( $pricing_groups );
		?>
        <div class="user-shop-pricing-groups">
			<?php
			foreach ( $pricing_groups as $pricing_group ) { ?>
                <div><span class="pg-min-quantity" data-min-quantity="<?php echo $pricing_group['min_quantity'] ?>"><?php echo number_format($pricing_group['min_quantity'], 0, '.', ' ') ?></span>
					<?php if ($pricing_group_count == $pricing_groups_total) { ?>
                        и больше
					<?php } else { ?>
                        - <span class="pg-max-quantity" data-max-quantity="<?php echo $pricing_group['max_quantity'] ?>"><?php echo number_format($pricing_group['max_quantity'], 0, '.', ' ') ?></span>
					<?php } ?>
                    = <span class="pg-price" data-price="<?php echo $pricing_group['price'] ?>"><?php echo $pricing_group['price'] ?></span> &#8381; / <span class="nim-coin-icon nim-coin-icon-small">1000</span></div>
				<?php $pricing_group_count++; } ?>
        </div>
	<?php } ?>

	<?php if ( get_current_user_id() != $uid ) { ?>
        <form action="" method="post" class="bp-custom-component" data-parsley-validate>
            <input type="hidden" name="user-buy-nims" value="true">
            <input type="hidden" name="seller_id" value="<?php echo $uid; ?>">
            <input type="number"
                   class="nim-amount-input shop-nim-amount"
                   name="shop_nim_amount"
                   placeholder="мин. <?php echo $min_nim_amount = $NMS_Shop_Settings->get_min_nims_amount(); ?>"
                   min="<?php echo $min_nim_amount ?>"
                   max="<?php echo $nim_amount; ?>"
                   required>
            <span class="shop-price">Цена: <span class="shop-price-amount" data-price-amount="<?php echo $nim_price; ?>"><?php echo $nim_price; ?></span> &#8381;</span>
            <div class="shop-price-total">Итого: <span class="shop-price-total-amount">0.00</span> &#8381;</div>

            <input type="submit" value="Купить нимы" class="woo-sc-button red buy-btn">
        </form>
	<?php } ?>
    </div>
<?php } ?>
