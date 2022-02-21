<?php
    $vista=147;
    define ("CLAVE_URI", "COD_CONCE");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>