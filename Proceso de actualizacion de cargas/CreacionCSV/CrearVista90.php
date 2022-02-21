<?php
    $vista=90;
    define ("CLAVE_URI", "COD_ELEC");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>