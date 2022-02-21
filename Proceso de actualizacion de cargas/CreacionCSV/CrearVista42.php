<?php
    $vista=42;
    define ("CLAVE_URI", "ORDENANZA_ID");
    define ("CLAVE_VISTA_27", "ENTIDAD_LOCAL");
    define ("CLAVE_VISTA_11", "DENOMINACION");
    define ("CLAVE_TENGO", "ORGAUTO_ID");
    define ("CLAVE_NECESITA", "CODIGO_MUN");
    define ("CLAVE_MODIFICAR","SUBTIPO");
    include 'comun.php';
    
    $xmlVista27 = simplexml_load_file ("../VistasXml/Vista27/vista_27_1.xml");
    $xmlVista11 = simplexml_load_file ("../VistasXml/Vista11/vista_11_1.xml");
    
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.
    
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