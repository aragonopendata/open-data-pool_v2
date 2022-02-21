<?php
	$vista = 72;               								//La vista de donde se optiene los datos
	
	define ("CLAVE_NECESITA", "codigo_mun");				//La clave que necesita para poder completar los datos
	define ("MUNICIPIO", "municipio_establecimiento");		//La clave relaciona con el municipio	
	define ("PROVINCIA", "provincia_establecimiento");		//La clave relaciona con la provincia
	define ("CLAVE_URI", "signatura");
	
	include 'comun.php';
	
		
	
	
	
	if ($archivoCSV !== false) {
	    array_push ($keys, CLAVE_NECESITA);
	    fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
	    
	    fwrite ($archivoCSV, "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
	    
		for ($i=1; $i <= $numeroArchivos; $i++){
		    $datosArchivo = file_get_contents (RUTA_XML."vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
				$datosArchivo = str_replace ("list-item", "item", $datosArchivo);
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
							$elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
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