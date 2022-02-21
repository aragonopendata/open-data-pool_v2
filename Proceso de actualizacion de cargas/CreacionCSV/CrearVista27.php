<?php
    $vista=27;
    define ("CLAVE_URI", "orgauto_id");
    define ("VISTA_NECESITA", "11");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "entidad_local");								//La clve que tiene para poder relacionarse
    define ("CLAVE_TIENE_DEPENDE", "denominacion");                         //La clave que corresponde en el xml que depende
    define ("CLAVE_NECESITA","codigo_mun"); 								//La clave que necesita
    define ("XML_DEPENDE", "Vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
        crearCsvUnaDependencia2(CLAVE_URI, $codigosVistaNecesita);
    }
    
?>