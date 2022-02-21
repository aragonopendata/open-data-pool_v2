<?php
    $vista=135;
    define ("CLAVE_URI", "IDOPERADOR");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>