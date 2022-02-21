<?php
    $vista = 103;
    define ("CLAVE_URI", "inventario");
    define ("VISTA_NECESITA", "11");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "lugar_de_proteccion_ceca");						//La clve que tiene para poder relacionarse
    define ("CLAVE_TIENE_DEPENDE", "denominacion");                         //La clave que corresponde en el xml que depende
    define ("CLAVE_NECESITA","codigo_mun"); 								//La clave que necesita
    define ("XML_DEPENDE", "Vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    define ("CLAVE_TITULO_DOC", "titulo_documento"); 	                    //La clave que tiene el titulo del documento.
    define ("CLAVE_TITULO", "titulo"); 	                                    //La clave que hace referencia a la propiedad de TITULO.
    define ("CLAVE_DESCRIPCION", "descripcion");                            //La clave que hace referencia a la propiedad de DESCRIPCION.
    define ("CLAVE_CONJUNTO", "conjunto");                                  //La clave que hace referencia a la propiedad de DESCRIPCION.
    
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        //Obtenermos los datos del xml que depende, del cual depende para poder realizar el csv
        if (file_exists (RUTA_XML_DEPENDE)) {
            
            $datosArchivo = file_get_contents (RUTA_XML_DEPENDE.XML_DEPENDE);
            $datosArchivo = str_replace("list-item", "item", $datosArchivo);
            $xmlDepende = simplexml_load_string($datosArchivo);
                        
            for ($i = 0; $i < ($xmlDepende->count ()); $i++) {
                $claveTiene = $xmlDepende->item[$i]->{CLAVE_TIENE_DEPENDE}->__toString();
                $claveNecesita = $xmlDepende->item[$i]->{CLAVE_NECESITA}->__toString();
                
                $claveTieneSinSaltos = preg_replace("/\r|\n/", "", $claveTiene);
                $claveNecesitaSinSaltos = preg_replace("/\r|\n/", "", $claveNecesita);
                $claveTieneSinSaltos = trim($claveTieneSinSaltos);
                $claveNecesitaSinSaltos = trim($claveNecesitaSinSaltos);
                
                $codigosVistaNecesita [$claveTieneSinSaltos] = [$claveNecesitaSinSaltos];
            }
            
            
            
        }
        
        array_push ($keys, CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
        
        array_push ($keys, CLAVE_TITULO_DOC); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_TITULO_DOC."\";"); //y la añadidomos al csv
        
        fwrite ($archivoCSV, "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
        
        //se leen los archivos xml de la vista de los datos y se crea el archivo csv correspondientes a la vista
        for ($i = 1; $i <= $numeroArchivos; $i++) {
            $datosXml2 = file_get_contents (RUTA_XML."vista_".$vista."_$i.xml");
			if (is_string ($datosXml2) ) {
            $xml2 = simplexml_load_string($datosXml2);
            
            for ($z = 0; $z < ($xml2->count()); $z++) {
                foreach ($keys as $key) {
                    $elemento = $xml2->item[$z]->$key;
                    
                    if ($key == CLAVE_NECESITA){ //Si es el elemento del codigo de provincia que no esta en el xml se busca en el array creado antes y se inserta en el documento
                        $idTiene = $xml2->item[$z]->{CLAVE_TIENE}->__toString();
                        $idTiene = preg_replace("/\r|\n/", "", $idTiene);	//Quitamos los saltos de linea porque sino da error
                        $idTiene = mb_strtoupper($idTiene);
                        
                        $idTiene = trim($idTiene);
                        $idNecesita = $codigosVistaNecesita[$idTiene];
                        $elemento = $idNecesita [0]; //OJO, obtenemos el codigo de municipio, porque la linea anterior devuelve un array
                    }
                    
                    if ($key == CLAVE_URL) {
                        $elemento = obtenerUrlVinculacion($xml2, $z, $vista, CLAVE_URI);
                    }
                    
                    if ($key == CLAVE_TITULO_DOC) {
                        $elemento = $xml2->item[$z]->{CLAVE_CONJUNTO}->__toString();
                        
                        if (empty ($elemento)) {
                            $elemento = $xml2->item[$z]->{CLAVE_TITULO}->__toString();
                            
                            if (empty ($elemento)) {
                                $elemento = $xml2->item[$z]->{CLAVE_DESCRIPCION}->__toString();
                            }
                        }
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