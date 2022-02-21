<?php
    $vista=44;
    define ("CLAVE_URI", "ORDYREG_ID");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>