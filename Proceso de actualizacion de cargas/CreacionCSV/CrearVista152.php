<?php
    $vista = 152;
    define ("CLAVE_URI", "contrato");
    define ("VISTA_NECESITA", "11");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "municipio");								//La clve que tiene para poder relacionarse
    define ("CLAVE_TIENE_DEPENDE", "denominacion");                         //La clave que corresponde en el xml que depende
    define ("CLAVE_NECESITA","codigo_mun"); 								//La clave que necesita
    define ("XML_DEPENDE", "Vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
        $codgiosVistaNecesita ["BINÉFAR"] = [22061];
        $codgiosVistaNecesita [iconv("UTF-8", "Windows-1252", "MONTALBÁN")] = [44155];
        $codgiosVistaNecesita [iconv("UTF-8", "Windows-1252", "MONZÓN")] = [22158];
        $codgiosVistaNecesita [iconv("UTF-8", "Windows-1252", "SARRIÓN")] = [44210]; 
        $codgiosVistaNecesita [iconv("UTF-8", "Windows-1252", "ALAGÓN")] = [50008]; 
        $codgiosVistaNecesita [iconv("UTF-8", "Windows-1252", "SABIÑÁNIGO")] = [22199]; 
        
        crearCsvUnaDependencia2(CLAVE_URI, $codigosVistaNecesita);
    }

?>