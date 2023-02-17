<?php
    $vista=16;
    define ("CLAVE_URI", "diputacion_id");
    define ("CLAVE_NECESITA", "codigo_mun");
    define ("CLAVE_TIENE", "denominacion");
    define("CLAVE_WIKI", "cod_wiki");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_DBPEDIA", "cod_dbPedia");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("CLAVE_ARAGOPEDIA", "denom_aragopedia");                             //La clave que tiene el nombre para poder hacer referencia a las paginas externas
    define("URL_CLAVE_WIKI", "../VistasCsv/Relacion/Vista_Aragopedia_Wikipedia_DbPedia_dipu.csv");     //Ruta al achivo de datos externo para en campo de MunicipioAragopediaURI
    define("CLAVE_COMUNIDAD", "cod_comunidad");
    define("TIT_COMARCA", "denom_diputacion");
    include 'comun.php';
    
    $codigosMunicipio = array ();
    $codigosMunicipio ["DIPUTACION PROVINCIAL DE ZARAGOZA"] = "50297";
    $codigosMunicipio ["DIPUTACION PROVINCIAL DE HUESCA"] = "22125";
    $codigosMunicipio ["DIPUTACION PROVINCIAL DE TERUEL"] = "44216";
    $arrayWikiDbpediaAragopedia = obtenerWikiDbPediaAragopedia (URL_CLAVE_WIKI);

    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
        array_push ($keys, CLAVE_WIKI);
        fwrite ($archivoCSV, "\"".CLAVE_WIKI."\";");      

        array_push ($keys, CLAVE_DBPEDIA);
        fwrite ($archivoCSV, "\"".CLAVE_DBPEDIA."\";");    
        
        array_push ($keys, CLAVE_ARAGOPEDIA);
        fwrite ($archivoCSV, "\"".CLAVE_ARAGOPEDIA."\";");    
        
        array_push ($keys, CLAVE_COMUNIDAD);
        fwrite ($archivoCSV, "\"".CLAVE_COMUNIDAD."\";");    

        array_push ($keys, TIT_COMARCA);
        fwrite ($archivoCSV, "\"".TIT_COMARCA."\";");    

       
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);    
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        }
                        
                        if ($key == CLAVE_NECESITA) {
                            $nombreMun = $xml->item[$x]->{CLAVE_TIENE}->__toString();
                            $elemento = $codigosMunicipio [$nombreMun];
                        }
                        if ($key == CLAVE_WIKI || $key == CLAVE_DBPEDIA || $key ==  CLAVE_ARAGOPEDIA  || $key ==  TIT_COMARCA) {
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
?>