<?php

// Funcion para abrir una conexion persistente a postgreSQL
function conectarPosgreSQL($host, $puerto, $bbdd, $usuario, $clave)
{
    $stringConexion = 'host=' . $host . ' port=' . $puerto . ' dbname=' . $bbdd . ' user=' . $usuario . ' password=' . $clave;
    $conexionPGSQL  = pg_connect($stringConexion);
    if (!$conexionPGSQL) {
        logErrores('Error: de conexion con PostgreSQL: ' . pg_last_error());
        die('TPAOD Error: de conexion con PostgreSQL: ' . pg_last_error());
    }
    //logErrores('Conectado a PostgreSQL con éxito');
    return $conexionPGSQL;
}


// Funcion para cerrar la conexion de postgreSQL
function DesconectarPostgreSQL($conexionPGSQL)
{
    pg_close($conexionPGSQL);
    logErrores('Desconectado de PostgreSQL ');
}

// Funcion para consultar a postgreSQL
function ConsultarPostgreSQL($conexionSQL, $consultaSQL)
{
    //logErrores("Consulto a PostgreSQL: " . $consultaSQL);
    $resultadoConsulta = pg_query($conexionSQL, $consultaSQL);
    //logErrores("Resultado de consulta a PostgreSQL " . pg_last_error($conexionSQL));
    return $resultadoConsulta;
}

function ObtenerArrayConsultaPostgreSQL($conexionSQL, $consultaSQL)
{
	$resultadoConsultaSQL=ConsultarPostgreSQL($conexionSQL, $consultaSQL);
    return pg_fetch_all($resultadoConsultaSQL);
}




  
?>