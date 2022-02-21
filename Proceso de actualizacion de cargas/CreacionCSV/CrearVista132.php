<?php
    $vista=132;
    define ("CLAVE_URI", "COD_CONCESION");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>