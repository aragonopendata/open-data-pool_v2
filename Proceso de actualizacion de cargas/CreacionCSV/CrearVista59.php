<?php
    $vista=59;
    define ("CLAVE_URI", "id_fun");
    include 'comun.php';   
    
    if ($archivoCSV !== false) { 
        crearCsvSinDependencias (CLAVE_URI);
    }	
?>