<?php
    $vista = 134;
    define ("CLAVE_URI1", "COD_PARADA");
    define ("CLAVE_URI2", "IDEXP");
    include 'comun.php'; //Incluimos toda la parte comun a todos los archivos
    define ("HORARIO","HORARIO");

    if ($archivoCSV !== false) {
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
            $xml = simplexml_load_string($datosArchivo);
            
            for ($x = 0; $x < ($xml->count()); $x++) {
                foreach ($keys as $key) {
                    $elemento = $xml->item[$x]->$key;
                    
                    if ($key == HORARIO) {
                        $posT = strpos ($elemento, "T");
                        $elemento = substr($elemento, ($posT + 1));
                    }
                    
                    if ($key == CLAVE_URL) {
                        $elemento = obtenerUrlVinculacionVariasClaves($xml, $x, $GLOBALS["vista"], CLAVE_URI1, CLAVE_URI2);
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