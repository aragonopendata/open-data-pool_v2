<?php 
    $vista=141;
    define ("CLAVE_URI1", "GRT_ID_RUTA");
    define ("CLAVE_URI2", "GRT_ID_ITINERARIO");
    define ("CLAVE_URI3", "GRT_ID_PARADA");
    define ("CLAVE_NECESITA","CODIGO_MUN");
    define ("GRT_ID_TIPO_PARADA","GRT_ID_TIPO_PARADA"); //Es el camp GRT_ID_TIPO_PARADA del archivo de datos
    define ("GRT_ID_LOCALIDAD","GRT_ID_LOCALIDAD"); //Es el camp GRT_ID_LOCALIDAD del archivo de datos
    define ("GRT_ID_LOCALIDAD_CRA","GRT_ID_LOCALIDAD_CRA"); //Es el camp GRT_ID_LOCALIDAD_CRA del archivo de datos
    define ("GRT_ID_CENTRO","GRT_ID_CENTRO"); //Es el camp GRT_ID_CENTRO del archivo de datos
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\"");
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
            $xml = simplexml_load_string($datosArchivo);
            
            for ($x = 0; $x < ($xml->count ()); $x++) {
                $tipo = $elemento = $xml->item[$x]->{GRT_ID_TIPO_PARADA}->__toString();
                if (strtoupper($tipo) != 'RUTA') {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key->__toString();
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacionTresClaves($xml, $x, $vista, CLAVE_URI1, CLAVE_URI2, CLAVE_URI3);
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            $elemento = $xml->item[$x]->{GRT_ID_LOCALIDAD}->__toString();
                            
                            if (empty ($elemento)) { //Comprobamos que el dato de la localidad no este vacio, 
                                //si lo esta se busca en el campo de la localidad cra y ese esta vacio se comprueba el campo de centro
                                $elemento = $xml->item[$x]->{GRT_ID_LOCALIDAD_CRA}->__toString();
                            }
                            if (empty ($elemento)) {
                                $elemento = $xml->item[$x]->{GRT_ID_CENTRO}->__toString();
                            }
                            
                            $elemento = substr ($elemento, 0, 5);
                        }
                        
                        editarElemento($elemento);
                        fwrite ($archivoCSV, "\"$elemento\";");
                    }
                    fwrite ($archivoCSV, "\n");
                }
                
                
            }
        }
        }
        fclose ($archivoCSV);
    }
?>