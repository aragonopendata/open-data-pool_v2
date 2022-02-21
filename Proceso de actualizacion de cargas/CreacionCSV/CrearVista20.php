<?php
    $vista=20;
    define ("CLAVE_URI", "codigo");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>