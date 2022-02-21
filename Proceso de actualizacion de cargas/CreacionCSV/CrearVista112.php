<?php
    $vista=112;
    define ("CLAVE_URI", "id_edar");
    include 'comun.php';
	
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>