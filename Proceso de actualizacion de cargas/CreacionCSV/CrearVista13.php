<?php
    $vista=13;
    define ("CLAVE_URI", "consorcio_id");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>