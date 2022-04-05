<?php
/**
 * Plugin Name: Contact Form 7 + PKF Attest
 * Plugin URI:  https://www.merkatu.com/
 * Description: Conexión entre Contact Form 7 y la API de PKF Attest
 * Version:     1.0
 * Author:      MERKATU
 * Author URI:  https://www.merkatu.com/
 * License:     GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7_pkf_attest
 *
 * PHP 7.3
 * WordPress 5.5.3
 */

//ini_set("display_errors", 1);

define('CF7_PKF_ATTEST_API_URL', get_option("_cf7_pkf_attest_api_url")); 
define('CF7_PKF_ATTEST_API_USER', get_option("_cf7_pkf_attest_api_user"));
define('CF7_PKF_ATTEST_API_PASS', get_option("_cf7_pkf_attest_api_password"));

//Cargamos librerías de conexión a la API
require_once(dirname(__FILE__)."/api.php");

//Cargamos las funciones que crean las páginas en el WP-ADMIN
require_once(dirname(__FILE__)."/admin.php");

//CReamos un directorio al activar el plugin
define( 'CF7_PKF_ATTEST_PLUGIN_FILE', __FILE__ );
register_activation_hook(CF7_PKF_ATTEST_PLUGIN_FILE, 'cf7_pkf_attest_plugin_activation' );
function cf7_pkf_attest_plugin_activation() {
  if ( ! current_user_can( 'activate_plugins' ) ) return;
  if(!is_dir(dirname(__FILE__)."/logs/")){
    mkdir(dirname(__FILE__)."/logs/", 0755);
  }
}

//HOOK de CF/
add_action("wpcf7_before_send_mail", "wpcf7_do_cf7_pkf_attest");

function wpcf7_do_cf7_pkf_attest(&$wpcf7_data) {
  //$wpcf7 = WPCF7_ContactForm::get_current();
  //if(!in_array($wpcf7_data->id, CF7_PKF_ATTEST_CFORMS)) return; //Chequeamos que sea uno de los formualrios aceptados
  $submission = WPCF7_Submission::get_instance();
  $formdata = $submission->get_posted_data();
  //print_r($formdata);

  //Formato del JSON -------------------------------------------------------------------------
  $json = [
    "idAccionFormativa" => false,
    "formatoAsistencia" => false,
    "formaPago" => false,
    "iban" => false,
    "aPlazos" => false,
    "alumnoComoPagador" => false,
    "contacto" => [
      "nombre" => false,
      "tipoIdentificador" => false,
      "dni" => false,
      "fechaNac" => false,
      "direccion" => false,
      "email" => false,
      "tel1" => false,
      "cp" => false,
      "municipio" => false
    ],
    "receptor" => [
      "razonSocial" => false,
      "tipoIdentificador" => false,
      "numIdentificador" => false,
      "domicilio" => false,
      "cp" => false,
      "telefono1" => false,
      "email" => false,
      "municipio" => false,
    ]
  ];
  
  //echo $json."\n----------------------------\n";
  $response = insertLead(json_encode($json));
  print_r($response);
}
