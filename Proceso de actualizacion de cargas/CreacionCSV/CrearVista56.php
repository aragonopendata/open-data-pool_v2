<?php
    $vista=56;
    define ("CLAVE_URI", "nombre");
    define ("CLAVE_CARGO", "cargo");
    define ("URL_VISTA_NECESITA", "../VistasXml/Vista61/Vista_61_1.xml"); //Ruta a la vista 61, para poder obtener el codgio del municipio al cual pertenece.
    define("CLAVE_NECESITA", "codigo_mun");  //La clave que pusimos en el mapeo
    define("CLAVE_TIENE", "cvt_id");  //La clave que pusimos en el mapeo
    define ("CLAVE_PARTIDO", "partido");
    
    $claveVillaTierra = "agrupante_id"; //La clave que tiene en comun de las dos vistas
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.
    
    $xmlDepende = simplexml_load_file (URL_VISTA_NECESITA);  //El xml del cual hay que sacar la relacion con el municipio
    
    include 'comun.php';
    
    $cargos = array ();
    $cargos ["C"] = "CONCEJAL";
    $cargos ["P"] = "PRESIDENTE";
    $cargos ["V"] = "VOCAL";
    $cargos ["VP"] = "VICEPRESIDENTE";
    
    
    if ($archivoCSV !== false) {
        array_push ($keys,CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";"); //y la añadimos al csv
        
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
                        
                        if ($key == CLAVE_CARGO) {
                            $elemento = $cargos[$elemento->__toString ()];
                        }
                        
                        if ($key == CLAVE_PARTIDO) {
                            $elemento = str_replace ("´", "", $elemento);
                            $elemento = str_replace ("'", "", $elemento);
                            $elemento = str_replace ("`", "", $elemento);
                            $elemento = str_replace ("C,s", "Cs", $elemento);
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            $codVillaTierra = $xml->item[$x]->{CLAVE_TIENE}->__toString ();
                            $codMun = $xmlDepende->xpath ($item."[".$claveVillaTierra."= '".$codVillaTierra."']/".CLAVE_NECESITA);
                            logErrores(print_r($codMun));
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