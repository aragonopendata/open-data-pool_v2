<?php
    define ("CLAVE_URI", "codigo_comarc");
    define("CLAVE_URL_COMAR", "ComarcaAragopediaURI");                      //La clave que esta puesta en el mapeo para poder hacer la relacion con aragopedia
    define("URL_CLAVE_COMAR", "../VistasCsv/Relacion/DatosURLComar.csv");   //La ruta al archvio de datos donde se encuentra el enelace para cada comarca
    define("CLAVE_COMAR", "denominacion");                                  //La clave que se usa para poder relacionar la vista con el archivo de datos adicionales
    define("CLAVE_NECESITA", "codigo_mun");                                 //La calve del id de la vista 11 Datos Municipio
    define ("RUTA_XML11", "../VistasXml/Vista11/Vista_11_1.xml");           //La ruta del xml para poder optener el codigo del municipio que corresponde
    define("CLAVE_WIKI", "cod_wiki");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_DBPEDIA", "cod_dbPedia");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_ARAGOPEDIA", "denom_aragopedia");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("URL_CLAVE_WIKI", "../VistasCsv/Relacion/Vista_Aragopedia_Wikipedia_DbPedia_comarca.csv");     //Ruta al achivo de datos externo para en campo de MunicipioAragopediaURI
    define("TIT_COMARCA", "tit_comarca");
    define("PRESUPUESTO", "presupuesto");
    $vista=10;
    include 'comun.php';   
    
    
    $xml11 = simplexml_load_file (RUTA_XML11);
    $arrayClaves = obtenerURL (URL_CLAVE_COMAR);
    $arrayWikiDbpediaAragopedia = obtenerWikiDbPediaAragopedia (URL_CLAVE_WIKI);

    $num=0;
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_URL_COMAR);
        fwrite ($archivoCSV, "\"".CLAVE_URL_COMAR."\";");      
        
        array_push ($keys, CLAVE_WIKI);
        fwrite ($archivoCSV, "\"".CLAVE_WIKI."\";");      

        array_push ($keys, CLAVE_DBPEDIA);
        fwrite ($archivoCSV, "\"".CLAVE_DBPEDIA."\";");    
        
        array_push ($keys, CLAVE_ARAGOPEDIA);
        fwrite ($archivoCSV, "\"".CLAVE_ARAGOPEDIA."\";");    

        array_push ($keys, TIT_COMARCA);
        fwrite ($archivoCSV, "\"".TIT_COMARCA."\";"); 

        array_push ($keys, PRESUPUESTO);
        fwrite ($archivoCSV, "\"".PRESUPUESTO."\";"); 

        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
            

			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                $num = $xml->count();
                for ($x = 0; $x < $num; $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL_COMAR) { //Insertamos en enlace con aragopedia
                            $id_comar = $xml->item[$x]->{CLAVE_COMAR}->__toString();
                            $elemento = $arrayClaves [$id_comar];
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            $mun = $xml->item[$x]->{'municipio_capital'};
                            $id_mun = $xml11->xpath("/root/item[denominacion='$mun']/codigo_mun");
                            $elemento = $id_mun[0];
                        }
                        
                        if ($key == CLAVE_URL) {                        
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        }
                        
                        if ($key == CLAVE_WIKI || $key == CLAVE_DBPEDIA || $key ==  CLAVE_ARAGOPEDIA || $key == TIT_COMARCA || $key == PRESUPUESTO) {
                                $codMun = $xml->item[$x]->{CLAVE_URI}->__toString();
                                $wiki = $arrayWikiDbpediaAragopedia [$codMun];
                                $elemento = $wiki [$key];
                        
                      }
                        editarElemento($elemento);
                        
                        fwrite ($archivoCSV, "\"$elemento\";");
                    }
                    fwrite ($archivoCSV, "\n");
                }
            }
        }
        fclose ($archivoCSV);
    }
    else {
        escribirError ($vista, "Se ha producido un error en la vista:");
    }
	
?>