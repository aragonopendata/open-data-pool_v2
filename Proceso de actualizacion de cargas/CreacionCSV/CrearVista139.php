<?php
    $vista=139;
    define ("CLAVE_URI", "EMPRESA");
    define ("VISTA_NECESITA", "11");										//El numero de la vista que necesita para completar sus datos
    define ("CLAVE_TIENE", "LOCALIDAD");								    //La clave que tiene el xml de la vista final
    define ("CLAVE_TIENE_DEPENDE", "DENOMINACION");                         //La clave que corresponde con el xml que se relaciona el de nuestra vista. En este caso vista 11 = denominacion y vista 139 = Localidad
    define ("CLAVE_NECESITA","CODIGO_MUN"); 								//La clave que necesita del xml que va a relacionar.
    define ("XML_DEPENDE", "vista_".VISTA_NECESITA."_1.xml"); 				//El xml que depende para sacar todos sus datos
    define ("RUTA_XML_DEPENDE", "../VistasXml/Vista".VISTA_NECESITA."/"); 	//La ruta del xml que necesita para completar datos
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        $codigosVistaNecesita = array (); //Codiogos de municipios de la vista que necesita
        
        crearCsvUnaDependencia2(CLAVE_URI, $codigosVistaNecesita);
    }
?>