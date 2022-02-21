<?php
    $vista=102;
    define ("CLAVE_URI", "name"); //Es la clave que identifica la entidad del sendero
    //Constantes para las coordenadas de los senderos
    define ("CLAVE_ID", "id"); //Es la clave que se una como el identificador de cada coordenada
    define ("CLAVE_GEOM", "geom"); //Es la calve de la cual sacamos los datos de la latitud, longitud y altitud
    define ("CLAVE_LAT", "lat"); //Es el campo del archivo de datos de las corrdenas para representar la latitud.
    define ("CLAVE_LONG", "long"); //Es el campo del archivo de datos de las corrdenas para representar la logitud.
    define ("CLAVE_ALT", "alt"); //Es el campo del archivo de datos de las corrdenas para representar la altitud.
    define ("RUTA_COORDE", "../VistasCsv/Vista".$vista."/coordenadas.csv"); //La ruta de donde se guardara el archivo de datos de las coordenadas
    include 'comun.php';
    
    $archivoCoorde = fopen (RUTA_COORDE, "w");
    @fwrite ($archivoCoorde, "\"".CLAVE_ID."\";\"".CLAVE_URI."\";\"".CLAVE_LAT."\";\"".CLAVE_LONG."\";\"".CLAVE_ALT."\";\"".CLAVE_URL."\";");
    @fwrite ($archivoCoorde, "\n");
    $idCoorde = 1; //El identificador de cada linea del archivo de coordenadas, es autoincremental.
    
    if ($archivoCSV !== false && $archivoCoorde !== false) {
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo =  str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        }
                        
                        if (CLAVE_GEOM == $key) {
                            $listaCoorde = substr($elemento->__toString (), 14, -1);
                            $geom = explode (",", $listaCoorde);
                            
                            for ($i = 0; $i < count ($geom); $i ++) {                            
                                fwrite ($archivoCoorde, "\"$idCoorde\";");
                                
                                $idSendero = $xml->item[$x]->{CLAVE_URI}->__toString ();
                                $fuente = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                                
                                fwrite ($archivoCoorde, "\"$idSendero\";");
                                
                                $coorde = explode (" ", $geom[$i]);
                                
                                $lat = $coorde [1];
                                $long = $coorde [0];
                                $alt = $coorde [2];
                                
                                fwrite ($archivoCoorde, "\"$lat\";");
                                fwrite ($archivoCoorde, "\"$long\";");                            
                                fwrite ($archivoCoorde, "\"$alt\";");                            
                                fwrite ($archivoCoorde, "\"$fuente\";");                            
                                fwrite ($archivoCoorde, "\n");
                                $idCoorde += 1;
                            }
                            
                        }
                        
                        editarElemento($elemento);
                        fwrite ($archivoCSV, "\"$elemento\";");
                    }
                    fwrite ($archivoCSV, "\n");
                }
            }
        }
        fclose ($archivoCSV);
        fclose ($archivoCoorde);
    }
?>