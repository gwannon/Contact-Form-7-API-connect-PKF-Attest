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


function cf7_pkf_attest_plugin_scripts_method(){
  wp_enqueue_script( 'jquery'); 
}
add_action( 'wp_enqueue_scripts', 'cf7_pkf_attest_plugin_scripts_method' );

define('CF7_PKF_ATTEST_API_URL', get_option("_cf7_pkf_attest_api_url")); 
define('CF7_PKF_ATTEST_API_USER', get_option("_cf7_pkf_attest_api_user"));
define('CF7_PKF_ATTEST_API_PASS', get_option("_cf7_pkf_attest_api_password"));

define( 'WPCF7_AUTOP', false );

//Cargamos librerías de conexión a la API
require_once(dirname(__FILE__)."/api.php");

//Cargamos validadores de DNI-NIF-NIE
require_once(dirname(__FILE__)."/valid-dni-nif-nie.php");

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
add_action("wpcf7_before_send_mail", "cf7_pkf_attest_before_send_mail", 10, 3);

function cf7_pkf_attest_before_send_mail(&$wpcf7_data, &$abort, $submission) {
  //print_r($wpcf7_data);
  //$submission = WPCF7_Submission::get_instance();
  $formdata = $submission->get_posted_data();

  if(isset($formdata['pkf_attest_id'])) {
    //print_r($formdata);
    $response = getCurso($formdata['pkf_attest_id']); 
    $curso = json_decode($response['response']);
    //print_r($curso);

    //Formato del JSON -------------------------------------------------------------------------
    $json = [
      "idAccionFormativa" => $formdata['pkf_attest_id'],
      "formatoAsistencia" => $formdata['pkf_attest_asistencia'][0],
      "formaPago" => $formdata['pkf_attest_pago'][0],
      "iban" => $formdata['pkf_attest_iban'],
      "aPlazos" => ($formdata['pkf_attest_plazos'][0] == 1 ? "true" : "false"),
      "alumnoComoPagador" => ($formdata['pkf_attest_estudiante_como_pagador'][0] == 1 ? "true" : "false"),
      "contacto" => [
        "nombre" => $formdata['pkf_attest_estudiante_nombre'],
        "apellidos" => $formdata['pkf_attest_estudiante_nombre'],
        "tipoIdentificador" => $formdata['pkf_attest_estudiante_tipo_identidad'][0],
        "dni" => strtoupper($formdata['pkf_attest_estudiante_identidad']),
        "fechaNac" => $formdata['pkf_attest_estudiante_fecha_nacimiento'],
        "direccion" => $formdata['pkf_attest_estudiante_direccion'],
        "email" => $formdata['pkf_attest_estudiante_email'],
        "tel1" => $formdata['pkf_attest_estudiante_telefono'],
        "cp" => $formdata['pkf_attest_estudiante_cp'],
        "municipio" => $formdata['pkf_attest_estudiante_ciudad']
      ]
    ];

    if($formdata['pkf_attest_estudiante_como_pagador'][0] != 1) $json['receptor'] = [
      "razonSocial" => $formdata['pkf_attest_receptor_nombre'],
      "tipoIdentificador" => $formdata['pkf_attest_receptor_tipo_identidad'][0],
      "numIdentificador" => strtoupper($formdata['pkf_attest_receptor_identidad']),
      //"fechaNac" => $formdata['pkf_attest_receptor_fecha_nacimiento'],
      "domicilio" => $formdata['pkf_attest_receptor_direccion'],
      "cp" => $formdata['pkf_attest_receptor_cp'],
      "telefono1" => $formdata['pkf_attest_receptor_telefono'],
      "email" => $formdata['pkf_attest_receptor_email'],
      "municipio" => $formdata['pkf_attest_receptor_ciudad'],
    ];

    if ($curso->gratuito) {
      unset($json['receptor']);
      unset($json['formaPago']);
      unset($json['iban']);
      unset($json['aPlazos']);
      //unset($json['alumnoComoPagador']);
    }

    //print_r($json);
    $response = insertLead(json_encode($json));
    //print_r ($response);
    if(isset($response['http_code']) && $response['http_code'] != '200') {
      $abort = true; //==> Here, it is with 'called by reference' since CF7-v5.0 :)
      $submission->set_status( 'validation_failed' );
      //$submission->set_response( $cf7->message( 'validation_error' ) ); //msg from admin settings;
      $submission->set_response( $response['response'] ); //custom msg;
    }
  }
}



// Add fields of the shortocedee to show in add-on
function filter_wpcf7_collect_mail_tags( $mailtags ) { 
  $currentform = WPCF7_ContactForm::get_current();
  if(has_shortcode($currentform->get_properties()['form'], 'curso')) {
    $mailtags[] = 'pkf_attest_asistencia';
    $mailtags[] = 'pkf_attest_plazos';
    $mailtags[] = 'pkf_attest_pago';
    $mailtags[] = 'pkf_attest_iban';
    
    $mailtags[] = 'pkf_attest_estudiante_nombre';
    $mailtags[] = 'pkf_attest_estudiante_apellidos';
    $mailtags[] = 'pkf_attest_estudiante_fecha_nacimiento';
    $mailtags[] = 'pkf_attest_estudiante_tipo_identidad';
    $mailtags[] = 'pkf_attest_estudiante_identidad';
    $mailtags[] = 'pkf_attest_estudiante_direccion';
    $mailtags[] = 'pkf_attest_estudiante_cp';
    $mailtags[] = 'pkf_attest_estudiante_ciudad';
    $mailtags[] = 'pkf_attest_estudiante_telefono';
    
    $mailtags[] = 'pkf_attest_estudiante_email';
    
    $mailtags[] = 'pkf_attest_estudiante_como_pagador';
    $mailtags[] = 'pkf_attest_receptor_nombre';
    $mailtags[] = 'pkf_attest_receptor_fecha_nacimiento';
    $mailtags[] = 'pkf_attest_receptor_tipo_identidad';
    $mailtags[] = 'pkf_attest_receptor_identidad';
    $mailtags[] = 'pkf_attest_receptor_direccion';
    $mailtags[] = 'pkf_attest_receptor_cp';
    $mailtags[] = 'pkf_attest_receptor_ciudad';
    $mailtags[] = 'pkf_attest_receptor_telefono';
    $mailtags[] = 'pkf_attest_receptor_email';
  }
  return $mailtags; 
}; 
add_filter( 'wpcf7_collect_mail_tags', 'filter_wpcf7_collect_mail_tags', 10, 1 );


// Activate shortcodes inside contact-form-7 forms and mails
add_filter( 'wpcf7_contact_form_properties', 'cf7_pkf_attest_shortcodes_in_forms' );
function cf7_pkf_attest_shortcodes_in_forms( $form ) {
  if (!is_admin()) $form['form'] = do_shortcode($form['form']);
  return $form;
}

add_filter( 'wpcf7_special_mail_tags', 'cf7_pkf_attest_shortcodes_in_mails', 10, 3 );
function cf7_pkf_attest_shortcodes_in_mails( $output, $name, $html ) {
  if ('curso_allinfo' == $name) $output = do_shortcode( "[$name]" );
  else if (strpos($name, 'curso_info_') == 0) {
    $label = str_replace('curso_info_', "", $name);
    $output = do_shortcode( "[curso_allinfo field='".str_replace('curso_info_', "", $label)."']" );
  }
  return $output;
}

//Validate form
function cf7_pkf_attest_validate_form ( $result, $tags ) {
  $submission = WPCF7_Submission::get_instance();
  $messages = WPCF7_ContactForm::get_current()->prop('messages');
  //print_r($messages);
  $formdata = $submission->get_posted_data();
  //print_r($formdata);
  if( isset($formdata['pkf_attest_id']) && $formdata['pkf_attest_id'] > 0) { //Chequeamos que tenga id de curso

    $response = getCurso($formdata['pkf_attest_id']); 
    $curso = json_decode($response['response']);

    foreach ($curso->formasPago as $forma) {
      if ($forma->formaPago == $formdata['pkf_attest_pago'][0]) {
        $exige_cuenta = ($forma->exigeCuenta == 1 ? true : false);
        break;
      }
    }

    if($exige_cuenta) {
      if(isset($formdata['pkf_attest_iban']) && $formdata['pkf_attest_iban'] == '') $result->invalidate('pkf_attest_iban', $messages['invalid_required']);
      else if(isset($formdata['pkf_attest_iban']) && !isValidIBAN($formdata['pkf_attest_iban'])) {
        $result->invalidate('pkf_attest_iban', __("Formato del IBAN incorrecto.", 'cf7_pkf_attest'));
      }
    }
    $formdata['pkf_attest_estudiante_identidad'] = strtoupper($formdata['pkf_attest_estudiante_identidad']);
    if($formdata['pkf_attest_estudiante_tipo_identidad'][0] == 0 && !isValidNIF($formdata['pkf_attest_estudiante_identidad'])) $result->invalidate('pkf_attest_estudiante_identidad', __("Formato del DNI incorrecto.", 'cf7_pkf_attest'));
    else if($formdata['pkf_attest_estudiante_tipo_identidad'][0] == 1 && !isValidCIF($formdata['pkf_attest_estudiante_identidad']))  $result->invalidate('pkf_attest_estudiante_identidad', __("Formato del NIF incorrecto.", 'cf7_pkf_attest'));
    else if($formdata['pkf_attest_estudiante_tipo_identidad'][0] == 4 && !isValidNIE($formdata['pkf_attest_estudiante_identidad']))  $result->invalidate('pkf_attest_estudiante_identidad', __("Formato del NIE incorrecto.", 'cf7_pkf_attest'));

    if($formdata['pkf_attest_estudiante_como_pagador'][0] != 1) {
      if($formdata['pkf_attest_receptor_nombre'] == '') $result->invalidate('pkf_attest_receptor_nombre', $messages['invalid_required']);

      /*if($formdata['pkf_attest_receptor_fecha_nacimiento'] == '') $result->invalidate('pkf_attest_receptor_fecha_nacimiento', $messages['invalid_required']);
      else if(!wpcf7_is_date($formdata['pkf_attest_receptor_fecha_nacimiento'])) $result->invalidate('pkf_attest_receptor_fecha_nacimiento', $messages['invalid_date']);*/

      if($formdata['pkf_attest_receptor_tipo_identidad'] == '') $result->invalidate('pkf_attest_receptor_tipo_identidad', $messages['invalid_required']);

      $formdata['pkf_attest_receptor_identidad'] = strtoupper($formdata['pkf_attest_receptor_identidad']);
      if($formdata['pkf_attest_receptor_identidad'] == '') $result->invalidate('pkf_attest_receptor_identidad', $messages['invalid_required']);
      else if($formdata['pkf_attest_receptor_tipo_identidad'][0] == 0 && !isValidNIF($formdata['pkf_attest_receptor_identidad'])) { $result->invalidate('pkf_attest_receptor_identidad', __("Formato del DNI incorrecto.", 'cf7_pkf_attest')); }
      else if($formdata['pkf_attest_receptor_tipo_identidad'][0] == 1 && !isValidCIF($formdata['pkf_attest_receptor_identidad'])) { $result->invalidate('pkf_attest_receptor_identidad', __("Formato del NIF incorrecto.", 'cf7_pkf_attest')); } 
      else if($formdata['pkf_attest_receptor_tipo_identidad'][0] == 4 && !isValidNIE($formdata['pkf_attest_receptor_identidad'])) { $result->invalidate('pkf_attest_receptor_identidad', __("Formato del NIE incorrecto.", 'cf7_pkf_attest')); }

      if($formdata['pkf_attest_receptor_direccion'] == '') $result->invalidate('pkf_attest_receptor_direccion', $messages['invalid_required']);

      if($formdata['pkf_attest_receptor_cp'] == '') $result->invalidate('pkf_attest_receptor_cp', $messages['invalid_required']);

      if($formdata['pkf_attest_receptor_telefono'] == '') $result->invalidate('pkf_attest_receptor_telefono', $messages['invalid_required']);
      elseif(!wpcf7_is_tel($formdata['pkf_attest_receptor_telefono'])) $result->invalidate('pkf_attest_receptor_telefono', $messages['invalid_tel']);
 
      if($formdata['pkf_attest_receptor_email'] == '') $result->invalidate('pkf_attest_receptor_email', $messages['invalid_required']);
      else if (!filter_var($formdata['pkf_attest_receptor_email'], FILTER_VALIDATE_EMAIL)) $result->invalidate('pkf_attest_receptor_email', $messages['invalid_email']);
 
      if($formdata['pkf_attest_receptor_ciudad'] == '') $result->invalidate('pkf_attest_receptor_ciudad', $messages['invalid_required']);

    }
  }
  //print_r($result);
  return $result;
}
add_filter('wpcf7_validate', 'cf7_pkf_attest_validate_form', 10, 2 );


//Shortcodes for contact-form-7
function cf7_pkf_attest_shortcode_form ($params = array(), $content = null) {
  ob_start();
  $response = getCurso($params['id']); 
  $curso = json_decode($response['response']);
  //echo "<pre>"; print_r($curso); echo "</pre>"; ?>
  <div id="inscripcion_pkf">
    [hidden pkf_attest_id "<?=$params['id'] ?>"]
    <?php if ($curso->plazasDisponibles > 0  ) {
      if($curso->preReserva) {
        //print_r($curso);
        if ($curso->formatoAsistencia == 2) { ?>
          <label><?php _e("Asistencia al curso", 'cf7_pkf_attest'); ?>
          [select pkf_attest_asistencia "<?php _e("Presencial", 'cf7_pkf_attest'); ?>|0" "<?php _e("No presencial (Videoconferencia)", 'cf7_pkf_attest'); ?>|1"]</label>
        <?php } else { ?>[hidden pkf_attest_asistencia "<?=$curso->formatoAsistencia ?>"]<?php }
        if (!$curso->gratuito) {
          ?><label id="pkf_attest_plazos"><?php _e("Forma de pago", 'cf7_pkf_attest'); ?>
            [select* pkf_attest_plazos include_blank "<?php _e("Al contado", 'cf7_pkf_attest'); ?>,0|0" <?php if($curso->aPlazos == 1) { ?>"<?php _e("A plazos", 'cf7_pkf_attest'); ?>,1|1"<?php } ?>]</label>
          <?php foreach ($curso->formasPago as $forma) {
             $formas[] = $forma->descripcion.",".($forma->exigeCuenta ? "1" : "0").",".($forma->aPlazos ? "1" : "0")."|".$forma->formaPago;
          } ?>
          <label id="pkf_attest_pago"><?php _e("Tipo de pago", 'cf7_pkf_attest'); ?>
          [select* pkf_attest_pago include_blank "<?php echo implode ('" "', $formas); ?>"]</label>
          <label id="pkf_attest_iban" style="display: none;"><?php _e("Cuenta bancaria (IBAN)", 'cf7_pkf_attest'); ?>
          [text pkf_attest_iban placeholder "<?php _e("Cuenta bancaria (IBAN)", 'cf7_pkf_attest'); ?>"]</label>

          <script>
            jQuery(document).ready(function() {
              jQuery("#pkf_attest_iban").css("display", "none");
              jQuery("#pkf_attest_iban input").attr("disabled", true);
              jQuery("#pkf_attest_plazos select option").each(function(index) {
                info = jQuery(this).text().split(',');
                jQuery(this).attr("data-aPlazos", info[1]);
                jQuery(this).html(info[0]);
              });
              jQuery("#pkf_attest_pago select option").each(function(index) {
                info = jQuery(this).text().split(',');
                jQuery(this).attr("data-exigecuenta", info[1]);
                jQuery(this).attr("data-aplazos", info[2]);
                jQuery(this).html(info[0]);
                jQuery(this).attr("disabled", true);
                jQuery(this).css("display", "none");
              });
              jQuery("#pkf_attest_plazos select").change(function() {
                jQuery('#pkf_attest_pago select').prop('selectedIndex',0);
                jQuery("#pkf_attest_iban").css("display", "none");
                jQuery("#pkf_attest_iban input").attr("disabled", true);
                var selection = jQuery("#pkf_attest_plazos select option:selected" ).data("aplazos");
                jQuery("#pkf_attest_pago select option").each(function(index) {
                  if(jQuery(this).data("aplazos") == selection) {
                    jQuery(this).attr("disabled", false);
                    jQuery(this).css("display", "block");
                  } else {
                    jQuery(this).attr("disabled", true);
                    jQuery(this).css("display", "none");
                  }
                });
              });
              jQuery("#pkf_attest_pago select").change(function() {
                var selection = jQuery("#pkf_attest_pago select option:selected" ).data("exigecuenta");
                if(selection == 1) {
                  jQuery("#pkf_attest_iban").css("display", "block");
                  jQuery("#pkf_attest_iban input").attr("disabled", false);
                } else  {
                  jQuery("#pkf_attest_iban").css("display", "none");
                  jQuery("#pkf_attest_iban input").attr("disabled", true);
                }

              });
            });
          </script>


        <?php } ?>
        <div id='inscripcion_pkf_estudiante'>          
          <p><b><?php _e("Datos del alumno", 'cf7_pkf_attest'); ?></b></p>
          <label><?php _e("Nombre", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_nombre placeholder "<?php _e("Nombre", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Apellidos", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_apellidos placeholder "<?php _e("Apellidos", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Fecha de nacimiento", 'cf7_pkf_attest'); ?>
          [date* pkf_attest_estudiante_fecha_nacimiento]</label>
          <label><?php _e("Tipo de identificación", 'cf7_pkf_attest'); ?>
          [select pkf_attest_estudiante_tipo_identidad "DNI|0" "NIF|1" "NIE|4"]</label>
          <label><?php _e("Número de identificación", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_identidad placeholder "<?php _e("Número de identificación", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Domicilio", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_direccion placeholder "<?php _e("Domicilio", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("CP", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_cp placeholder "<?php _e("CP", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Municipio", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_ciudad placeholder "<?php _e("Municipio", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Teléfono", 'cf7_pkf_attest'); ?>
          [tel* pkf_attest_estudiante_telefono placeholder "<?php _e("Teléfono", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Email", 'cf7_pkf_attest'); ?>
          [email* pkf_attest_estudiante_email placeholder "<?php _e("Email", 'cf7_pkf_attest'); ?>"]</label>
        </div>
        <?php if (!$curso->gratuito) { ?>
          <script>
            jQuery(document).ready(function() {
              jQuery("input[name='pkf_attest_estudiante_como_pagador[]']").click(function() {
                if(jQuery(this).is(":checked")) {
                  jQuery("#inscripcion_pkf_receptor").fadeOut(200);
                } else {
                  jQuery("#inscripcion_pkf_receptor").fadeIn(300);
                }
              });
              document.addEventListener( 'wpcf7mailsent', function( event ) {
                jQuery("#inscripcion_pkf_receptor").fadeOut(200);
              });
           });
          </script>
          [checkbox pkf_attest_estudiante_como_pagador use_label_element default:1 "<?php _e("Los datos para facturar son los de la persona a inscribir.", 'cf7_pkf_attest'); ?>|1"]
          <div id='inscripcion_pkf_receptor' style='display: none;'>
            <p><b><?php _e("Datos de facturación", 'cf7_pkf_attest'); ?></b></p>
            <label><?php _e("Razón social", 'cf7_pkf_attest'); ?>
            [text pkf_attest_receptor_nombre placeholder "<?php _e("Razón social", 'cf7_pkf_attest'); ?>"]</label>
            <?php /* <label><?php _e("Fecha de nacimiento", 'cf7_pkf_attest'); ?>
            [date pkf_attest_receptor_fecha_nacimiento]</label> */ ?>
            <label><?php _e("Tipo de identifcación", 'cf7_pkf_attest'); ?>
            [select pkf_attest_receptor_tipo_identidad "DNI|2" "NIF|1" "NIE|4"]</label>
            <label><?php _e("Número de identificación", 'cf7_pkf_attest'); ?>
            [text pkf_attest_receptor_identidad placeholder "<?php _e("Número de identificación", 'cf7_pkf_attest'); ?>"]</label>
            <label><?php _e("Domicilio", 'cf7_pkf_attest'); ?>
            [text pkf_attest_receptor_direccion placeholder "<?php _e("Domicilio", 'cf7_pkf_attest'); ?>"]</label>
            <label><?php _e("CP", 'cf7_pkf_attest'); ?>
            [text pkf_attest_receptor_cp placeholder "<?php _e("CP", 'cf7_pkf_attest'); ?>"]</label>
            <label><?php _e("Municipio", 'cf7_pkf_attest'); ?>
            [text pkf_attest_receptor_ciudad placeholder "<?php _e("Municipio", 'cf7_pkf_attest'); ?>"]</label>
            <label><?php _e("Teléfono", 'cf7_pkf_attest'); ?>
            [tel pkf_attest_receptor_telefono placeholder "<?php _e("Teléfono", 'cf7_pkf_attest'); ?>"]</label>
            <label><?php _e("Email", 'cf7_pkf_attest'); ?>
            [email pkf_attest_receptor_email placeholder "<?php _e("Email", 'cf7_pkf_attest'); ?>"]</label>
          </div>
        <?php } ?>

        <?=$content ?>

        [submit "<?php _e("Enviar", 'cf7_pkf_attest'); ?>"]<?php 
      } else { ?><p class='nopreserva'><b><?php _e("No admite preserva", 'cf7_pkf_attest'); ?></b></p><?php }
    } else { ?><p class='noplazas'><b><?php _e("No hay plazas disponibles", 'cf7_pkf_attest'); ?></b></p><?php } ?>
  </div>
  <style><?php echo preg_replace("/(\t|\n)+/", "", stripslashes(get_option("_cf7_pkf_attest_css"))); ?></style>
  <?php $html = ob_get_clean(); 
  return $html;
}
add_shortcode('curso', 'cf7_pkf_attest_shortcode_form');

function cf7_pkf_attest_shortcode_mail($params = array(), $content = null) {
  $submission = WPCF7_Submission::get_instance();
  $formdata = $submission->get_posted_data();
  ob_start(); 
  if(isset($params['field'])) {
    if($formdata["pkf_attest_estudiante_como_pagador"]) {
      $params['field'] = str_replace("receptor", "estudiante", $params['field']);
    }
    if($params['field'] == 'asistencia') return ($formdata["pkf_attest_asistencia"] == 1 ? "No presencial" : "Presencial");
    else if($params['field'] == 'plazos') return ($formdata["pkf_attest_plazos"] == 1 ? "Pago aplazado" : "Al contado");
    else return (is_array($formdata["pkf_attest_".$params['field']]) ? $formdata["pkf_attest_".$params['field']][0] : $formdata["pkf_attest_".$params['field']]);
  }
  
  ?>
    <?php foreach ($formdata as $label => $value){
      echo str_replace("_", " ", str_replace("pkf_attest_", "", $label)).": ".(is_array($value) ? $value[0] : $value)."\n";
    } ?>
  <?php $html = ob_get_clean(); 
  return $html;
}
add_shortcode('curso_allinfo', 'cf7_pkf_attest_shortcode_mail');
