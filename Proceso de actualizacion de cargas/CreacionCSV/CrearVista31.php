<?php
    define ("CLAVE_URI", "og_id");
    
    define ("URL_VISTA_10", "../VistasXml/Vista10/Vista_10_1.xml"); //Ruta a la vista 10
    define ("URL_VISTA_11", "../VistasXml/Vista11/Vista_11_1.xml"); //Ruta a la vista 11
    define ("URL_VISTA_12", "../VistasXml/Vista12/Vista_12_1.xml"); //Ruta a la vista 11
    define ("URL_VISTA_13", "../VistasXml/Vista13/Vista_13_1.xml"); //Ruta a la vista 13
    define ("URL_VISTA_16", "../VistasXml/Vista16/Vista_16_1.xml"); //Ruta a la vista 16
    define ("URL_VISTA_19", "../VistasXml/Vista19/Vista_19_1.xml"); //Ruta a la vista 19
    define ("URL_VISTA_20", "../VistasXml/VistasCompletas/Vista20_completa.xml"); //Ruta a la vista 20
    define ("URL_VISTA_22", "../VistasXml/Vista22/Vista_22_1.xml"); //Ruta a la vista 22
    define ("URL_VISTA_24", "../VistasXml/Vista24/Vista_24_1.xml"); //Ruta a la vista 24
    define ("URL_VISTA_26", "../VistasXml/VistasCompletas/Vista26_completa.xml"); //Ruta a la vista 26
    define ("URL_VISTA_27", "../VistasXml/Vista27/Vista_27_1.xml"); //Ruta a la vista 27
    define ("URL_VISTA_34", "../VistasXml/Vista34/Vista_34_1.xml"); //Ruta a la vista 34
    define ("URL_VISTA_35", "../VistasXml/Vista35/Vista_35_1.xml"); //Ruta a la vista 35
    
    $claveComun = "denominacion"; //La clave que tiene en comun todas la vista
    $claveVista12 = "agrupacion_secretarial"; //La clave en la que tiene que buscarcar en la vista 12
    $root = "root"; //El directorio raiz para la consulta xpath
    $item = "item"; //El nombre de cada elemento item del xml.
    
    define ("URI10","http://opendata.aragon.es/def/ei2a#comarca-");  //La uri para hacer referencia a entidades de la vista 10
    define ("URI11","http://opendata.aragon.es/def/ei2a#municipio-"); //La uri para hacer referencia a entidades de la vista 11
    define ("URI12","http://opendata.aragon.es/def/ei2a#agrupacion-secretarial-"); //La uri para hacer referencia a entidades de la vista 11
    define ("URI13","http://opendata.aragon.es/def/ei2a#consorcios-"); //La uri para hacer referencia a entidades de la vista 13
    define ("URI16","http://opendata.aragon.es/def/ei2a#diputacion-"); //La uri para hacer referencia a entidades de la vista 16
    define ("URI19","http://opendata.aragon.es/def/ei2a#entidad-mayor-"); //La uri para hacer referencia a entidades de la vista 19
    define ("URI20","http://opendata.aragon.es/def/ei2a#entidad-singular-"); //La uri para hacer referencia a entidades de la vista 20
    define ("URI22","http://opendata.aragon.es/def/ei2a#datos-fundaciones-"); //La uri para hacer referencia a entidades de la vista 22
    define ("URI24","http://opendata.aragon.es/def/ei2a#macomunidad-"); //La uri para hacer referencia a entidades de la vista 24
    define ("URI26","http://opendata.aragon.es/def/ei2a#nucleo-"); //La uri para hacer referencia a entidades de la vista 36
    define ("URI27","http://opendata.aragon.es/def/ei2a#organismo-autonomo-"); //La uri para hacer referencia a entidades de la vista 27
    define ("URI34","http://opendata.aragon.es/def/ei2a#sociedad-mercantil-"); //La uri para hacer referencia a entidades de la vista 34
    define ("URI35","http://opendata.aragon.es/def/ei2a#villas-y-tierras-"); //La uri para hacer referencia a entidades de la vista 35
    
    $clave10 = "codigo_comarc"; //La clave de la uri de la vista 10
    $clave11 = "codigo_mun"; //La clave de la uri de la vista 11
    $clave12 = "id_agrupacion_secretarial"; //La clave de la uri de la vista 11
    $clave13 = "consorcio_id"; //La clave de la uri de la vista 13
    $clave16 = "diputacion_id"; //La clave de la uri de la vista 16
    $clave19 = "elm_id"; //La clave de la uri de la vista 19
    $clave20 = "codigo"; //La clave de la uri de la vista 20
    $clave22 = "fundacion_id"; //La clave de la uri de la vista 22
    $clave24 = "manco_id"; //La clave de la uri de la vista 24
    $clave26 = "codigo_ine"; //La clave de la uri de la vista 26
    $clave27 = "orguato_id"; //La clave de la uri de la vista 27
    $clave34 = "socmerc_id"; //La clave de la uri de la vista 34
    $clave35 = "cvt_id";   //La clave de la uri de la vista 35
    
    define("CLAVE_NECESITA", "UrlEntidad");  //La clave que pusimos en el mapeo
    define("CLAVE_TIENE", "entidad");  //La calve que tiene por la que tiene que buscar para relacionar con las demas vistas
    
    $vista=31;
    
    $xmls = array(); //Un array con los xml a buscar
    $xmls[0] = simplexml_load_file (URL_VISTA_10);
    $xmls[1] = simplexml_load_file (URL_VISTA_11);
    $xmls[2] = simplexml_load_file (URL_VISTA_13);
    $xmls[3] = simplexml_load_file (URL_VISTA_16);
    $xmls[4] = simplexml_load_file (URL_VISTA_19);
    $xmls[5] = simplexml_load_file (URL_VISTA_20);
    $xmls[6] = simplexml_load_file (URL_VISTA_22);
    $xmls[7] = simplexml_load_file (URL_VISTA_24);
    $xmls[8] = simplexml_load_file (URL_VISTA_26);
    $xmls[9] = simplexml_load_file (URL_VISTA_27);
    $xmls[10] = simplexml_load_file (URL_VISTA_34);
    $xmls[11] = simplexml_load_file (URL_VISTA_35);
    $xmls[12] = simplexml_load_file (URL_VISTA_12);
    
    $encontrado = false;
    
    
    
    include 'comun.php';
    
    //$arrayClaves = obtenerURL (URL_CLAVE_COMAR);
    
    if ($archivoCSV !== false) {
        array_push ($keys, CLAVE_NECESITA);
        fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
        
        fwrite ($archivoCSV, "\n");
        for ($i = 1; $i <= $numeroArchivos; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count()); $x++) {
                    foreach ($keys as $key) {
                        
                        $elemento = $xml->item[$x]->$key->__toString();
                        
                        if ($key == CLAVE_NECESITA) {
                            $encontrado = false;
                            $claveTiene = $xml->item[$x]->{CLAVE_TIENE}->__toString();
                            for ($z = 0; $z < count ($xmls) && !$encontrado; $z++) {
                                $xmlDepende = $xmls[$z];
                                $resultado = $xmlDepende->xpath ("/".$root."/".$item."/".$claveComun." [text()= '".$claveTiene."']");
                                
                                if (count ($resultado) == 0) {
                                    $resultado = $xmlDepende->xpath ("/".$root."/".$item."/".$claveVista12." [text()= '".$claveTiene."']");
                                }
                                
                                if (count ($resultado) > 0) {
                                    $encontrado = true;
                                    switch ($z) {
                                        case 0:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave10);
                                            $elemento = URI10.$codigo[0];
                                            break;
                                        case 1:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave11);
                                            $elemento = URI11.$codigo[0];
                                            break;
                                        case 2:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave13);
                                            $elemento = URI13.$codigo[0];
                                            break;
                                        case 3:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave16);
                                            $elemento = URI16.$codigo[0];
                                            break;
                                        case 4:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave19);
                                            $elemento = URI19.$codigo[0];
                                            break;
                                        case 5:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave20);
                                            $elemento = URI20.$codigo[0];
                                            break;
                                        case 6:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave22);
                                            $elemento = URI22.$codigo[0];
                                            break;
                                        case 7:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave24);
                                            $elemento = URI24.$codigo[0];
                                            break;
                                        case 8:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave26);
                                            $elemento = URI26.$codigo[0];
                                            break;
                                        case 9:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave27);
                                            $elemento = URI27.$codigo[0];
                                            break;
                                        case 10:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave34);
                                            $elemento = URI34.$codigo[0];
                                            break;
                                        case 11:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave35);
                                            $elemento = URI35.$codigo[0];
                                            break;
                                        case 12:
                                            $codigo = $xmlDepende->xpath ("/".$root."/".$item."[".$claveComun."= '".$claveTiene."']/".$clave12);
                                            $elemento = URI12.$codigo[0];
                                            break;
                                    }
                                }
                            }
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

?>