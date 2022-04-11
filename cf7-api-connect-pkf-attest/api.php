<?php 

/* LIBs ---------------------------------------- */
function insertLead($payload_data) { //Inserta un lead
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => CF7_PKF_ATTEST_API_URL."/Inscripciones",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_USERPWD => CF7_PKF_ATTEST_API_USER . ":" . CF7_PKF_ATTEST_API_PASS,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $payload_data,
    CURLOPT_HTTPHEADER => array(
      "Content-Type: application/json",
      "cache-control: no-cache"
    ),
  ));
  $response = curl_exec($curl); 
  $err = curl_error($curl);
  $curl_info = curl_getinfo($curl);
  curl_close($curl); 

  if ($err) {
    writeErrorLog ("INSERTLEAD CURL ERROR", $payload_data, $response, $error, json_encode($curl_info)); 
  } else if ($curl_info['http_code'] != '200') {
    writeErrorLog ("INSERTLEAD API ERROR", $payload_data, $response, $error, json_encode($curl_info)); 
  } else {
    writeLog ("INSERTLEAD OK", $payload_data, $response);     
  }
  return json_decode($response);
}

function getCurso($id) { //Consigue los datos de un curso
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => CF7_PKF_ATTEST_API_URL."/Acciones/".$id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_USERPWD => CF7_PKF_ATTEST_API_USER . ":" . CF7_PKF_ATTEST_API_PASS,
    CURLOPT_HTTPHEADER => array(
      "Content-Type: application/json",
      "cache-control: no-cache"
    ),
  ));
  $response = curl_exec($curl);
  $error = curl_error($curl);
  $curl_info = curl_getinfo($curl);
  curl_close($curl); 

  if ($error) {
    writeErrorLog ("GETCURSO CURL ERROR", $id, $response, $error, json_encode($curl_info)); 
  } else if ($curl_info['http_code'] != '200') {
    writeErrorLog ("GETCURSO API ERROR", $id, $response, $error, json_encode($curl_info)); 
  } else {
    writeLog ("GETCURSO OK", $id, $response);     
  }
  return json_decode($response);
}

function writeLog ($title, $data, $response) {
  $fp = fopen(dirname(__FILE__)."/logs/log-".date("Y-m").".txt", 'a+');
  fwrite($fp, date("Y-m-d H:i:s")."|".$title."|".$response."|".$data."\n");
  fwrite($fp, "-------------------------------------\n");
  fclose($fp);
  return;
}

function writeErrorLog ($title, $data, $response, $error, $curl_info) {
  $headers = array('Content-Type: text/html; charset=UTF-8');
  wp_mail(get_option("_cf7_pkf_attest_email"), $title, $data."<br/>-------------------<br/>".$error."<br/>-------------------<br/>".$response."<br/>-------------------<br/>".$curl_info, $headers);
  $fp = fopen(dirname(__FILE__)."/logs/error-".date("Y-m").".txt", 'a+');
  fwrite($fp, date("Y-m-d H:i:s")."|".$title."|".$data."|".$response."|".$error."|".$curl_info."\n");
  fwrite($fp, "-------------------------------------\n");
  fclose($fp);
  return;
}
