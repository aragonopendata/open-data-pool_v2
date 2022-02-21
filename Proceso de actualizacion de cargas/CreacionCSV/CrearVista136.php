<?php
    $vista=136;
    define ("CLAVE_URI", "COD_PARADA");
    include 'comun.php';
    
	define ("CLAVE_NECESITA", "CODIGO_MUN");				//La clave que necesita para tener todos los datos
	define ("MUNICIPIO", "COD_MUNICIPIO");					//La clave del codigo municipio en el xml de la vista
    define ("PARADA", "COD_PARADA");
	
	array_push ($keys, CLAVE_NECESITA);
	fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
		
	fwrite ($archivoCSV, "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
	
	if ($archivoCSV !== false) { 
	   for ($i=1; $i <= $numeroArchivos; $i++){
	        $datosArchivo = file_get_contents (RUTA_XML."vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
    		$xml = simplexml_load_string($datosArchivo);
	       	for ($x = 0; $x < ($xml->count ()); $x++) {			
			     foreach ($keys as $key) {
				    $elemento = $xml->item[$x]->$key;
				
				    if ($key == CLAVE_NECESITA){
					   $municipio = $xml->item[$x]->{MUNICIPIO};						
					   $elemento = substr($municipio,0,5);
				    }
				    
				    if($key == PARADA){
				        $elemento = substr($elemento,0,strlen($elemento)-2);
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