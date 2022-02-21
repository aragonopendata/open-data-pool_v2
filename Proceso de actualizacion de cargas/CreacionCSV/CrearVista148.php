<?php
    $vista=148;
    define ("CLAVE_URI", "COD_EXP");
    define ("CLAVE_NECESITA", "COD_RUTA"); //La clave que necesita para poder relacionarse con la vista 151
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
        
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
                    
                    if ($key == CLAVE_NECESITA) {
                        $clave = $xml->item[$x]->{CLAVE_URI}->__toString ();
                        $elemento = substr($clave, 0, (strpos($clave, 'E') - 1));
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