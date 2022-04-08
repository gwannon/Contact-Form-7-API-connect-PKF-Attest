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
    writeErrorLog ("INSERTLEAD CURL ERROR", $payload_data, $err); 
  } else if ($curl_info['http_code'] != '200') {
    writeErrorLog ("INSERTLEAD API ERROR", $payload_data, $response); 
  } else {
    writeLog ("INSERTLEAD API OK", $payload_data, $response);     
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
  $err = curl_error($curl);
  $curl_info = curl_getinfo($curl);
  curl_close($curl); 

  if ($err) {
    writeErrorLog ("GETCURSO CURL ERROR", $id, $err); 
  } else if ($curl_info['http_code'] != '200') {
    writeErrorLog ("GETCURSO API ERROR", $id, $response); 
  } else {
    writeLog ("INSERTLEAD API OK", $id, $response);     
  }
  return json_decode($response);
}

function writeLog ($title, $data, $response) {
  $fp = fopen(dirname(__FILE__)."/logs/log.txt", 'a+');
  fwrite($fp, date("Y-m-d H:i:s")."|".$title."|".$response."|".$data."\n");
  fwrite($fp, "-------------------------------------\n");
  fclose($fp);
  return;
}

function writeErrorLog ($title, $data, $response) {
  $fp = fopen(dirname(__FILE__)."/logs/error.txt", 'a+');
  fwrite($fp, date("Y-m-d H:i:s")."|".$title."|".$response."|".$data."\n");
  fwrite($fp, "-------------------------------------\n");
  fclose($fp);
  return;
}
