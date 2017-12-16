<?php
global $NMS_Shop_Settings;
$transfer_time = $NMS_Shop_Settings->transfer_minutes_to_hours_minutes( get_order_transfer_time( $post->ID ) );
$user_balance = get_user_balance( $buyer->ID );
$order_total = get_order_total( $post->ID  );
$user_enough_funds = $user_balance >= $order_total;

$nimses_login = get_order_nimses_login( $post->ID );
$order_description = get_order_description( $post->ID );

$data_changed = $_SESSION['order_data_changed'];
unset( $_SESSION['order_data_changed'] );
?>
<script type="text/javascript">
    window.orderTotal = <?php echo $order_total; ?>;
</script>
<article <?php post_class(); ?>>

    <header>
        <h2>
            <a href="<?php the_permalink(); ?>" rel="bookmark"
               title="<?php the_title_attribute(); ?>">Подтверждение заказа #<?php echo $post->ID; ?></a>
        </h2>
    </header>

    <div id="template-notices" role="alert" aria-atomic="true">
		<?php do_action( 'template_notices' ); ?>
    </div>


    <section class="entry fix">

        <h3>Детали заказа: </h3>

        <table class="order-details">
            <tr><td>Продавец:</td>
                <td><b><a href="<?php echo bp_core_get_user_domain( $seller->ID ); ?>" target="_blank">
                        <?php echo $seller->display_name ?></a></b></td>
            </tr>

            <tr><td><span rel="tooltip" title="Если продавец не переведет нимы в течение указанного времени, вы сможете отменить заказ и купить нимы у другого продавца, при этом рейтинг текущего продавца понизится.">Гарант времени передачи нимов:</span></td>
                <td class="<?php echo isset( $data_changed['transfer_time']) ? 'data-changed' : '';?>">
                    <?php echo $transfer_time['h'] ?
                        getNumEnding($transfer_time['h'], array('час', 'часа', 'часов'), true) : ''; ?>

                    <?php echo $transfer_time['m'] ?
	                    getNumEnding($transfer_time['m'], array('минута', 'минуты', 'минут'), true) : ''; ?>
                </td>
            </tr>

            <tr><td>Количество нимов: </td>
                <td class="<?php echo isset( $data_changed['nims_amount']) ? 'data-changed' : '';?>"><span class="nim-coin-icon"><?php echo number_format(get_order_nims_amount( $post->ID ), 0, '.', ' '); ?></span></td></tr>

            <tr><td><b>Цена:</b></td>
                <td class="<?php echo isset( $data_changed['price']) ? 'data-changed' : '';?>"><b><?php echo format_balance(get_order_price( $post->ID ), true ); ?></b></td></tr>

            <tr><td><b>Итого:</b></td>
                <td class="<?php echo isset( $data_changed['total']) ? 'data-changed' : '';?>"><b><?php echo format_balance( $order_total, true); ?></b></td></tr>
        </table>

        <form action="" method="post" class="order-confirmation-form" data-parsley-validate>
            <input type="hidden" name="order-confirmation" value="true">

            <p><label><b>Ваш логин в NIMSES:</b><br>
                    <input type="text" name="nimses_login" placeholder="для перевода нимов" required value="<?php echo $nimses_login ? $nimses_login : ''; ?>" maxlength="50"></label>
            <br><small>Внимание: если в вашем логине NIMSES есть точка, обязательно сообщите об этом продавцу, (т.к. в данный момент приложение NIMSES не находит аккаунты где присутствуют точки). Продавец в комментарии к заказу предоставит свой логин в приложении и вы должны будете написать ему сообщение внутри приложения. Таким образом продавец найдет ваш аккаунт и сможет перевести Вам нимы.</small></p>

            <p>
                <label><b>Дополнительная информация для продавца (необязательно):</b><br>
                <textarea maxlength="500" name="order_description" cols="50" rows="3"><?php echo $order_description ? $order_description : ''?></textarea></label><br>
                <small>Например если вы хотите чтобы продавец перевел нимы по частям, на несколько аккаунтов и т.д.<br>
                Запрещается указывать контактную информацию.</small>
            </p>


            <p class="order-payment">
                <label><b>Оплата заказа:</b><br>
                    <?php if ( $user_enough_funds ) : ?>
                        <input type="radio" checked> Оплата с внутреннего баланса
                        (<?php echo format_balance( $user_balance, true ); ?>)</label></p>

                    <?php else:
                        $price_diff = $order_total - $user_balance; ?>
                        <input type="radio" checked> Оплата с внутреннего баланса

                        (<span class="user-balance"><?php echo format_balance( $user_balance, true );
                        ?></span><span id="topup-note">, <b>необходимо <u><a id="topup-link" href="<?php echo bp_core_get_user_domain( $buyer->ID ) ?>balance/?topup=<?php echo $price_diff; ?>" target="_blank">пополнить баланс на
            <span id="topup-amount"><?php echo format_balance($price_diff, true)?></span></a></u></b></span>)</label>

                        <small id="refresh-btn" rel="tooltip" title="После пополнения баланса нажмите на эту кнопку">Обновить баланс</small>
                    <?php endif; ?>

            <p>
                <span style="font-size: 90%">Создавая и оплачивая заказ Вы подтверждаете согласие с <a href="<?php echo home_url( 'rules/' );?>">правилами работы</a> торговой площадки stockexchange.com</span>
            </p>
            <p>
                <input id="confirm-order-submit" type="submit" value="Оплатить и подтвердить заказ" class="woo-sc-button teal"
                <?php echo ! $user_enough_funds ? 'disabled' : '' ?>>
                <button name="cancel-order" class="link-button" onclick="this.form.nimses_login.removeAttribute('required'); jQuery(this.form).parsley().destroy();">Отменить заказ</button>
                <br>
                <small>После подтверждения заказа сумма заказа будет списана с баланса
                    и будет удержана до момента завершения заказа.
                </small>
            </p>
        </form>


    </section>

</article><!-- .post -->