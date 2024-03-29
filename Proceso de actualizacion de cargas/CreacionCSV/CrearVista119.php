<?php
   $vista=119;
   define ("CLAVE_URI", "objectid");
   define ("CLAVE_MUN", "codigo_mun");   
   define ("COD_MUN", "co2mun");   
   define ("COD_PRO", "co1prv");   
   include 'comun.php';
   
   if ($archivoCSV !== false) {            
       array_push ($keys,CLAVE_MUN); //Le añadimos la clave que necesita y no la tiene el xml
       fwrite ($archivoCSV, "\"".CLAVE_MUN."\";"); //y la añadidomos al csv
       
       fwrite ($archivoCSV, "\n");
       for ($i = 1; $i <= $numeroArchivos; $i ++) {
           $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
		   if (is_string ($datosArchivo) ) {
           $xml = simplexml_load_string($datosArchivo);
           
           for ($x = 0; $x < ($xml->count ()); $x++) {
               foreach ($keys as $key) {
                   $elemento = $xml->item[$x]->$key;
                   
                   if ($key == CLAVE_URL) {
                       $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                   }
                   
                   if ($key == CLAVE_MUN) {     
                       $codMun = $xml->item[$x]->{COD_MUN};
                       
                       while (strlen($codMun) < 3) {
                           $codMun = "0".$codMun;
                       }
                       
                       $codPro= $xml->item[$x]->{COD_PRO};
                       $elemento = $codPro.$codMun;
                   }
                   
                   editarElemento($elemento);
                   	if (isInteger($elemento) || trim($elemento) == "" ) {
							fwrite($archivoCSV, $elemento.";");
						} else {
							fwrite($archivoCSV, "\"$elemento\";");
						}


               }
               fwrite ($archivoCSV, "\n");
           }
       }
       }
       fclose ($archivoCSV);
   }
?>
