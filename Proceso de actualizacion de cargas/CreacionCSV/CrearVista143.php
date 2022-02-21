<?php
    $vista=143;
    define ("CLAVE_URI", "CCENTRO");
    define ("VISTA_NECESITA", "11");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "LOCALIDADS_CLOC");								//La clve que tiene para poder relacionarse
    define ("CLAVE_TIENE_DEPENDE", "DENOMINACION");                         //La clave que corresponde en el xml que depende
    define ("CLAVE_NECESITA","CODIGO_MUN"); 								//La clave que necesita
    define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
        
        crearCsvUnaDependencia2(CLAVE_URI, $codigosVistaNecesita);
    }
?>