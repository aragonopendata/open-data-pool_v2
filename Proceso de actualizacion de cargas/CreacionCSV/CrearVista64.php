<?php
    define ("CLAVE_URI", "signatura");
    define("CLAVE_NECESITA", "codigo_mun");                         //La clave que necesita para relacionarse con la vista 11   
    define("CLAVE_MUN", "municipio_establecimiento");               //Una de las claves por la cual se compone la calve que necesita
    define("CLAVE_PRO", "provincia_establecimiento");               //La otra de las claves que se compone la calve que necesita
    define ("RUTA_XML_11", "../VistasXml/Vista11/Vista_11_1.xml"); //La ruta donde consultar si se realizo bien la vinculación
    define ("CLAVE_CP", "codigo_postal_establecimiento");          //La clave para el codigo postal en la vista 64
    
      
    $vista=64;
    include 'comun.php';
    
    $claveBuscar = "CP"; //La clave por la cual se comprueba que se ha realizado bien la vinculacion,
                           //Sino es por la calve que se necesita para sacar el codigo de municipio
    $root = "root";
    $item = "item";
   
    
      
    $datos11 = file_get_contents (RUTA_XML_11);
    $datos11 = str_replace("list-item", "item", $datos11);
    $xml11 = simplexml_load_string($datos11);
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
       
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace ("list-item", "item", $datosArchivo);
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
                            
                            $cod_mun = $id_pro.$id_mun;
                            
                            $resultado = $xml11->xpath ("/".$root."/".$item."[".CLAVE_NECESITA."='".$cod_mun."']"."/".$claveBuscar);
                            
                            if (empty ($resultado)) {
                                $cp = $xml->item[$x]->{CLAVE_CP}->__toString();
                                $resultado = $xml11->xpath ("/".$root."/".$item."[$claveBuscar=".$cp."]/".CLAVE_NECESITA);
                                
                                $cod_mun = $resultado [0]; //Resultados es un array 
                            }
                            
                            $elemento = $cod_mun;
                            
                        }                    
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
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