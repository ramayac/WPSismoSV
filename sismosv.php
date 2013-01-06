<?php
/* Plugin Name: SismoSV
Plugin URI: http://sismosv.com/
Description: Muestra informacion sismografica recolectada del servicio SismoSV.
Version: 0.2
Author: Rodrigo Amaya
Author URI: http://twitter.com/ramayac
License: GPLv2
*/
define(SISMOSV_WIDGET_ID, "widget_sismosv_id");  

require_once('JSON.php');

function getSismoText(){
  $opts = array(
    'http'=>array(
      'method'=>"GET",
      'header'=>"Accept-language: en\r\n" .
              "Cookie: foo=bar\r\n"
    )
  );
  $context = stream_context_create($opts);
  // Open the file using the HTTP headers set above
  $file = file_get_contents('http://sismosv.appspot.com/sismos?ultimo=1', false, $context);
  return $file;
}

function getSismoJSON(){
  $txt = getSismoText();
  $json = new Services_JSON();
  //echo $txt;
  $sismojson = $json->decode($txt);
  return $sismojson;  
}

function getLatLon($sismojson){
  $lat = $sismojson->resp[0]->latitud;
  $lon = $sismojson->resp[0]->longitud;
  return $lat . "," . $lon;
}

function getImgUrl($latlon, $mag){
    $img = "http://maps.google.com/maps/api/staticmap?center=" . $latlon . "&zoom=6&markers=";
    $img .= $latlon;
    $img .= "&path=color:0x0000FF80|";
    $img .= "weight:". $mag . "|"; //weight:|";
    $img .= $latlon;
    $img .= "&size=200x200&sensor=false";
    return $img;
}

function widget_sismosv_control() {
  $options = get_option(SISMOSV_WIDGET_ID);
  if (!is_array($options)) {
    $options = array();
  }

  $widget_data = $_POST[SISMOSV_WIDGET_ID];
  if ($widget_data['submit']) {
    $options['titulo'] = $widget_data['titulo'];
    update_option(SISMOSV_WIDGET_ID, $options);
  }

  // Render form
  $titulo = $options['titulo']; 
  ?><label for="<?php echo SISMOSV_WIDGET_ID;?>-titulo">Titulo:</label><input class="widefat" type="text" name="<?php echo SISMOSV_WIDGET_ID; ?>[titulo]" id="<?php echo SISMOSV_WIDGET_ID; ?>-titulo" value="<?php echo $titulo; ?>"/><input type="hidden" name="<?php echo SISMOSV_WIDGET_ID; ?>[submit]" value="1"/><?php 
}

function mostar_sismosv($titulo) {
    $sismojson = getSismoJSON();
    $msj = $sismojson->resp[0]->msj;
    $mag = $sismojson->resp[0]->magnitud;
    $msj = str_replace("<br>", "", $msj);
    $msj = utf8_encode($msj);
    $latlon = getLatLon($sismojson);
    $imgurl = getImgUrl($latlon, $mag);
    echo "<h3 class='widget-title'>" . $titulo . "</h3><center><a href='http://sismosv.appspot.com'><img src='" . $imgurl . "' /></a><br/>" . $msj ."</center>";
}

function widget_sismosv($args) {
  extract($args, EXTR_SKIP);
  
  $options = get_option(SISMOSV_WIDGET_ID);  
  // Query el titulo 
  $titulo = $options["titulo"];  
  
  echo $before_widget;
  mostar_sismosv($titulo);
  echo $after_widget;
}

/* widget hook */
function widget_sismosv_init() {
  wp_register_sidebar_widget(SISMOSV_WIDGET_ID, __('SismoSV'), 'widget_sismosv');
  wp_register_widget_control(SISMOSV_WIDGET_ID, __('SismoSV'), 'widget_sismosv_control');  
}

// Register widget to WordPress
add_action("plugins_loaded", "widget_sismosv_init");
