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

//require_once './config/configuracion.php'; // Contiene la configuracion del programa.

/*
 * Esta funcion descarga el XML correspondiente a una vista, si la descarga es exitosa
 * devuelve un array con los datos descargados, la ruta donde  
 * se guarda el fichero, en caso contrario devuelve un array con un boleano a FALSE.
 */
function DescargarVistaAOD($idVista, $numPagina)
{
    if (!is_int($idVista) || !is_int($numPagina)) {
        logErrores("Error al descargar la vista, los identificadores no son numericos, el numero de pagina: $numPagina o numero de vista: $idVista.");
        return FALSE;
    }
    global $RutaTrabajo;
    //logErrores("TPAOD:  Inicio descarga de la vista $idVista , pagina $numPagina");
    libxml_use_internal_errors(true);
    $ch = curl_init(); // instanciamos curl e iniciamos un handler para trabajar.
    $xml;
    $datosXMLDescargadosCorrecto;
    $datosXMLDescargados;
    $ContadorReintentosDescarga = 1;
    //30112021 no tiene sentido usar las paginas porque no pagina el resultado. Se mantiene por si en el futuro funciona paginando
    $url = "https://opendata.aragon.es/GA_OD_Core/download?view_id=$idVista&formato=xml&_pageSize=1000&_page=$numPagina"; //Url del archivo xml
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    
    
    // Intentamos la descarga, si falla reintentamos 3 veces.    
    while ((empty($datosXMLDescargadosCorrecto) || strpos($datosXMLDescargados, "<TITLE>P&aacute;gina en mantenimiento</TITLE>")) and $ContadorReintentosDescarga < 4) {
        //logErrores("TPAOD:  Intento: $ContadorReintentosDescarga ");
        $datosXMLDescargados         = curl_exec($ch);
        $datosXMLDescargadosCorrecto = preg_replace('/[\x00-\x1F\x7F]/u', '', $datosXMLDescargados); //se quitan todos los caracteres especiales
        if (empty($datosXMLDescargadosCorrecto) || strpos($datosXMLDescargadosCorrecto, "<TITLE>P&aacute;gina en mantenimiento</TITLE>")) {
            logErrores("Error descargando la pagina $numPagina de la vista $idVista, pagina en mantenimiento, intento numero: $ContadorReintentosDescarga ");
            sleep(10);
        }
        $ContadorReintentosDescarga++;
    }
    //Si al final no hemos descargado nada
    if ($datosXMLDescargadosCorrecto === false) {
        logErrores("Error al leer el xml de la pagina $numPagina de la vista $idVista");
        return FALSE;
    } else {
        //Hemos descargado la pagina
        //logErrores("TPAOD: Descargada la pagina $numPagina");
        return $datosXMLDescargadosCorrecto;
    }
    
    
}

/*
 * Descarga una vista de AOD y la guarda en un fichero, devuelve un string con donde a guardado el fichero,
 * en caso de que haya fallado la descarga devolvera un booleano a FALSE.
 */

function DescargarVistaAODaFichero($idVista, $numPagina)
{
    //Obtenemos los datos de una peticion curl usando la funcion DescargarVistaAOD
    $datosXMLDescargadosCorrecto = DescargarVistaAOD($idVista, $numPagina);
    
    
    global $RutaTrabajo;
    //No se ha descargado
    if ($datosXMLDescargadosCorrecto === false) {
        logErrores('Error al leer el xml de la pagina $numPagina de la vista $idVista');
        file_put_contents($RutaTrabajo . "/Log/ArchivosErroneos/(" . date("Ymd Gis") . ")" . "Error_vista_" . $idVista . "_$numPagina.xml", $datosXMLDescargados);
        return 'FIN';
    } else {
         //comprobamos si el fichero correspondiente a la pagina anterior y los datos descargados son iguales, en ese caso se finaliza la descarga
        //Comprobamos si ya hemos descargado esta pagina. Hay que hacerlo porque actualmente no devuelve el resultado paginado (30112021)
        if ($numPagina > 1){
            //logErrores("COMPROBAMOS LA PAGINA ANTERIOR");
            $datosAnteriores = DescargarVistaAOD($idVista, $numPagina - 1);
            if ($datosAnteriores === $datosXMLDescargadosCorrecto){
                //logErrores("La pagina $numPagina es igual que la anterior, no descargamos");
                return 'FIN';
            }
        }
        //Creamos la carpeta de la vista si no existe
        if (!file_exists($RutaTrabajo . "/VistasXml/Vista$idVista")) {
            //logErrores('TPAOD: Creamos la carpeta de la vista en xml');
            mkdir($RutaTrabajo . "/VistasXml/Vista$idVista");
        }
        
        // Comprobamos si existe la carpeta de descarga, si no la creamos.
        if (!file_exists($RutaTrabajo . "/VistasCsv/Vista$idVista")) {
            //logErrores('TPAOD: Creamos la carpeta de la vista en csv');
            mkdir($RutaTrabajo . "/VistasCsv/Vista$idVista");
        }

        //Generamos la ruta del fichero destino, donde estaran los datos que hemos recibido
        //Rellenamos el fichero
        $FicheroDestino = $RutaTrabajo . "/VistasXml/Vista$idVista/Vista_" . $idVista . "_$numPagina.xml";      
        file_put_contents($FicheroDestino, $datosXMLDescargadosCorrecto);
        //logErrores("Se ha rellenado el xml de la pagina en $FicheroDestino");
        return $FicheroDestino;
    }
    
}


function DescargarVistaCompleta($idVista)
{
    $numResultados = 0;
    for ($i = 1; $i <= 160; $i++) {		
		//logErrores('DescargarVistaCompleta - vista: ' .$idVista);
        $RutaFichero = DescargarVistaAODaFichero($idVista, $i);
        
		if ($RutaFichero === 'FIN') {
           return 'FIN';
        }
        else{
            $contenido = file_get_contents($RutaFichero);
            //logErrores($contenido);
            //Hay que controlar que no devuelva un html
            if (strpos($contenido, "<html") > 0){
                //logErrores("No se ha podido descargar la vista, obtenemos un html");
                return 'NO';
            }            
            $xml = simplexml_load_string($contenido);
            libxml_clear_errors();
            if($xml != false){
                $numResultados = $numResultados + $xml->count();
            }
            //Si se obtienen menos de los resultados que se han pedido por pagina, estamos en la ultima
            if ($numResultados < 1000){
                //logErrores("Se ha terminado la descarga de la vista $idVista");
                return "FIN";
            }
            else{
                //logErrores("Seguimos con la siguiente pagina");
            }
        }
        
    }
}

/*
 * Esta funcion descarga el XML correspondiente a una vista, si la descarga es exitosa
 * devuelve un array con los datos descargados, la ruta donde  
 * se guarda el fichero, en caso contrario devuelve un array con un boleano a FALSE.
 */
function DescargarCSVOrigen($nombreVista)
{	
    global $RutaTrabajo;
	global $URLEndpointVirtuoso;
    libxml_use_internal_errors(true);
    $ch = curl_init(); // instanciamos curl e iniciamos un handler para trabajar.
    
	$parametro = $nombreVista;
    if ($nombreVista === "Relación de inmuebles del Inventario de Patrimonio del Gobierno de Aragón"){
        $parametro = "Relacion de inmuebles del Inventario de patrimonio del Gobierno de Aragon";    
    }
	$parametro = str_replace("_observaciones", "", $parametro);
    $consulta = "select ?o from <http://opendata.aragon.es/def/ei2av2> where { ?s <http://purl.org/dc/terms/title> ?ob . ?s <http://www.w3.org/ns/dcat#accessURL> ?o filter ( contains(str(?ob), \"$parametro\"))} limit 1";
	
    

    $consulta = urlencode($consulta);
    //$consulta = urlencode("select ?o from <http://opendata.aragon.es/def/ei2av2> where { ?s <http://www.w3.org/ns/dcat#accessURL> ?o filter ( contains(str(?o), \"$parametro\"))} limit 1");
    
    //if ($nombreVista === "Relación de inmuebles del Inventario de Patrimonio del Gobierno de Aragón"){
        //$consulta = urlencode("select ?o from <http://opendata.aragon.es/def/ei2av2> where { ?s <http://www.w3.org/ns/dcat#accessURL> ?o filter ( contains(str(?o), "inmuebles"))} limit 1");
    //}
    $url = $URLEndpointVirtuoso . "?default-graph-uri=&query=" . $consulta . "&format=text%2Fcsv&timeout=0&signal_void=on";
	//logErrores("aod:  queryVirtuoso: $url ");
	

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    
    
    // Intentamos la descarga, si falla reintentamos 3 veces.    
    
    //logErrores("aod:  Inicio descarga de la vista $nombreVista ");
    $datosCSVDescargados = curl_exec($ch);
	//echo "\nDATOS: $datosCSVDescargados";
	//logErrores("aod:  Fin descarga de la vista $nombreVista ");
	
	$urlCsv = str_replace(PHP_EOL, '', str_replace('\r\n', '', str_replace('"', '', str_replace('"o"', '', $datosCSVDescargados)))); 
	//logErrores("aod:  url: $urlCsv ");

    if (empty($urlCsv)){
        $consulta = "select ?o from <http://opendata.aragon.es/def/ei2av2> where { ?s <http://www.w3.org/ns/dcat#accessURL> ?o filter ( contains(str(?o), '$parametro.csv'))} limit 1";
        $consulta = urlencode($consulta);
        $url = $URLEndpointVirtuoso . "?default-graph-uri=&query=" . $consulta . "&format=text%2Fcsv&timeout=0&signal_void=on";
        $ch3 = curl_init(); 
        curl_setopt($ch3, CURLOPT_HEADER, 0);
        curl_setopt($ch3, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch3, CURLOPT_TIMEOUT, 400);
        curl_setopt($ch3, CURLOPT_URL, $url);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch3, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch3, CURLOPT_ENCODING, 'UTF-8');
        $datosCSVDescargados = curl_exec($ch3);
        $urlCsv = str_replace(PHP_EOL, '', str_replace('\r\n', '', str_replace('"', '', str_replace('"o"', '', $datosCSVDescargados)))); 
    
    }
	
	$ch2 = curl_init(); // instanciamos curl e iniciamos un handler para trabajar.
	curl_setopt($ch2, CURLOPT_HEADER, 0);
    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 400);
    curl_setopt($ch2, CURLOPT_URL, $urlCsv);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch2, CURLOPT_ENCODING, 'UTF-8');
	$datosCSVDescargados = curl_exec($ch2);
    $datosCSVDescargados = str_replace("\r\n", ";\n", $datosCSVDescargados);
    $datosCSVDescargados = str_replace(";;;;;;;;;;;\n", "", $datosCSVDescargados);

	//$datosCSVDescargadosCorrecto = preg_replace('/[\x00-\x1F\x7F]/u', '', $datosCSVDescargados); //se quitan todos los caracteres especiales
	if (empty($datosCSVDescargados) || strpos($datosCSVDescargados, "<TITLE>P&aacute;gina en mantenimiento</TITLE>") > 0) {
		//logErrores("Error descargando la pagina $nombreVista , pagina en mantenimiento");
        return "NO";
	}
    
	//Give our CSV file a name.
    $nombreVista = str_replace (" ", "", $nombreVista);
    if (!file_exists($RutaTrabajo . "/VistasCsv/Vista$nombreVista")) {
        //logErrores('TPAOD: Creamos la carpeta de la vista en csv');
        mkdir($RutaTrabajo . "/VistasCsv/Vista$nombreVista");
    }
	$FicheroDestinoCSV = $RutaTrabajo . "/VistasCsv/Vista$nombreVista/Vista_$nombreVista.csv";
	//logErrores("aod ruta fichero csv: $FicheroDestinoCSV");
	 
	//Give our CSV file a name.
	$fp = fopen($FicheroDestinoCSV, 'w+');

	// Add header
	fwrite($fp, $datosCSVDescargados);
	 
	//Finally, close the file pointer.
	fclose($fp);
    
    return $datosCSVDescargados;
}

function GenerarCSVDesdeXMLVista($idVista)
{
    //logErrores('Entra en generar ' . $idVista);
    // Limpiamos la variable idVista, dejando solo numeros.
    $NumeroVista         = (int) filter_var($idVista, FILTER_SANITIZE_NUMBER_INT);
    $RutaDeTrabajo       = getcwd();

    $nombreCsv = "./CreacionCSV/CrearVista$NumeroVista.php";
    if (!file_exists($nombreCsv)){
        $ComandoCMD = "cd $RutaDeTrabajo/CreacionCSV && " . PHP_BINARY . ' ./CSVComun.php ' . $NumeroVista;
    }
    else{
        $ComandoCMD = "cd $RutaDeTrabajo/CreacionCSV && " . PHP_BINARY . ' ./CrearVista' . $NumeroVista . '.php';
    }
    //logErrores($ComandoCMD);
    $ResultadoComandoCMD = shell_exec($ComandoCMD);

    if (empty($ResultadoComandoCMD)) {
        $ResultadoComandoCMD = 'Ejecucion Correcta!';
    }
    //logErrores($ResultadoComandoCMD);
    return;
}


/*
 * Esta funcion se encarga de actualizar la vista con el CSV.
 * $idVista es el identificador numerico de la vista.
 * $nombreVista Es el nombre del esquema que se usara para actualizar la vista.
 * $dcTypes es el array de dcTypes de todas las vistas.
 * $URLApi es la URL del Api-docs de Aragon Open Data Pool.
 */
function actualizarCsv($idVista, $nombreVista, $dcTypes, $URLApi)
{
    if ($nombreVista === "boa_eli" or $nombreVista === "boa_eli_correcciones" or $nombreVista === "boa_eli_ordenes" or $nombreVista === "boa_eli_ordenes_correcciones"){
        $ficheroCSV = "/data/apps/ActualizarAODv2/VistasJson/VistaBOA/vista_$nombreVista"."_v2.csv";
        if (!file_exists($ficheroCSV)) {
            logErrores("Error al leer el fichero $ficheroCSV");
            return FALSE;
        }
    }
    else{
        $ficheroCSV = "./VistasCsv/Vista$idVista/Vista_$idVista.csv";
        if (!file_exists($ficheroCSV)) {
            logErrores("Error al leer el fichero $ficheroCSV");
            return FALSE;
        }
    }

    $ch       = curl_init(); //Se crea un curl
    $datosCSV = file_get_contents($ficheroCSV);
    //logErrores($datosCSV);
    $datosCSV = curl_escape($ch, $datosCSV);
    
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2500);
    curl_setopt($ch, CURLOPT_URL, $URLApi);
    curl_setopt($ch, CURLOPT_POST, 1); //Se le dice que tiene que usar el protocolo POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, "idesquema=" . $nombreVista . "&csv=" . $datosCSV /*. "&dc_type=" . $dcTypes[$idVista]*/);
    
    $RespuestaHTTP = curl_exec($ch); //Se ejecuta la peticion
    
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    logErrores("Se ha mandado la peticion de actualizacion del csv de la vista: $nombreVista \nEstado: $httpcode \nRespuesta: $RespuestaHTTP");
    
    switch ($idVista) {
        case 10:
            subirRelacion("57 Relaciones de Comarca", $nombreVista, 57, $URLApi);
            break;
        case 20:
            subirRelacion("58 Relaciones de Entidades Singulares", $nombreVista, 58, $URLApi);
            break;
        case 22:
            subirRelacion("59 Relaciones de Fundaciones", $nombreVista, 59, $URLApi);
            break;
        case 24:
            subirRelacion("60 Relaciones de Mancomunidades", $nombreVista, 60, $URLApi);
            break;
        case 35:
            subirRelacion("61 Relaciones de Villas y Tierras", $nombreVista, 61, $URLApi);
            break;
        case 102:
            subirArchivoAdicinal("Coordenadas Vista 102", $nombreVista, "../VistasCsv/Vista$idVista/coordenadas.csv", $URLApi);
            break;
        default:
            break;
    }
}


/*
 * Esta funcion se encarga de publicar la relacion de una vista atraves del Api.
 * $nombre Es el Nombre del equema que se usara para actualizar la vista.
 * $ipPublicacion es la URL del Api-docs de Aragon Open Data Pool.
 * $vistaActualizar es el nombre de la vista.
 * $ruta es el fichero adicional que se publica.
 */
function subirArchivoAdicinal($nombre, $vistaActualizar, $ruta, $ipPublicacion)
{
    $ipServidor = $ipPublicacion; //Ip para puiblicar las relaciones
    
    $ch = curl_init(); //Se crea un curl
    if (!file_exists($ruta)) {
        logErrores("Error al leer el fichero adicional $ruta");
        return FALSE;
    }
    $datosCsv = file_get_contents($ruta);
    
    $datosCsv = curl_escape($ch, $datosCsv);
    
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    curl_setopt($ch, CURLOPT_URL, $ipServidor);
    curl_setopt($ch, CURLOPT_POST, 1); //Se le dice que tiene que usar el protocolo POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, "idesquema=" . $nombre . "&csv=" . $datosCsv);
    
    curl_exec($ch); //Se ejecuta la peticion
    
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    
    //logErrores("Se ha mandado la peticion de subida del archivo adicional de la vista: $vistaActualizar Estado: $httpcode");
}

/*
 * Esta funcion se encarga de publicar la relacion de una vista atraves del Api.
 * $id Es el Numero Identificativo de la Vista en el Api CORE.
 * $ipPublicacion es la URL del Api-docs de Aragon Open Data Pool.
 * $nombre es el nombre del Esquema
 * 
 */
function subirRelacion($nombre, $vistaActualizar, $id, $ipPublicacion)
{
    $ipServidor = $ipPublicacion; //Ip para puiblicar las relaciones  
    
    
    $ficheroCSV = "./VistasCsv/Vista$id/vista_$id.csv";
    if (!file_exists($ficheroCSV)) {
        $ficheroCSV = "./VistasCsv/Vista$id/Vista_$id.csv";
        if (!file_exists($ficheroCSV)) {
            logErrores("Error al leer el fichero $ficheroCSV");
            return FALSE;
        }
    }
    $ch       = curl_init(); //Se crea un curl
    $datosCSV = file_get_contents($ficheroCSV);
    $datosCSV = curl_escape($ch, $datosCSV);
    
    
    
    
    
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    curl_setopt($ch, CURLOPT_URL, $ipServidor);
    curl_setopt($ch, CURLOPT_POST, 1); //Se le dice que tiene que usar el protocolo POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, "idesquema=" . $nombre . "&csv=" . $datosCsv);
    
    curl_exec($ch); //Se ejecuta la peticion
    
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    //logErrores("Se ha mandado la peticion de actualizacion las relaciones de la vista: $vistaActualizar. Estado: $httpcode");
}

function VerificarDatosVista($idVista)
{
    logErrores("Verificamos los datos de la vista");
    $ficheroCSV = "./VistasCsv/Vista$idVista/vista_$idVista.csv";
    if (!file_exists($ficheroCSV)) {
        $ficheroCSV = "./VistasCsv/Vista$idVista/Vista_$idVista.csv";
        if (!file_exists($ficheroCSV)) {
            logErrores("TPAOD: Error al leer el fichero $ficheroCSV");
            return false;
        }
    }
    $LineasTotalesFicheroCSV = count(file($ficheroCSV, FILE_SKIP_EMPTY_LINES));
    $LineasTotalesVirtuoso = ObtenerNumeroSujetosVirtuoso($idVista);
    // Nos aseguramos que la actualizacion contiene los mismos datos o mas.
    //logErrores("Verificamos la vista numero $idVista con $LineasTotalesFicheroCSV en el CSV y $LineasTotalesVirtuoso en virtuoso.");
    if ($LineasTotalesFicheroCSV >= $LineasTotalesVirtuoso) {
        return true;
    } else {
        logErrores("No se actualiza la vista porque contiene menos datos que virtuoso");
        return false;
    }
}

function VerificarDatosBOA($idVista)
{
    //logErrores("Verificamos los datos del BOA");
    $ficheroCSV = "./VistasJson/VistaBOA/vista_$idVista"."_v2.csv";
    if (!file_exists($ficheroCSV)) {
        logErrores("Error al leer el fichero " . $ficheroCSV);
        return false;
    }
    $LineasTotalesFicheroCSV = count(file($ficheroCSV, FILE_SKIP_EMPTY_LINES));
    $LineasTotalesVirtuoso=ObtenerNumeroSujetosVirtuoso($idVista);

    // Nos aseguramos que la actualizacion contiene los mismos datos o mas.
    //logErrores("Verificamos el fichero del BOA con " . $LineasTotalesFicheroCSV . " en el CSV y " . $LineasTotalesVirtuoso . " en virtuoso.");
    if ($LineasTotalesFicheroCSV >= $LineasTotalesVirtuoso  ) {
        return true; 
    } else {
        logErrores("No se actualiza la vista porque contiene menos datos que virtuoso"); 
        return false;
    }
}

function VerificarEspacioEnDisco() {
    //logErrores("Verificamos el espacio en disco");
    global $RutaTrabajo;
    global $ProcentajeEspacioMinimoEnDisco;
    $EspacioLibreEnBytes = disk_free_space($RutaTrabajo);
    $EspacioTotalEnBytes = disk_total_space($RutaTrabajo);
    $EspacioUsadoEnBytes = $EspacioTotalEnBytes - $EspacioLibreEnBytes;
    $PorcentajeDeUsoDeDisco = sprintf('%.2f', ($EspacioUsadoEnBytes / $EspacioTotalEnBytes) * 100);
    //$PorcentajeEspacioLibre = round(100 - $PorcentajeDeUsoDeDisco);
    //if ($PorcentajeEspacioLibre <= $ProcentajeEspacioMinimoEnDisco) {
      //  logErrores('Espacio de disco insuficiente, se detiene el proceso de carga.');
        //die('Espacio de disco insuficiente, se detiene el proceso de carga.');
    //}
}

function EnviarMail($asunto = 'Notificacion AOD Pool', $mensaje, $RutaFicheroadjunto = null) {
    global $EmailResponsable;
    global $EmailOrigen;

    $mail = new PHPMailer(true); // Passing `true` enables exceptions
    try {
        // Cabecera del Correo.
        $mail->setFrom($EmailOrigen, 'AOD Pool');
        $mail->addAddress($EmailResponsable, 'Responsable AOD Pool'); // Add a recipient

        //Adjuntos
        if (file_exists($RutaFicheroadjunto)) {
            $mail->addAttachment($RutaFicheroadjunto); // Agregamos el adjunto.
        }


        //Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $asunto;
        $mail->Body = 'This is the HTML message body <b>in bold!</b>'; //contenido del Email en HTML.
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients'; //Contenido del email en Text Plano

        $mail->send();
        logErrores('Notificacion de correo enviada al email: ' . $EmailResponsable);
    } catch (Exception $e) {
        logErrores('Error al enviar la notificacion de correo: ' . $mail->ErrorInfo);
    }
}

function ObtenerNumeroSujetosVirtuoso($idVista)
{
    global $URLEndpointVirtuoso;
    global $dcTypes;
    global $dcTypesBOA;

    // Check if BOA
    if ($idVista == 'boa_eli') {
        $dctypeParaConsulta = $dcTypesBOA[0]; // Obtenemos el dctype que corresponde al BOA.
        // $dctypeParaConsulta = $dcTypes[3];
    } else {
        $dctypeParaConsulta = $dcTypes[$idVista]; // Obtenemos el dctype que corresponde a la vista.
    }

    $db = sparql_connect($URLEndpointVirtuoso);
    sparql_ns("ei2a", "http://opendata.aragon.es/def/ei2a"); // Agregamos el prefijo ei2a. Para mas informacion consultar: https://opendata.aragon.es/def/ei2a/
    $FiltroSQL = str_replace('ei2a:', 'http://opendata.aragon.es/def/ei2a#', $dctypeParaConsulta);
    $ConsultaSPARQL = 'select count(distinct(?s)) As ?total from <http://opendata.aragon.es/def/ei2a> where { ?s ?p ?o.  ?s dc:type  <' . $FiltroSQL . '>}';
    $resultadoConsultaSPARQL = sparql_query($ConsultaSPARQL);
    $ArrayResultado = sparql_fetch_array($resultadoConsultaSPARQL);
    $ValorResultado = $ArrayResultado["total"];
    if (!$resultadoConsultaSPARQL) { //Controlamos errores de conexion, si no funciona virtuoso detenemos la carga.
        $errorSPARQL = sparql_errno() . ": " . sparql_error(); // Capturamos el error de virtuoso.
        logErrores($errorSPARQL);
        die("\n" . $errorSPARQL . "\n");
    }
    //Si no hay ningun error, obtenemos el numero de filas y lo devolvemos.
    return $ValorResultado;
}


function DescargarBOAJSON($nombreVista)
{
    /*BOA_ELI "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSOND&SORT=-PUBL&SEPARADOR=&&SECC-C=generales&RANG=(ley+o+decreto)&OP1-C=NO&RANG=real&OP2-C=NO&RANG=correccion&OP3-C=NO&RANG=organica";  Url del archivo json de leyes y decretos BOA */
    /*ORDENES "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSONO&SORT=-PUBL&SEPARADOR=&SECC-C=generales&CODR-C=11&@FDIS-GE=20120101"; */
    /*CORRECCIONES "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSONOCORRE&SORT=-PUBL&SEPARADOR=&SECC-C=generales&CODR-C=(4+o+20+o+22+o+103+o+144+o+18)";*/
    /*ORDENES_CORRECCIONES "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSONOCORRE&SORT=-PUBL&SEPARADOR=&SECC-C=generales&CODR-C=12&@FDIS-GE=20120101";*/
    //logErrores($nombreVista);
    switch ($nombreVista) {
        case 'boa_eli_correcciones':
           $url = "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSONOCORRE&SORT=-PUBL&SEPARADOR=&SECC-C=generales&CODR-C=(4+o+20+o+22+o+103+o+144+o+18)";
        break;
        case 'boa_eli_ordenes':
            $url = "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSONO&SORT=-PUBL&SEPARADOR=&SECC-C=generales&CODR-C=11&@FDIS-GE=20120101";
        break;
        case 'boa_eli_ordenes_correcciones':
            $url = "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSONOCORRE&SORT=-PUBL&SEPARADOR=&SECC-C=generales&CODR-C=12&@FDIS-GE=20120101";
        break;
        default:
            //boa_eli
            $url = "http://www.boa.aragon.es/cgi-bin/AODB/BRSCGI?CMD=VERLST&OUTPUTMODE=JSON&BASE=BOLE&DOCS=1-10000&SEC=OPENDATAELIJSOND&SORT=-PUBL&SEPARADOR=&&SECC-C=generales&RANG=(ley+o+decreto)&OP1-C=NO&RANG=real&OP2-C=NO&RANG=correccion&OP3-C=NO&RANG=organica";
        break;
    }
    global $RutaTrabajo;
    //logErrores($nombreVista . " " . $url);
    
    $datosJSONDescargados = "";
    //$ch = curl_init(); //instanciamos curl e iniciamos un handler para trabajar.
    //curl_setopt($ch, CURLOPT_HEADER, TRUE);
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    //curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    //curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array("Connection: keep-alive", "Upgrade-Insecure-Requests: 1",  "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.55 Safari/537.36 Edg/96.0.1054.34", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9"));
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $datosJSONDescargados);
    //curl_exec($ch);
    //curl_close($ch);

    //Codigo generado en https://reqbin.com/req/php/c-vdhoummp/curl-get-json-example
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $headers = array(
    "Connection: keep-alive",
    "Upgrade-Insecure-Requests: 1",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.55 Safari/537.36 Edg/96.0.1054.34",
    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $datosJSONDescargados = curl_exec($curl);
    curl_close($curl);
    
    if (empty($datosJSONDescargados)) {
        logErrores("No se ha descargado");
        return FALSE;
    } else {
        //Comprobamos si existe la carpeta de descarga, si no la creamos.
        CrearCarpeta($RutaTrabajo . "/VistasJson/VistaBOA");
        $FicheroDestinoJSON = $RutaTrabajo . "/VistasJson/VistaBOA/vista_$nombreVista.json";
        file_put_contents($FicheroDestinoJSON, $datosJSONDescargados);
        logErrores("Se ha descargado exitosamente el fichero JSON de $nombreVista");
        return 'FIN';
    }
}


function GenerarCSVDesdeBOAJSON()
{
        global $RutaTrabajo;
        global $nombreVista;
        //Give our CSV file a name.
        $FicheroDestinoCSV = $RutaTrabajo . "/VistasJson/VistaBOA/vista_$nombreVista.csv";
        $FicheroDestinoJSON = $RutaTrabajo . "/VistasJson/VistaBOA/vista_$nombreVista.json";

        $json = utf8_encode(file_get_contents($FicheroDestinoJSON));
        $jsonDecoded = json_decode($json, true);
         
        //Give our CSV file a name.
        $fp = fopen($FicheroDestinoCSV, 'w+');

        //Add header
        fputcsv($fp, array_keys($jsonDecoded[0]), ';', '"');
        //Loop through the associative array.
        foreach($jsonDecoded as $row){
            //Write the row to the CSV file.
            fputcsv($fp, $row, ';', '"');
            // fwrite($fp, $row);
        }
         
        //Finally, close the file pointer.
        fclose($fp);

        FormatearCSV($FicheroDestinoCSV);
}

function FormatearCSV($FicheroCSV)
{
    // $fcsv = file_get_contents($FicheroCSV);
    // logErrores("CSV: " . $fcsv);
    global $RutaTrabajo;
    global $nombreVista;
    $separador = ";";
    $FicheroDestinoCSV = $RutaTrabajo . "/VistasJson/VistaBOA/vista_$nombreVista"."_v2.csv";
    //Give our CSV file a name.
    $fp = fopen($FicheroDestinoCSV, 'w+');

    if (($fichero = fopen($FicheroCSV, "r+")) !== FALSE) {
        while (($datos = fgetcsv($fichero, 0, ';')) !== FALSE) {
            $row = "";
            for ($i = 0; $i <= count($datos) - 1; $i++) {
                // Reemplazar charset
                $datos[$i] = ReemplazarCharset($datos[$i]);
                // Remove preview double quote
                $datos[$i] = EliminarComillas($datos[$i]);
                // Enclouse fields with double quote
                $datos[$i] = PonerComillas($datos[$i]);
                // Concatenate csv columns
                if ($i > 0) {
                    // if is the first field
                    $row = $row . $separador . $datos[$i];    
                } else {
                    // if is not the first field
                    $row = $row . $datos[$i];
                }
            }
            // Write back to CSV format
            EscribirFilaEnCSV($fp, $row);
        }
    }
    //Finally, close the file pointer.
    fclose($fichero);
    fclose($fp);
}

function ReemplazarCharset($campo)
{
    return str_replace('&quot;', "'", $campo);
}

function EliminarComillas($campo)
{
    return str_replace('"', '', $campo);
}

function PonerComillas($campo)
{
    return '"' . $campo . '"';
}

function EscribirFilaEnCSV($fichero, $fila)
{
    $fila = $fila . "\n";
    fwrite($fichero, $fila);
}









?>