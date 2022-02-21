<?php 
    $vista = 78;
    define ("CLAVE_URI1", "CCORRE");
    define ("CLAVE_URI2", "CCOSEC");   
    define ("CLAVE_NECESITA", "CODIGO_MUN"); //La clave que necesita para poder relacionarse con la vista 11 Datos Municipio
    define ("CLAVE_MUN", "CMUNDO"); //La clave que tiene el codgio de municipio
    define ("CLAVE_PRO", "CPRODO"); //La clave que tiene el codigo de la provincia
    define ("CLAVE_NOMBRE", "NOMBRE_PER"); //La clave donde se guarda el nombre de la persona
    
    include 'comun.php';
    
    $idPersona = 1;
    if ($archivoCSV !== false) {
        array_push ($keys,CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
        
        array_push ($keys,CLAVE_NOMBRE); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NOMBRE."\";"); //y la añadidomos al csv
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
            $xml = simplexml_load_string($datosArchivo);
            
            for ($x = 0; $x < ($xml->count ()); $x++) {
                foreach ($keys as $key) {
                    $elemento = $xml->item[$x]->$key;
                    
                    if ($key == CLAVE_URL) {
                        $elemento = obtenerUrlVinculacionVariasClaves ($xml, $x, $vista, CLAVE_URI1, CLAVE_URI2);
                    }
                    
                    if ($key == CLAVE_NECESITA) {
                        $codMun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                        $codPro = $xml->item[$x]->{CLAVE_PRO}->__toString();
                        
                        while (strlen($codMun) < 3) {
                            $codMun = "0".$codMun;
                        }
                        
                        $elemento = $codPro.$codMun;
                    }
                    
                    if ($key == CLAVE_NOMBRE) {
                        $elemento = "ANONIMO-$idPersona";
                        $idPersona += 1; 
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