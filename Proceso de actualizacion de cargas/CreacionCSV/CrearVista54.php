<?php
    $vista=54;
    define ("CLAVE_URI", "nombre");
    define ("CLAVE_CARGO", "cargo");
    define ("CLAVE_VISTA_27", "entidad_local");
    define ("CLAVE_VISTA_11", "denominacion");
    define ("CLAVE_TENGO", "orgauto_id");
    define ("CLAVE_NECESITA", "codigo_mun");
    define ("CLAVE_PARTIDO", "partido");
    include 'comun.php';
    
    $datosXmlDepende = file_get_contents ("../VistasXml/Vista27/Vista_27_1.xml");
    $datosXmlDepende = str_replace ("list-item", "item", $datosXmlDepende);
    file_put_contents ("../VistasXml/Vista27/Vista_27_1.xml", $datosXmlDepende);
    $xmlVista27 = simplexml_load_file ("../VistasXml/Vista27/Vista_27_1.xml");

    $datosXmlDepende = file_get_contents ("../VistasXml/Vista11/Vista_11_1.xml");
    $datosXmlDepende = str_replace ("list-item", "item", $datosXmlDepende);
    file_put_contents ("../VistasXml/Vista11/Vista_11_1.xml", $datosXmlDepende);
    $xmlVista11 = simplexml_load_file ("../VistasXml/Vista11/Vista_11_1.xml");
    
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.
    
    $cargos = array ();
    $cargos ["C"] = "CONCEJAL";
    $cargos ["P"] = "PRESIDENTE";
    $cargos ["V"] = "VOCAL";
   
    
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
                        
                        if ($key == CLAVE_PARTIDO) {
                            $elemento = str_replace ("´", "", $elemento);
                            $elemento = str_replace ("'", "", $elemento);
                            $elemento = str_replace ("`", "", $elemento);
                            $elemento = str_replace ("C,s", "Cs", $elemento);
                        }
                        
                        if ($key == CLAVE_CARGO) {
                            $elemento = $cargos[$elemento->__toString ()];
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            $codOrganismo = $xml->item[$x]->{CLAVE_TENGO}->__toString();
                            $nombreMun = $xmlVista27->xpath("/".$root."/".$item."[".CLAVE_TENGO."= '".$codOrganismo."']/".CLAVE_VISTA_27);
                            $nombreMun = $nombreMun[0]->__toString ();
                            $codMun = $xmlVista11->xpath("/".$root."/".$item."[".CLAVE_VISTA_11."= '".$nombreMun."']/".CLAVE_NECESITA);
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