<?php
    $vista=38;
    define ("CLAVE_URI", "ORDENANZA_ID");
    define ("CLAVE_MODIFICAR","SUBTIPO");
    include 'comun.php';
    
    
    fwrite ($GLOBALS["archivoCSV"], "\n");
    for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i ++) {
        $datosArchivo = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
		if (is_string ($datosArchivo) ) {
        $xml = simplexml_load_string($datosArchivo);
        
        for ($x = 0; $x < ($xml->count ()); $x++) {
            foreach ($GLOBALS["keys"] as $key) {
                $elemento = $xml->item[$x]->$key;
                
                if ($key == CLAVE_URL) {
                    $elemento = obtenerUrlVinculacion($xml, $x, $GLOBALS["vista"], CLAVE_URI);
                }
                if($key == CLAVE_MODIFICAR){ //Si es el elemento termina en BLICOS
                    $pos = strpos($elemento, "BLICOS");
                    if($pos !== false){
                        $elemento = 'PRECIOS PUBLICOS';
                    }
                }
                
                editarElemento($elemento);
                fwrite ($GLOBALS["archivoCSV"], "\"$elemento\";");
            }
            fwrite ($GLOBALS["archivoCSV"], "\n");
        }
    }
    }
    fclose ($GLOBALS["archivoCSV"]);
?>