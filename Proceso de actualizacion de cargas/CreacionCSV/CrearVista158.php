<?php
   $vista=158;
   define ("CLAVE_URI", "ID_LEGISLATURA");
   define ("CLAVE_INI", "FECHA_INI");
   define ("CLAVE_FIN", "FECHA_FIN");
   include 'comun.php';
   
   if ($archivoCSV !== false) {     
       fwrite ($archivoCSV, "\n");
       for ($i = 1; $i <= $numeroArchivos; $i ++) {
           $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
		   if (is_string ($datosArchivo) ) {
           $xml = simplexml_load_string($datosArchivo);
           
           for ($x = 0; $x < ($xml->count ()); $x++) {
               foreach ($keys as $key) {
                   $elemento = $xml->item[$x]->$key->__toString();
                   
                   if ($key == CLAVE_URL) {
                       $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                   }
                   
                   if ($key == CLAVE_INI || $key == CLAVE_FIN) {                       
                       $elemento = substr ($elemento, 0,strpos ($elemento, "T"));
                   }
                   
                   editarElemento($elemento);
                   fwrite ($archivoCSV, "\"$elemento\";");
               }
               fwrite ($archivoCSV, "\n");
           }
       }
       }
       fclose ($archivoCSV);
   }
?>