<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
    <ul>
		<?php bp_get_options_nav(); ?>
    </ul>
</div><!-- .item-list-tabs -->

<p class="user-balance-summary">
    <b>Ваш баланс</b>: <?php echo format_balance( get_user_balance( bp_displayed_user_id() ), true ); ?>
    &nbsp;&nbsp;&nbsp; <b>Буфер</b>: <span rel="tooltip" title="Баланс в удержании">
        <?php echo format_balance( get_user_buffer_balance( bp_displayed_user_id() ), true ); ?></span>
</p>


<div class="bp-custom-component">
    <?php switch ( bp_current_action() ) :

	case 'transactions'   :
		bp_get_template_part( 'members/single/balance/transactions' );
		break;

	case 'topup' :
		bp_get_template_part( 'members/single/balance/topup' );
		break;

	case 'withdraw-funds' :
		bp_get_template_part( 'members/single/balance/withdraw-funds' );
		break;

endswitch; ?>
</div>