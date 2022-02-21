<?php
    $vista=19;
    define ("CLAVE_URI", "elm_id");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>