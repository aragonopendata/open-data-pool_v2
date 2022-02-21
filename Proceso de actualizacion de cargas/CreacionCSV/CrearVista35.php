<?php
    $vista=35;
    define ("CLAVE_URI", "cvt_id");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>