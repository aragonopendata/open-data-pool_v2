<?php
    $vista=24;
    define ("CLAVE_URI", "manco_id");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>