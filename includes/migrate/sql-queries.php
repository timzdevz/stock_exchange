<?php

if (! current_user_can('manage_options') )
	return;

if ( isset($_GET['update_user_meta_welcome_message']) ) {
	//update_user_meta_welcome_message();
}
function update_user_meta_welcome_message() {
	$user_query = get_users(array(
		'fields' => array( 'id' )
	));

	foreach ( $user_query as $user ) {
		update_user_meta( $user->id, 'welcome_message_viewed', true );
	}

	echo "updated";
}