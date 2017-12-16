<?php
require_once 'admin-stock-functions.php';
require_once 'marketplace-statistics-page.php';
require_once 'marketplace-statistics-days-page.php';

// create custom plugin settings menu
add_action( 'admin_menu', 'stock_create_menu' );

function stock_create_menu() {

	//create new top-level menu
	add_menu_page(
		'Настройки маркейтплейса',
		'Маркетплейс нимов',
		'administrator',
		basename( __FILE__ ),
		'stock_settings_page', get_template_directory_uri() . '/images/nims-coin.png', 50);

	add_submenu_page( basename( __FILE__ ), 'Отправить уведомление пользователю', 'Уведомление пользователю', 'manage_options', 'stock-notification', 'admin_user_notification_page' );


	add_submenu_page( basename( __FILE__ ), 'Статистика и вывод денег', 'Статистика и вывод денег', 'manage_options', 'stock-statistics', 'stock_statistics_page' );



	add_submenu_page( basename( __FILE__ ), 'Статистика по дням', 'Статистика по дням', 'manage_options', 'stock-statistics-days', 'stock_statistics_days_page' );

	add_submenu_page( basename( __FILE__ ), 'Транзакции', 'История транзакций', 'manage_options', 'marketplace-transactions', 'marketplace_transactions_page' );

	//call register settings function
	add_action( 'admin_init', 'register_stock_settings' );
}


function register_stock_settings() {
	register_setting( 'stock-settings-group', 'stock_commission' );
	register_setting( 'stock-settings-group', 'stock_bots' );
	register_setting( 'stock-settings-group', 'stock_min_balance' );
	register_setting( 'stock-settings-group', 'lot_max_pay_time' );
	register_setting( 'stock-settings-group', 'min_nims_amount' );
	register_setting( 'stock-settings-group', 'min_lot_sum' );
	register_setting( 'stock-settings-group', 'orders_per_page' );
	register_setting( 'stock-settings-group', 'yandex_notification_secret' );
	register_setting( 'stock-settings-group', 'yandex_commission' );
	register_setting( 'stock-settings-group', 'lot_purchase_nim_transfer_time' );
	register_setting( 'stock-settings-group', 'lot_nim_transfer_time' );
	register_setting( 'stock-settings-group', 'lot_nim_refund_time' );
	register_setting( 'stock-settings-group', 'yandex_stock_withdraw_account' );
	register_setting( 'stock-settings-group', 'stock_work_hours' );
}



add_action( 'admin_post_admin_user_notification', 'nims_admin_user_notification' );
function nims_admin_user_notification() {
	$user_id = (int) $_POST['user_id'];
	if ( ! $user_id ) {
		return;
	}

	$redirect_url = $_POST['redirect_url'];

	if ( send_notification_to_user( $user_id, "Сообщение от администраии биржи", sanitize_text_field( $_POST['notification_message'] ), true) ) {
		$redirect_url .= "&success=$user_id";
	} else {
		$redirect_url .= "&error=$user_id";
	}

	wp_safe_redirect( $redirect_url );
	exit();
}

function admin_user_notification_page() {
	?>
    <div class="wrap">
        <h2>Отправить уведомление пользователю</h2>

        <?php if ($_GET['success']) : ?>
            <div class="notice notice-success">
                <p>Сообщение пользователю #<?php echo $_GET['success'] ?> успешно отправлено.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input name="user_id" placeholder="ID пользователя"><br>
            <textarea name="notification_message" placeholder="Сообщение пользователю" cols="35"
                      rows="10"></textarea><br><br>
            <input type="hidden" name="redirect_url"
                   value="<?php echo admin_url( 'admin.php?page=stock-notification' ) ?>">
            <input type="hidden" name="action" value="admin_user_notification">
            <input type="submit" value="Отправить уведомление" class="button button-primary">
        </form>
    </div>
	<?php
}

function stock_settings_page() { ?>
    <div class="wrap">
        <h2>Настройки маркетплейса нимов</h2>

        <form method="post" action="options.php">
			<?php settings_fields( 'stock-settings-group' ); ?>
			<?php do_settings_sections( 'stock-settings-group' ); ?>
            <h2>Базовые</h2>
            <table class="form-table">
                <tr valign="top">
                    <th><label for="stock-commission">Комиссия маркетплейса (%)</label></th>
                    <td><input name="stock_commission" size="3"
                               value="<?php echo get_option( 'stock_commission' ); ?>" id="stock-commission"></td>
                </tr>


<!--                <tr valign="top">
                    <th scope="row"><label for="stock_work_hours">Рабочие часы биржи (чч:мм-чч:мм)</label></th>
                    <td><input type="text" name="stock_work_hours" size="10"
                               value="<?php /*echo get_option( 'stock_work_hours' ); */?>" id="stock_work_hours"></td>
                </tr>-->

            </table>
            <h2>Лимиты</h2>
            <table class="form-table">

                <tr valign="top">
                    <th><label for="stock-min-balance">Минимальная сумма на балансе для выплаты
                            (руб.):</label></th>

                    <td><input name="stock_min_balance" size="3"
                               value="<?php echo get_option( 'stock_min_balance' ); ?>" id="stock-min-balance"> ₽
                    </td>
                </tr>

                <tr valign="top">
                    <th><label for="min-nims-amount">Минимальное кол-во нимов для заказа</label></th>

                    <td><input name="min_nims_amount" size="5"
                               value="<?php echo get_option( 'min_nims_amount', 5000 ); ?>" id="min-nims-amount"></td>
                </tr>
                
                <tr valign="top">
                    <th><label for="orders_per_page">Кол-во заказов на 1 странице рынка нимов (not working)</label></th>

                    <td><input name="orders_per_page" size="5"
                               value="<?php echo get_option( 'orders_per_page', 20 ); ?>" id="orders_per_page">
                    </td>
                </tr>
            </table>

            <h2>Тайминги</h2>
            <?php /* <table class="form-table">

                <tr valign="top">
                    <th scope="row"><label for="lot-max-pay-time">Время на оплату лота (мин.):</label></th>

                    <td><input type="text" name="lot_max_pay_time" size="3"
                               value="<?php echo get_option( 'lot_max_pay_time', 15 ); ?>" id="lot-max-pay-time"> мин.
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="lot_purchase_nim_transfer_time">Время на передачу нимов после оплаты (час.):</label></th>

                    <td><input type="text" name="lot_purchase_nim_transfer_time" size="3"
                               value="<?php echo get_option( 'lot_purchase_nim_transfer_time'); ?>" id="lot_purchase_nim_transfer_time"> час.
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="lot_nim_transfer_time">Время на проверку передачи нимов бирже (час.):</label></th>

                    <td><input type="text" name="lot_nim_transfer_time" size="3"
                               value="<?php echo get_option( 'lot_nim_transfer_time'); ?>" id="lot_nim_transfer_time"> час.
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="lot_nim_refund_time">Время на возврат нимов пользователю (час.):</label></th>

                    <td><input type="text" name="lot_nim_refund_time" size="3"
                               value="<?php echo get_option( 'lot_nim_refund_time'); ?>" id="lot_nim_refund_time"> час.
                    </td>
                </tr>

            </table>

            <h2>Настройки Яндекса</h2>
            <table class="form-table">

                <tr valign="top">
                    <th><label for="yandex-commission">Комиссия Яндекса (%)</label></th>

                    <td><input name="yandex_commission" size="10"
                               value="<?php echo get_option( 'yandex_commission', 0.005 ); ?>" id="yandex-commission">
                    </td>
                </tr>


                <tr valign="top">
                    <th>
                        <label for="yandex_stock_withdraw_account">Аккаунт в Яндексе для вывода денег</label></th>
                    <td>
                        <input size="35" name="yandex_stock_withdraw_account" value="<?php echo get_option( 'yandex_stock_withdraw_account'); ?>" id="yandex_stock_withdraw_account">
                    </td>
                </tr>

                <tr valign="top">
                    <th><label for="yandex-notification-secret">Секретный код Яндекса для
                            HTTP-уведомлений</label></th>

                    <td><input name="yandex_notification_secret" size="35"
                               value="<?php echo get_option( 'yandex_notification_secret' ); ?>"
                               id="yandex-notification-secret"></td>
                </tr>

                <tr valign="top">
                    <th><label for="yandex-access-token">Access Token Yandex</label></th>
                    <td>
                        <input readonly size="35" value="<?php echo get_option( 'yandex-access-token' ); ?>"
                               id="yandex-access-token">
                        <button name="get_new_token" value="yandex_auth_token">Получить новый токен</button>
                    </td>
                </tr>


            </table><?php */ ?>

			<?php submit_button(); ?>

        </form>
    </div>
<?php } ?>