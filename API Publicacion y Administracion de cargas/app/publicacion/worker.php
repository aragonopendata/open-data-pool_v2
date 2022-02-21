<?php

require_once("Triple.php");
require_once("Isonomia.php");
require_once("Csv.php");
require_once("Trazas.php");

require '../vendor/autoload.php';

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

$id = $argv[1]; 
$carpeta = $argv[2];

$isql_exe = $argv[3];
$isql_host =  $argv[4];
$isql_username  = $argv[5];
$isql_password = $argv[6];
$isql_db = $argv[7];

$email_protocol = $argv[8];
$email_host =  $argv[9];
$email_port =  $argv[10];
$email_username =  $argv[11];
$email_password =  $argv[12];
$email_from =  $argv[13];
$email_to =   $argv[14];

/*
$recojo = sprintf("id: %s carpeta: %s isql_exe: %s isql_host: %s isql_username: %s isql_password: %s isql_db: %s email_protocol: %s email_host: %s email_port: %s email_username: %s email_password: %s email_from: %s email_to: %s",
                  $id,$carpta,$isql_exe,$isql_host,$isql_username,$isql_password,$isql_db,$email_protocol,$email_host,$email_port,$email_username,$email_password,$email_from,$email_to);

printf($recojo);*/

/*
$id = 11;
$carpeta = "";

$isql_exe = "";
$isql_host = "localhost";
$isql_username = "";
$isql_password = "";
$isql_db ="";

$email_protocol ="ssl";
$email_host = "smtp.gmail.com";
$email_port  = 465;
$email_username = '';
$email_password = '';
$email_from = "";
$email_to  = "";
*/



Procesa($id, $carpeta,
        $isql_exe, $isql_host, $isql_username, $isql_password, $isql_db,
        $email_protocol, $email_host, $email_port, $email_username, $email_password, $email_from, $email_to);


/**
 * Llamada pricipal para procesar los datos:
 *  1- Carga es esquema isonomico
 *  2- Carga las filas del CSV
 *  3- Genera las triples cruzando los datos
 * 
 * Parametros:
 *  id:                 Identificador del esquema isonomico
 *  carpeta:            Nombre de la carpeta donde se encuentra el archivo CSV (datos.csv)
 *                      El proceso busca en al carpeta prestablecida /web/publoicacion/NoProcesados/{$carpeta}
 *  isql_exe:            Nombre del ejecutable isql / isql-vt
 *  isql_host:           Host del virtuoso
 *  isql_username:        Usuario con permisos isql 
 *  isql_password      Contraseña del usuario isql
 *  isql_db: Nombre BD virtuoso 
 *  
 *  email_protocol:     Protocolo ssl
 *  email_host:         Host smtp
 *  email_port:         Puerto smtp
 *  email_username:     Nombre cuenta smtp    
 *  email_password:     Contraseña cuenta smtp
 *  email_from:         Correo origen
 *  email_to:           Correo destino
 */
function Procesa($id,$carpeta,
                 $isql_exe,$isql_host,$isql_username,$isql_password,$isql_db,
                 $email_protocol,$email_host,$email_port,$email_username,$email_password,$email_from,$email_to)
{  
  //informa de la ruta a buscar y perara las carpetas de salida
  $webPath = realpath(dirname(__FILE__));
  $pathNoporcesados =  sprintf("%s/NoProcesados/%s", $webPath, $carpeta);
  $pathPorcesados =  sprintf("%s/Procesados/%s", $webPath, $carpeta);
  $pathError =  sprintf("%s/Error/%s", $webPath, $carpeta);

    //genera un nuevo contenedor de trazas
  $trazas = new Trazas($pathNoporcesados);
  $trazas->setClase("worker");

  //Si la carpeta no existe informa del error
  if (file_exists($pathNoporcesados. "/datos.csv")) { 
    $trazas->LineaInfo("Procesa","Archivo datos CSV encontrado");
  } else{
    $trazas->LineaError("Procesa","Archivo datos CSV no encontrado");
  }

  //Si no hay errores carga la isonomia y procesa el esquema 
  if ($trazas->SinError()) {
    $trazas->LineaInfo("Procesa","Carga la isonomia y procesa el esquema"); 
    $Isonomia = new Isonomia($id,$trazas);
    $PilaTriplesEsquema = $Isonomia->ProcesaEsquema();
  }

  //Si no hay errores carga los datos del archivo CSV
  if ($trazas->SinError()) {
    $trazas->LineaInfo("Procesa","Carga los datos del archivo CSV"); 
    $Csv = new Csv($pathNoporcesados,$trazas);
    $FilasCsv = $Csv->DameCsv();
  }
 
  //Si no hay errores genero las triples
  //Cruza los datos del esquema preparado con los datos del CSV
  if ($trazas->SinError()) {
      $trazas->LineaInfo("Procesa","Cruza los datos del esquema preparado con los datos del CSV"); 
      $TriplesProcesadas = GeneraTriples($PilaTriplesEsquema,$FilasCsv,$pathNoporcesados,$trazas);
  }

  //Si no hay errores guardo las triples en virtuosos
  if ($trazas->SinError()) {
    $trazas->LineaInfo("Procesa","Guardo las triples en virtuoso"); 
    GuardaTriplesVirtuoso($pathNoporcesados,$trazas,
                          $isql_exe,$isql_host,$isql_username,$isql_password,$isql_db);
  }

  //Envia correo con la información al administrador
  $trazas->LineaInfo("Procesa","Envía correo con la información al administrador"); 
  if ($trazas->SinError()) {
    EnviaEmial($pathNoporcesados,TRUE, $trazas,
               $email_protocol,$email_host,$email_port,$email_username,$email_password,$email_from,$email_to);
  }  else {
    EnviaEmial($pathError, FALSE, $trazas,
               $email_protocol,$email_host,$email_port,$email_username,$email_password,$email_from,$email_to);
  }

  //Si hay errores mueve la carpeta a la carpeta raiz de errores
  //Si no hay errores mueve la carpeta a la carpeta raiz de Procesados 
  $fileSystem = new Filesystem();
  //borro las carpetas que pudieran existir
  array_map('unlink', glob("$pathPorcesados/*.*"));
  array_map('unlink', glob("$pathError/*.*"));

  if ($trazas->SinError()) {
    $trazas->LineaInfo("Procesa","Mueve la carpeta a la carpeta raiz de Procesados");
    $fileSystem->mkdir($pathPorcesados, 0755);
    rename($pathNoporcesados,$pathPorcesados);
  } else {
    $trazas->LineaInfo("Procesa","Mueve la carpeta a la carpeta raiz de errores");

    $fileSystem->mkdir($pathError, 0755);
    rename($pathNoporcesados,$pathError);
  }
  return;
}

/**
 * Funcion que realiza un doble Loop por cada una de las filas del CSV y cada una de los nodos del la isonomia
 * Parametros:
 *    pilaTriples:      array contenedor de las triples  (objetos Triple)
 *    regitrosCSV:      array con los datos del archivo CSV
 *    pathNoporcesados: path de la carpeta donde está el archivo n3 a procesar
 *                      /web/publoicacion/NoProcesados/{$carpeta}
 *    trazas:           objeto de trazas
 * 
 */
function GeneraTriples($pilaTriples,$regitrosCSV,$pathNoporcesados,$trazas)
{    
  $trazas->LineaInfo("GeneraTriples","Inicio de generación de triples"); 
  $nombreFichero = $pathNoporcesados . "/datos.n3";    
  $myfile = fopen($nombreFichero, "w+");
  $cuenta=0;
  foreach ($regitrosCSV as $filaCVS) 
  {
    foreach ($pilaTriples as $triple) 
    {
        $triple->ProcesaDatos($filaCVS);
        //la triple puede ser nula porque algun campo del archivo CSV esta vacio
        if (!empty($triple->getTripleValor())) {
          fwrite($myfile, $triple->getTripleValor() ."\n");
            $cuenta++;
        } 
    }
  }
  fclose($myfile);
  $trazas->LineaInfo("GeneraTriples","Fin de genarción de triples.Triples generdas:". $cuenta ); 
}

/**
 * Función que guarda las triples en servidor Vituoso mediante la herramienta de lines comando isql
 * Parametros:
 *    pathNoporcesados:   path de la carpeta donde está el archivo n3 a procesar
 *                        /web/publicacion/NoProcesados/{$carpeta}
 *    trazas:             objeto de trazas
 *    isqlExe:            Nombre del ejecutable isql / isql-vt
 *    isqlhost:           Host del virtuoso
 *    isqlUsuario:        Usuario con permisos isql 
 *    isqlContrasena      Contraseña del usuario isql
 *    isqlNombreIsonomia: Nombre BD virtuoso 
 */
function GuardaTriplesVirtuoso($pathNoporcesados,$trazas,
                               $isql_exe,$isql_host,$isql_username,$isql_password,$isql_db){
  //Informa configuracion con los parametros
  $fileSystem = new Filesystem();
  $webPath = realpath(dirname(__FILE__));
  $nombrefichero = $pathNoporcesados . "/datos.n3";  
  $nombreficheroVirtuoso = $webPath . "/datos.n3"; 
  $nombrefichero = $pathNoporcesados . "/datos.n3";  
  //conprueba que exita el archivo n3 a procesar
  if (file_exists($nombrefichero)) { 
    try{
      //copia el archivo a la ruta  /web/publicacion que tiene permisos concedidos por virtuosos
      $fileSystem->copy($nombrefichero,$nombreficheroVirtuoso,true);
    } catch (Exception $e) {
      $trazas->LineaError("GuardaTriplesVirtuoso",'Excepción capturada: ',  $e->getMessage());
    }
    if ($trazas->SinError()) {
      $contexto = sprintf("%s %s:1111 %s %s \"EXEC=COMANDO\"", $isql_exe, $isql_host, $isql_username, $isql_password);
    
      //desbilita indexacion
      $comando = str_replace("COMANDO", "DB.DBA.VT_BATCH_UPDATE('DB.DBA.RDF_OBJ', 'ON', NULL);", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      //LanzaSubporceso($comando,$trazas);
      //limpia los dato
      $comando = str_replace("COMANDO", "delete from DB.DBA.load_list;", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      $cargadatos = sprintf("DB.DBA.TTLP (file_to_string_output('%s'), '', '%s', 0);", $nombreficheroVirtuoso, $isql_db );

      $comando = str_replace("COMANDO", $cargadatos, $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);
     
      $comando = str_replace("COMANDO", "set isolation='uncommitted';", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);
     
      $comando = str_replace("COMANDO", "rdf_loader_run();", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);
      
      //establezco un checkpoint
      $comando = str_replace("COMANDO", "checkpoint;", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      $comando = str_replace("COMANDO", "commit WORK;", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      $comando = str_replace("COMANDO", "checkpoint;", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      //restablezco el indice
      $comando = str_replace("COMANDO", "DB.DBA.RDF_OBJ_FT_RULE_ADD(null, null, 'All');", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      $comando = str_replace("COMANDO", "DB.DBA.VT_INC_INDEX_DB_DBA_RDF_OBJ();", $contexto );
      $output=exec($comando);
      $trazas->LineaInfo("GuardaTriplesVirtuoso", $output . ': '.  $comando);

      //borra el archivo de carga de /web/publicacion
      unlink($nombreficheroVirtuoso);
   }
  }
}


/**
 * Función que envia correo al administrador adjuntando el archivo n3 si no hay error o el de trazas 
 * hay error
 * Parametros:
 *    path:               path de la carpeta donde está el archivo n3 a procesar
 *                        /web/publoicacion/NoProcesados/{$carpeta}
 *    sinError:           Indica si ha habido error o no
 *    trazas:             Objeto de trazas
 *    email_protocol:     Protocolo ssl
 *    email_host:         Host smtp
 *    email_port:         Puerto smtp
 *    email_username:     Nombre cuenta smtp    
 *    email_password:     Contraseña cuanta smtp
 *    email_from:         Correo origen
 *    email_to:           Correo destino
 */
function EnviaEmial($path,$sinError,$trazas,
                    $email_protocol,$email_host,$email_port,$email_username,$email_password,$email_from,$email_to)
{
    $transport = (new Swift_SmtpTransport($email_host , $email_port, $email_protocol))
    ->setUsername($email_username)
    ->setPassword($email_password);

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);
    if ($sinError) {
       $body = "El proceso de publicacion ha terminado con éxito \n. Ajuntamos el archivo de triples generadas";
    } else {
       $body = "El proceso de publicacion ha terminado con errores \n. Ajuntamos el archivo de log generado";
    }
    // Create a message
    $message = (new Swift_Message('AodPool mensaje fin proceso Publicación'))
      ->setFrom([$email_from => 'AodPool'])
      ->setTo([$email_to => 'Administrador AodPool'])
      ->setBody( $body );
    if ($sinError) {  
      $message->attach(Swift_Attachment::fromPath($path . '/datos.n3'));
    } else {
      $message->attach(Swift_Attachment::fromPath($trazas->DamePath()));
    }
    try {
      // Send the message
      $result = $mailer->send($message);
    } catch (Exception $e) {
		  	$this->trazas->LineaError("EnviaEmial",'Excepción capturada: ',  $e->getMessage());
	  }
}
