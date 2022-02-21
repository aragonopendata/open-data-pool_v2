<?php
    $vista=39;
    define ("CLAVE_URI", "ORDENANZA_ID");
    define ("CLAVE_NECESITA", "CODIGO_MUN");
    define ("CLAVE_TIENE", "DENOMINACION");
    define ("CLAVE_MODIFICAR","SUBTIPO");
    include 'comun.php';
    
    $codigosMunicipio = array ();
    $codigosMunicipio ["DIPUTACION PROVINCIAL DE ZARAGOZA"] = "50297";
    $codigosMunicipio ["DIPUTACION PROVINCIAL DE HUESCA"] = "22125";
    $codigosMunicipio ["DIPUTACION PROVINCIAL DE TERUEL"] = "44216";
    
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
                    
                    if($key == CLAVE_MODIFICAR){ //Si es el elemento termina en BLICOS
                        $pos = strpos($elemento, "BLICOS");
                        if($pos !== false){
                            $elemento = 'PRECIOS PUBLICOS';
                        }
                    }
                    
                    if ($key == CLAVE_NECESITA) {
                        $nombreMun = $xml->item[$x]->{CLAVE_TIENE}->__toString();
                        $elemento = $codigosMunicipio [$nombreMun];
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