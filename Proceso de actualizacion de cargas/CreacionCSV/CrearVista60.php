<?php
    $vista=60;
    define ("CLAVE_URI", "id_man");
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>