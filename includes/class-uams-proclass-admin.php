<?php
add_action( 'admin_menu', 'uamswp_proclass_add_admin_menu' );
add_action( 'admin_init', 'uamswp_proclass_settings_init' );


function uamswp_proclass_add_admin_menu(  ) { 

	add_options_page( 'UAMSWP Proclass', 'UAMSWP Proclass', 'manage_options', 'uamswp_proclass', 'uamswp_proclass_options_page' );

}


function uamswp_proclass_settings_init(  ) { 

	register_setting( 'pluginPage', 'uamswp_proclass_settings' );

	add_settings_section(
		'uamswp_proclass_pluginPage_section', 
		__( 'Proclass API Authentication', 'uamswp-proclass-calendar' ), 
		'uamswp_proclass_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'uamswp_proclass_authentication_user', 
		__( 'Username', 'uamswp-proclass-calendar' ), 
		'uamswp_proclass_authentication_user_render', 
		'pluginPage', 
		'uamswp_proclass_pluginPage_section' 
	);

	add_settings_field( 
		'uamswp_proclass_authentication_pass', 
		__( 'Password', 'uamswp-proclass-calendar' ), 
		'uamswp_proclass_authentication_pass_render', 
		'pluginPage', 
		'uamswp_proclass_pluginPage_section' 
	);


}


function uamswp_proclass_authentication_user_render(  ) { 

	$options = get_option( 'uamswp_proclass_settings' );
	?>
	<input type='text' name='uamswp_proclass_settings[uamswp_proclass_authentication_user]' value='<?php echo $options['uamswp_proclass_authentication_user']; ?>'>
	<?php

}


function uamswp_proclass_authentication_pass_render(  ) { 

	$options = get_option( 'uamswp_proclass_settings' );
	?>
	<input type='text' name='uamswp_proclass_settings[uamswp_proclass_authentication_pass]' value='<?php echo $options['uamswp_proclass_authentication_pass']; ?>'>
	<?php

}


function uamswp_proclass_settings_section_callback(  ) { 

	echo __( 'Please change the settings accordingly.', 'uamswp-proclass-calendar' );

}


function uamswp_proclass_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<h2>UAMSWP Proclass</h2>

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}