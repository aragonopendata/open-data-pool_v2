<?php
    $vista=32;
    define ("CLAVE_URI", "GENERAL_CLOB_ID");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>