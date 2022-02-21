<?php
    $vista=58;
    define ("CLAVE_URI", "COD_ENTIDAD");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>