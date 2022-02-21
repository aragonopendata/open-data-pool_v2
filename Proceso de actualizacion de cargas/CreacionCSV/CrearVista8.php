<?php
    $vista = 8;
    define ("CLAVE_URI", "ORDENANZA_ID");
	define ("VISTA_NECESITA", "10");										//El numero de la vista que necesita para completar sus datos
	define ("CLAVE_TIENE", "COMAR_ID");										//La clve que tiene para poder relacionarse
	define ("CLAVE_NECESITA","CODIGO_COMARC"); 								//La clave que necesita
	define ("CLAVE_MODIFICAR","SUBTIPO");
	define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
	define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
	
	include 'comun.php';
	
	if ($archivoCSV !== false) {
	    $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
	    
	    //Obtenermos los datos del xml que depende, es decir, el xml que tiene los datos para poder relacionarse con la otra vista
	    if (file_exists (RUTA_XML_DEPENDE)) {
	        $codigosVistaNecesita = arrayClavesFicheroRelacionar($codigosVistaNecesita);
	    }
	    
	    array_push ($GLOBALS["keys"],CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
	    fwrite ($GLOBALS["archivoCSV"], "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
	    
	    fwrite ($GLOBALS["archivoCSV"], "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
	    
	    //se leen los archivos xml de la vista de los datos y se crea el archivo csv correspondientes a la vista
	    for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i++) {
	        $datosXml2 = file_get_contents (RUTA_XML."vista_".$GLOBALS["vista"]."_$i.xml");
			if (is_string ($datosXml2) ) {
				$xml2 = simplexml_load_string($datosXml2);
				
				for ($z = 0; $z < ($xml2->count ()); $z++) {
					foreach ($GLOBALS["keys"] as $key) {
						$elemento = $xml2->item[$z]->$key;
						
						if ($key == CLAVE_NECESITA){ //Si es el elemento del codigo de provincia que no esta en el xml se busca en el array creado antes y se inserta en el documento
							$idTiene = $xml2->item[$z]->{CLAVE_TIENE}->__toString();
							$idTiene = preg_replace("/\r|\n/", "", $idTiene);	//Quitamos los saltos de linea porque sino da error
							$idNecesita = $codigosVistaNecesita[$idTiene];
							$elemento = $idNecesita [0]; //OJO, obtenemos el codigo de municipio, porque la linea anterior devuelve un array
						}
						
						if($key == CLAVE_MODIFICAR){ //Si es el elemento termina en BLICOS
							$pos = strpos($elemento, "BLICOS");
							if($pos !== false){
								$elemento = 'PRECIOS PUBLICOS';
							}
						}
						
						if ($key == CLAVE_URL) {
							$elemento = obtenerUrlVinculacion($xml2, $z, $GLOBALS["vista"], CLAVE_URI);
						}
						
						editarElemento($elemento);
						
						fwrite ($GLOBALS["archivoCSV"], "\"$elemento\";");
					}
					
					fwrite($GLOBALS["archivoCSV"], "\n");
				}
			}
	    }
	    
	    fclose ($GLOBALS["archivoCSV"]);
	    
	    
	}	
	
	
?>