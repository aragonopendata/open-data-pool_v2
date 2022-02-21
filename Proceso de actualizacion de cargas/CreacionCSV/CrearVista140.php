<?php
    $vista=140;
    define ("CLAVE_URI1", "GRT_ID_RUTA");
    define ("CLAVE_URI2", "GRT_ID_ITINERARIO");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias2 (CLAVE_URI1, CLAVE_URI2);
    }    
?>