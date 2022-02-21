<?php
    define ("CLAVE_URI", "codigo");
    define("CLAVE_NECESITA", "codigo_mun");                                 //La clave que necesita para relacionarse con la vista 11   
    define("CLAVE_MUN", "municipio_establecimiento");                       //Una de las claves por la cual se compone la calve que necesita
    define ("CLAVE_BUSCAR", "codigo_comarc");                               //Es la clave que se tiene que buscar en la vista 10 para poder obtener los datos para completar la relación
    define("URL_CLAVE_PRO", "../VistasCsv/Relacion/DatosNomPro.csv");       //La ruta para obtener los codigos de provincias
    define ("CLAVE_NOMBRE_PRO", "nombre_provincia");                        //El nombre de la clave para obtener el nombre de la provincia
    
    $vista=71;
    include 'comun.php';
       
    $arrayClavesPro = obtenerURL (URL_CLAVE_PRO);
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
       
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count()); $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_NECESITA) {
                            $id_mun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                            $nombre_pro = $xml->item[$x]->{CLAVE_NOMBRE_PRO}->__toString();                        
                            $id_pro = $arrayClavesPro [$nombre_pro];
                            
                            while (strlen($id_mun) < 3) {
                                $id_mun = "0".$id_mun;
                            }
                            
                            $elemento = $id_pro.$id_mun;
                            
                        }                    
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
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
    else {
        escribirError ($vista, "Se ha producido un error en la vista:");
    }

?>