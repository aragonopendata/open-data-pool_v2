<?php
    $vista=57;
    define ("CLAVE_URI", "CODIGO_COMARC");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>