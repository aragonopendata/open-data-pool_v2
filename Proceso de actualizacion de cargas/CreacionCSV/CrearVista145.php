<?php
    $vista=145;
    define ("CLAVE_URI", "iditem");
    define ("CLAVE_NOM_MUN", "nombre_municipio"); //El el campo del archivo de datos donde se gurada el nombre del municipio.
    define ("CLAVE_COD_MUN", "codigo_mun"); //Es el campo del archivo de datos donde se guarda el codigo del municipio.
    define ("CLAVE_ANIO", "anio"); //Es el campo del archivo de datos donde se guarda el codigo del municipio.
    define ("CLAVE_LICENCIA", "licencia"); //Es el campo del archivo de datos donde se guarda la licencia que tiene el documento.
    define ("CLAVE_TIPO", "tipo"); //Es el campo del archivo de datos donde se guarda el tipo que tiene el documento.    
    define ("CLAVE_XML", "marcxml"); //Es el campo que tiene la fecha del documento.
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista11/Vista_11_1.xml");
    define ("CONSULTA_MUNICIPIO1", "record/datafield[@tag='651']/subfield[@code='a']"); //Una de las dos consultas que se necestian para sacar el nombre del municipio.
    define ("CONSULTA_MUNICIPIO2", "record/datafield[@tag='592']/subfield[@code='g']"); //Una de las dos consultas que se necestian para sacar el nombre del municipio.
    define ("CONSULTA_ANIO", "record/datafield[@tag='592']/subfield[@code='f']"); //La consulta para sacar la fecha del documento.
    define ("CONSULTA_LICENCIA1", "record/datafield[@tag='540']/subfield[@code='u']"); //Una de las dos consultas que se necesitan para saber el tipo de la licencia del documento.
    define ("CONSULTA_LICENCIA2", "record/datafield[@tag='506']/subfield[@code='a']"); //Una de las dos consultas que se necesitan para saber el tipo de la licencia del documento.
    define ("CONSULTA_TIPO", "record/datafield[@tag='351']/subfield[@code='c']"); //La consulta que se necesita para sacar el tipo del documento.
    define ("LINK_EI2A", "http://opendata.aragon.es/def/ei2a#");
    define ("CLAVE_FALTA1", "title"); //Clave que no se encuentra en el primer elementon pero tiene que estar mapeada
    define ("CLAVE_FALTA2", "lastmodified"); //Clave que no se encuentra en el primer elementon pero tiene que estar mapeada
    
    include 'comun.php';
    
    $claveId = CLAVE_COD_MUN;
    
    $claveMun = "denominacion"; //Es la clave por la que hay buscar en la vista 11;
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.   
   
    
    $keys = array_diff($keys, array (CLAVE_XML));
    array_push ($keys, CLAVE_FALTA1, CLAVE_FALTA2);
    $archivoCSV = @fopen (RUTA_CSV.ARCHIVO_CSV, "w");
    
    //Se reescriben las claves porque sino, aparece el campo del xml
    foreach ($keys as $key) {            
        fwrite ($archivoCSV, "\"".$key."\";");
    }
    
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NOM_MUN); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_NOM_MUN."\";"); //y la añadidomos al csv
        
        array_push ($keys, CLAVE_COD_MUN); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_COD_MUN."\";"); //y la añadidomos al csv
        
        array_push ($keys, CLAVE_ANIO); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_ANIO."\";"); //y la añadidomos al csv
        
        array_push ($keys, CLAVE_LICENCIA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_LICENCIA."\";"); //y la añadidomos al csv
        
        array_push ($keys, CLAVE_TIPO); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($archivoCSV, "\"".CLAVE_TIPO."\";"); //y la añadidomos al csv
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= 751; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 1; $x < ($xml->count ()); $x++) {
                    $nombreMun = "";
                    foreach ($keys as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml, $x, $vista, CLAVE_URI);
                        }
                        
                        if ($key == CLAVE_NOM_MUN) {
                            $datosXml = $xml->item[$x]->{CLAVE_XML}->__toString();
                            $xmlMarc = simplexml_load_string($datosXml);
                            $municipio = $xmlMarc->xpath(CONSULTA_MUNICIPIO1);
                            
                            if (empty($municipio)){
                                $municipio = $xmlMarc->xpath(CONSULTA_MUNICIPIO2);
                            }
                            
                            if (!empty ($municipio)) {
                                $elemento = $municipio[0]->__toString();
                                $nombreMun = mb_strtoupper($elemento);
                            }                            
                            
                        }
                        
                        if ($key == CLAVE_COD_MUN) {
                            $datosXml11 = simplexml_load_file (RUTA_XML_DEPENDE);                        
                            $codMun = $datosXml11->xpath ("/".$root."/".$item."[".$claveMun."= '".$nombreMun."']/".$claveId);                        
                            if (!empty ($codMun)) {
                                $elemento = $codMun [0]; 
                                $elemento = $elemento->__toString ();
                            }
                        }
                        
                        if ($key == CLAVE_ANIO) {
                            $datosXml = $xml->item[$x]->{CLAVE_XML}->__toString();
                            $xmlMarc = simplexml_load_string($datosXml);
                            $anio = $xmlMarc->xpath(CONSULTA_ANIO);
                            
                            if (!empty ($anio)) {
                                $elemento = $anio[0]->__toString();                            
                            } 
                        }                    
                
                        if ($key == CLAVE_LICENCIA) {
                            $datosXml = $xml->item[$x]->{CLAVE_XML}->__toString();
                            $xmlMarc = simplexml_load_string($datosXml);
                            $licencia = $xmlMarc->xpath(CONSULTA_LICENCIA1);
                            
                            if (empty($licencia)){
                                $licencia = $xmlMarc->xpath(CONSULTA_LICENCIA2);
                            }
                            
                            if (!empty ($licencia)) {
                                $elemento = $licencia[0]->__toString();                           
                            }      
                        }
                        
                        if ($key == CLAVE_TIPO) {
                            $datosXml = $xml->item[$x]->{CLAVE_XML}->__toString();
                            $xmlMarc = simplexml_load_string($datosXml);
                            $tipo = $xmlMarc->xpath(CONSULTA_TIPO);
                            
                            if (!empty ($tipo)) {
                                $elemento = $tipo[0]->__toString();
                                
                                switch ($elemento) {
                                    case "Agrupación de fondos":
                                        $elemento = "GrupoDeFondos";
                                        break;
                                    case "Expediente":
                                        $elemento = "Expediente";
                                        break;
                                    case "Fondo":
                                        $elemento = "GrupoDeFondos";
                                        break;
                                    case "Grupo de fondos":
                                        $elemento = "Fondo";
                                        break;
                                    case "Serie":
                                        $elemento = "Serie";
                                        break;
                                    case "Documento":
                                        $elemento = "Documento";
                                        break;
                                    case "Fracción de serie":
                                        $elemento = "Serie";
                                        break;
                                    case "Sección":
                                        $elemento = "Serie";
                                        break;
                                    case "Unidad documental simple":
                                        $elemento = "DocumentoSimple";
                                        break;
                                    case "Subsección":
                                        $elemento = "Serie";
                                        break;
                                    case "Unidad de instalación":
                                        $elemento = "Serie";
                                        break;
                                    case "Unidad documental compuesta":
                                        $elemento = "Expediente";
                                        break;
                                    case "1 fotografia":
                                        $elemento = "DocumentoSimple";
                                        break;
                                }
                                
                                $elemento = LINK_EI2A.$elemento;
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
?>