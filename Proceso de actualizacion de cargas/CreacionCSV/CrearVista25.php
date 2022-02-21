<?php
    $vista=25;
    define ("CLAVE_URI", "wnov_id");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>