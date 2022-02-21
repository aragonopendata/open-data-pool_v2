<?php
    $vista=138;
    define ("CLAVE_URI", "COD_RUTA");
    include 'comun.php';
	
    if ($archivoCSV !== false) {
        crearCsvSinDependencias (CLAVE_URI);
    }
?>