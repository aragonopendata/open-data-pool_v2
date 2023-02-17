<?php
    $vista=70;
    define ("CLAVE_URI", "codigo");
    define ("CLAVE_TIENE", "loca_mun");
    define ("CLAVE_NECESITA", "codigo_mun");
    define("URL_CLAVE_PRO", "../VistasCsv/Relacion/DatosNomPro.csv");       //La ruta para obtener los codigos de provincias
    define ("CLAVE_NOMBRE_PRO", "nombre_provincia");                         //El nombre de la clave para obtener el nombre de la provincia
    include 'comun.php';   
    
    $claveComun = "denominacion"; //La clave que tiene en comun todas la vista
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml
    
#    $xmlDepende = simplexml_load_file ("../VistasXml/Vista11/Vista_11_1.xml");
    
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
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            #$id_mun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                            $nombre_pro = $xml->item[$x]->{CLAVE_NOMBRE_PRO}->__toString();
                            $id_pro = $arrayClavesPro [$nombre_pro]; 
			    $nombre_mun = $xml->item[$x]->{CLAVE_TIENE}->__toString();                  
                            $id_mun = substr($nombre_mun, -3);	                            

                            while (strlen($id_mun) < 3) {
                                $id_mun = "0".$id_mun;
                            }
                            
                            $elemento = $id_pro.$id_mun;
                            
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
