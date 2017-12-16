<?php
global $NMS_Shop_Settings;
$uid                  = bp_displayed_user_id();
$nims_price_depends   = $NMS_Shop_Settings->nims_price_dynamic($uid);
$user_verified_status = $NMS_Shop_Settings->user_verified_status( $uid );
$verification_pending = $user_verified_status === 'pending';
?>

<script type="text/javascript">
    window.minNimAmount = <?php echo $NMS_Shop_Settings->get_min_nims_amount(); ?>;
</script>

<div class="bp-custom-component">

    <h2 class="bp-screen-reader-text">Продажа нимов</h2>

    <div class="clearfix"></div>

    <div class="user-shop-settings-container">

        <form name="user-shop-settings-form" method="post" action="" data-parsley-validate data-parsley-excluded=":hidden">

            <?php if ( ! $user_verified_status && ( $message = $NMS_Shop_Settings->get_verified_message( $uid ) ) ) { ?>
                <p class="user-verification-admin-notice">Администратор отклонил проверку со следующим сообщением: <br><b><?php echo esc_html($message); ?></b><br><br>
                Измените данные профиля согласно комментарию и отправьте магазин на проверку заного!</p>

            <?php } elseif ( $user_verified_status === "1" ) { ?>

            <p>
                <input type="checkbox" id="sell-nims" name="user_sells_nims" <?php echo $NMS_Shop_Settings->user_sells_nims($uid) ? 'checked' : '' ?> <label for="sell-nims"> <b>Я продаю нимы</b> (включить магазин)</label>
            </p>

                <p>
                    <b>Ваш stockexchange рейтинг продавца:</b> <span class="tooltip" rel="tooltip" title="За каждую успешную сделку +1, за каждую неудачную -2. <br>Если рейтинг ниже 5, продавец больше не сможет продавать нимы. <br> Максимальный рейтинг 15.00. Рейтинг по-умолчанию у всех продавцов 10.00"><?php echo get_stockexchange_rating( $uid, true ); ?></span>

	            <?php if ( ! stockexchange_rating_ok( $uid ) ) : ?>
                    <br><small style="color: red;">У вас слишком низкий рейтинг stockexchange продавца. Вы больше не можете продавать нимы.</small>
	            <?php endif; ?>
                </p>


            <?php } ?>




            <p><b>Имя магазина:</b> <?php echo get_userdata($uid)->display_name ?> (<a href="<?php echo bp_members_edit_profile_url('', $uid); ?>" target="_blank">редактировать</a>)</p>

            <p><b>Аватар: </b> <a href="<?php echo bp_core_get_user_domain( $uid ) . bp_get_profile_slug() . '/change-avatar/'; ?>" target="_blank">редактировать</a></p>
            <p>
                <label><b>Описание магазина:</b><br>
                    <textarea required maxlength="160" minlength="50" name="shop_description" rows="3" cols="45" id="shop_description"><?php echo esc_html( $NMS_Shop_Settings->get_user_shop_description( $uid ) ); ?></textarea><br>
                <small>Укажите информацию для покупателя, как быстро вы передаете нимы,<br> откуда вы их берете, сможете ли передать нимы по частям и т.д. Запрещено указывать контакную информацию. </small></label>
            </p>

            <p>
                <?php $garant_time = $NMS_Shop_Settings->get_user_transfer_time($uid, true);?>
                <label for="nim_amount"><b>Ваш гарант времени</b>
                </label>
                <input type="number"
                       min="0"
                       max="24"
                       placeholder="часы"
                       name="transfer_hours"
                       id="transfer_hours"
                       size="2"
                       required
                       onchange="document.getElementById('transfer_minutes').setAttribute('min', +this.value === 0 ? '<?php echo $NMS_Shop_Settings::DEFAULT_MIN_TRANSFER_TIME; ?>' : '0')"
                       value="<?php echo $garant_time['h'] ?>"> ч.

                <input type="number"
                       min="<?php echo $garant_time['h'] == 0 ? $NMS_Shop_Settings::DEFAULT_MIN_TRANSFER_TIME : 0; ?>"
                       max="59"
                       placeholder="минуты"
                       name="transfer_minutes"
                       id="transfer_minutes"
                       size="2"
                       required
                       value="<?php echo $garant_time['m'] ?>"
                       onchange="
                       document.getElementById('transfer_hours').setAttribute('min', +this.value === 0 ? '1' : '0');
                       document.getElementById('transfer_hours').setAttribute('max', +this.value === 0 ? '24' : '23')"> м.
                <br><small>С помощью "гаранта времени" вы можете пообещать покупателю что переведете нимы НЕ ПОЗЖЕ установленного времени. <br>Если время передачи истекло, то покупателен сам вправе выбрать отменять заказ или подождать ещё (максимум 24 часа).<br><b>Указывайте время перевода строго в том случае, если готовы перевести нимы в заданный период.</b> В случае отмены заказа рейтинг stockexchange продавца будет понижен. <br>Минимум 10 минут, максимум 24 часа. Не рекомендуем ставить слишком низкое значение.</small>
            </p>

            <p>
                <label for="nim_amount"><b>Количество <span class="nim-coin-icon">нимов</span> на продажу (ваш резерв нимов):</b></label>
                <input type="number"
                       min="<?php echo $min_nim_amount = $NMS_Shop_Settings->get_min_nims_amount()?>"
                       max="<?php echo $max_nim_amount = $NMS_Shop_Settings->get_max_nims_amount(); ?>"
                       placeholder="мин. <?php echo $min_nim_amount ?>"
                       name="nim_amount"
                       id="nim_amount"
                       required
                       class="nim-amount-input"
                       value="<?php echo $NMS_Shop_Settings->get_user_sell_nim_amount($uid);?>">
            </p>


            <p>
                <b>Цена зависит от количества покупаемых нимов</b>
                <label><input type="radio" name="nims_price_depends" class="nims-price-depends" value="0"
                        <?php echo ! $nims_price_depends ? 'checked' : '' ?>>Нет</label>
                <label><input type="radio" name="nims_price_depends" class="nims-price-depends" value="1"
                        <?php echo $nims_price_depends ? 'checked' : '' ?>> Да</label>
                <br>
                <small>В первой группе минимальное и в последней группе максимальное значения игнорируется.</small>
            </p>

            <p id="nims-price-static" class="<?php echo $nims_price_depends ? 'hidden' : '' ?>"><label for="nim_price">Цена за 1000 нимов: </label>
                <input type="number" step="0.01" name="nim_price" id="nim_price" required class="nim-price"
                value="<?php echo $NMS_Shop_Settings->get_user_sell_nim_price($uid) ?>" min="0.01"
                > ₽
            </p>

            <div class="dynamic-pricing-group-container <?php echo ! $nims_price_depends ? 'hidden' : '' ?> noselect">
                <?php
                $pricing_group_count = 0;
                $pricing_groups = $NMS_Shop_Settings->get_user_pricing_groups($uid);
                $total_pricing_groups_count = count($pricing_groups);
                foreach ($pricing_groups as $user_pricing_group) {
	                $pricing_group_id = $user_pricing_group->id; ?>
                    <div class="dynamic-pricing-group">
                        <label>от
                            <input type="number" name="price_grouping[<?php echo $pricing_group_id ?>][min_quantity]"
                                  class="nim-amount-input nim-quantity-min"
                                  value="<?php echo $user_pricing_group->min_quantity ?>"
                                   min="<?php echo $min_nim_amount; ?>"
                                   max="<?php echo $max_nim_amount; ?>"
                                   placeholder="кол-во нимов"
                                  readonly></label>
                        <label>до
                            <input type="number" name="price_grouping[<?php echo $pricing_group_id ?>][max_quantity]"
                                   value="<?php echo $user_pricing_group->max_quantity ?>"
                                   min="<?php echo $min_nim_amount; ?>"
                                   max="<?php echo $max_nim_amount; ?>"
                                   class="nim-amount-input nim-quantity-max"
                                   placeholder="кол-во нимов"
                                   required
                                   <?php echo $pricing_group_count + 1 != $total_pricing_groups_count ? 'readonly' : '' ?>
                            ></label>
                        <label>Цена за 1000 нимов: <input type="number" step="0.01" name="price_grouping[<?php echo $pricing_group_id ?>][price]" class="nim-price" value="<?php echo $user_pricing_group->price ?>" required min="0.01"> &#8381;</label>

                        <?php if ($pricing_group_count > 0) { ?>
                            <?php if ($pricing_group_count + 1 == $total_pricing_groups_count) { ?>
                                <a class="add-pricing-rule"></a>
	                        <?php } ?>
                            <a class="delete-pricing-rule"></a>
                        <?php } $pricing_group_count++; ?>
                    </div>
                <?php } ?>

            </div>

            <p>
                <?php
                $btn_val = "Сохранить";
                if ( $verification_pending ) {
	                $btn_val = "Магазин находится на проверке";
                } elseif ( ! $user_verified_status ) {
	                $btn_val = "Отправить магазин на проверку";
                }

                ?>
                <input type="submit" value="<?php echo $btn_val; ?>" class="woo-sc-button teal" <?php echo $verification_pending ? 'disabled' : '' ?>>
                <?php if ( $verification_pending ||  ! $user_verified_status ) { ?>
                    <br><small>При успешной верификации, ваш магазин будет активирован и отобразиться в общем списке продавцов.</small>
                <?php } ?>
                <input type="hidden" name="save_shop_settings" value="true">
                <?php if (is_super_admin()) { ?>
                    <p style="padding: 15px; background-color: #efefef; border: 1px solid #ccc; display: inline-block; max-width: 100%; box-sizing: border-box">
                        <b>[Администратор] Верификация пользователя</b><br><br>
                        <label><input type="radio" name="verification_status" value="1"> Одобрить</label>
                        <label><input type="radio" name="verification_status" value="0"> Отклонить</label><br>

                        <textarea name="admin_verified_message" cols="35" rows="3" placeholder="Комментарий к верификации" style="max-width: 100%;"></textarea><br>
                        <input type="submit" name="verification_admin" value="Сохранить" class="woo-sc-button teal">
                    </p>
                <?php } ?>
            </p>
        </form>

    </div>
</div>

<script type="text/html" id="dynamic-pricing-group-template">
    <div class="dynamic-pricing-group">
        <label>от <input type="number"
                                  name="price_grouping[][min_quantity]"
                                  class="nim-amount-input nim-quantity-min"
                                  min="<?php echo $min_nim_amount; ?>"
                                  max="<?php echo $max_nim_amount; ?>"
                                  placeholder="кол-во нимов"
                                  readonly></label>
        <label>до <input type="number"
                                   name="price_grouping[][max_quantity]"
                                   class="nim-amount-input nim-quantity-max"
                                   min="<?php echo $min_nim_amount; ?>"
                                   max="<?php echo $max_nim_amount; ?>"
                                   placeholder="кол-во нимов"
                                   required></label>
        <label>Цена за 1000 нимов: <input type="number" step="0.01" name="price_grouping[][price]" class="nim-price" required min="0.01"> &#8381;</label>
        <a class="add-pricing-rule"></a>
        <a class="delete-pricing-rule"></a>
    </div>
</script>

