<?php
    $vista=17;
    define ("CLAVE_URI", "direccion_id");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>