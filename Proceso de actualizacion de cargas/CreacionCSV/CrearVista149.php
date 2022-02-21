<?php
    $vista=149;
    define ("CLAVE_URI1", "COD_EXPEDICION");
    define ("CLAVE_URI2", "COD_PARADA");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
            $xml = simplexml_load_string($datosArchivo);
            
            for ($x = 0; $x < ($xml->count ()); $x++) {
                foreach ($keys as $key) {
                    $elemento = $xml->item[$x]->$key;
                    
                    if ($key == CLAVE_URL) {
                        $valor1 = $xml->item[$x]->{CLAVE_URI1}->__toString();
                        $valor2 = $xml->item[$x]->{CLAVE_URI2}->__toString();
                        $valor2 = quitarDecimales ($valor2);
                        
                        $filtro = "CLAVE_URI1='$valor1' and CLAVE_URI2='$valor2'";
                        
                        $filtro = urlencode ($filtro);
                        
                        
                        $elemento = "https://opendata.aragon.es/GA_OD_Core/preview?view_id=$vista&filter_sql=$filtro&_pageSize=1&_page=1";
                       
                    }
                    
                    if ($key == CLAVE_URI2) {
                        $elemento = quitarDecimales ($elemento);
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