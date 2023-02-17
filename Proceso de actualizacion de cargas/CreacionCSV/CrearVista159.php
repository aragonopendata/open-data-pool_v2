<?php
$vista = 159;
define("CLAVE_URI", "id_entidad");
define("CLAVE_NECESITA", "padre");
define("CLAVE_NECESITA1", "municipio");
define("CLAVE_PADRE", "id_entidad_padre");
define("CLAVE_PADRE_LOC", "localidad");
define("CLAVE_LEGISLATURA", "id_legislatura");
include 'comun.php';

if ($archivoCSV !== false) {
   array_push($keys, CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
    fwrite($archivoCSV, "\"" . CLAVE_NECESITA . "\";"); //y la añadidomos al csv
    array_push($keys, CLAVE_NECESITA1); //Le añadimos la clave que necesita y no la tiene el xml
   fwrite($archivoCSV, "\"" . CLAVE_NECESITA1 . "\";"); //y la añadidomos al csv

    fwrite($archivoCSV, "\n");
    for ($i = 1; $i <= $numeroArchivos; $i++) {
        $datosArchivo = file_get_contents(RUTA_XML . "Vista_" . $vista . "_$i.xml");
        if (is_string($datosArchivo)) {
            $datosArchivo = str_replace("list-item", "item", $datosArchivo);
            $xml = simplexml_load_string($datosArchivo);

            for ($x = 0; $x < ($xml->count()); $x++) {
                if (!empty($xml->item[$x]->{CLAVE_URI})) {
                    if (($xml->item[$x]->{CLAVE_LEGISLATURA}) == 25){
                    	foreach ($keys as $key) {
                        	$elemento = $xml->item[$x]->$key;

                        	if ($key == CLAVE_URL) {
                            		$elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        	}

	                        if ($key == CLAVE_NECESITA) {
        		                   $elemento = $xml->item[$x]->{CLAVE_PADRE};
                        	}
                        	if ($key == CLAVE_NECESITA1) {
                            		$elemento = $xml->item[$x]->{CLAVE_PADRE_LOC};
                        	}
                        
				editarElemento($elemento);

                        	if (isInteger($elemento) || trim($elemento) == "" ) {
                            		fwrite($archivoCSV, $elemento.";");
                        	} else {
                            		fwrite($archivoCSV, "\"$elemento\";");
                        	}
                    	}
                    	fwrite($archivoCSV, "\n");
                    }	
		}
            }
        }
    }
    fclose($archivoCSV);
}
?>
