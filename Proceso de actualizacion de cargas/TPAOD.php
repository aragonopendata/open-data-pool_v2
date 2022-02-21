<?php
require_once './config/configuracion.php'; // Contiene la configuracion del programa.
require_once './config/dcTypes.php'; // Incluimos los dcTypes que tiene cada vista.
require_once './config/dcTypesBOA.php'; // Incluimos los dcTypes que tiene cada vista.
require_once './lib/pgsql.php'; // Contiene las funciones relacionadas con PostgreSQL
require_once './lib/filesystem.php'; // Contiene las funciones relacionadas con el manejo de archivos.
require_once './lib/aod.php'; // Contiene funciones relacionadas con el Api de Aragon.
require_once('./lib/sparqllib.php'); // Contiene las funciones para acceder al EndPoint de virtuoso.
require_once('./lib/PHPMailer/src/PHPMailer.php'); // Contiene las funciones y metodos para enviar correos.
require_once('./lib/PHPMailer/src/Exception.php'); // Contiene el control de excepciones para enviar correos.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

iniciarEstructuraDirectorios();

if (empty($VistasActualizar)) {
    $ConsultaSQLVistasActualizar = "SELECT distinct code,nombre,criterio,periodicidad,fecha,hora,estado,logs,archivos,active,created FROM public.cargavistas where periodicidad in ('semanal','diaria','mensual','anual') and active is true";
} else {
    //Generamos el string para usar la funcion IN de SQL Server.
    $ArrayVistasActualizar  = explode(',', $VistasActualizar); // Convertimos los parametros a Array para poder tratar los datos.
    // Inicializamos variables vacias para prevenir errores de codigo.
    $ListaVistasActualizar  = '';
    $ParametrosCondicionSQL = '';
	
    // Recorremos el array y generamos un string para anadir a la funcion IN de SQL.
    foreach ($ArrayVistasActualizar as $numArray => $ListaVistasActualizar) {
        // Si la vista no es del BOA: nos aseguramos que el valor es numerico para evitar SQLi y preparamos la condicion.
		$tieneNumero = filter_var($ListaVistasActualizar, FILTER_SANITIZE_NUMBER_INT) != "";
        if ($ListaVistasActualizar != 'boa_eli' and $ListaVistasActualizar != 'boa_eli_correcciones' and $ListaVistasActualizar != 'boa_eli_ordenes' and $ListaVistasActualizar != 'boa_eli_ordenes_correcciones' and !$tieneNumero) {
            $ParametrosCondicionSQL = $ParametrosCondicionSQL . 'nombre like \'' . filter_var($ListaVistasActualizar, FILTER_SANITIZE_NUMBER_INT) . ' %\' or ';
        } else {
            $ParametrosCondicionSQL = $ParametrosCondicionSQL . 'nombre like \'' . $ListaVistasActualizar . '%\' or ';
        }
    }
    $ParametrosCondicionSQL      = substr_replace($ParametrosCondicionSQL, "", -3); // Eliminamos la ultima coma del string generado.
    $ConsultaSQLVistasActualizar = "SELECT distinct code,nombre,criterio,periodicidad,fecha,hora,estado,logs,archivos,active,created FROM public.cargavistas where " . $ParametrosCondicionSQL;
    
}


$conexionSQL      = conectarPosgreSQL($host, $puerto, $bbdd, $usuario, $clave);
$VistasActualizar = ConsultarPostgreSQL($conexionSQL, $ConsultaSQLVistasActualizar);

$Numfila = 0;
$ArrayVistasExcluir  = explode(',', $VistasExcluir); // Convertimos los parametros a Array para poder tratar los datos.
//Necesitamos poner limite al while 
while ($fila = pg_fetch_array($VistasActualizar)) {    
    // Indica la fila que luego hay que actualizar en Base de datos.
    $IDFilaEnBBDD              = $fila[0];
    $nombreVista               = $fila[1];
    $periocidad                = $fila[3];
    $ultimaActualizacion       = $fila[4];
    $horaUltimaActualizacion   = $fila[5];

    $FechaultimaActualizacion  = new DateTime($ultimaActualizacion); // convertimos el valor de BBDD a un objeto DateTime .
    $FechaFullUltimaActualizacion = new DateTime($ultimaActualizacion . " " . $horaUltimaActualizacion); // convertimos el valor de BBDD a un objeto DateTime .
    $FechaActual               = new DateTime(); // Obtenemos la fecha actual como objeto DateTime.
    
    $anios = $FechaFullUltimaActualizacion->diff($FechaActual)->format("%y");
    $meses = $FechaFullUltimaActualizacion->diff($FechaActual)->format("%m");
    $dias = $FechaFullUltimaActualizacion->diff($FechaActual)->format("%d");
    $DiasDiferenciaEntreFechas = ($anios * 365) + ($meses * 27) + $dias; 
    // Condicion para actualizar la vista, primero si es la vista del BOA (diferente fuente de dato), si es mensual mas de 6 dias sin refrescar, si es diaria todos los dias y si es Mensual cada 27 dias para no agregar complejidad y que actulize en febrero.
    if (($nombreVista == 'boa_eli' or $nombreVista == 'boa_eli_correcciones' or $nombreVista == 'boa_eli_ordenes' or $nombreVista == 'boa_eli_ordenes_correcciones') and
        (($periocidad == 'diaria' and $DiasDiferenciaEntreFechas > 0) or ($periocidad == 'semanal' and $DiasDiferenciaEntreFechas > 6) or ($periocidad == 'anual' and $DiasDiferenciaEntreFechas > 364) or ($periocidad == 'mensual' and $DiasDiferenciaEntreFechas > 27))) {
                
        $ResultadoDescarga = DescargarBOAJSON($nombreVista);
        if ($ResultadoDescarga === 'FIN') {
            
            // Solo al terminar de descargar bien el fichero BOA en JSON procedemos con la conversion del CSV.
            GenerarCSVDesdeBOAJSON();            
            actualizarCsv(0, $nombreVista, $dcTypesBOA, $URLApi);
            
        }
    } elseif (($nombreVista != 'boa_eli' and $nombreVista != 'boa_eli_correcciones' and $nombreVista != 'boa_eli_ordenes' and $nombreVista != 'boa_eli_ordenes_correcciones') and
        (($periocidad == 'semanal' and $DiasDiferenciaEntreFechas > 6) or ($periocidad == 'diaria' and $DiasDiferenciaEntreFechas > 0) or ($periocidad == 'anual' and $DiasDiferenciaEntreFechas > 364) or ($periocidad == 'mensual' and $DiasDiferenciaEntreFechas > 27) )) {
            
		$tieneNumero = filter_var($nombreVista, FILTER_SANITIZE_NUMBER_INT) != "";
		if($tieneNumero)
		{
			$NumeroVista = (int) filter_var($nombreVista, FILTER_SANITIZE_NUMBER_INT);
            if ($NumeroVista != 0){
			    $ResultadoDescarga = DescargarVistaCompleta($NumeroVista);
                if ($ResultadoDescarga === 'NO'){
                    //No se ha descargado la vista porque es un html
                    logErrores("No se ha podido descargar la vista $NumeroVista, obtenemos un html");
                }
                else{
                    if ($ResultadoDescarga === 'FIN') {                    
                        // Solo al terminar de descargar bien la vista procedemos con la conversion del CSV.
                        GenerarCSVDesdeXMLVista($NumeroVista);
                        if (!in_array($NumeroVista, $ArrayVistasExcluir) /*or VerificarDatosVista($NumeroVista)*/ ) {                        
                            actualizarCsv($NumeroVista, $nombreVista, $dcTypes, $URLApi);
                        }
                    }
                }
            }
			
		}
		else{
			// Vistas que no estan en el GA_OD_CORE
			$ResultadoDescarga = DescargarCSVOrigen($nombreVista);
            if ($ResultadoDescarga === "NO"){
                logErrores("No se ha podido descargar la vista $nombreVista, pagina en mantenimiento");
            }
            else{
                actualizarCsv(str_replace(" ", "", $nombreVista), $nombreVista, $dcTypesBOA, $URLApi);
            }
		}
    }

    $Numfila++;
    
    sleep(10);
}

logErrores("TERMINADO");
?>
