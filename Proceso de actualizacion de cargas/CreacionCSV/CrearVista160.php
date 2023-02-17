<?php
    $vista = 160;
    
    define("CLAVE_URI", "id_cargo");
  
    define ("VISTA_NECESITA", "159");	
    define("CLAVE_NECESITA", "id_legislatura");
    define ("CLAVE_TIENE", "id_entidad");	
     //El numero de la vista que necesita para completar sus datos
										//La clve que tiene para poder relacionarse
							//La clave que necesita
	define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
	define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
	

	include 'comun.php';
	if ($archivoCSV !== false) {
          $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
         //Obtenermos los datos del xml que depende, es decir, el xml que tiene los datos para poder relacionarse con la otra vista
        if (file_exists (RUTA_XML_DEPENDE)) {            
            $codigosVistaNecesita = arrayClavesFicheroRelacionar($codigosVistaNecesita);
        }
       
        //se leen los archivos xml de la vista de los datos y se crea el archivo csv correspondientes a la vista
        for ($i = 1; $i <= $numeroArchivos; $i++) {
            $datosArchivo = file_get_contents(RUTA_XML . "Vista_" . $vista . "_$i.xml");
            if (is_string ($datosArchivo) ) {
                 $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                 $xml = simplexml_load_string($datosArchivo);
                $num = $xml->count();
                for ($x = 0; $x < $num; $x++) {
                    
                    if (!empty($xml->item[$x]->{CLAVE_TIENE})){
                            $idTiene = $xml->item[$x]->{CLAVE_TIENE}->__toString();
                            $idTiene = preg_replace("/\r|\n/", "", $idTiene);	//Quitamos los saltos de linea porque sino da error
                            $idNecesita = $codigosVistaNecesita[$idTiene];
                            $elemento = $idNecesita [0]; //OJO, obtenemos el codigo de municipio, porque la linea anterior devuelve un array
                            if ($elemento == 25){
                                foreach ($keys as $key) {
                                   $elemento = $xml->item[$x]->$key;
                                  
                                    if ($key == CLAVE_URL) {
                                        $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
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
