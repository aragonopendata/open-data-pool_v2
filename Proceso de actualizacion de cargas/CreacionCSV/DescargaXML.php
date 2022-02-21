<?php
require_once('./config.php'); 
$log = fopen ("Log/log".date("Ymd").".txt", "a+");
libxml_use_internal_errors(true); //Se utiliza para que se pueden manejar los error
$ch = curl_init();
$error = 0;
$xml;
$vistasSaltar = array (14, 18, 37, 78, 79, 80, 81, 102, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 146);

for ($i = 145; $i <= 145; $i++) {	
	if (!in_array ($i, $vistasSaltar)) { 
		$numPagina = 234;	
		
		if (!file_exists("VistasXml/Vista$i")) {
			mkdir("VistasXml/Vista$i");
		}
		
		do {
			$error=0;			
			$url = "https://opendata.aragon.es/GA_OD_Core/download?view_id=$i&formato=xml&_pageSize=1000&_page=$numPagina";//Url del archivo xml
			curl_setopt($ch, CURLOPT_HEADER, 0);		
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 400);
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch,CURLOPT_ENCODING, "gzip");
			curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			
			$datosXML = obtenerPagVista($ch);
			
			//Si la paguina esta caida, esperamos hasta que nos de una respuesta
			while (empty($datosXML) || strpos ($datosXML,"<TITLE>P&aacute;gina en mantenimiento</TITLE>")) {
				$datosXML = obtenerPagVista($ch);
			}
			
			$xml = simplexml_load_string($datosXML);
			
			
			if ($xml === false) {
				fwrite ($log, date(DATE_W3C)." Error al leer el xml de la página $numPagina de la vista $i"."\r\n"); 
				$error=1;				
			}
			
			$continuar = 0;
			
			libxml_clear_errors();
			
			if(!$error){
				if ($xml->count() > 1) {
					file_put_contents("VistasXml/Vista$i/vista_".$i."_$numPagina.xml", $datosXML);
					fwrite ($log, date(DATE_W3C)." Se a terminado la descargar de la página $numPagina de la vista $i"."\r\n");					
				}
				
				$continuar = $xml->count();
			}
			else {				
				file_put_contents("Log/ArchivosErroneos/(".date("Ymd Gis").")"."Error_vista_".$i."_$numPagina.xml", $datosXML);
			}			
			
			$numPagina += 1;
		}while ($continuar > 1);		
	} 		
}
curl_close ($ch);

fwrite ($log, date(DATE_W3C)." Se han descargado todas las vistas"."\r\n");

//Obtiene el xml de la pagina y la vista introducida
function obtenerPagVista($curl) {		
	$datosXMLDescargados = curl_exec ($curl);
	
	$datosXMLDescargadosCorrecto = preg_replace('/[\x00-\x1F\x7F]/u', '', $datosXMLDescargados); //se quitan todos los caracteres especiales
	
	if (empty($datosXMLDescargadosCorrecto) || strpos ($datosXMLDescargadosCorrecto,"<TITLE>P&aacute;gina en mantenimiento</TITLE>")) {
		fwrite ($GLOBALS["log"], date(DATE_W3C).curl_error($curl)." Página en mantenimiento"."\r\n");
		sleep (30);					
	}	
		
	return $datosXMLDescargadosCorrecto;
}