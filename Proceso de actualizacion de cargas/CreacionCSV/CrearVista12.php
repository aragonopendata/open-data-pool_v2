<?php
    $vista=12;
    define ("CLAVE_URI", "id_agrupacion_secretarial");
    include 'comun.php';

    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>