<?php 
    $vista=6;
    
    define ("CLAVE_URI", "ORDYREG_ID");
    define ("VISTA_NECESITA", "10");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "COMAR_ID");										//La clve que tiene para poder relacionarse
    define ("CLAVE_NECESITA","CODIGO_COMARC"); 								//La clave que necesita
    define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCSVDependeUnaVista (CLAVE_URI);
    }	
    
    
?>