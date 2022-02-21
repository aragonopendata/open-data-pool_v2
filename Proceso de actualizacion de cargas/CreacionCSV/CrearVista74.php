<?php
	$vista = 74;               								//La vista de donde se optiene los datos
	
	define ("CLAVE_NECESITA", "codigo_mun");				//La clave que necesita para poder completar los datos
	define ("MUNICIPIO", "municipio_expediente");		//La clave relaciona con el municipio	
	define ("PROVINCIA", "provincia_expediente");		//La clave relaciona con la provincia
	define ("CLAVE_URI1", "num_expte");
	define ("CLAVE_URI2", " f_acuerdo");
	
	include 'comun.php';
	
	if ($archivoCSV !== false) {
	    array_push ($keys, CLAVE_NECESITA);
	    fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
	    
	    fwrite ($archivoCSV, "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
	    
		for ($i=1; $i <= $numeroArchivos; $i++){
		    $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
				$datosArchivo = str_replace("list-item", "item", $datosArchivo);
				$xml = simplexml_load_string($datosArchivo);
				for ($x = 0; $x < ($xml->count ()); $x++) {			
					foreach ($keys as $key) {
						$elemento = $xml->item[$x]->$key;
						
						if ($key == CLAVE_NECESITA){
							$municipio = $xml->item[$x]->{MUNICIPIO};
							$provincia = $xml->item[$x]->{PROVINCIA};
							while (strlen($municipio) < 3) {
								$municipio = "0".$municipio;
							} 
							$elemento = $provincia.$municipio;
						}
						
						if ($key == CLAVE_URL) {					    
							$elemento = obtenerUrlVinculacionVariasClaves($xml, $x, $vista, CLAVE_URI1, CLAVE_URI2);
						}
						editarElemento($elemento);
						fwrite ($archivoCSV, "\"$elemento\";");
					}
					fwrite($archivoCSV, "\n");
				}
			}	
		}
		fclose ($archivoCSV);
	}
?>