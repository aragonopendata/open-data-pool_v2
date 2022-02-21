<?php
    $vista=142;
    define ("CLAVE_URI", "CLOC");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>