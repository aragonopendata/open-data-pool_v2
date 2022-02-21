<?php
    $vista=15;
    define ("CLAVE_URI", "CURSO_ID");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>