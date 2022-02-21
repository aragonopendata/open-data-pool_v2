<?php
    $vista=70;
    define ("CLAVE_URI", "codigo");
    define ("CLAVE_TIENE", "loca_mun");
    define ("CLAVE_NECESITA", "codigo_mun");
    include 'comun.php';   
    
    $claveComun = "denominacion"; //La clave que tiene en comun todas la vista
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml
    
    $xmlDepende = simplexml_load_file ("../VistasXml/Vista11/Vista_11_1.xml");
    
    if ($archivoCSV !== false) { 
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            $nombreMun = $xml->item[$x]->{CLAVE_TIENE};                                                
                            $nombreMun = substr ($nombreMun, 0, -3);
                            
                            $codMun = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$nombreMun."']/".CLAVE_NECESITA);
                            $elemento = $codMun [0];
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