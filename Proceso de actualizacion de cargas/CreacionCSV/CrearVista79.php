<?php 
    $vista = 79;
    define ("CLAVE_URI1", "CCORRE");
    define ("CLAVE_URI2", "CCOSEC");
    define ("CLAVE_URI3", "CORDEN");
    define ("CLAVE_NECESITA", "CODIGO_MUN");
    define ("CLAVE_MUN", "CMUNCA");
    define ("CLAVE_PRO", "CPROCA");
    
    define ("RUTA_XML_ADICIONAL", "../VistasCsv/Vista".$vista."_adicional/");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        array_push ($keys,CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
            $xml = simplexml_load_string($datosArchivo);
            
            for ($x = 0; $x < ($xml->count ()); $x++) {
                foreach ($keys as $key) {
                    $elemento = $xml->item[$x]->$key;
                    
                    if ($key == CLAVE_URL) {
                        $elemento = obtenerUrlVinculacionTresClaves ($xml, $x, $vista, CLAVE_URI1, CLAVE_URI2, CLAVE_URI3);
                    }
                    
                    if ($key == CLAVE_NECESITA) {
                        $codMun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                        $codPro = $xml->item[$x]->{CLAVE_PRO}->__toString();
                        
                        while (strlen($codMun) < 3) {
                            $codMun = "0".$codMun;
                        }
                        
                        $elemento = $codPro.$codMun;
                    }
                    
                    editarElemento($elemento);
                    fwrite ($archivoCSV, "\"$elemento\";");
                }
                fwrite ($archivoCSV, "\n");
            }
        }
		}
    }
?>	