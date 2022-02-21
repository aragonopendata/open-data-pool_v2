<?php
    $vista = 82;
    define ("CLAVE_URI1", "CANO");
    define ("CLAVE_URI2", "CPRODU");
    include 'comun.php';   
	
	if ($archivoCSV !== false) {
	    crearCsvSinDependencias2(CLAVE_URI1, CLAVE_URI2);
	}	
	
?>