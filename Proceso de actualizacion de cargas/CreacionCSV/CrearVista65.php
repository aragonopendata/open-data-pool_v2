<?php
    define ("CLAVE_URI", "signatura");
    define("CLAVE_NECESITA", "codigo_mun");                         //La clave que necesita para relacionarse con la vista 11   
    define("CLAVE_MUN", "municipio_establecimiento");               //Una de las claves por la cual se compone la calve que necesita
    define("CLAVE_PRO", "provincia_establecimiento");               //La otra de las claves que se compone la calve que necesita
    define ("CLAVE_CATEGORIA", "categoria_alojamiento");            //El campo de la categoria del estableciomento       
    define ("CLAVE_NOMBRE", "nombre_empresa");                      //Es el campo principal del nombre del establecimiento
    define ("CLAVE_NOMBRE2", "nombre_alojamiento");                 //Es el campo secundario del nombre del establecimiento, este campo se usa cuando el campo principal no tiene datos
    define ("CLAVE_NOMBRE_CADENA", "nombre_cadena_hotelera");       //Es la clave que tiene el dato del nombre de la cadena hotelera
    define ("CLAVE_NOMBRE_MAPEO", "nombre_completo");               //Es el campo completo de la entidad, es decir, es el nombre la emprea y si tiene el nombre de la cadena hotelera, si este aparece se separa por un "-"
    
      
    $vista=65;
    include 'comun.php';
    
    $relacion = array (); //El array que se usa para realizar el cambio en el campo CATEGORIA
    
    $relacion ["1estrella"] = "1";  
    $relacion ["2estrellas"] = "2";   
    $relacion ["3estrellas"] = "3";    
    $relacion ["4estrellas"] = "4";    
    $relacion ["5estrellas"] = "5";
    
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
        
        array_push ($keys, CLAVE_NOMBRE_MAPEO);
        fwrite ($archivoCSV, "\"".CLAVE_NOMBRE_MAPEO."\";");
       
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count()); $x++) {
                    foreach ($keys as $key) {                    
                            $elemento = $xml->item[$x]->$key;
                            
                            if ($key == CLAVE_NECESITA) {
                                $id_mun = $xml->item[$x]->{CLAVE_MUN}->__toString();
                                $id_pro = $xml->item[$x]->{CLAVE_PRO}->__toString();
                                
                                while (strlen($id_mun) < 3) {
                                    $id_mun = "0".$id_mun;
                                }
                                
                                $elemento = $id_pro.$id_mun;
                            }
                            
                            if ($key == CLAVE_URL) {
                                $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                            }
                            
                            if ($key == CLAVE_CATEGORIA) {                           
                                $elemento = $relacion [$elemento->__toString ()];
                            }
                            
                            if ($key == CLAVE_NOMBRE_MAPEO) {
                                
                                if (empty ($elemento->__toString())) {
                                    $elemento = $xml->item[$x]->{CLAVE_NOMBRE2};
                                }
                                
                                $nombreCadena = $xml->item[$x]->{CLAVE_NOMBRE_CADENA}->__toString();
                                
                                if (!empty ($nombreCadena)) {
                                    $elemento .= "-$nombreCadena";
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
    }
    else {
        escribirError ($vista, "Se ha producido un error en la vista:");
    }

?>