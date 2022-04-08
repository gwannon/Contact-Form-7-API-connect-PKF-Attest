<?php

//ADMIN -----------------------------------------

add_action( 'admin_menu', 'cf7_pkf_attest_plugin_menu' );
function cf7_pkf_attest_plugin_menu() {
	add_options_page( __('Administración API PKF Attest', 'cf7_pkf_attest'), __('PKF Attest', 'cf7_pkf_attest'), 'manage_options', 'cf7_pkf_attest', 'cf7_pkf_attest_page_settings');
}

function cf7_pkf_attest_page_settings() { 
	//echo "<pre>"; print_r($_REQUEST); echo "</pre>";
	if(isset($_REQUEST['send']) && $_REQUEST['send'] != '') { 
		update_option('_cf7_pkf_attest_api_url', $_POST['_cf7_pkf_attest_api_url']);
		update_option('_cf7_pkf_attest_api_user', $_POST['_cf7_pkf_attest_api_user']);
		if ($_POST['_cf7_pkf_attest_api_password'] != '') update_option('_cf7_pkf_attest_api_password', $_POST['_cf7_pkf_attest_api_password']);
		?><p style="border: 1px solid green; color: green; text-align: center;"><?php _e("Datos guardados correctamente.", 'cf7_pkf_attest'); ?></p><?php
	} ?>
	<form method="post">
		<h1><?php _e("Configuración de la conexión con API PKF Attest", 'cf7_pkf_attest'); ?></h1>
		<h2><?php _e("URL final de la API", 'cf7_pkf_attest'); ?>:</h2>
		<input type="text" name="_cf7_pkf_attest_api_url" value="<?php echo get_option("_cf7_pkf_attest_api_url"); ?>" style="width: 100%" /><br/><br/>
		<h2><?php _e("Usuario de la API", 'cf7_pkf_attest'); ?>:</h2>
		<input type="text" name="_cf7_pkf_attest_api_user" value="<?php echo get_option("_cf7_pkf_attest_api_user"); ?>" /><br/><br/>
		<h2><?php _e("Contraseña de la API", 'cf7_pkf_attest'); ?>:</h2>
		<input type="password" name="_cf7_pkf_attest_api_password" value="" /><br/><br/>
		<input type="submit" name="send" value="Enviar" />
	<?php
}
