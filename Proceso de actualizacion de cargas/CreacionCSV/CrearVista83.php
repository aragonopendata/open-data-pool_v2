<?php 
    $vista = 83;
    define ("CLAVE_URI1", "CANO");   
    define ("CLAVE_URI2", "CPRODU");   
    define ("CLAVE_URI3", "CVARIE");   
    
    include 'comun.php';
    
    if ($archivoCSV !== false) {
        crearCsvSinDependencias3 (CLAVE_URI1, CLAVE_URI2, CLAVE_URI3);
    }
 ?>