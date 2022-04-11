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
    //print_r($formdata);

    $curso = getCurso($formdata['pkf_attest_id']);

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
      unset($json['alumnoComoPagador']);
    }

    //print_r($json);
    $response = insertLead(json_encode($json));
    //print_r($response);
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
  if( isset($formdata['pkf_attest_id']) && $formdata['pkf_attest_id'] > 0) { //Chequeamos que tenga id de curso
    if(!isValidIBAN($formdata['pkf_attest_iban'])) {
      $result->invalidate('pkf_attest_iban', __("Formato del IBAN incorrecto.", 'cf7_pkf_attest'));
    }
    $formdata['pkf_attest_estudiante_identidad'] = strtoupper($formdata['pkf_attest_estudiante_identidad']);
    if($formdata['pkf_attest_estudiante_tipo_identidad'] == 0 && !isValidDNI($formdata['pkf_attest_estudiante_identidad'])) $result->invalidate('pkf_attest_estudiante_identidad', __("Formato del DNI incorrecto.", 'cf7_pkf_attest'));
    else if($formdata['pkf_attest_estudiante_tipo_identidad'] == 1 && !isValidNIF($formdata['pkf_attest_estudiante_identidad'])) $result->invalidate('pkf_attest_estudiante_identidad', __("Formato del NIF incorrecto.", 'cf7_pkf_attest'));
    else if($formdata['pkf_attest_estudiante_tipo_identidad'] == 4 && !isValidNIE($formdata['pkf_attest_estudiante_identidad'])) $result->invalidate('pkf_attest_estudiante_identidad', __("Formato del NIE incorrecto.", 'cf7_pkf_attest'));

    if($formdata['pkf_attest_estudiante_como_pagador'][0] != 1) {
      if($formdata['pkf_attest_receptor_nombre'] == '') $result->invalidate('pkf_attest_receptor_nombre', $messages['invalid_required']);

      if($formdata['pkf_attest_receptor_fecha_nacimiento'] == '') $result->invalidate('pkf_attest_receptor_fecha_nacimiento', $messages['invalid_required']);
      else if(!wpcf7_is_date($formdata['pkf_attest_receptor_fecha_nacimiento'])) $result->invalidate('pkf_attest_receptor_fecha_nacimiento', $messages['invalid_date']);

      if($formdata['pkf_attest_receptor_tipo_identidad'] == '') $result->invalidate('pkf_attest_receptor_tipo_identidad', $messages['invalid_required']);

      $formdata['pkf_attest_receptor_identidad'] = strtoupper($formdata['pkf_attest_receptor_identidad']);
      if($formdata['pkf_attest_receptor_identidad'] == '') $result->invalidate('pkf_attest_receptor_identidad', $messages['invalid_required']);
      else if($formdata['pkf_attest_receptor_tipo_identidad'] == 0 && !isValidDNI($formdata['pkf_attest_receptor_identidad'])) $result->invalidate('pkf_attest_receptor_identidad', __("Formato del DNI incorrecto.", 'cf7_pkf_attest'));
      else if($formdata['pkf_attest_receptor_tipo_identidad'] == 1 && !isValidNIF($formdata['pkf_attest_receptor_identidad'])) $result->invalidate('pkf_attest_receptor_identidad', __("Formato del NIF incorrecto.", 'cf7_pkf_attest'));
      else if($formdata['pkf_attest_receptor_tipo_identidad'] == 4 && !isValidNIE($formdata['pkf_attest_receptor_identidad'])) $result->invalidate('pkf_attest_receptor_identidad', __("Formato del NIE incorrecto.", 'cf7_pkf_attest'));

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
  $curso = getCurso($params['id']); 
  //print_r($curso); ?>
  <div id="inscripcion_pkf">
    [hidden pkf_attest_id "<?=$params['id'] ?>"]
    <?php if ($curso->plazasDisponibles > 0  ) {
      if($curso->preReserva) {
        //print_r($curso);
        //TODO. Las formas de pago varian según se elige al contado o a plazos.
        //TODO. Revisar si es un curso gratuito y lo de la preserva
        if ($curso->formatoAsistencia == 2) { ?>
          <label><?php _e("Asistencia al curso", 'cf7_pkf_attest'); ?>
          [select pkf_attest_asistencia "<?php _e("Presencial", 'cf7_pkf_attest'); ?>|0" "<?php _e("No presencial", 'cf7_pkf_attest'); ?>|1"]</label>
        <?php } else  { ?>[hidden pkf_attest_asistencia "<?=$curso->formatoAsistencia ?>"]<?php }
        if (!$curso->gratuito) {
          if ($curso->aPlazos) { ?>
            <label><?php _e("Forma de pago", 'cf7_pkf_attest'); ?>
            [select pkf_attest_plazos "<?php _e("Al contado", 'cf7_pkf_attest'); ?>|0" "<?php _e("A plazos", 'cf7_pkf_attest'); ?>|1"]</label>
          <?php }
          foreach ($curso->formasPago as $forma) {
            $formas[] = $forma->descripcion."|".$forma->formaPago;
          }?>
          <label><?php _e("Forma de pago", 'cf7_pkf_attest'); ?>
          [select* pkf_attest_pago "<?php echo implode ('" "', $formas); ?>"]</label>
          <label><?php _e("IBAN de la cuenta", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_iban placeholder "<?php _e("IBAN de la cuenta", 'cf7_pkf_attest'); ?>"]</label>
        <?php } ?>
        <div id='inscripcion_pkf_estudiante'>          
          <p><b><?php _e("Datos del alumno", 'cf7_pkf_attest'); ?></b></p>
          <label><?php _e("Nombre y apellidos", 'cf7_pkf_attest'); ?>
          [text* pkf_attest_estudiante_nombre placeholder "<?php _e("Nombre y apellidos", 'cf7_pkf_attest'); ?>"]</label>
          <label><?php _e("Fecha de nacimiento", 'cf7_pkf_attest'); ?>
          [date* pkf_attest_estudiante_fecha_nacimiento]</label>
          <label><?php _e("Tipo de identifcación", 'cf7_pkf_attest'); ?>
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
                console.log("click");
                if(jQuery(this).is(":checked")) {
                  jQuery("#inscripcion_pkf_receptor").fadeOut(200);
                } else {
                  jQuery("#inscripcion_pkf_receptor").fadeIn(300);
                }
              });
           });
          </script>
          [checkbox pkf_attest_estudiante_como_pagador use_label_element default:1 "<?php _e("Los datos para facturar son los de la persona a inscribir.", 'cf7_pkf_attest'); ?>|1"]
          <div id='inscripcion_pkf_receptor' style='display: none;'>
            <p><b><?php _e("Datos de facturación", 'cf7_pkf_attest'); ?></b></p>
            <label><?php _e("Nombre y apellidos", 'cf7_pkf_attest'); ?>
            [text pkf_attest_receptor_nombre placeholder "<?php _e("Nombre y apellidos", 'cf7_pkf_attest'); ?>"]</label>
            <label><?php _e("Fecha de nacimiento", 'cf7_pkf_attest'); ?>
            [date pkf_attest_receptor_fecha_nacimiento]</label>
            <label><?php _e("Tipo de identifcación", 'cf7_pkf_attest'); ?>
            [select pkf_attest_receptor_tipo_identidad "DNI|0" "NIF|1" "NIE|4"]</label>
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


//Checks and validations
function isValidIBAN ($iban) {
  $iban = strtolower($iban);
  $Countries = array(
    'al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,
    'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,
    'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,
    'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,
    'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24
  );
  $Chars = array(
    'a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,
    'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35
  );

  if (strlen($iban) != $Countries[ substr($iban,0,2) ]) { return false; }

  $MovedChar = substr($iban, 4) . substr($iban,0,4);
  $MovedCharArray = str_split($MovedChar);
  $NewString = "";

  foreach ($MovedCharArray as $k => $v) {

    if ( !is_numeric($MovedCharArray[$k]) ) {
      $MovedCharArray[$k] = $Chars[$MovedCharArray[$k]];
    }
    $NewString .= $MovedCharArray[$k];
  }
  if (function_exists("bcmod")) { return bcmod($NewString, '97') == 1; }

  // http://au2.php.net/manual/en/function.bcmod.php#38474
  $x = $NewString; $y = "97";
  $take = 5; $mod = "";

  do {
    $a = (int)$mod . substr($x, 0, $take);
    $x = substr($x, $take);
    $mod = $a % $y;
  }
  while (strlen($x));

  return (int)$mod == 1;
}

function isValidDNI($dni){
  $letra = substr($dni, -1);
  $numeros = substr($dni, 0, -1);
  $valido;
  if (substr("TRWAGMYFPDXBNJZSQVHLCKE", $numeros%23, 1) == $letra && strlen($letra) == 1 && strlen ($numeros) == 8 ){
    $valido=true;
  }else{
    $valido=false;
  }
}

function isValidNIF($nif) {
  $nif_codes = 'TRWAGMYFPDXBNJZSQVHLCKE';

  $sum = (string) $this->getCifSum ($nif);
  $n = 10 - substr($sum, -1);

  if (preg_match ('/^[0-9]{8}[A-Z]{1}$/', $nif)) {
    // DNIs
    $num = substr($nif, 0, 8);

    return ($nif[8] == $nif_codes[$num % 23]);
  } elseif (preg_match ('/^[XYZ][0-9]{7}[A-Z]{1}$/', $nif)) {
    // NIEs normales
    $tmp = substr ($nif, 1, 7);
    $tmp = strtr(substr ($nif, 0, 1), 'XYZ', '012') . $tmp;

    return ($nif[8] == $nif_codes[$tmp % 23]);
  } elseif (preg_match ('/^[KLM]{1}/', $nif)) {
    // NIFs especiales
    return ($nif[8] == chr($n + 64));
  } elseif (preg_match ('/^[T]{1}[A-Z0-9]{8}$/', $nif)) {
    // NIE extraño
    return true;
  }

  return false;
}

function isValidNIE($nif){
  if (preg_match('/^[XYZT][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/', $nif)) {
    for ($i = 0; $i < 9; $i ++){
      $num[$i] = substr($nif, $i, 1);
    }

    if ($num[8] == substr(‘TRWAGMYFPDXBNJZSQVHLCKE’, substr(str_replace(array(‘X’,’Y’,’Z’), array(‘0′,’1′,’2’), $nif), 0, 8) % 23, 1)) {
      return true;
    } else {
      return false;
    }
  }
}