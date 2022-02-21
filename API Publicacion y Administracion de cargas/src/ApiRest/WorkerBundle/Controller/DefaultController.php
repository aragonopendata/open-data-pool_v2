<?php

namespace ApiRest\WorkerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use ApiRest\WorkerBundle\Controller\Trazas;
use ApiRest\WorkerBundle\Controller\Worker;

class DefaultController extends Controller
{
    /**
     * @Route("/worker/{id}") 1522477576 "152247757"
     */
    public function indexAction($id)
    {
        $nowtime = time();
        $trazaError="";
        $error = false;
        $carpeta ="";
        try{
            $parametrosUrl = explode("_",$id);
            $idIso = base64_decode($parametrosUrl[0]);
            $cuenta = strlen($parametrosUrl[1]);
            //recojo las variables de la cadena URL
            //el timestamp de seguridad
            $fromtime = substr($parametrosUrl[1], 14);
            //el nombre de la carpeta 
            $csv  = substr($parametrosUrl[1], 0, 8) . "_" . substr($parametrosUrl[1], 8, 6);
            $dctype = base64_decode($parametrosUrl[2]);
            $actualizarItems = base64_decode($parametrosUrl[3]);
         
            (empty($parametrosUrl[4])) ?  $fichero="" : $fichero=base64_decode($parametrosUrl[4]);
            (empty($parametrosUrl[5])) ?  $log="" : $log=base64_decode($parametrosUrl[5]);
            $carpeta = $csv;
                
        }  catch (Exception $e) {
            $trazaError=$e->getMessage();
        } 
        //recojo los parámetros de la configuracion
        $parametros = $this->container->getParameter('api_publicacion');
    
        if (is_null($parametros)) {
            $error = true;
            $trazaError = "No se han informado los parámetros de configuración AodPool";
        }
        if (!$error) {
            $error = $error && (!isset($parametros['isql_host']));
            $error = $error && (!isset($parametros['isql_db']));  
            $error = $error && (!isset($parametros['isql_tam_buffer_lineas']));   
            $error = $error && (!isset($parametros['smtp_encryption']));  
            $error = $error && (!isset($parametros['smtp_host']));
            $error = $error && (!isset($parametros['smtp_port'])); 
            $error = $error && (!isset($parametros['smtp_username'])); 
            $error = $error && (!isset($parametros['smtp_password']));
            $error = $error && (!isset($parametros['email_from']));
            $error = $error && (!isset($parametros['email_to']));
            $error = $error && (!isset($parametros['mail_file']));
            $error = $error && (!isset($parametros['trazas_debug']));
            $error = $error && (!isset($parametros['time_stamp_worker']));
            $error = $error && (!isset($parametros['usu_virtuoso']));
            $error = $error && (!isset($parametros['pass_virtuoso']));
            $error = $error && (!isset($parametros['dominio_aplicacion']));
            
            if ($error) {
                $trazaError = "No se han informado alguno de los parámetros de configuración AodPool";
            } 
        }
     /*   if (!$error) { 
            $timeStamp = $parametros['time_stamp_worker'];
            //compruebo que a llamada es inmediata      
            if (($nowtime - $fromtime)> intval($timeStamp)){
                $error = true;
                $trazaError = "Llamada fuera de tiempo ";
            }
        }*/
        //compruebo que la llamada es local
        if (!in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1','localhost'))){
            $error = true;
            $trazaError = "La llamada no es localhost";
        }  
        if (!isset($idIso)) {
            $error = true;
            $trazaError = "El idisonomia es requerido";
        } 
        //calculo las rutas relativas al proceso
        $appPath = $this->container->getParameter('kernel.root_dir');
        $webPath = realpath($appPath . '/../web/publicacion');
        $pathNoprocesados =  sprintf("%s/NoProcesados/%s", $webPath, $csv);
        //genera un nuevo contenedor de trazas
        $trazas = new Trazas($pathNoprocesados);
        $trazas->setClase("worker");
        $trazas->LineaInfo("indexAction","Inicio Trabajo worker");

        //inicio el dal de acceso a la tabla de trazas carga cargaVistas y preparo datos para cargar en tabla
        $dal = $this->get('Repository_AdminCargas'); 

        $tiempo = explode("_",$carpeta);
        $fecha = substr($tiempo[0],0,4) . "-". substr($tiempo[0],-4,2) . "-".  substr($tiempo[0],-2);
        $hora = substr($tiempo[1],0,2) . ":" . substr($tiempo[1],2,2);
        $ficherotraza = "log_" . $fecha . ".txt";

        if (!$error) {          
            $worker = new Worker($idIso, 
                                $csv,
                                $dctype,
                                $actualizarItems,
                                $parametros['isql_host'],
                                $parametros['isql_db'], 
                                $parametros['isql_tam_buffer_lineas'],
                                $parametros['smtp_encryption'], 
                                $parametros['smtp_host'],
                                $parametros['smtp_port'],
                                $parametros['smtp_username'],
                                $parametros['smtp_password'],
                                $parametros['email_from'],
                                $parametros['email_to'],
                                $parametros['mail_file'],
                                $parametros['trazas_debug'],
                                $parametros['usu_virtuoso'],
                                $parametros['pass_virtuoso'],
                                $parametros['dominio_aplicacion'],
                                $trazas);
			$worker->Procesa($webPath,$appPath); 
            $trazas->LineaInfo("indexAction","Fin Trabajo worker"); 
            if (!empty($fichero)) {
                $logfichero  = sprintf("%s %s %s\n", $fecha, $hora, $log);
                file_put_contents($fichero,  $logfichero, FILE_APPEND | LOCK_EX);          
                $dal->InsertaCargaVistas($idIso,"A demanda","",$log,$ficherotraza,$carpeta,$fecha,$hora);
            }
            return $this->render('@ApiRestAodPool/Default/index.html.twig', array(
                "mensaje" =>  "Proceso realizado con éxito"));  
        } else {
            $trazas->LineaInfo("indexAction","Fin Trabajo worker");   
            $trazas->LineaError("indexAction", $trazaError); 
            if (!empty($fichero)) {
                $logfichero  = sprintf("%s %s %s %s %s\n", $fecha, $hora, $log," Error ", $trazaError );
                file_put_contents($logfichero,  $logfichero , FILE_APPEND | LOCK_EX);
                $log = $log . " Error " . $trazaError; 
                $dal->InsertaCargaVistas($idIso,"A demanda","",$log, $ficherotraza, $carpeta,$fecha,$hora);
            }  
            return $this->render('@ApiRestAodPool/Default/index.html.twig', array(
                "mensaje" =>  "Acceso no permitido o erróneo"));
        }

    }

}
