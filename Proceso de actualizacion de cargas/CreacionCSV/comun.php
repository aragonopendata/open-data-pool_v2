
<?php 
require_once '../lib/filesystem.php'; // Contiene la configuracion del programa.   
    define ("RUTA_XML", "../VistasXml/Vista".$vista."/");        //La ruta al los xml de la 137
    define ("RUTA_CSV", "../VistasCsv/Vista".$vista."/");        //La ruta dode se guardara el csv
    define ("ARCHIVO_CSV", "Vista_".$vista.".csv");              //El nombre del archivo csv
    define ("CLAVES_XML", "Vista_".$vista."_1.xml");             //El archivo de donde se sacan las claves
    define("CLAVE_URL", "FUENTE");
    
    //ponemos las claves en la cabecera del archivo
    set_error_handler('escribirErroresPHP');
    $keys = array ();    
    
    if (!file_exists (RUTA_CSV)) {
        mkdir(RUTA_CSV);
    }
    $log = fopen ("../Log/log".date("Ymd").".txt", "a+");
    fclose ($log);
    $archivoCSV = @fopen (RUTA_CSV.ARCHIVO_CSV, "w");
	//Lee un archivo xml y tranforma las etiquetas en las claves del csv
    if ((file_exists (RUTA_XML.CLAVES_XML) && ($archivoCSV !== false))) {
        $carpeta = new FilesystemIterator(RUTA_XML, FilesystemIterator::SKIP_DOTS); //Obtiene la carpeta de los xml para saber cunatos tiene
        $numeroArchivos = iterator_count($carpeta);
        //Cargar el XML de los datos
        $datosArchivo = file_get_contents (RUTA_XML.CLAVES_XML);
		if (is_string ($datosArchivo) ) {
            $datosArchivo = str_replace("list-item", "item", $datosArchivo);
            $xml = simplexml_load_string($datosArchivo);
            $elementoItem = $xml->children();
            foreach ($elementoItem->children() as $hijo) {
                $key = $hijo->getName ();
                array_push ($keys, $key);
                fwrite ($archivoCSV, "\"".$key."\";");
            }
            //Añadimos al CSV el campo FUENTE
            array_push ($GLOBALS["keys"], CLAVE_URL);
            fwrite ($GLOBALS["archivoCSV"], "\"".CLAVE_URL."\";");
        }
	}
    else 
    {
        $log = fopen ("../Log/log".date("Ymd").".txt", "a+");
        fwrite ($log, date(DATE_W3C)." Se ha producido un error en la creacion del csv de la vista ". $GLOBALS["vista"]."\r\n");
        echo "Error:".file_exists(RUTA_XML.CLAVES_XML)." comprueba el log ".RUTA_CSV.ARCHIVO_CSV." ".RUTA_XML.CLAVES_XML;
        fclose ($log);
    }
    
    
    
    
    
    //Crea un csv que no necesite dependencias de otro, que no se relacionan con ninguna otra vista.
    function crearCsvSinDependencias($claveURI) {
                
        fwrite ($GLOBALS["archivoCSV"], "\n");
        for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($GLOBALS["keys"] as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {                        
                            $elemento = obtenerUrlVinculacion($xml, $x, $GLOBALS["vista"], $claveURI);
                        }
                        
                        editarElemento($elemento);
                        fwrite ($GLOBALS["archivoCSV"], "\"$elemento\";");
                    }
                    fwrite ($GLOBALS["archivoCSV"], "\n");
                }
            }
        }
        fclose ($GLOBALS["archivoCSV"]);
    }
    
    function crearCsvSinDependencias2($claveURI1, $claveURI2) {
        
        fwrite ($GLOBALS["archivoCSV"], "\n");
        for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
        
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($GLOBALS["keys"] as $key) {
                        $elemento = $xml->item[$x]->$key;
                    
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacionVariasClaves ($xml, $x, $GLOBALS["vista"], $claveURI1, $claveURI2);
                        }
                        
                        editarElemento($elemento);
                        fwrite ($GLOBALS["archivoCSV"], "\"$elemento\";");
                    }
                    fwrite ($GLOBALS["archivoCSV"], "\n");
                }
            }
        }
        fclose ($GLOBALS["archivoCSV"]);
    }
    
    function crearCsvSinDependencias3 ($claveURI1, $claveURI2, $claveURI3) {
        
        fwrite ($GLOBALS["archivoCSV"], "\n");
        for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i ++) {
            $datosArchivo = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
            
			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
                $xml = simplexml_load_string($datosArchivo);
                
                for ($x = 0; $x < ($xml->count ()); $x++) {
                    foreach ($GLOBALS["keys"] as $key) {
                        $elemento = $xml->item[$x]->$key;
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacionTresClaves ($xml, $x, $GLOBALS["vista"], $claveURI1, $claveURI2, $claveURI3);
                        }
                        
                        editarElemento($elemento);
                        fwrite ($GLOBALS["archivoCSV"], "\"$elemento\";");
                    }
                    fwrite ($GLOBALS["archivoCSV"], "\n");
                }
            }
        }
        fclose ($GLOBALS["archivoCSV"]);
    }
    
    
    
    
    
    //Cambia la fecha de DD-MM-AAAA a AAAA-MM-DD
    function cambiarFecha ($fecha) {
        $fechaCorrecta = date("Y-m-d", strtotime($fecha));
        return $fechaCorrecta;
    }
    
    
    
    
    
    //Transforma el archivo csv indicado a un array de clave => valor
    function obtenerURL ($ruta) {
        $array = array();
        $archivoClaves = fopen ($ruta, "r");
        while (($datos = fgetcsv($archivoClaves, ";","\"",'"')) == true)
        {            
            
            $array [$datos[1]] = substr($datos[0], 0, -1); 
            
        }
        fclose($archivoClaves); 
        return $array;
    }
    
    function obtenerWikiDbPediaAragopedia($ruta) {
        $array = array();
        $archivoClaves = fopen ($ruta, "r");
        fgetcsv($archivoClaves, 0, ";","\"",'"');
        while (($datos = fgetcsv($archivoClaves, 0, ";","\"",'"')) == true)
        {
            $corde = array ();
            
            $corde [CLAVE_WIKI] = $datos[2];
            $corde [CLAVE_DBPEDIA] = $datos[3];
            $corde [CLAVE_ARAGOPEDIA] = $datos[1];
            $corde [CLAVE_COMUNIDAD] = $datos[4];
            $corde[TIT_COMARCA] = $datos[5];
            $corde[PRESUPUESTO] = $datos[6];
            $array [$datos[0]] = $corde;
            
        }
            
        fclose($archivoClaves);
        return $array;
    }
    
    function arrayClavesFicheroRelacionar ($codigosVistaNecesita){
        $carpetaRelacionar = new FilesystemIterator(RUTA_XML_DEPENDE, FilesystemIterator::SKIP_DOTS); //Obtiene la carpeta de los xml para saber cunatos tiene
		
        $numeroArchivos = iterator_count($carpetaRelacionar);
		for($x = 1; $x <= $numeroArchivos; $x++){
		    $prueba = RUTA_XML_DEPENDE."Vista_".VISTA_NECESITA."_$x.xml";
			
			$datosArchivo = file_get_contents (RUTA_XML_DEPENDE."Vista_".VISTA_NECESITA."_$x.xml");
            

			if (is_string ($datosArchivo) ) {
                $datosArchivo = str_replace("list-item", "item", $datosArchivo);
				$xmlDepende = simplexml_load_string($datosArchivo);	
				for ($i = 0; $i < ($xmlDepende->count ()); $i++) {
						$claveTiene = $xmlDepende->item[$i]->{CLAVE_TIENE}->__toString();
						$claveNecesita = $xmlDepende->item[$i]->{CLAVE_NECESITA}->__toString();
						$claveTieneSinSaltos = preg_replace("/\r|\n/", "", $claveTiene);
						$claveNecesitaSinSaltos = preg_replace("/\r|\n/", "", $claveNecesita);
						$codigosVistaNecesita [$claveTieneSinSaltos] = [$claveNecesitaSinSaltos]; //Guardamos los minicipios id con sus codigos de provincia
				}
			}
		}
		
		return $codigosVistaNecesita;
		        
	}
    
    
    //Crea un csv que en el campo de la uri solo tiene un valor y se relacciona con otra vista. 
    function crearCSVDependeUnaVista ($claveURI) {
        $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
        
        //Obtenermos los datos del xml que depende, es decir, el xml que tiene los datos para poder relacionarse con la otra vista
        if (file_exists (RUTA_XML_DEPENDE)) {            
            $codigosVistaNecesita = arrayClavesFicheroRelacionar($codigosVistaNecesita);
        }
        
        array_push ($GLOBALS["keys"],CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($GLOBALS["archivoCSV"], "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
        
        fwrite ($GLOBALS["archivoCSV"], "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
        
        //se leen los archivos xml de la vista de los datos y se crea el archivo csv correspondientes a la vista

        for ($i = 1; $i < $GLOBALS["numeroArchivos"]; $i++) {
            $datosXml2 = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
			if (is_string ($datosXml2) ) {
                $datosXml2 = quitarListItem($datosXml2);
                $xml2 = simplexml_load_string($datosXml2);
            for ($z = 0; $z < ($xml2->count ()); $z++) {
                foreach ($GLOBALS["keys"] as $key) {
                    $elemento = $xml2->item[$z]->$key;
                    if ($key == CLAVE_NECESITA){ //Si es el elemento del codigo de provincia que no esta en el xml se busca en el array creado antes y se inserta en el documento
                        $idTiene = $xml2->item[$z]->{CLAVE_TIENE}->__toString();
                        $idTiene = preg_replace("/\r|\n/", "", $idTiene);	//Quitamos los saltos de linea porque sino da error
                        $idNecesita = $codigosVistaNecesita[$idTiene];
                        $elemento = $idNecesita [0]; //OJO, obtenemos el codigo de municipio, porque la linea anterior devuelve un array
                    }
                    
                    if ($key == CLAVE_URL) {                       
                        $elemento = obtenerUrlVinculacion($xml2, $z, $GLOBALS["vista"], $claveURI);
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
    
    
    
    //Crea un csv que necesita buscar datos de otra vista pero no tienen una clave comun entre las 2
    function crearCsvUnaDependencia2($claveURI, &$codigosVistaNecesita) {
        //Obtenermos los datos del xml que depende, del cual depende para poder realizar el csv
        if (file_exists (RUTA_XML_DEPENDE)) {
            
            $datosArchivo = file_get_contents (RUTA_XML_DEPENDE.XML_DEPENDE);
           

			if (is_string ($datosArchivo) ) {
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
        }
        array_push ($GLOBALS["keys"],CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($GLOBALS["archivoCSV"], "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
        
        fwrite ($GLOBALS["archivoCSV"], "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
        
        //se leen los archivos xml de la vista de los datos y se crea el archivo csv correspondientes a la vista
        for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i++) {
            $datosXml2 = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
            
			if (is_string ($datosXml2) ) {
                $datosXml2 = str_replace("list-item", "item", $datosXml2);
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
                        
                        if ($key == CLAVE_URL) {
                            $elemento = obtenerUrlVinculacion($xml2, $z, $GLOBALS["vista"], CLAVE_URI);
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
    
    //Crea un csv que en el campo de la uri tiene tres valores y se relacciona con otra vista. 
    function crearVistaUnaDependencia3 ($claveURI1, $claveURI2, $claveURI3, &$codigosVistaNecesita) {
        //Obtenermos los datos del xml que depende, del cual depende para poder realizar el csv
        if (file_exists (RUTA_XML_DEPENDE)) {
            
            $datosArchivo = file_get_contents (RUTA_XML_DEPENDE.XML_DEPENDE);
            $datosArchivo = str_replace("list-item", "item", $datosArchivo);

			if (is_string ($datosArchivo) ) {
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
            
            
        }
        
        array_push ($GLOBALS["keys"],CLAVE_NECESITA); //Le añadimos la clave que necesita y no la tiene el xml
        fwrite ($GLOBALS["archivoCSV"], "\"".CLAVE_NECESITA."\";"); //y la añadidomos al csv
        
        fwrite ($GLOBALS["archivoCSV"], "\n"); //introducimos un salto de linea para separar las keys del resto de los elemntos
        
        //se leen los archivos xml de la vista de los datos y se crea el archivo csv correspondientes a la vista
        for ($i = 1; $i <= $GLOBALS["numeroArchivos"]; $i++) {
            $datosXml2 = file_get_contents (RUTA_XML."Vista_".$GLOBALS["vista"]."_$i.xml");
            $datosXml2 = quitarListItem($datosXml2);
			if (is_string ($datosXml2) ) {
            $xml2 = simplexml_load_string($datosXml2);
            
            for ($z = 0; $z < ($xml2->count ()); $z++) {
				
                foreach ($GLOBALS["keys"] as $key) {
                    $elemento = $xml2->item[$z]->$key;
                    
                    if ($key == CLAVE_NECESITA){ //Si es el elemento del codigo de provincia que no esta en el xml se busca en el array creado antes y se inserta en el documento
                        $idTiene = $xml2->item[$z]->{CLAVE_TIENE}->__toString();
                        $idTiene = preg_replace("/\r|\n/", "", $idTiene);	//Quitamos los saltos de linea porque sino da error
                        $idTiene = mb_strtoupper($idTiene);
                        $idNecesita = $codigosVistaNecesita[$idTiene];
                        $elemento = $idNecesita [0]; //OJO, obtenemos el codigo de municipio, porque la linea anterior devuelve un array
                    }
                    
                    if ($key == CLAVE_URL) {
                        $elemento = obtenerUrlVinculacionTresClaves($xml2, $z, $GLOBALS["vista"], $claveURI1, $claveURI2, $claveURI3);
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
    
    
    //Devuelve la url de donde se extraen los datos de cada fila del csv
    function obtenerUrlVinculacion ($xml, $posicion, $vista, $claveURI) {
        
        $valor = $xml->item[$posicion]->$claveURI->__toString();        
        $filtro = "$claveURI='$valor'";
        $filtro = urlencode ($filtro);
        
        return "https://opendata.aragon.es/GA_OD_Core/preview?view_id=$vista&filter_sql=$filtro&_pageSize=1&_page=1";
    }
    
    //Igual que la anterior pero es para vistas que la URI tiene dos campos
    function obtenerUrlVinculacionVariasClaves ($xml, $posicion, $vista, $claveURI1, $claveURI2) {
        $valor1 = $xml->item[$posicion]->{CLAVE_URI1}->__toString();
        $valor2 = $xml->item[$posicion]->{CLAVE_URI2}->__toString();
        
        $filtro = "$claveURI1='$valor1' and $claveURI2='$valor2'";
        
        $filtro = urlencode ($filtro);
        
        
        return "https://opendata.aragon.es/GA_OD_Core/preview?view_id=$vista&filter_sql=$filtro&_pageSize=1&_page=1";
    }
    
    function obtenerUrlVinculacionTresClaves ($xml, $posicion, $vista, $claveURI1, $claveURI2, $claveURI3) {
        $valor1 = $xml->item[$posicion]->{CLAVE_URI1}->__toString();
        $valor2 = $xml->item[$posicion]->{CLAVE_URI2}->__toString();
        $valor3 = $xml->item[$posicion]->{CLAVE_URI3}->__toString();
        
        $filtro = "$claveURI1='$valor1' and $claveURI2='$valor2' and $claveURI3='$valor3'";
        
        $filtro = urlencode ($filtro);
       
        
        return "https://opendata.aragon.es/GA_OD_Core/preview?view_id=$vista&filter_sql=$filtro&_pageSize=1&_page=1";
    }
    
    function escribirError ($vista, $mensaje) {
        $log = fopen ("../Log/log".date("Ymd").".txt", "a+");
        fwrite ($log, date(DATE_W3C)." $mensaje ". $vista."\r\n");
        echo $mensaje;
        fclose ($log);
    }
    
    function editarElemento(&$elemento) {
        $elemento = preg_replace("/\r|\n/", "", $elemento); //quitamos los saltos de linea
        $elemento = str_replace ("\"", "\"\"", $elemento); //cambiamos el caracter " por ""
        $elemento = trim ($elemento);
    }

    function isInteger($input){
        return(ctype_digit(strval($input)));
    }

    function escribirErroresPHP($errno, $errstr, $errfile, $errline) {
        $log = fopen ("../Log/log".date("Ymd").".txt", "a+");
        $error = "";
        
        switch ($errno) {
            case E_ERROR:
                $error = "Error: [$errno] $errstr  en la línea $errline en el archivo $errfile \r\n";               
                exit(1);
                break;
            case E_WARNING:
                $error = "WARNING: [$errno] $errstr  en la línea $errline en el archivo $errfile \r\n";
                break;
                
            case E_NOTICE:
                $error = "NOTICE: [$errno] $errstr en la línea $errline en el archivo $errfile \r\n";
                break;
            default:
                $error =  "Error descononocido: [$errno] $errstr \r\n";
                break;
        }
        
        fwrite ($log, date(DATE_W3C)." $error");
        fclose ($log);
    }
    
    function quitarDecimales ($numero) {
        $posPunto = strpos ($numero, ".");
        $numero = substr ($numero, 0, $posPunto);
        return $numero;
    }
    
    function quitarListItem($datos){
        return str_replace("list-item", "item", $datos);
    }
    
?>
