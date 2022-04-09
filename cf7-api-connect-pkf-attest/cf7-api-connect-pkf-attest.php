<?php
/**
 * Plugin Name: Contact Form 7 + PKF Attest
 * Plugin URI:  https://www.merkatu.com/
 * Description: Conexión entre Contact Form 7 y la API de PKF Attest usando los shortocdes [curso id="xxx"] (en el formulario) e [infocurso] en el mail
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
add_action("wpcf7_before_send_mail", "cf7_pkf_attest_before_send_mail");

function cf7_pkf_attest_before_send_mail(&$wpcf7_data) {
  $submission = WPCF7_Submission::get_instance();
  $formdata = $submission->get_posted_data();

  if(isset($formdata['pkf_attest_id'])) {
    print_r($formdata);

    //Formato del JSON -------------------------------------------------------------------------
    $json = [
      "idAccionFormativa" => $formdata['pkf_attest_id'],
      "formatoAsistencia" => $formdata['pkf_attest_asistencia'][0],
      "formaPago" => $formdata['pkf_attest_pago'][0],
      "iban" => $formdata['pkf_attest_iban'],
      "aPlazos" => ($formdata['pkf_attest_plazos'][0] == 1 ? true : false),
      "alumnoComoPagador" => ($formdata['pkf_attest_estudiante_como_pagador'][0] == 1 ? "true" : "false"),
      "contacto" => [
        "nombre" => $formdata['pkf_attest_estudiante_nombre'],
        "tipoIdentificador" => $formdata['pkf_attest_estudiante_tipo_identidad'][0],
        "dni" => $formdata['pkf_attest_estudiante_identidad'],
        "fechaNac" => $formdata['pkf_attest_estudiante_fecha_nacimiento'],
        "direccion" => $formdata['pkf_attest_estudiante_direccion'],
        "email" => $formdata['pkf_attest_estudiante_email'],
        "tel1" => $formdata['pkf_attest_estudiante_telefono'],
        "cp" => $formdata['pkf_attest_estudiante_cp'],
        "municipio" => $formdata['pkf_attest_estudiante_ciudad']
      ]
    ];

    if($formdata['pkf_attest_estudiante_como_pagador'][0] != 1) $json['receptor'] = [
      "razonSocial" =>  $formdata['pkf_attest_receptor_nombre'],
      "tipoIdentificador" =>  $formdata['pkf_attest_receptor_tipo_identidad'][0],
      "numIdentificador" =>  $formdata['pkf_attest_receptor_identidad'],
      "domicilio" =>  $formdata['pkf_attest_receptor_direccion'],
      "cp" =>  $formdata['pkf_attest_receptor_cp'],
      "telefono1" =>  $formdata['pkf_attest_receptor_telefono'],
      "email" =>  $formdata['pkf_attest_receptor_email'],
      "municipio" =>  $formdata['pkf_attest_receptor_ciudad'],
    ];
    print_r($json);
    $response = insertLead(json_encode($json));
    print_r($response);
  }
}

// Activate shortcodes inside contact-form-7 forms and mails
add_filter( 'wpcf7_contact_form_properties', 'cf7_pkf_attest_shortcodes_in_forms' );
function cf7_pkf_attest_shortcodes_in_forms( $form ) {
  if (!is_admin()) $form['form'] = do_shortcode($form['form']);
  return $form;
}

add_filter( 'wpcf7_special_mail_tags', 'cf7_pkf_attest_shortcodes_in_mails', 10, 3 );
function cf7_pkf_attest_shortcodes_in_mails( $output, $name, $html ) {
  if ('curso_allinfo' == $name)$output = do_shortcode( "[$name]" );
  return $output;
}

//Validate form
function cf7_pkf_attest_validate_form ( $result, $tags ) {
  $submission = WPCF7_Submission::get_instance();
  $formdata = $submission->get_posted_data();
  //print_r($tags);
  if( isset($formdata['pkf_attest_id']) && $formdata['pkf_attest_id'] > 0) { //Chequeamos que tenga id de curso
    if($formdata['pkf_attest_plazos'] != 'sadasdasd') {
      //$result->invalidate('pkf_attest_plazos', 'Los tickets deben ser de diferentes tiendas.');
    }
  }
  //print_r($result);
  return $result;
}
add_filter('wpcf7_validate', 'cf7_pkf_attest_validate_form', 10, 2 );


//Shortcodes for contact-form-7
function cf7_pkf_attest_shortcode_form ($params = array(), $content = null) {
  ob_start();
  $curso = getCurso($params['id']); ?>
  <div id="inscripcion_pkf">
    [hidden pkf_attest_id "<?=$params['id'] ?>"]
    <?php 
      if ($curso->plazasDisponibles > 0  ) {
        if($curso->preReserva) {
          //print_r($curso);
          $html = '';
          if ($curso->formatoAsistencia == 2) $html .= '[select pkf_attest_asistencia "Presencial|0" "No presencial|0"]'."\n";
          else $html .= '[hidden pkf_attest_asistencia "'.$curso->formatoAsistencia.'"]';
          if (!$curso->gratuito) {
            if ($curso->aPlazos) $html .= '[select pkf_attest_plazos "A plazos|1" "Al contado|0"]'."\n";
            foreach ($curso->formasPago as $forma) {
              $formas[] = $forma->descripcion."|".$forma->formaPago;
            }
            $html .= '[select* pkf_attest_pago "'.implode ('" "', $formas).'"]'."\n";
            $html .= '[text* pkf_attest_iban placeholder "IBAN de la cuenta"]'."\n";
          }
          

          $html .= "<h2>Datos del alumno</h2>";
          $html .= '[text* pkf_attest_estudiante_nombre placeholder "Nombre y apellidos"]'."\n";
          $html .= '[date* pkf_attest_estudiante_fecha_nacimiento]'."\n";
          $html .= '[select pkf_attest_estudiante_tipo_identidad "DNI|0" "NIF|1" "NIE|4"]'."\n";
          $html .= '[text* pkf_attest_estudiante_identidad placeholder "Número de identificación"]'."\n";
          $html .= '[text* pkf_attest_estudiante_direccion placeholder "Domicilio"]'."\n";
          $html .= '[text* pkf_attest_estudiante_cp placeholder "CP"]'."\n";
          $html .= '[tel* pkf_attest_estudiante_telefono placeholder "Telefono"]'."\n";
          $html .= '[email* pkf_attest_estudiante_email placeholder "Email"]'."\n";
          $html .= '[text* pkf_attest_estudiante_ciudad placeholder "Municipio"]'."\n";

          $html .= '[checkbox pkf_attest_estudiante_como_pagador use_label_element "Los datos para facturar son los de la persona a inscribir.|1"] '."\n";
          $html .= "<h2>Datos de facturación</h2>";
          $html .= '[text* pkf_attest_receptor_nombre placeholder "Nombre y apellidos"]'."\n";
          $html .= '[date* pkf_attest_receptor_fecha_nacimiento]'."\n";
          $html .= '[select pkf_attest_receptor_tipo_identidad "DNI|0" "NIF|1" "NIE|4"]'."\n";
          $html .= '[text* pkf_attest_receptor_identidad placeholder "Número de identificación"]'."\n";
          $html .= '[text* pkf_attest_receptor_direccion placeholder "Domicilio"]'."\n";
          $html .= '[text* pkf_attest_receptor_cp placeholder "CP"]'."\n";
          $html .= '[tel* pkf_attest_receptor_telefono placeholder "Telefono"]'."\n";
          $html .= '[email* pkf_attest_receptor_email placeholder "Email"]'."\n";
          $html .= '[text* pkf_attest_receptor_ciudad placeholder "Municipio"]'."\n";

          echo $html.'[submit "Enviar"]';
        } else {
          echo "<h1 class='nopreserva'>".__("No admite preserva", 'cf7_pkf_attest')."</h1>";
        }
      } else {
        echo "<h1 class='noplazas'>".__("No hay plazas disponibles", 'cf7_pkf_attest')."</h1>";
      }
    ?>
  </div>
  <?php $html = ob_get_clean(); 
  return $html;
}
add_shortcode('curso', 'cf7_pkf_attest_shortcode_form');

function cf7_pkf_attest_shortcode_mail($params = array(), $content = null) {
  $submission = WPCF7_Submission::get_instance();
  $formdata = $submission->get_posted_data();
  ob_start(); ?>
    <?php foreach ($formdata as $label => $value){
      echo str_replace("_", " ", str_replace("pkf_attest_", "", $label)).": ".(is_array($value) ? $value[0] : $value)."\n";
    } ?>
  <?php $html = ob_get_clean(); 
  return $html;
}
add_shortcode('curso_allinfo', 'cf7_pkf_attest_shortcode_mail');
