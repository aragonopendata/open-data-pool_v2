<?php
    $vista=151;
    define ("CLAVE_URI", "COD_RUTA");
    define ("CLAVE_NECESITA1","PARADA_ORIGEN");
    define ("CLAVE_NECESITA2","PARADA_DESTINO");
    define ("CLAVE_ORIGEN","ORIGEN");
    define ("CLAVE_DESTINO","DESTINO");
    define ("CLAVE_MUN_ORIGEN","MUN_ORIGEN");
    define ("CLAVE_MUN_DESTINO","MUN_DESTINO");
    define ("COD_MUN","CODIGO_MUN");
    include 'comun.php';
    
    $xmlDepende = simplexml_load_file("../VistasXml/VistasCompletas/Vista150_completa.xml");
    $xml11 = simplexml_load_file("../VistasXml/Vista11/vista_11_1.xml"); //El xml de la vista 11 Datos Municipio
        
    $claveNucleo = "NUCLEO"; //La clave que tiene en comun todas la vista    
    $claveParada = "COD_PARADA";
    $claveMunicipio = "DENOMINACION";
    
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.
    
    if ($archivoCSV !== false) {
        array_push ($keys,CLAVE_NECESITA1); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA1."\";"); //y la añadidomos al csv
        
        array_push ($keys,CLAVE_NECESITA2); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA2."\";"); //y la añadidomos al csv
        
        array_push ($keys,CLAVE_MUN_ORIGEN); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_MUN_ORIGEN."\";"); //y la añadidomos al csv
        
        array_push ($keys,CLAVE_MUN_DESTINO); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_MUN_DESTINO."\";"); //y la añadidomos al csv
        
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
                        $claveOrigen = $xml->item[$x]->{CLAVE_ORIGEN}->__toString();
                        $id_parada = $xmlDepende->xpath ("/".$root."/".$item."[".$claveNucleo."= '".$claveOrigen."']/".$claveParada);
                        $elemento = $id_parada[0];
                        $elemento = quitarDecimales ($elemento);
                    }
                    
                    if ($key == CLAVE_NECESITA2) {
                        $claveDestino = $xml->item[$x]->{CLAVE_DESTINO}->__toString();
                        $id_parada = $xmlDepende->xpath ("/".$root."/".$item."[".$claveNucleo."= '".$claveDestino."']/".$claveParada);
                        $elemento = $id_parada[0];
                        $elemento = quitarDecimales ($elemento);
                    }
                    
                    if ($key == CLAVE_MUN_ORIGEN) {
                        $claveOrigen = $xml->item[$x]->{CLAVE_ORIGEN}->__toString();
                        $idMun = $xml11->xpath ("/".$root."/".$item."[".$claveMunicipio."= '".$claveOrigen."']/".COD_MUN);
                        $elemento = $idMun [0];
                    }
                    
                    if ($key == CLAVE_MUN_DESTINO) {
                        $claveDestino = $xml->item[$x]->{CLAVE_DESTINO}->__toString();
                        $idMun = $xml11->xpath ("/".$root."/".$item."[".$claveMunicipio."= '".$claveDestino."']/".COD_MUN);
                        $elemento = $idMun [0];
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