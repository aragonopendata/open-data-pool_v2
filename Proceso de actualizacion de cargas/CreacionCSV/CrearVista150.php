<?php
    $vista = 150;
    define ("CLAVE_URI", "COD_PARADA");
    define ("VISTA_NECESITA", "11");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "NUCLEO");								//La clve que tiene para poder relacionarse
    define ("CLAVE_TIENE_DEPENDE", "DENOMINACION");                         //La clave que corresponde en el xml que depende
    define ("CLAVE_NECESITA","CODIGO_MUN"); 								//La clave que necesita
    define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    define ("COOR_X", "X");
    define ("COOR_Y", "Y");
    include 'comun.php';
    include 'convertir.php'; //Clase para convertir coordenadas
    
    if ($archivoCSV !== false) {
        $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita

        //Obtenermos los datos del xml que depende, del cual depende para poder realizar el csv
        if (file_exists (RUTA_XML_DEPENDE)) {
            
            $datosArchivo = file_get_contents (RUTA_XML_DEPENDE.XML_DEPENDE);
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
                        $idTiene = mb_strtoupper($idTiene);
                        
                        $idTiene = trim($idTiene);
                        $idNecesita = $codigosVistaNecesita[$idTiene];
                        $elemento = $idNecesita [0]; //OJO, obtenemos el codigo de municipio, porque la linea anterior devuelve un array
                    }
                    
                    if ($key == CLAVE_URI) {
                        $elemento = quitarDecimales ($elemento);
                    }
                    
                    if ($key == COOR_Y) {
                        $x = $xml2->item[$z]->{'X'}->__toString ();
                        $y = $xml2->item[$z]->{'Y'}->__toString ();
                        
                        $array = utm2ll ($x,$y,30,true);
                        $elemento = radian2degree($array[0]);
                        
                        
                    }
                    
                    if ( $key == COOR_X) {
                        $x = $xml2->item[$z]->{'X'}->__toString ();
                        $y = $xml2->item[$z]->{'Y'}->__toString ();
                        
                        $array = utm2ll ($x,$y,30,true);
                        
                        $elemento = radian2degree($array[1]);
                        
                    }
                    
                    if ($key == CLAVE_URL) { //Se tiene que quitar el float
                        $valor = $xml->item[$z]->{CLAVE_URI}->__toString();
                        
                        $valor = quitarDecimales ($valor);
                        
                        $filtro = CLAVE_URI."='$valor'";
                        
                        $filtro = urlencode ($filtro);
                        
                        
                        $elemento = "https://opendata.aragon.es/GA_OD_Core/preview?view_id=$vista&filter_sql=$filtro&_pageSize=1&_page=1";
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