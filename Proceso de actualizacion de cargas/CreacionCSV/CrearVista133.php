<?php

    $vista=133;
    
    
    define ("VISTA_NECESITA", "137");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "IDRUTA");										//La clve que tiene para poder relacionarse
    define ("CLAVE_NECESITA","COD_RUTA"); 								//La clave que necesita
    define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xm
    define ("CLAVE_URI", "COD_EXP");
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCSVDependeUnaVista (CLAVE_URI);
    }	
    
    
?>