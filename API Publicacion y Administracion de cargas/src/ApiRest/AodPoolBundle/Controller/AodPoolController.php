<?php // AodPool/src/ApiRest/AodPoolBundle/Controller/AodPoolController.php

namespace ApiRest\AodPoolBundle\Controller;

//Importamos las bibliotecas necesarias
use FOS\RestBundle\Controller\Annotations\Get; 
use FOS\RestBundle\Controller\Annotations\Post; 
use FOS\RestBundle\Controller\Annotations\Delete; 
use FOS\RestBundle\Controller\Annotations\Put; 
use FOS\RestBundle\Controller\Annotations\View; 
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\ExceptionController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter; 
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Swagger\Annotations as SWG;
use ApiRest\AodPoolBundle\Entity\Publicacion; 
use ApiRest\AodPoolBundle\Form\Type\PublicacionPoolType;
use ApiRest\AodPoolBundle\Entity\Esquema; 
use ApiRest\AodPoolBundle\Form\Type\EsquemaPoolType;
use ApiRest\AodPoolBundle\Entity\Update; 
use ApiRest\AodPoolBundle\Form\Type\UpdatePoolType;
use ApiRest\AodPoolBundle\Entity\UpdateItems; 
use ApiRest\AodPoolBundle\Form\Type\UpdateItemsPoolType;
use ApiRest\AodPoolBundle\Repository\WebConfig;
use ApiRest\AodPoolBundle\Entity\Configuracion;
use ApiRest\AodPoolBundle\Controller\Utility\Trazas;
use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Yaml\Yaml;

/**
 * Clase que publica los servicios REST
 */
class AodPoolController extends FOSRestController {

    //Clase de trazas
    protected $trazas=null;
    //Directorio de la aplicacion
    protected $appPath=null;

    public function setTrazas($trazas){
        $this->trazas = $trazas;
    }

    public function getTrazas(){
        if (!isset($this->trazas)){
            $this->appPath = $this->container->getParameter('kernel.root_dir');
            $directoryPath = str_replace("app","src/ApiRest/AodPoolBundle/Resources/Files/Logs/",$this->appPath);
            $trazas = new Trazas($directoryPath);
            $trazas->setClase("AodPoolController");
            $trazasDebug = $this->container->getParameter('api_publicacion')['trazas_debug'];
            $trazas->setEscribeTrazasDebug($trazasDebug); 
            $this->SetTrazas($trazas);
        }
        return $this->trazas;
    }

    /** 
    * Servicio de lectura de las configuraciones web, en el servidor. Devuelve los nombres, rdftype o dftype de los existentes.
    * El servicio es síncrono     * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Get("/web/configuracion/listadoyammer")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Devuelve el listado de configuraciones existentes en el servidor ",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    */
	public function getListadoYammerAction(Request $request)
	{
        $errorFormulario = "";
        $this->getTrazas()->LineaDebug("getListadoAction","Llamada a Función");
        $dal = $this->get('Repository_WebConfig');   
        $rdfs = $dal->GetConfiguracionWebs();         
        if (count($rdfs)==0) {
            $errorFormulario = $errorFormulario . " No existe ninguna configuración" ;
            $this->getTrazas()->LineaInfo("getListadoAction","No existe ninguna configuración");
        }
        if (empty($errorFormulario)) {
            $this->getTrazas()->LineaError("getListadoAction","Fin proceso rest");  
            return $rdfs;   
        } else {
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("getListadoAction","Fin proceso rest");  
            throw $ex;
        }
    }

    /** 
    * Servicio de lectura de una configuración web en servidor. Devuelve el archivo yammer por nombre en formato yml. Para descarga directa del archivo *.yml desde el navegador ( /api/web/configuracion/yammer/{nombre}.json)
    * El servicio es síncrono     * 
	* @var Request $request
	* @return View|file
	*
	* @View()
    * @Get("/web/configuracion/yammer/{nombre}")
    * @throws BadRequestHttpException
    * @return file
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Devuelve una configuración web existente en el servidor por nombre en formato yml.",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function getYammerAction(Request $request, $nombre)
	{
        $this->getTrazas()->LineaInfo("getYammerAction","Llamada a Función");
        $yammerContenido="";
        $errorFormulario ="";
        if ($nombre=="{nombre}"){
            $nombre="";
        }
        if (empty($nombre)) {
            $errorFormulario = $errorFormulario . " El nombre de la configuración es requerido.";
            $this->getTrazas()->LineaError("getYammerAction","El nombre del esquema es requerido");
        } else {
            $dal = $this->get('Repository_WebConfig');   
            $yammerContenido = $dal->GetConfiguracionWebYammerbySlug($nombre); 
            if (strlen($yammerContenido)==0) {
                $errorFormulario = $errorFormulario . " No existe ninguna configuración con nombre: '" . $nombre . "'.";
                $this->getTrazas()->LineaError("getYammerAction","El nombre del esquema es requerido");
            }
        }
        if (empty($errorFormulario)) {
            $this->getTrazas()->LineaDebug("getYammerAction","Nombre:" .$nombre);
            $directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Yammer/",$this->appPath);
            $ficheroYammer = $nombre . ".yml";
  
            $fileDestino = $directoryPath . $ficheroYammer; 
            $this->getTrazas()->LineaDebug("getYammerAction","File:" . $fileDestino);


            $fileSystem = new Filesystem();
            if ($fileSystem->exists($fileDestino)) {
                $fileSystem->remove($fileDestino);
                $this->getTrazas()->LineaDebug("getYammerAction","Fichero borrado:" . $fileDestino);
            }
            $fileSystem->dumpFile($fileDestino, $yammerContenido);
            $this->getTrazas()->LineaDebug("getYammerAction","Fichero Credo:" . $fileDestino);
            chmod($fileDestino, 0744); 
            
            $this->getTrazas()->LineaDebug("getYammerAction","Fichero publicado:" . $fileDestino);
            header('Content-Description: File Transfer');
            header('Content-Type: text/html; charset=UTF-8');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileDestino));
            header('Content-disposition: attachment; filename='. basename($fileDestino));
            header('Pragma:public');
            header('Content-Length:' . filesize($fileDestino));
            $this->getTrazas()->LineaInfo("getYammerAction","Fin proceso rest");    
            readfile($fileDestino);       
        } else {     
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("getYammerAction","Fin proceso rest");  
            throw $ex;
        }       
    }

    /** 
    * Servicio de lectura de la configuración web en servidor por nombre. Devuelve el en formato json el yml introducido.
    * El servicio es síncrono     * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Get("/web/configuracion/{nombre}")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Devuelve una configuración web existente en el servidor por nombre en formato json",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function getConfiguracionAction(Request $request, $nombre)
	{    
        $this->getTrazas()->LineaInfo("getConfiguracionAction","Llamada a Función");
        $errorFormulario ="";
        $rdfs = array();
        if ($nombre=="{nombre}"){
            $nombre="";
        }
        if (empty($nombre)) {
            $errorFormulario = $errorFormulario . " El nombre de la configuración es requerido.";
            $this->getTrazas()->LineaError("getConfiguracionAction","El nombre del esquema es requerido");
        } else {
            $dal = $this->get('Repository_WebConfig');   
            $rdfs  = $dal->GetConfiguracionWebbySlug($nombre); 
            if (count($rdfs)==0) {
                $errorFormulario = $errorFormulario . " No existe ninguna configuración con nombre: '" . $nombre . "'.";
                $this->getTrazas()->LineaError("getConfiguracionAction","El nombre del esquema es requerido");
            }
        }
        if (empty($errorFormulario)) {
            $dal = $this->get('Repository_WebConfig');   
            $rdfs = $dal->GetConfiguracionWebbySlug($nombre);      
            /* retornar el arreglo en formato JSON */
            $this->getTrazas()->LineaInfo("getConfiguracionAction","Fin proceso rest");  
            return $rdfs;   
        } else {     
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("getConfiguracionAction","Fin proceso rest");  
            throw $ex;
        }
    }


  /** 
    * Servicio de borrado de una configuración web en servidor por nombre. Borra yml introducido por nombre.
    * El servicio es síncrono     * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Delete("/web/configuracion/{nombre}")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *   resource=false,
    *  description="Borra la configuración yammer existente en el servidor por nombre ",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function deleteConfiguracionAction($nombre)
	{
        $this->getTrazas()->LineaInfo("deleteConfiguracionAction","Llamada a Función");
        $errorFormulario ="";
        $rdfs = array();
        if ($nombre=="{nombre}"){
            $nombre="";
        }
        if (empty($nombre)) {
            $errorFormulario = $errorFormulario . " El nombre de la configuración es requerido.";
            $this->getTrazas()->LineaError("deleteConfiguracionAction","El nombre del esquema es requerido");
        } else {
            $dal = $this->get('Repository_WebConfig');   
            $rdfs  = $dal->GetConfiguracionWebbySlug($nombre); 
            if (count($rdfs)==0) {
                $errorFormulario = $errorFormulario . " No existe ninguna configuración con nombre: '" . $nombre . "'.";
                $this->getTrazas()->LineaError("deleteConfiguracionAction","El nombre del esquema es requerido");
            }
        }
        if (empty($errorFormulario)) {
            $dal = $this->get('Repository_WebConfig');   
            $rdfs = $dal->DeleteConfiguracionWeb($nombre);   
            $this->getTrazas()->LineaInfo("getConfiguracionAction","Fin proceso rest");   
            return array($nombre=>"Configuracion RdfType Borrado");  
        } else {     
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("deleteConfiguracionAction","Fin proceso rest");  
            throw $ex;
        }       
    }


    /**
    * Servicio de publicación de las configuraciones web en servidor desde archivo o contenido yml.
    * El servicio es síncrono, es decir, registra la entrada y guarda el contenido del archivo yml en base de datos</br>
    * <h4>Notas a la publicación:</h4></br>
    * 0.- El servicio responde con un json proveniente la conversión del yml a array y de array a json</br>
    * 1.- Es muy importante revisar que la información del json es la misma que se ha introducido en estructura yml .</br>
    * 2.- Es posible que, por error de formato el archivo yml, se guarde correctamente pero no contenga toda la información pretendida </br>
    * 3.- Para confirmar que la información introducida es la misma que se va a extraer se responde con el json a revisar manualmente </br>
    * 4.- El encoding esperado es "UTF-8".
    * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Post("/web/configuracion")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Guarda configuraciones web en la Base de datos, por archivo (*.yml) o contenido con formato yammer.",
    *  input= {
    *    "class" = "ApiRest\AodPoolBundle\Form\Type\ConfiguracionType",
    *  },
    *  output="ApiRest\AodPoolBundle\Entity\Configuracion",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function postConfiguracionAction(Request $request)
	{
        //calculo la ruta de destino
        $this->getTrazas()->LineaInfo("postConfiguracionAction","Llamada a Función");   
        $this->getTrazas()->LineaDebug("postConfiguracionAction","Creo un objeto rdf");
        $configuracion = new Configuracion();
        $this->getTrazas()->LineaDebug("postConfiguracionAction","Creo el formulario de validación");
        $form = $this->createForm('ApiRest\AodPoolBundle\Form\Type\ConfiguracionType', $configuracion);
        $dal = $this->get('Repository_WebConfig'); 
        //Gestiono el request
        $form->handleRequest($request);
        $yamer= array();
        if ($form->isValid()) {
            $errorFormulario ="";
            $fileSystem = new Filesystem();
            //Primero si viene desde archivo
            if (empty($configuracion->getNombre())) {
                $errorFormulario = $errorFormulario . " El nombre del esquema es requerido.";
                $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);
            } else {
                if($dal->GetExitsConfiguracionWebbySlug($configuracion->getNombre())) {
                    $errorFormulario = $errorFormulario . " El nombre del esquema ya existe.";
                    $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);   
                }    
            }
            if (empty($configuracion->getTipo())) {
                $errorFormulario = $errorFormulario . " El tipo del esquema es requerido.";
                $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);
            }  else {
                if($dal->GetExitsConfiguracionWebbyTipo($configuracion->getTipo())) {
                    $errorFormulario = $errorFormulario . " El del tipo ya se está utilizando.";
                    $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);   
                } 
            }
            if (empty($errorFormulario)) {
                $ficheroEsquema = $configuracion->getNombre() . ".yml";
                if (count($request->files)>0) {
                    if ($_FILES['yml']['error'] > 0) {
                        $errorArchivo = $this->DameErrorUpload($_FILES['yml']['error']);
                        $errorFormulario = $errorFormulario .$errorArchivo ;
                        $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);   
                    }  else {
                        $file = $request->files->get('yml');
                        $this->getTrazas()->LineaDebug("postConfiguracionAction","Recojo el archivo YML");              
                        if (!isset($file)) {
                            $errorFormulario =  $errorFormulario . " No se ha informado el archivo XML.";
                            $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);  
                        }  else  {
                            $original_filename =  $file->getClientOriginalName();
                            $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
                            if ($ext!='yml') {
                                $errorFormulario = $errorFormulario ." El Archivo no es yml.";
                                $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);  
                            }
                            if (empty($errorFormulario)) {
                                $contenido = @file_get_contents($file);
                                try {
                                    $yamer = Yaml::parse( $contenido );
                                } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                                    $errorFormulario = 'Caught exception: ' .  $e->getMessage();
                                    $this->getTrazas()->LineaError("postConfiguracionAction", $errorFormulario); 
                                }
                                if (empty($errorFormulario)) {   
                                    $configura = $dal->SetConfiguracionWeb($configuracion->getNombre(), $configuracion->getTipo(), $contenido ); 
                                }     
                            }   
                        }
                    }
                } else {
                    //Segundo si viene desde texto 
                    //recojo el texto si lo ha enviado via textArea
                    $fileTxt =  $configuracion->getYml();
                    //valido que el csv stá bien formado separado por  ;
                    $this->getTrazas()->LineaDebug("postConfiguracionAction","Validación del texto introducido como YML");    
                    if (!empty($fileTxt)) 
                    {   
                        //reviso el yml desde el texto
                        try {
                            $yamer = Yaml::parse( $fileTxt );
                        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                            $errorFormulario = 'Caught exception: ' .  $e->getMessage();
                            $this->getTrazas()->LineaError("postConfiguracionAction", $errorFormulario); 
                        }  
                        $dal = $this->get('Repository_WebConfig');   
                        $configura = $dal->SetConfiguracionWeb($configuracion->getNombre(), $configuracion->getTipo(), $fileTxt  );      
                    } else {
                        $errorFormulario =  $errorFormulario . " No se ha informado el archivo Yml.";
                    } 
                }
            }
            if (empty($errorFormulario)) { 
                $this->getTrazas()->LineaInfo("postConfiguracionAction","Archivo yml de configuración rdfType guardado con éxito en Base de datos");   
                $this->getTrazas()->LineaInfo("postConfiguracionAction","Fin proceso rest");    
                return array("Configuracion RdfType" =>  $yamer);
            } else {     
                $ex = new BadRequestHttpException($errorFormulario);
                $this->getTrazas()->LineaError("postConfiguracionAction","Fin proceso rest");  
                throw $ex;
                //return array('Configuracion RdfType' => "Proceso con errores");
            }
        } else {
            $ex = new BadRequestHttpException("Sin Parámetros",null ,400);
            $this->getTrazas()->LineaInfo("postConfiguracionAction","Fin proceso rest");  
            throw $ex;
        }
        $this->getTrazas()->LineaInfo("postConfiguracionAction","Fin proceso rest");  
	    return array(
            //devuelvo el error
		    'form' => $form,
        );  
    }



    /**
    * Servicio de lectura de esquemas de configuración para la publicación en virtuoso. Devuelve el listado de los esquemas existentes.
    * El servicio es síncrono, es decir, registra la entrada y responde inmediatamente. 
    * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Get("/publicacion/esquema/listadoxml")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Devuelve el listado de los esquemas de configuración para la publicación en virtuoso",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function getListadoXmlAction(Request $request)
	{
        $errorFormulario ="";
        $this->getTrazas()->LineaInfo("getesquemasAction","Llamada a Función");  
        $directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",$this->appPath);

        $files = preg_grep('~\.(xml)$~', scandir($directoryPath,1)); 
        if (count($files)==0){
            $errorFormulario = $errorFormulario . " No hay esquemas en el servidor.";
            $this->getTrazas()->LineaError("getesquemasAction",$errorFormulario);
        } 
        if (empty($errorFormulario)) { 
            $esquemas = array();
            foreach ($files as $file) {
                $esquemas[] = str_replace(".xml", "",$file);
            }
            $this->getTrazas()->LineaInfo("getesquemasAction","Fin proceso rest");
            return array ("esquemas"=>$esquemas); 
        } else {     
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("getesquemasAction","Fin proceso rest");  
            throw $ex;
        } 
    }

    /** 
    * Servicio de lectura de una configuración de publicación de triples en virtuoso. Devuelve el archivo XML por nombre en formato XML. Para descarga directa del archivo *.yml desde el navegador ( /api/publicacion/esquema/xml/{nombre}.json)
    * El servicio es síncrono     * 
	* @var Request $request
	* @return View|file
	*
	* @View()
    * @Get("/publicacion/esquema/xml/{nombre}")
    * @throws BadRequestHttpException
    * @return file
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Devuelve una configuración de publicación de triples en virtuoso por nombre en formato XML.",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function getXmlAction(Request $request, $nombre)
	{
        $this->getTrazas()->LineaInfo("getXmlAction","Llamada a Función");
        $this->getTrazas()->LineaDebug("getXmlAction","Nombre:" .$nombre);
        $fileDestino="";
        $errorFormulario ="";
        $fileSystem = new Filesystem();
        if ($nombre=="{nombre}"){
            $nombre="";
        }
        if (empty($nombre)) {
            $errorFormulario = $errorFormulario . " El nombre del esquema es requerido.";
            $this->getTrazas()->LineaError("getXmlAction",$errorFormulario);
        } else {
            $directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",$this->appPath);
            $fileDestino = $directoryPath . $nombre . ".xml";
            if (!$fileSystem->exists($fileDestino)) {
                $errorFormulario = $errorFormulario . " No hay esquemas en el servidor con el nombre indicado.";
                $this->getTrazas()->LineaError("getXmlAction",$errorFormulario);
            }  
        } 
        if (empty($errorFormulario)) {
            $this->getTrazas()->LineaDebug("getXmlAction","Fichero publicado:" . $fileDestino);
            header('Content-Description: File Transfer');
            header('Content-Type: text/html; charset=UTF-8');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileDestino));
            header('Content-disposition: attachment; filename='. basename(str_replace(" ", "_",$fileDestino)));
            $this->getTrazas()->LineaInfo("getXmlAction","Fin proceso rest");    
            readfile($fileDestino);    
			$e = new \Exception;
			return $e->getTraceAsString();
        } else {     
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("getXmlAction","Fin proceso rest");  
            throw $ex;
        }       
    }

    /**
    * Servicio de borrado de los esquemas XML de configuración para la publicacion en virtuoso existentes por nombre.
    * El servicio es síncrono, es decir: registra la entrada y responde inmediatamente. 
    * 
	* @var Request $request
	* @return View|array
	*
    * @View()
    * @Delete("/publicacion/esquema/{nombre}")

    * @throws BadRequestHttpException
    * @return array
    * @ApiDoc(
    *  description="Borra esquemas XML de configuración para la publicacion en virtuoso existentes por nombre",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function deleteEsquemaAction(Request $request, $nombre)
	{
        $errorFormulario ="";
        if ($nombre=="{nombre}"){
            $nombre="";
        }
        $this->getTrazas()->LineaInfo("deleteEsquemaAction","Llamada a Función"); 
        $fileSystem = new Filesystem();
        $fileDestino="";
        if (empty($nombre)) {
            $errorFormulario = $errorFormulario . " El nombre del esquema es requerido.";
            $this->getTrazas()->LineaError("postConfiguracionAction",$errorFormulario);
        } else {
            $directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",$this->appPath);
            $fileDestino = $directoryPath . $nombre . ".xml";
            if (!$fileSystem->exists($fileDestino)) {
                $errorFormulario = $errorFormulario . " No hay esquemas en el servidor con el nombre indicado.";
                $this->getTrazas()->LineaError("deleteEsquemaAction",$errorFormulario);
            }  
        }     
        if (empty($errorFormulario)) { 
            $fileSystem->remove($fileDestino);
            $this->getTrazas()->LineaInfo("deleteEsquemaAction","Fin proceso rest");
            return array("status" => "Borrado:" . $fileDestino); 
        } else {     
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("deleteEsquemaAction","Fin proceso rest");  
            throw $ex;
        }  
          
    }

    /**
    * Servicio de publicación de esquemas de configuración para la publicacion en virtuoso desde: archivo, o contenido XML.
    * El servicio es asíncrono, es decir, registra la entrada y guarda el archivo para devolver la respuesta. </br>
    * <h4>Notas a la publicación:</h4></br>
    * 0.- Todos los campos de referencia al csv han de estar limitados por llaves Ejemplos: '{CAMPO}',  '{CAMPO1}{CAMPO2}'.</br>
    * 1.- Si el lugar donde se introduce un campo o campos del csv parsea un namespace, no ha de haber espacios ni caracteres no válidos para dicho namespace.</br>
    * 2.- Los atribute 'condition' han de informarse con un campo que tenga informado un valor booleano  [1|0] y [true|false] (incluidas las minúsculas)</br>
    * 3.- Tipos permitidos: [time | dateTime | integer | float | decimal | string  | boolean ]. (Se ha de poner exacto incluidas las mayúsculas). </br>
    * 4.- Los nodos con atributo ‘value’ han de ser de tipo string</br>
    * 5.- El atributo type es obligatorio para todos los nodos Property.
    * 6.- Añañadir nodo superior (<?xml version="1.0" encoding="UTF-8"?>)
    * 7.- El encoding esperado es "UTF-8".
    * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Post("/publicacion/esquema")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Guarda esquemas XML de configuración para la publicacion en virtuoso desde archivo o contenido XML.",
    *  input= {
    *    "class" = "ApiRest\AodPoolBundle\Form\Type\EsquemaPoolType",
    *  },
    *  output="ApiRest\AodPoolBundle\Entity\Esquema",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    *
    */
	public function postEsquemaAction(Request $request)
	{
        //Calculo de la ruta de destino
        $this->getTrazas()->LineaInfo("postEsquemaAction","Llamada a Función"); 
        $this->getTrazas()->LineaInfo("postEsquemaAction","Inicio proceso API publicacion");
        $this->getTrazas()->LineaInfo("postEsquemaAction","Creo un objeto entidad");
        $esquemaXML = new Esquema();
        $this->getTrazas()->LineaInfo("postEsquemaAction","Creo el formulario de validación");
        $form = $this->createForm('ApiRest\AodPoolBundle\Form\Type\EsquemaPoolType', $esquemaXML);
        $directoryPath = str_replace("app","src/ApiRest/WorkerBundle/Resources/Files/Isonomias/",$this->appPath);
        //Gestiono el request
        $form->handleRequest($request);
        if ($form->isValid()) {
            $errorFormulario ="";
            $fileSystem = new Filesystem();
            //Primero si viene desde archivo
            if (empty($esquemaXML->getNombre())) {
                $errorFormulario = $errorFormulario . " El nombre del esquema es requerido";
                $this->getTrazas()->LineaError("postEsquemaAction","El nombre del esquema es requerido"); 
            } 
            if (empty($errorFormulario)) {
                $ficheroEsquema = urldecode($esquemaXML->getNombre()) . ".xml";                
                
                if (count($request->files)>0) {
                    if ($_FILES['xml']['error'] > 0) {
                        $errorArchivo = $this->DameErrorUpload($_FILES['xml']['error']);
                        $errorFormulario = $errorFormulario .$errorArchivo ;
                        $this->getTrazas()->LineaError("postInsertAction",$errorFormulario );     
                    }  else {
                        $file = $request->files->get('xml');
                        $this->getTrazas()->LineaInfo("postEsquemaAction","Recojo el archivo XML"); 
                        
                        if (!isset($file)) {
                            $errorFormulario =  $errorFormulario . " No se ha informado el archivo XML.";
                            $this->getTrazas()->LineaError("postEsquemaAction","No se ha informado el archivo Xml."); 
                        }  else  {
                            $original_filename =  $file->getClientOriginalName();
                            $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
                            if ($ext!='xml') {
                                $errorFormulario = $errorFormulario ." El Archivo no es xml.";
                                $this->getTrazas()->LineaError("postEsquemaAction","El Archivo no es xml."); 
                            }
                          // $errorFormulario = $errorFormulario  . $this->ComprueboXMLBienformado($file);
                            if (empty($errorFormulario)) {
                                $comprubaISOCSV = new Isonomia("", $this->trazas->getLogger(), $this->appPath, $this->trazas->getEscribeTrazasDebug());
                                $errorFormulario = $errorFormulario . $comprubaISOCSV->CompruebaEntityCarga($file);
                            }
                            if (empty($errorFormulario)) {
                                //Muevo el archivo csv a la carpeta del proceso
                                $this->getTrazas()->LineaInfo("postEsquemaAction","Muevo el archivo xml a la carpeta:" . $directoryPath); 
                                $fileDestino = $directoryPath . $ficheroEsquema;
                                if ($fileSystem->exists($fileDestino)) {
                                $fileSystem->remove($fileDestino);
                                }
                                $file->move($directoryPath, $ficheroEsquema);
                                chmod($fileDestino, 0744); 
                            }     
                        }
                    }
                } else {
                    //Segundo si viene desde texto 
                    //Recojo el texto si lo ha enviado via textArea
                    $fileTxt =  $esquemaXML->getXml();
                    //Valido que el csv stá bien formado separado por  ;
                    $this->getTrazas()->LineaInfo("postEsquemaAction","Validación del texto introducido como XML");     
                    if (!empty($fileTxt)) 
                    {   
                        //Muevo el archivo csv a la carpeta del proceso     
                        $this->getTrazas()->LineaInfo("postInsertAction","Creo el archivo XML a la carpeta:" . $directoryPath);
                        $fileDestino = $directoryPath . $ficheroEsquema;
                        if ($fileSystem ->exists($fileDestino)) {
                            $fileSystem->remove($fileDestino);
                        }
                        $fileSystem->dumpFile($fileDestino, $fileTxt);
                        chmod($fileDestino, 0744); 
                        $this->getTrazas()->LineaInfo("postInsertAction","Compruebo XML bien formado"); 

                       // $errorFormulario = $errorFormulario . $this->ComprueboXMLBienformado($fileDestino);   
                       if (empty($errorFormulario)) {
                            $comprubaISOCSV = new Isonomia("", $this->trazas->getLogger(), $this->appPath, $this->trazas->getEscribeTrazasDebug()); 
                            $errorFormulario = $errorFormulario . $comprubaISOCSV->CompruebaEntityCarga($fileDestino);
                        }           
                        if (!empty($errorFormulario)) {
                            $this->getTrazas()->LineaInfo("postInsertAction","Borro el Archivo" . $fileDestino);  
                            $fileSystem->remove($fileDestino);
                        }
                    } else {
                        $errorFormulario =  $errorFormulario . " No se ha informado el archivo csv.";
                    } 
                }
            }
            if (empty($errorFormulario)) {
                $this->getTrazas()->LineaInfo("postEsquemaAction","Archivo XML isonómico guardado con éxito");        
                $this->getTrazas()->LineaInfo("postEsquemaAction","Fin proceso rest");    
                return array("esquema" =>  $esquemaXML);
            } else {     
                $ex = new BadRequestHttpException($errorFormulario);
                $this->getTrazas()->LineaInfo("postEsquemaAction","Fin proceso rest");
                throw $ex;
                //return array('Isonomía' => "Proceso con errores",);
            }
        } else {
            $ex = new BadRequestHttpException("Sin Parámetros",null ,400);
            $this->getTrazas()->LineaInfo("postEsquemaAction","Fin proceso rest");
            throw $ex;
        }
        $this->getTrazas()->LineaInfo("postEsquemaAction","Fin proceso rest");
	    return array(
            //devuelvo el error
		    'form' => $form,
        );  
    }
    
    /**
    * Servicio de publicación de triples en servidor Virtuoso, desde archivo csv o json, o contenido csv separado por ';' o ',' e identificador de isonomía. <br>
    * El servicio es asíncrono, es decir, registra la entrada y lanza la tarea de carga de las triples en un subproceso. <br>
    * El mensaje de confirmación del servicio, es inmediato y sólo indica la recepción de la solicitud. El proceso de carga triples tarda más tiempo. <br>
    * Al termino del subproceso se envía un email informando al administrador de lo ocurrido. <br>
    * <h4>Notas a la carga de datos:</h4></br>
    * 0.- El archivo de datos ha de estar limitado por ";" o ",". </br>
    * 1.- Si se crea el csv con excel, todos los campos en el csv han de ser de formato texto. </br>
    * 2.- Los campos con tipo booleano y el atributo ‘condition’ han de ser valores [1/0] y [true/false] (con minúsculas) <font color="red">Cuidado con excel porque pone valores booleanos  "VERDADERO/FALSO" y "TRUE/FALSE" y darían error.</font><br>
    * 3.- Si el separador es ";", los datos float y decimal deben de estar con separador decimal . y sin marcador de millares. <br/>
    * 4.- Los datos time y datetime tienen que tener el formato indicado: time: 21:14:07.851-02:00  dateTime:2012-03-26T21:14:07.851-05:00
    * 5.- Todos los campos reflejados en el esquema han de existir en el csv.
    * 6.- El encoding esperado es UTF-8.
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Post("/publicacion/insert")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Publica triples desde csv o json",
    *  input= {
    *    "class" = "ApiRest\AodPoolBundle\Form\Type\PublicacionPoolType",
    *    "name" = ""
    *  },
    *  output="ApiRest\AodPoolBundle\Entity\Publicacion",
    *  statusCodes = {
    *     200 = "Petición recibida correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },

    * )   
    *
    */
	public function postInsertAction(Request $request)
	{
        //calculo de la ruta de destino
        $this->getTrazas()->LineaInfo("postInsertAction","Inicio proceso API publicacion");
        $webPath = realpath($this->appPath. '/../web');
        $path_logs = $webPath . "/logs";
        $path_publicacion = $webPath . "/publicacion";
        $path_publicacion_noprocesados = $webPath . "/publicacion/NoProcesados";
        $path_publicacion_error = $webPath . "/publicacion/Error";
        $path_publicacion_procesados = $webPath . "/publicacion/Procesados";

        //creo un objeto entidad
        $publica = new Publicacion();

        //creo el nombre de la carpeta donde irá el proceso y almacenará el archivo de datos, log y nt
        $datetimecarpeta = new \DateTime();
        $carpeta =  $datetimecarpeta->format('Ymd_His');
        //creo la ruta de carpeta destino 
        
        $this->getTrazas()->LineaInfo("postInsertAction","KKKKKKKKKKKK" . $carpeta);
        $directorio =  sprintf("%s/%s/",$path_publicacion_noprocesados,$carpeta,false); 
        $this->getTrazas()->LineaInfo("postInsertAction","Creo un objeto entidad");
        
        
        //creo el formulario de validación
        $this->getTrazas()->LineaInfo("postInsertAction","Creo el formulario de validación");
        $form = $this->createForm('ApiRest\AodPoolBundle\Form\Type\PublicacionPoolType', $publica);
        //Gestiono el request
        $form->handleRequest($request);
        $fileSystem = new Filesystem();
        //compruebo la carpeta publicacion
        $this->getTrazas()->LineaInfo("postInsertAction","Compruebo la carpeta publicacion");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion);
        //compruebo la carpeta publicacion/NoProcesados
        $this->getTrazas()->LineaInfo("postInsertAction","Compruebo la carpeta publicacion/NoProcesados");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion_noprocesados);
        //compruebo la carpeta publicacion/Error
        $this->getTrazas()->LineaInfo("postInsertAction","Compruebo la carpeta publicacion/Error");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion_error);
        //compruebo la carpeta publicacion/Procesados
        $this->getTrazas()->LineaInfo("postInsertAction","Compruebo la carpeta publicacion/Procesados");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion_procesados);
        //compruebo la carpeta publicacion/Procesados
        $this->getTrazas()->LineaInfo("postInsertAction", "Compruebo la carpeta publicacion/NoProcesados/" . $carpeta);
        $this->ComprueboCarpeta($fileSystem,$directorio);

        $original_filename="";
        $nombre_esquema ="";
        //si la entidad mandada es validada por el formulario
	    if ($form->isValid()) {
            $errorFormulario ="";
            //compruebo el id isonomia
            $this->getTrazas()->LineaInfo("postInsertAction","Compruebo que existe la isonomia");
            $comprubaISOCSV = new Isonomia($publica->getIdesquema(), $this->trazas->getLogger(), $this->appPath, $this->trazas->getEscribeTrazasDebug()); 

            
            $nombre_esquema = urldecode(strtolower($publica->getIdesquema()));
            $nombre_esquema = str_replace(" ","_", $nombre_esquema);
            if (empty($publica->getIdesquema())) {
                $errorFormulario = $errorFormulario . " El id de isonomía es necesario";
                $this->getTrazas()->LineaError("postInsertAction","El id de isonomía es necesario"); 
                 
            } else if (!$comprubaISOCSV->ExiteIsonomia()) {
                $errorFormulario = $errorFormulario . "No existe la isonomia: " . $publica->getIdesquema();
                $this->getTrazas()->LineaError("postInsertAction","No existe la isonomia: " . $publica->getIdesquema()); 
            }   
            //compruebo el archivo de datos
            if (empty($errorFormulario)) {
                $this->getTrazas()->LineaInfo("postInsertAction","Si existe la isonomia: " . $publica->getIdesquema()); 
                //Primero si viene desde archivo
                if (count($request->files)>0) {
                    //Recojo el archivo
                    //Pone csv porque es el nombre del parametro que viene por defecto y si intentamos diferenciarlo de json se produce un error
                    //Es indiferente porque comprobamos la extension del archivo antes de guardarlo
                    $file = $request->files->get('csv');
                    $this->getTrazas()->LineaInfo("postInsertAction","Recojo el archivo de datos");   
                    //Si no existe el archivo 
                    if (!isset($file))
                    {
                        $errorFormulario =  $errorFormulario . " No existe el archivo de datos.";
                        $this->getTrazas()->LineaError("postInsertAction","No se ha informado el archivo de datos."); 
                    }  else if ($_FILES['csv']['error'] > 0)
                    {
                        //Existe el archivo pero se ha producido un error en la carga
                        $errorArchivo = $this->DameErrorUpload($_FILES['csv']['error']);
                        $errorFormulario = $errorFormulario .$errorArchivo ;
                        $this->getTrazas()->LineaError("postInsertAction",$errorFormulario );     
                    }
                    else 
                    {
                        $original_filename =  $file->getClientOriginalName();
                        $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
                        //Comprobamos la extension
                        if ($ext!='csv' && $ext!='json') {
                            //El archivo no tiene uno de los formatos esperados
                            $errorFormulario = $errorFormulario ." El archivo no tiene uno de los formatos esperados.";
                            $this->getTrazas()->LineaError("postInsertAction","El Archivo no es csv."); 
                        }
                        if ($ext=='json'){
                            //Muevo el archivo json a la carpeta del proceso
                            $this->getTrazas()->LineaInfo("postInsertAction","Muevo el archivo json a la carpeta:" . $directorio); 
                            $file->move($directorio,'datos.json');
                            chmod($directorio . "datos.json", 0744);
                        }
                        else{
                            //El archivo es csv
                            if (empty($errorFormulario)) {
                                $headCSV = $this->DameEncabezdosCSV($file); 
                                if (count($headCSV)<=1) {
                                    $errorFormulario =  $errorFormulario . " El contenido del archivo csv no es el esperado. Compruebe la separacion por ';' o ',' de los campos";
                                } else {
                                    $errorFormulario = $errorFormulario . $this->CompruebaCorrespondenciaCsvIsonomia($comprubaISOCSV, $headCSV);
                                }
                            }
                            if (empty($errorFormulario)) {
                                //Muevo el archivo csv a la carpeta del proceso
                                $this->getTrazas()->LineaInfo("postInsertAction","Muevo el archivo csv a la carpeta:" . $directorio); 
                                $file->move($directorio,'datos.csv');
                                chmod($directorio . "datos.csv", 0744); 
                            }
                        }
                    }
                }
                else 
                {
                    //Segundo si viene desde texto 
                    //recojo el texto si lo ha enviado via textArea
                    //Por cómo lo recoge, no se puede escribir un json en el area de texto
                    $fileTxt =  $publica->getCsv();
                    //valido que el csv está bien formado 
                    $this->getTrazas()->LineaInfo("postInsertAction","Validación del texto introducido como archivo de datos"); 

                    if (!empty($fileTxt)) 
                    {   
                        //Si pudieramos recoger json, habria que mirar si el texto introducido tiene formato json para hacer que lo guarde en datos.json
                        //Muevo el archivo csv a la carpeta del proceso    
                        $this->getTrazas()->LineaInfo("postInsertAction","Creo el archivo csv a la carpeta:" . $directorio); 
                        $fileSystem->dumpFile($directorio. 'datos.csv', $fileTxt);
                        chmod($directorio . "datos.csv", 0744); 
                        $file = $directorio . "datos.csv";

                        $this->getTrazas()->LineaInfo("postInsertAction","Compruebo csv bien formado por ;");  
                        $errorFormulario = $errorFormulario . $this->ComprueboCsvBienFormado($file); 
                        if (empty($errorFormulario)) {
                            $this->getTrazas()->LineaInfo("postInsertAction","Archivo csv bien formado por el texto");  

                            //compruebo que el csv y la isonomia se corresponden
                            $headCSV = $this->DameEncabezdosCSV($file);
                            if (count($headCSV)<=1) {
                                $errorFormulario =  $errorFormulario . " El contenido del archivo csv no es el esperado. Compruebe la separacion por ';' o ',' de los campos.";
                            } else {
                                $errorFormulario = $errorFormulario . $this->CompruebaCorrespondenciaCsvIsonomia($comprubaISOCSV, $headCSV);
                            }          
                        }    
                    } else {
                        $errorFormulario =  $errorFormulario . " No se ha informado el archivo csv.";
                    } 
                }
            }
    
            if (empty($errorFormulario)) {
                //Si no ha habido errores al recoger el archivo de datos, mandamos el proceso
                $urlweb = $this->container->getParameter('api_publicacion')['server_worker'];  
              /*  if ($_SERVER['SERVER_PORT']!="80"){
                    $urlweb =  sprintf("%s://localhost:%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['SERVER_PORT']);
                } else {
                    $urlweb =  sprintf("%s://localhost",$_SERVER['REQUEST_SCHEME']);
                }*/
                
                $this->getTrazas()->LineaInfo("postInsertAction","La entidad mandada es validada por el formulario");                
                //lanzo el timespan de seguridad
                $nowtime = time();
                $isonomiaNombre = base64_encode($publica->getIdesquema()); 
                //$param = sprintf("%s_%s%s",$isonomiaNombre,str_replace("_","",$carpeta),$nowtime);
                $valorDcType = base64_encode("false");
                $actualizarItems = base64_encode("false");

                //escribo el log de actualizacion
                //compruebo la carpeta publicacion
                $this->getTrazas()->LineaInfo("postInsertAction","Compruebo la carpeta logs");
                $this->ComprueboCarpeta($fileSystem,$path_logs);      
                $fichero = $path_logs . "/" .  $nombre_esquema  . ".txt";         
                if (strlen($original_filename)>0)  {
                    $log = sprintf("Tipo (Inserción): Archivo (%s)", $original_filename);
                } else {
                    $log = sprintf("Tipo (Inserción): Formulario Web");
                }
                $fichero = base64_encode($fichero);
                $log = base64_encode($log);

                $param = sprintf("%s_%s%s_%s_%s_%s_%s",$isonomiaNombre,str_replace("_","",$carpeta),$nowtime, $valorDcType, $actualizarItems,$fichero,$log);
                $procesCMD = sprintf("curl %s/worker/%s",$urlweb, $param);        
                $this->getTrazas()->LineaInfo("execute", "Lanzo comando: ". $procesCMD);
                exec($procesCMD . " > /dev/null 2>&1 &"); 

                $this->getTrazas()->LineaInfo("postInsertAction","Fin proceso rest");  
                return array("publicacion" =>  "Carga registrada con éxito");

            } else {       
                //Si ha habido error, mueve el proceso a la carpeta de error
                if (strpos($path_publicacion_noprocesados, $carpeta)===FALSE) {
                  $path_publicacion_noprocesados = $path_publicacion_noprocesados . "/" . $carpeta . "/NO PASA A WORKER";
                }
                if (strpos($path_publicacion_error, $carpeta)===FALSE) {
                  $path_publicacion_error = $path_publicacion_error . "/" . $carpeta . "/NO PASA A WORKER";
                }
				rename($path_publicacion_noprocesados ,$path_publicacion_error);
                $ex = new BadRequestHttpException($errorFormulario);
                $this->getTrazas()->LineaInfo("postInsertAction","Fin proceso rest");
                //return array('Publicacion' => "Proceso con errores");
                throw $ex;
            }
        } else {
            //El formulario no es valido, mueve el proceso a la carpeta de errores
            if (strpos($path_publicacion_noprocesados, $carpeta)===FALSE) {
                $path_publicacion_noprocesados = $path_publicacion_noprocesados . "/" . $carpeta . "/No valido";
              }
              if (strpos($path_publicacion_error, $carpeta)===FALSE) {
                $path_publicacion_error = $path_publicacion_error . "/" . $carpeta . "/No valido";
            }
            rename($path_publicacion_noprocesados,$path_publicacion_error);
            $ex = new BadRequestHttpException("Sin Parámetros",null ,400);
            $this->getTrazas()->LineaInfo("postInsertAction","Fin proceso rest");
            throw $ex;
        }
        $this->getTrazas()->LineaInfo("postInsertAction","Fin proceso rest");
	    return array(
            //devuelvo el error
		    'form' => $form,
        );
    }

	
	
    
    /** 
    * Servicio de lectura de los dc:types existentes en el servidor. Devuelve todos los dctype exitentes en servidor.
    * El servicio es síncrono     * 
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Get("/publicacion/update/viewdctype")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Devuelve todos los dctype exitentes en servidor. Util para utilizar el servicio /publicacion/update/view donde se solicita dicho valor",
    *  statusCodes = {
    *     200 = "Proceso realizado correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },
    * )   
    */
	public function getViewDcTypeAction(Request $request)
	{
        $solucion = array();
        $errorFormulario = "";
        $this->getTrazas()->LineaDebug("getListadoDcTypeAction","Llamada a Función");
        $from = $this->container->getParameter('api_publicacion')['isql_db'];
        $url = $this->container->getParameter('api_publicacion')['isql_host'];

        $query ="select distinct ?dctype from <$from>  where {?s dc:type ?dctype .} order by ?dctype ";
        $dctypypes = $this->LanzaConsultaRespuesta($url,$query); 
        $dctypypes = $dctypypes->{"results"};		
        //Genero los triples
        $elementos = $dctypypes->{"result"}->count();
        for ($i = 0; $i < $elementos; $i ++) {
            $result = $dctypypes->result[$i];
            for ($x = 0; $x < $result->{"binding"}->count(); $x++) {
                $blindig = $result->binding [$x];
                $dctype = $blindig->{'uri'}->__toString(); 
                $dctype  = str_replace("http://opendata.aragon.es/def/ei2a#","iea2:",$dctype);
                $solucion[] = $dctype;
            }
        }
        if (count($dctypypes)==0) {
            $errorFormulario = $errorFormulario . " No existe ninguna configuración" ;
            $this->getTrazas()->LineaInfo("getListadoDcTypeAction","No existe ninguna configuración");
        }
        if (empty($errorFormulario)) {
            $this->getTrazas()->LineaError("getListadoDcTypeAction","Fin proceso rest");  
            return $solucion;   
        } else {
            $ex = new BadRequestHttpException($errorFormulario);
            $this->getTrazas()->LineaError("getListadoDcTypeAction","Fin proceso rest");  
            throw $ex;
        }   
    }


	/**
    * Servicio de actualización de triples en servidor Virtuoso, desde archivo csv o json, o contenido csv separado por ';' o ',' e identificador de isonomía. </br>
	* Este servicio borra los triples existentes de la vista indicada y carga los nuevos con la ayuda del dc:type de la vista. </br>
    * El servicio es asíncrono, es decir, registra la entrada y lanza la tarea de carga de las triples en un subproceso. </br>
    * El mensaje de confirmación del servicio es inmediato y sólo indica la recepción de la solicitud. El proceso de carga triples tarda más tiempo. </br>
    * <h4>Notas a la carga de datos:</h4></br>
    * 0.- El archivo de datos ha de estar limitado por ";" o ",". </br>
    * 1.- Si se crea el csv con excel, todos los campos en el csv ha de ser de formato texto. </br>
    * 2.- Los campos con tipo booleano y el atributo ‘condition’ han de ser valores [1/0] y [true/false] (con minúsculas) <font color="red">Cuidado con excel que pone valores booleanos  "VERDADERO/FALSO" y "TRUE/FALSE" y darían error.</font></br>
    * 3.- Si el separador es ";", los datos float y decimal deben de estar con separador decimal . y sin marcador de millares. <br/>
    * 4.- Los datos time y datetime tienen que tener el formato indicado: time: 21:14:07.851-02:00  dateTime:2012-03-26T21:14:07.851-05:00 </br>
    * 5.- Todos los campos reflejados en el esquema han de existir en el csv.
	* @var Request $request
	* @return View|array
	*
	* @View()
    * @Post("/publicacion/update/view")
    * @throws BadRequestHttpException
    * @return array
    * @param Request $request
    * @ApiDoc(
    *  resource=false,
    *  description="Actualiza los triples",
    *  input= {
    *    "class" = "ApiRest\AodPoolBundle\Form\Type\UpdatePoolType",
    *    "name" = ""
    *  },
    *  output="ApiRest\AodPoolBundle\Entity\Update",
    *  statusCodes = {
    *     200 = "Petición recibida correctamente",
    *     400 = "Devuelve si error de parámetros introducidos",
    *     500 = "Devuelve error sistema."
    *   },

    * )   
    *
    */
    public function postViewAction(Request $request)
	{
        $this->getTrazas()->LineaInfo("postInsertAction","Inicio proceso API publicacion");
        
        //calculo la ruta de destino
        $webPath = realpath($this->appPath . '/../web');
        $path_logs = $webPath . "/logs";
        $path_publicacion = $webPath . "/publicacion";
        $path_publicacion_noprocesados = $webPath . "/publicacion/NoProcesados";
        $path_publicacion_error = $webPath . "/publicacion/Error";
        $path_publicacion_procesados = $webPath . "/publicacion/Procesados";

        //creo el nombre de la carpeta donde irá el proceso y almacenará el csv, log y nt
        $carpeta =date("Ymd_His");
        //creo la ruta de carpeta destino 
        $directorio =  sprintf("%s/%s/",$path_publicacion_noprocesados,$carpeta,false);
        $this->getTrazas()->LineaInfo("postViewAction","Inicio proceso API publicacion");
        $this->getTrazas()->LineaInfo("postViewAction","Creo un objeto entidad");
        //creo un objeto entidad
        $update = new Update();
        //creo el formulario de validación
        $this->getTrazas()->LineaInfo("postViewAction","Creo el formulario de validación");
        $form = $this->createForm('ApiRest\AodPoolBundle\Form\Type\UpdatePoolType', $update);
        //Gestiono el request
        $form->handleRequest($request);
        $fileSystem = new Filesystem();
			
		//compruebo la carpeta publicacion
        $this->getTrazas()->LineaInfo("postViewAction","Compruebo la carpeta publicacion");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion);
        //compruebo la carpeta publicacion/NoProcesados
        $this->getTrazas()->LineaInfo("postViewAction","Compruebo la carpeta publicacion/NoProcesados");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion_noprocesados);
        //compruebo la carpeta publicacion/Error
        $this->getTrazas()->LineaInfo("postViewAction","Compruebo la carpeta publicacion/Error");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion_error);
        //compruebo la carpeta publicacion/Procesados
        $this->getTrazas()->LineaInfo("postViewAction","Compruebo la carpeta publicacion/Procesados");
        $this->ComprueboCarpeta($fileSystem,$path_publicacion_procesados);
        //compruebo la carpeta publicacion/Procesados
        $this->getTrazas()->LineaInfo("postViewAction", "Compruebo la carpeta publicacion/NoProcesados/" . $carpeta);
        $this->ComprueboCarpeta($fileSystem,$directorio);
        
        $original_filename="";
        $nombre_esquema ="";
        $dcType ="";
         //si la entidad mandada es validada por el formulario
	    if ($form->isValid()) {
            $errorFormulario ="";
            
            //compruebo el id isonomia
            $nombre_esquema = strtolower($update->getIdesquema());
            $nombre_esquema = str_replace(" ","_", $nombre_esquema);

            $comprubaISOCSV = new Isonomia($update->getIdesquema(), $this->trazas->getLogger(), $this->appPath, $this->trazas->getEscribeTrazasDebug());
            if (empty($update->getIdesquema())) {
                $errorFormulario = $errorFormulario . " El id de isonomía es requerido";
                $this->getTrazas()->LineaError("postViewAction","El id de isonomía es requerido"); 
                 
            } else if (!$comprubaISOCSV->ExiteIsonomia()) {
                $errorFormulario = $errorFormulario . "No existe la isonomia: " . $update->getIdesquema();
                $this->getTrazas()->LineaError("postViewAction","No existe la isonomia: " . $update->getIdesquema()); 
            }   
            //compruebo el csv
            if (empty($errorFormulario)) {
                $this->getTrazas()->LineaInfo("postViewAction","Si existe la isonomia: " . $update->getIdesquema()); 
                //Primero si viene desde archivo
                if (count($request->files)>0) {
                    //recojo el archivo
                    //Pone csv porque es el nombre del parametro que viene por defecto y si intentamos diferenciarlo de json se produce un error
                    //Es indiferente porque comprobamos la extension del archivo antes de guardarlo
                    $file = $request->files->get('csv');
                    $this->getTrazas()->LineaInfo("postViewAction","Recojo el archivo csv");    
                    if (!isset($file))
                    {
                        //el archivo no se ha mandado
                        $errorFormulario =  $errorFormulario . " No se ha informado el archivo de datos.";
                        $this->getTrazas()->LineaError("postInsertAction","No se ha informado el archivo de datos.");                    
                    }                      
                    else if ($_FILES['csv']['error'] > 0) {
                        //Se recoge archivo pero se produce un error en la carga
                        $errorArchivo = $this->DameErrorUpload($_FILES['csv']['error']);
                        $errorFormulario = $errorFormulario .$errorArchivo ;
                        $this->getTrazas()->LineaError("postViewAction",$errorFormulario );     
                    }
                    else 
                    {
                        //Comprobamos la extensión del archivo
                        $original_filename =  $file->getClientOriginalName();
                        $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
                        if ($ext!='csv' && $ext!='json') {
                            //No es .csv ni .json
                            $errorFormulario = $errorFormulario ." El archivo no tiene uno de los formatos esperados.";
                            $this->getTrazas()->LineaError("postViewAction","El archivo no es csv."); 
                        }
                        if($ext=='json'){
                            //Muevo el archivo json a la carpeta del proceso
                            $this->getTrazas()->LineaInfo("postViewAction","Muevo el archivo json a la carpeta:" . $directorio); 
                            $file->move($directorio,'datos.json');
                            chmod($directorio . "datosjson", 0744);
                        }
                        else{
                            //El archivo es csv, si el primer campo es URI y está bien formateado, lo guarda
                            if (empty($errorFormulario)) {
                                $headCSV = $this->DameEncabezdosCSV($file); 
                                if (count($headCSV)<=1) {
                                    $errorFormulario =  $errorFormulario . " El contenido del archivo csv no es el esperado. Compruebe la separacion por ';' o ',' de los campos";
                                } else {
                                    $errorFormulario = $errorFormulario . $this->CompruebaCorrespondenciaCsvIsonomia($comprubaISOCSV, $headCSV);
                                }
                            }
                            if (empty($errorFormulario)) {
                                //Muevo el archivo csv a la carpeta del proceso
                                $this->getTrazas()->LineaInfo("postViewAction","Muevo el archivo csv a la carpeta:" . $directorio); 
                                $file->move($directorio,'datos.csv');
                                chmod($directorio . "datos.csv", 0744);                                 
                            }
                        }
                    }
                }
                else 
                {
                    //Segundo si viene desde texto 
                    //recojo el texto si lo ha enviado via textArea
                    //Por cómo lo recoge, no se puede escribir un json en el area de texto
                    $fileTxt =  $update->getCsv();
                    //valido que el csv stá bien formado separado por  ;
                    $this->getTrazas()->LineaInfo("postViewAction","Validación del texto introducido como csv");     
                    if (!empty($fileTxt)) 
                    {   
                        //Si pudieramos recoger json, habria que mirar si el texto introducido tiene formato json para guardarlo como .json
                        //Muevo el archivo csv a la carpeta del proceso     
                        $this->getTrazas()->LineaInfo("postViewAction","Creo el archivo csv a la carpeta:" . $directorio); 
                        $fileSystem->dumpFile($directorio. 'datos.csv', $fileTxt);
                        chmod($directorio . "datos.csv", 0744); 
                        $file = $directorio . "datos.csv";

                        $this->getTrazas()->LineaInfo("postViewAction","Compruebo csv bien formado por ;");  
                        $errorFormulario = $errorFormulario . $this->ComprueboCsvBienFormado($file); 

                        if (empty($errorFormulario)) {
                            $this->getTrazas()->LineaInfo("postViewAction","Archivo csv bien formado por el texto");  
                            
                            //compruebo que el csv y la Isonomia se corresponden
                            $headCSV = $this->DameEncabezdosCSV($file);
                            if (count($headCSV)<=1) {
                                $errorFormulario =  $errorFormulario . " El contenido del archivo csv no es el esperado. Compruebe la separacion por ';' o ',' de los campos.";
                            } else {
                                $errorFormulario = $errorFormulario . $this->CompruebaCorrespondenciaCsvIsonomia($comprubaISOCSV, $headCSV);
                            }          
                        }
                    } else {
                        $errorFormulario =  $errorFormulario . " No se ha informado el archivo csv.";
                    } 
                }
            }
            
           
            if (empty($errorFormulario)) {
                $this->getTrazas()->LineaInfo("postViewAction","La entidad mandada es validada por el formulario");   
                
                //Preparo la cadena para lanzar el proceso asíncrono llamando al worker por curl a localhost
                $urlweb = $this->container->getParameter('api_publicacion')['server_worker'];  
        
                //lanzo el timespan de seguridad
                $nowtime = time();
                $isonomiaNombre = base64_encode($update->getIdesquema());
                $dctypelog = $dcType;
                $dcType = base64_encode($dcType);
                $actualizarItems = base64_encode("true");

                //escribo el log de actualizacion
                //compruebo la carpeta publicacion
                $this->getTrazas()->LineaInfo("postEntitiesAction","Compruebo la carpeta logs");
                $this->ComprueboCarpeta($fileSystem,$path_logs);
                $fichero = $path_logs . "/" .  $nombre_esquema  . ".txt"; 
                if (strlen($original_filename)>0)  {
                    $log = sprintf("Tipo (Actualización): Archivo (%s)", $original_filename);
                } else {
                    $log = sprintf("Tipo (Actualización): Formulario Web");
                }
                $fichero = base64_encode($fichero);
                $log = base64_encode($log);

                //$param = sprintf("%s_%s%s_%s_%s",$isonomiaNombre,str_replace("_","",$carpeta),$nowtime, $dcType, $actualizarItems);
                $param = sprintf("%s_%s%s_%s_%s_%s_%s",$isonomiaNombre,str_replace("_","",$carpeta),$nowtime, $dcType, $actualizarItems,$fichero,$log);
                $procesCMD = sprintf("curl %s/worker/%s",$urlweb, $param);        
                $this->getTrazas()->LineaInfo("execute", "Lanzo comando: ". $procesCMD);
                exec($procesCMD . " > /dev/null 2>&1 &"); 
    
                $this->getTrazas()->LineaInfo("postViewAction","Fin proceso rest");  
                return array("actualización" =>  "Carga csv con actulización por dc:type registrada con éxito");
            } else {       
                if (strpos($path_publicacion_noprocesados, $carpeta)===FALSE) {
                    $path_publicacion_noprocesados = $path_publicacion_noprocesados . "/" . $carpeta . "/NO PASA A WORKER";
                }
                if (strpos($path_publicacion_error, $carpeta)===FALSE) {
                    $path_publicacion_error = $path_publicacion_error . "/" . $carpeta . "/NO PASA A WORKER";
                }
                rename($path_publicacion_noprocesados ,$path_publicacion_error);
                $ex = new BadRequestHttpException($errorFormulario);
                $this->getTrazas()->LineaInfo("postViewAction","Fin proceso rest");
                throw $ex;
                //return array('Actualización' => "Proceso con errores",);
            } 
        } else {
            if (strpos($path_publicacion_noprocesados, $carpeta)===FALSE) {
                $path_publicacion_noprocesados = $path_publicacion_noprocesados . "/" . $carpeta . "/No valido";
              }
              if (strpos($path_publicacion_error, $carpeta)===FALSE) {
                $path_publicacion_error = $path_publicacion_error . "/" . $carpeta . "/No valido";
            }
            rename($path_publicacion_noprocesados,$path_publicacion_error);
            $ex = new BadRequestHttpException("Sin Parámetros",null ,400);
            $this->getTrazas()->LineaInfo("postInsertAction","Fin proceso rest");
            throw $ex;           
        }
	    return array(
            //devuelvo el error
		    'form' => $form,
        );
    }


    /**
     * Función que comprueba que el csv introducido por texto esté bien formado separado por ;
     * La función recibe una ruta de archivo para poder utilizar fgetcsv
     * fgetcsv nos asegura que los valores del csv estén limitados entre comillas o no indistintamente
     * Si no es valido devuelve el error enn formato texto
     */
    private function ComprueboCsvBienFormado($file) 
    {
        $errorFormulario = "";
        if (file_exists($file)) { 
            try{
                $data = array();
                //abro el archivo
                if (($gestor = fopen($file, "r")) !== FALSE) {
                    $this->getTrazas()->LineaInfo("ComprueboCsvBienFormado","Compruebo fichero csv bien formado"); 
                    //recojo el header, probando el delimitador para guardarlo
                    $header = fgetcsv($gestor, 0, ";"); 
                    $delimitador = ";";
                    if (count($headCSV)<=1){ //Si no coge nada separando por ; lo intenta separando por ,
                        $header = fgetcsv($gestor, 0, ",");
                        $delimitador = ",";
                    }
                    $line_count = -1; // para ignorar el limite del bucle
                     //recorro todas la filas y creo un array clave => valor (encabezado => valor)
                    while (($row = fgetcsv($gestor, 0, $delimitador)) !== FALSE)
                    {
                        foreach ($header as $i => $heading_i)
                        {
                            $row_new[$heading_i] = $row[$i];
                        }
                        $data[] = $row_new;
                    }
                    fclose($gestor);
                    //si no hay regitros al margen del encabezado (primera linea es que no hay registros)
                    if (count($data)==0){
                        $errortxt = "El csv ha de estar bien formado separado por ';' o ','";
                        $errortxt = $errortxt . " Puede que haya seleccionado 'String' en lugar de 'TextArea' en el tipo de control web";
                        $this->getTrazas()->LineaError("ComprueboCsvBienFormado", $errortxt); 
                        $errorFormulario = $errorFormulario . $errortxt; 
                    }
                    $this->getTrazas()->LineaInfo("ComprueboCsvBienFormado",'Fichero csv validado y cerrado'); 
                }
            } catch (Exception $e) {
                $this->getTrazas()->LineaError("ComprueboCsvBienFormado",'Excepción capturada: '.  $e->getMessage()); 
                $errorFormulario = 'Excepción capturada: ' .  $e->getMessage();
                $errorFormulario = $errorFormulario .  "El csv ha de estar bien formado separado por ';' o ','";
            }

        } else {
            $this->getTrazas()->LineaError("ComprueboCsvBienFormado", $file .": Fichero csv no encontrado"); 
            $errorFormulario = $file .": Fichero csv no encontrado";
            $errorFormulario = $errorFormulario .  "El csv ha de estar bien formado separado por ';' o ','";
        }
        return  $errorFormulario;
    }

    /**
     * Función que comprueba que los encabezados del archivo csv existen en el esquema isonomico
     * Si no se corresponden devuelve el error enn formato texto
     */
    private function CompruebaCorrespondenciaCsvIsonomia($comprubaISOCSV, $headCSV)
    {
        $errorFormulario = "";
        $comprubaISOCSV->setHeadCSV($headCSV);
        $this->getTrazas()->LineaInfo("CompruebaCorrespondenciaCsvIsonomia","Compruebo correspondencia csv isonomia "); 
        if ($comprubaISOCSV->CompruebaEsquema()) {
            $this->getTrazas()->LineaInfo("CompruebaCorrespondenciaCsvIsonomia","csv e isonomia se corresponden");  
        } else {
            $errorFormulario = "La isonomia seleccionada no coincide con el csv cargado";
            $this->getTrazas()->LineaError("CompruebaCorrespondenciaCsvIsonomia","La isonomia seleccionada no coincide con el csv cargado"); 
        }
        return $errorFormulario;
    }

    /**
     * Función que devuelve los encabezados del fichero csv dado una ruta
     * Es necesario hacerlo via archivo para poder utilizar la función fgetcsv
     * fgetcsv nos asegura que los valores del csv estén limitados entre comillas o no indistintamente
     */
    private function DameEncabezdosCSV($file){
        $headCSV  = null;
        if (($gestor = fopen($file, "r")) !== FALSE) {
            $headCSV = fgetcsv($gestor, 0, ";"); 
            if (count($headCSV)<=1){
                $gestor = fopen($file, "r");
                $headCSV = fgetcsv($gestor, 0, ",");
            }
        }
        fclose($gestor);
        return  $headCSV;
    }

    /**
     * Función que com prueba la existencia de las carpeta y la crea dándole permisos
     */
    private function ComprueboCarpeta($fileSystem, $carpeta)
    {
        if (!file_exists($carpeta)){
            $this->getTrazas()->LineaInfo("ComprueboCarpeta","Creo Carpeta: " . $carpeta); 
            $fileSystem->mkdir($carpeta);
        }
        /*       chmod($carpeta, 0744);
        } else {
            chmod($carpeta, 0744);
        }*/
    }

    /**
    * Función que devuelve el error de sistema al subir un archivo
     */
    private function DameErrorUpload($error){
        $errorSubida ="";
        switch ($error) {
            case 1:
                $errorSubida="El fichero subido excede la directiva upload_max_filesize de php.ini.";
            break;
            case 2:
                $errorSubida="El fichero subido excede la directiva MAX_FILE_SIZE especificada en el formulario HTML.";
            break;
            case 3:
                $errorSubida="El fichero fue sólo parcialmente subido.";
            break;
            case 4:
                $errorSubida="No se subió ningún fichero.";
            break;
            case 5:
                $errorSubida="";$isonomiaNombre = base64_encode($publica->getIdesquema()); 
            break;$isonomiaNombre = base64_encode($publica->getIdesquema()); 
            case 6:$isonomiaNombre = base64_encode($publica->getIdesquema()); 
                $errorSubida="Falta la carpeta temporal. Introducido en $isonomiaNombre = base64_encode($publica->getIdesquema());  PHP 5.0.3.";
            break;$isonomiaNombre = base64_encode($publica->getIdesquema()); 
            case 7:$isonomiaNombre = base64_encode($publica->getIdesquema()); 
                $errorSubida="No se pudo escribir el fichero en el disco $isonomiaNombre = base64_encode($publica->getIdesquema()); Introducido en PHP 5.1.0.";
            break;$isonomiaNombre = base64_encode($publica->getIdesquema()); 
            case 8:$isonomiaNombre = base64_encode($publica->getIdesquema()); 
                $errorSubida="Una extensión de PHP detuvo la subida de $isonomiaNombre = base64_encode($publica->getIdesquema()); ficheros. PHP no proporciona una forma de determinar la extensión que causó la parada de la subida de ficheros; el examen de la lista de extensiones cargadas con phpinfo() puede ayudar. Introducido en PHP 5.2.0.";
            break;            
            default:
            $errorSubida="Se ha producido un error desconocido";
             break;
        }
        return  $errorSubida;
    }

    	/**
	 * Función que lanza la consulta POST sobre VIRTUOSO
	 *  Parámetros:
	 *    url:               url endpoint del servicio web virtuoso (http://localhost:8890/sparql)
	 *    query:             spaql de inserción
	 */
	private function LanzaConsultaRespuesta($url,&$query)
	{
		$this->trazas->LineaDebug("LanzaConsultaRespuesta", sprintf("Inicio: url:%s , query:%s", $url, $query));
	    $resultArray = array();
		$data = array('query' => $query , 
					'timeout' => 0,
					'format' => 'application/sparql-query-results+xml',
					'Content-Type' => 'application/x-www-form-urlencoded');
		// use key 'http' even if you send the request to https://... 
		$options = array(
			    'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded",
				'method'  => 'POST',
				'encoding' => 'UTF8',
				'content' => http_build_query($data)
			)
		);
		$context  = stream_context_create($options);
		$this->trazas->LineaDebug("LanzaConsultaRespuesta", sprintf("SPARQL>>>: %s", $query));
		$result = @file_get_contents($url, false, $context);
		
		//si es error informo del mismo
		if ($result === FALSE) { 
			$this->error400 = "Se a producido un error en la carga:";
			$this->trazas->LineaError("LanzaConsultaRespuesta",trim($this->error400));
			$this->error = true;
		} else {
			//si no es error
			$this->trazas->LineaDebug("LanzaConsultaRespuesta", sprintf("Se ha realizado la consulta correctamente"));
			$resultArray = simplexml_load_string ($result,'SimpleXMLElement', LIBXML_NOCDATA);
		}
		return $resultArray;	
	} 

} 
