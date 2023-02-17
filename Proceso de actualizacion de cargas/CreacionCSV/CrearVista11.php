<?php
    
    define("CLAVE_URL_MUN", "MunicipioAragopediaURI");                    //La clave que esta puesta en el mapeo para poder hacer la relacion con aragopedia
    define("CLAVE_URL_PRO", "ProvinciaAragopediaURI");                    //La clave que esta puesta en el mapeo para poder hacer la relacion con aragopedia
    define("URL_CLAVE_MUN", "../VistasCsv/Relacion/DatosURLMun.csv");     //Ruta al achivo de datos externo para en campo de MunicipioAragopediaURI
    define("URL_CLAVE_PRO", "../VistasCsv/Relacion/DatosURLPro.csv");     //Ruta al achivo de datos externo para en campo de ProvinciaAragopediaURI
    define("URL_CORDENADAS", "../VistasCsv/Relacion/CorneadasMun.csv");   //Ruta al achivo de datos externo para optener las cordenadas
    define("CLAVE_MUN", "mun_id");                                        //La clave que tiene para la relacion del archivo de datos extra para los municipios
    define("CLAVE_PRO", "provincia");                                     //La clave que tiene para la relacion del archivo de datos extra para las provincias
    define("CLAVE_URI", "codigo_mun");
    define("CLAVE_LAT", "lat");                                           //La clave que tiene la latitud de la coordenada
    define("CLAVE_LOG", "log");                                           //La clave que tiene la longitud de la coordenada
    define("CLAVE_NOM_ENLACE", "nom_enlace");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_WIKI", "cod_wiki");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_DBPEDIA", "cod_dbPedia");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_ARAGOPEDIA", "denom_aragopedia");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("URL_CLAVE_WIKI", "../VistasCsv/Relacion/Vista_Aragopedia_Wikipedia_DbPedia_mun.csv");     //Ruta al achivo de datos externo para en campo de MunicipioAragopediaURI
    define("CLAVE_COMUNIDAD", "cod_comunidad");
    define("PRESUPUESTO", "presupuesto");
    
    $vista=11;
    include 'comun.php';   
    
    $arrayClaves = obtenerURL (URL_CLAVE_MUN);
    $arrayProvincias = obtenerURL (URL_CLAVE_PRO); 
    $arrayCordenadas = obtenerCordenadas ();
    $arrayWikiDbpediaAragopedia = obtenerWikiDbPediaAragopedia (URL_CLAVE_WIKI);

    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_URL_MUN);
        fwrite ($archivoCSV, "\"".CLAVE_URL_MUN."\";");
        
        array_push ($keys, CLAVE_URL_PRO);
        fwrite ($archivoCSV, "\"".CLAVE_URL_PRO."\";");
        
        array_push ($keys, CLAVE_LAT);
        fwrite ($archivoCSV, "\"".CLAVE_LAT."\";"); 
        
        array_push ($keys, CLAVE_LOG);
        fwrite ($archivoCSV, "\"".CLAVE_LOG."\";");      
        
        array_push ($keys, CLAVE_NOM_ENLACE);
        fwrite ($archivoCSV, "\"".CLAVE_NOM_ENLACE."\";");      
        
        array_push ($keys, CLAVE_WIKI);
        fwrite ($archivoCSV, "\"".CLAVE_WIKI."\";");      

        array_push ($keys, CLAVE_DBPEDIA);
        fwrite ($archivoCSV, "\"".CLAVE_DBPEDIA."\";");    
        
        array_push ($keys, CLAVE_ARAGOPEDIA);
        fwrite ($archivoCSV, "\"".CLAVE_ARAGOPEDIA."\";");    
        
        array_push ($keys, CLAVE_COMUNIDAD);
        fwrite ($archivoCSV, "\"".CLAVE_COMUNIDAD."\";");    

        array_push ($keys, PRESUPUESTO);
        fwrite ($archivoCSV, "\"".PRESUPUESTO."\";"); 
        
        fwrite ($archivoCSV, "\n");
        
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
            $datosArchivo = str_replace("list-item", "item", $datosArchivo);
			if (is_string ($datosArchivo) ) {
            $xml = simplexml_load_string($datosArchivo);
            
            for ($x = 0; $x < ($xml->count()); $x++) {
                foreach ($keys as $key) {
                    $elemento = $xml->item[$x]->$key;
                    
                    if ($key == CLAVE_URL_MUN) {
                        $id_municipio = $xml->item[$x]->{CLAVE_URI}->__toString();                        
                        $elemento = @$arrayClaves [$id_municipio];
                        
                        /*if(filter_var($elemento, FILTER_VALIDATE_URL) === FALSE)
                        {
                            $nombre = substr ($elemento, strrpos ($elemento, "/", -1) + 1);
                            $nombreUrl = urlencode($nombre);
                            $elemento = str_replace($nombre, $nombreUrl, $elemento);
                        }*/
                    }
                    
                    if ($key == CLAVE_URL_PRO) {
                        $provincia = $xml->item[$x]->{CLAVE_PRO}->__toString();
                        $elemento = $arrayProvincias[$provincia];
                    }
                                        
                    if ($key == CLAVE_URL) {                      
                        $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                    }
                    
                    if ($key == CLAVE_LAT || $key == CLAVE_LOG) {
                        $codMun = $xml->item[$x]->{CLAVE_URI}->__toString();
                        $corde = $arrayCordenadas [$codMun];
                        $elemento = $corde [$key];
                        $elemento = str_replace (",", ".", $elemento);
                    }
                    
                    if ($key == CLAVE_WIKI || $key == CLAVE_DBPEDIA || $key ==  CLAVE_ARAGOPEDIA || $key == CLAVE_COMUNIDAD || $key == PRESUPUESTO) {
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
	
    //Funcion que obtiene las codenadas de un archivo de datos externo
    function obtenerCordenadas() {
        $array = array();
        $archivoClaves = fopen (URL_CORDENADAS, "r");
        fgetcsv($archivoClaves, 0, ";","\"",'"');
        while (($datos = fgetcsv($archivoClaves, 0, ";","\"",'"')) == true)
        {
            $corde = array ();
            
            $corde [CLAVE_LOG] = $datos[1];
            $corde [CLAVE_LAT] = $datos[2];
            
            $array [$datos[0]] = $corde;
            
        }
            
        fclose($archivoClaves);
        return $array;
    }
?>