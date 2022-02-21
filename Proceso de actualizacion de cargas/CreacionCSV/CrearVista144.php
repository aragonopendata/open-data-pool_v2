<?php
    $vista=144;
    define ("CLAVE_URI", "GRT_ID_RUTA");
    define ("CLAVE_NECESITA1","PARADA_ORIGEN");
    define ("CLAVE_NECESITA2","PARADA_DESTINO");
    define ("CLAVE_NECESITA3","ITINERARIO");
    define ("CLAVE_GRT_ID_RUTA","GRT_ID_RUTA");
    define ("COD_MUN","CODIGO_MUN");
    define ("COD_MUN_OR","CODIGO_MUN_OR");
    define ("COD_MUN_DES","CODIGO_MUN_DES");
    include 'comun.php';
    
    $xmlDepende = simplexml_load_file("../VistasXml/VistasCompletas/Vista141_completa.xml"); //La ruta al archivo con todos los datos de la vista 141
    $xml11 = simplexml_load_file("../VistasXml/Vista11/vista_11_1.xml"); //El xml de la vista 11 Datos Municipio
    
    $claveLocalidad = "GRT_ID_LOCALIDAD"; //La clave que tiene en comun todas la vista
    $claveParada = "GRT_ID_PARADA";
    $claveItinerario = "GRT_ID_ITINERARIO";
    $claveMunicipio = "DENOMINACION";
    
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.
    
    if ($archivoCSV !== false) {
        
        array_push ($keys,CLAVE_NECESITA1); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA1."\";"); //y la añadidomos al csv
        
        array_push ($keys,CLAVE_NECESITA2); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA2."\";"); //y la añadidomos al csv
        
        array_push ($keys,CLAVE_NECESITA3); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA3."\";"); //y la añadidomos al csv
        
        array_push ($keys,COD_MUN_OR); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".COD_MUN_OR."\";"); //y la añadidomos al csv
        
        array_push ($keys,COD_MUN_DES); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".COD_MUN_DES."\";"); //y la añadidomos al csv
        
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
                    
                    if ($key == CLAVE_NECESITA1) {
                        $claveOrigen = $xml->item[$x]->{CLAVE_GRT_ID_RUTA}->__toString();
                        $id_parada = $xmlDepende->xpath ("/".$root."/".$item."[".CLAVE_GRT_ID_RUTA."= '".$claveOrigen."']/".$claveParada);
                        $elemento = $id_parada[0];
                    } 
                    
                    if ($key == CLAVE_NECESITA2) {
                        $claveOrigen = $xml->item[$x]->{CLAVE_GRT_ID_RUTA}->__toString();
                        $id_parada = $xmlDepende->xpath ("/".$root."/".$item."[".CLAVE_GRT_ID_RUTA."= '".$claveOrigen."']/".$claveParada);
                        $numeroElementos = count($id_parada) - 1;
                        $elemento = $id_parada[$numeroElementos];
                    } 
                    
                    if ($key == CLAVE_NECESITA3) {
                        $claveOrigen = $xml->item[$x]->{CLAVE_GRT_ID_RUTA}->__toString();
                        $id_itinerario = $xmlDepende->xpath ("/".$root."/".$item."[".CLAVE_GRT_ID_RUTA."= '".$claveOrigen."']/".$claveItinerario);
                        $elemento = $id_itinerario[0];
                    }
                    
                    
                    if ($key == COD_MUN_OR) {
                        $claveDestino = $xml->item[$x]->{CLAVE_GRT_ID_RUTA}->__toString(); 
                        $idMun = $xmlDepende->xpath ("/".$root."/".$item."[".CLAVE_GRT_ID_RUTA."= '".$claveOrigen."']/".$claveLocalidad);
                        $elemento = $idMun [0];
                        $i=0;
                        while (empty($elemento)) {
                            $i = $i+1;
                            $elemento = $idMun [$i+i];
                        }
                        $elemento = substr($elemento,0,5);
                    }
                    
                    if ($key == COD_MUN_DES) {
                        $claveDestino = $xml->item[$x]->{CLAVE_GRT_ID_RUTA}->__toString();
                        $idMun = $xmlDepende->xpath ("/".$root."/".$item."[".CLAVE_GRT_ID_RUTA."= '".$claveOrigen."']/".$claveLocalidad);
                        $numeroElementos = count($idMun) - 1;
                        $elemento = $idMun [$numeroElementos];
                        $i=0;
                        while (empty($elemento)) {
                            $i = $i+1;
                            $elemento = $idMun [$numeroElementos-$i];
                        }
                        
                        $elemento = substr($elemento,0,5);
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