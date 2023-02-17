<?php
    $vista=2;
    define ("CLAVE_URI1", "ENTIDAD");
    define ("CLAVE_URI2", "EMBLEMA");
    define ("CLAVE_NECESITA", "CODIGO_MUN"); //la clave que necesita para poder relacionarse con la vista 11
    define ("CLAVE_BUSCAR","DENOMINACION"); //La clave por la cual se necesita buscar en la vista 11 para sacar el codigo de municipio
    define ("RUTA_XML_11", "../VistasXml/Vista11/vista_11_1.xml");  //La ruta al xml 11 para poder obtener el codigo de municipio.
   
    include 'comun.php';
	
    $root = "root"; //Es en elemento raiz, se usa para la consuta xpath
    $item = "item"; //Es cada elemento de la cual se crean las entidades de la vista
    
    $datosXML11 = file_get_contents (RUTA_XML_11);
    $xml11 = simplexml_load_string($datosXML11);
    
    array_push ($keys, CLAVE_NECESITA);
    fwrite ($archivoCSV, "\"".CLAVE_NECESITA."\";");
    
    fwrite ($archivoCSV, "\n");
    for ($i = 1; $i <= $numeroArchivos; $i ++) {
        $datosArchivo = file_get_contents (RUTA_XML."Vista_".$vista."_$i.xml");
		if (is_string ($datosArchivo) ) {
				$xml = simplexml_load_string($datosArchivo); //Creamos un xml con los datos del fichero
				
				for ($x = 0; $x < ($xml->count()); $x++) {
					foreach ($keys as $key) {
						$elemento = $xml->item[$x]->$key;
						if ($key == "ANO") { //Cambiamos los datos para añadir el dia y el mes
							$elemento = $elemento."-01-01";
						}
						
						if ($key == "F_APROBACION" or $key == "F_PUBLICACION")  {
							$elemento = cambiarFecha ($elemento);
						}
						
						if ($key == CLAVE_NECESITA) {
							$entidad = $xml->item[$x]->{CLAVE_URI1}->__toString();
							$codigoMun = $xml11->xpath ("/".$root."/".$item."[".CLAVE_BUSCAR."='".$entidad."']"."/".CLAVE_NECESITA);
							$elemento = $codigoMun [0]; //La cunsulta xpath devuelve un array
						}
						
						if ($key == CLAVE_URL) { //Creamos los datos para hacer poner la url de la fuente
							$valor1 = $xml->item[$x]->{CLAVE_URI1}->__toString();
							$valor2 = $xml->item[$x]->{CLAVE_URI2}->__toString();
							$elemento = obtenerUrlVinculacionVariasClaves($xml, $x, $vista, CLAVE_URI1, CLAVE_URI2);
						}
						
						$elemento = preg_replace("/\r|\n/", "", $elemento); //Quitamos los saltos de linea
						$elemento = str_replace ("\"", "\"\"", $elemento); //cambiamos el caracter " por ""
						if (isInteger($elemento) || trim($elemento) == "" ) {
		                            		fwrite($archivoCSV, $elemento.";");
                		        	} else {
                            				fwrite($archivoCSV, "\"$elemento\";");
						}
					}
					fwrite ($archivoCSV, "\n");
				}
				}
    }
    
    fclose ($archivoCSV);
?>
