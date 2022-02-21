<?php
    define ("CLAVE_URI", "cod_agen");
    define("CLAVE_NECESITA1", "codigo_mun");
    define("CLAVE_NECESITA2", "nombre_muni");
    define("CLAVE_NECESITA3", "nombre_prov");
    define("CLAVE_MUN", "cod_muni");
    define("CLAVE_PRO", "cod_prov");
    define("URL_CLAVE_NECESITA", "../VistasCsv/Relacion/DatosCodPro.csv");    
    define("CLAVE_ARCHIVO_NECESITA", "denominacion");
    define ("VISTA_NECESITA", "11");
    define ("RUTA_XML_11", "../VistasXml/Vista11/vista_".VISTA_NECESITA."_1.xml");
        
    $claveRequiere = "denominacion";
    $root = "root";
    $item = "item";
    
    $vista=63;
    include 'comun.php';
    
    $arrayClavesPro = obtenerURL (URL_CLAVE_NECESITA);
    
    $datos11 = file_get_contents (RUTA_XML_11);
    $xml11 = simplexml_load_string($datos11);
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA1);        
        array_push ($keys, CLAVE_NECESITA2);
        array_push ($keys, CLAVE_NECESITA3);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA1."\";");
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA2."\";");
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA3."\";");
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count()); $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_NECESITA1) {
                            $id_mun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                            $id_pro = $xml->item[$x]->{CLAVE_PRO}->__toString();
                            
                            $elemento = $id_pro.$id_mun;
                        }
                        
                        if ($key == CLAVE_NECESITA2) {
                            $id_mun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                            $id_pro = $xml->item[$x]->{CLAVE_PRO}->__toString();
                            
                            $codigo_Mun = $id_pro.$id_mun;
                            $denominacion = $xml11->xpath ("/".$root."/".$item."[".CLAVE_NECESITA1."='".$codigo_Mun."']"."/".$claveRequiere);
                            $elemento = $denominacion[0];
                        }
                        
                        if ($key == CLAVE_NECESITA3) {
                            $id_pro = $xml->item[$x]->{CLAVE_PRO}->__toString();
                            $elemento = $arrayClavesPro [$id_pro];
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