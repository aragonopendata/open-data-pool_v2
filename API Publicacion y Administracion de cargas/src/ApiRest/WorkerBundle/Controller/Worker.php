<?php

namespace ApiRest\WorkerBundle\Controller;

use ApiRest\WorkerBundle\Controller\Triple;
use ApiRest\WorkerBundle\Controller\Isonomia;
use ApiRest\WorkerBundle\Controller\Csv;
use ApiRest\WorkerBundle\Controller\Trazas;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
/**
 * Clase realiza las y tareas de worker del proceso de carga, instancia a loas demás clase para:
 * Cargar los datos del CSV, las isonomía, generar las triples , cargarlas en virtuoso y
 * enviar el mail.
 */

class Worker
{
	/**
     * Id de la isonomía
     */
	protected $id;
	/**
     * Nombre de la carpeta donde se almacena el proceso
     */
	protected $carpeta;
	/*
	* El dcType de la isonomía para borrar pude ser "false" si no se requiere borrar 
	*/
	protected $dcType;
	/*
	* El si es true borra los regitros contenidos en el CSV columna 0 URL
	*/
	protected $actualizarItems;
	/**
     * host del virtuoso
     */
	protected $isqlHost;
	/**
     * Base de datos de virtuoso
     */
	protected $isqlDb;
	/**
     * Nº de triples que se cargan por bloque
     */
	protected $isqlBufferLineas;
	/**
     * Protocolo smtp SSL
     */
	protected $smtpEncryption;
	/**
     * Host smtp
     */
	protected $smtpHost;
	/**
     * Puerto smtp
     */
	protected $smtpPort;
	/**
     * Nombre cuenta smtp   
     */
	protected $smtpUsername;
	/**
     *  Contraseña cuanta smtp
     */
	protected $smtpPassword;
	/**
     *  Correo origen
     */
	protected $emailFrom;
	/**
     * Correo destino
     */
	protected $emailTo;
	/**
     * Adjunto fichero  (trazas o n3) al mail si/no
     */
	protected $mailFile;
	/**
	* El usuario de virtuoso
	*/
	protected $usuVirtuoso;
	/**
	* La contraseña de virtuoso
	*/
	protected $passVirtuoso;

	/**
	* El dominio de la aplicación
	*/
	protected $dominioAplicacion;

	protected $trazas;
	/**
     * Uris actualizar
     */
	protected $UrisActualizar;
	public function __construct($id, $carpeta, $dcType,	$actualizarItems, $isqlHost, $isqlDb, $isqlBufferLineas, $smtpEncryption, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $emailFrom, $emailTo,
								$mailFile,	$trazasDebug, $usuVirtuoso,	$passVirtuoso, $dominioAplicacion, $trazas) 
	{
		$this->trazas = $trazas;
		$this->id = urldecode($id);
		$this->carpeta = $carpeta;
		$this->dcType = $dcType;
		$this->actualizarItems = $actualizarItems;
		$this->isqlHost = $isqlHost;
		$this->isqlDb = $isqlDb;
		$this->isqlBufferLineas = $isqlBufferLineas;
		$this->smtpEncryption = $smtpEncryption;
		$this->smtpHost = $smtpHost;
		$this->smtpPort = $smtpPort;
		$this->smtpUsername = $smtpUsername;
		$this->smtpPassword = $smtpPassword;
		$this->emailFrom = $emailFrom;
		$this->emailTo = $emailTo;
		$this->mailFile = $mailFile;
		$this->usuVirtuoso = $usuVirtuoso;
		$this->passVirtuoso = $passVirtuoso;
		$this->dominioAplicacion = $dominioAplicacion;
		$this->trazas->setClase("worker");
		$this->trazas->LineaInfo("__construct", "Inicia el constructor del worker para la vista: " . $id); 
		$this->trazas->setEscribeTrazasDebug($trazasDebug);
		$this->trazas->setEscribeTrazasInfo(true);
		$this->UrisActualizar = array();
	}
	
    public function Procesa($webPath,$appPath)
	{  
		//Recoge el archivo de datos, genera los triples y comprueba si es una actualizacion o una insercion
		//Si se trata de una actualizacion, primero borra los triples existentes de esa vista y los del provenance asociado, después hace la inserción
		//Si se trata de una inserción, la realiza directamente

		//Durante toda la ejecución comprueba si hay errores en las trazas para continuar o no. Si se ha producido alguno, el proceso se interrumpirá en la comprobación antes del siguiente paso
		$pathNoprocesados =  sprintf("%s/NoProcesados/%s", $webPath, $this->carpeta);
		$pathprocesados =  sprintf("%s/Procesados/%s", $webPath, $this->carpeta);
		$pathError =  sprintf("%s/Error/%s", $webPath, $this->carpeta);
		
		$this->trazas->LineaInfo("Procesa"," Inicio  " . $this->actualizarItems); 

		//Busca un csv
		if (file_exists($pathNoprocesados. "/datos.csv")) { 
			$this->trazas->LineaInfo("Procesa","Archivo datos CSV encontrado");
		} 
		//Busca un json 
		elseif (file_exists($pathNoprocesados. "/datos.json")){
			$this->trazas->LineaInfo("Procesa","Archivo datos JSON encontrado");
		}
		else{
			$this->trazas->LineaError("Procesa","Archivo de datos no encontrado");
		}
		//Si no hay errores carga la isonomia y procesa el esquema 
		if ($this->trazas->SinError()) {
			$this->trazas->LineaInfo("Procesa","Carga la isonomía y procesa el esquema"); 
			$Isonomia = new Isonomia($this->id, $this->trazas, $appPath);
			$PilaTriplesEsquema = $Isonomia->ProcesaEsquema();
		}

		$this->trazas->LineaInfo("Procesa","Isonomia");
		//Si no hay errores carga los datos del archivo 
		if ($this->trazas->SinError()) {
			$this->trazas->LineaInfo("Procesa","Carga los datos del archivo"); 
			$Csv = new Csv($pathNoprocesados,$this->trazas);
			//DameCsv está modificado para leer un archivo csv o un archivo json
			$FilasCsv = $Csv->DameCsv();
		}
	
		//Si no hay errores genero los triples
		if ($this->trazas->SinError()) {
			$this->trazas->LineaInfo("Procesa","Genera los triples"); 
			$TriplesProcesadas = $this->GeneraTriples($PilaTriplesEsquema,$FilasCsv,$pathNoprocesados);
		}
		
		//Si estamos actualizando, borra los triples cargados actualmente y los del provenance asociados antes de cargar los nuevos
		if ($this->actualizarItems=="true"){
			if ($this->trazas->SinError()) {
				//Obtiene URIS de recursos a actualizar
				$this->trazas->LineaInfo("Procesa","Obtengo las uris"); 
				$uris = $Csv->DameUris();
				//Cambiamos el nombre de la carpeta para que sea mas descriptivo y muestre el id de la carga ademas de la fecha
				$newPath = new Filesystem();
				$newPath->mkdir($pathNoprocesados . " " . $this->id);
				rename($pathNoprocesados, $pathNoprocesados . " " . $this->id);
				$pathNoprocesados = $pathNoprocesados . " " . $this->id;
			}
			
			if ($this->trazas->SinError()) {
				//$this->UrisActualizar = $uris;
				//Generamos los triples a borrar
				$this->trazas->LineaInfo("Procesa","La variable urisActualizar contiene: " . $this->UrisActualizar[0]);
				$this->trazas->LineaInfo("Procesa","Genero los triples que hay que borrar, pertenecientes a " . count($this->UrisActualizar) . " URIs");
				$triples = $this->GeneraTriplesBorrar($this->UrisActualizar, $pathNoprocesados);
				//Obtenemos los triples del provenance relacionado para borrarlo cuando acabe el borrado de recursos
				$this->trazas->LineaInfo("Procesa","Genero los triples del provenance");
				$provenance = $this->ObtenerTriplesProv($this->UrisActualizar, $pathNoprocesados);
			}

			//Borro los triples que se quieren actualizar
			if ($this->trazas->SinError()) {
				$this->trazas->LineaInfo("Procesa","Borro los triples a actualizar");
				$this->BorraTriples($triples);
			}

			//Una vez que se han borrado los triples sin errores, borramos el provenance. No puede ser antes porque, en caso de que se produjera un error en el borrado de recursos, 
			//el provenance es la forma de buscarlos y acceder a ellos. 
			if ($this->trazas->SinError()){
				$this->trazas->LineaInfo("Procesa","Borro los triples del provenance");
				$this->BorraTriples($provenance);
			}
			
		}	

		//Si no hay errores guardo las triples en virtuosos
		if ($this->trazas->SinError()) {
			$this->trazas->LineaInfo("Procesa","Guardo las triples en virtuoso"); 
			$this->GuardaTriplesVirtuoso($pathNoprocesados);
		}

		//Envía correo con la información al administrador
		$this->trazas->LineaInfo("Procesa","No Envía correo con la información al administrador"); 
		if ($this->trazas->SinError()) {
		//$this->EnviaEmail($pathNoprocesados,TRUE);
		}  else {
		//$this->EnviaEmail($pathError, FALSE);
		}
		//borro las capetas que pudieran existir
		try{
			$this->trazas->LineaInfo("Procesa","existe carpeta: " . $pathprocesados . " " . $this->id);
			if (file_exists($pathprocesados . " " . $this->id)){
				$this->trazas->LineaInfo("Procesa","borro carpeta" . $pathprocesados . " " . $this->id);
				array_map('unlink', glob("$pathprocesados/*.*"));
			}
			$this->trazas->LineaInfo("Procesa","existe carpeta: " . $pathError . " " . $this->id);
			if (file_exists($pathError . " " . $this->id)){
				$this->trazas->LineaInfo("Procesa","borro carpeta" . $pathError . " " . $this->id);
				array_map('unlink', glob("$pathError/*.*"));
			}
		} catch (Exception $e) {
			$this->trazas->LineaError("Procesa",'Excepción capturada: ' . $e->getMessage());
		}
		try{
			//Si hay errores mueve la carpeta a la carpeta raiz de errores
			//Si no hay errores mueve la carpeta a la carpeta raiz de Procesados 
			$fileSystem = new Filesystem();
			if ($this->trazas->SinError()) {
				$this->trazas->LineaInfo("Procesa","Mueve la carpeta a la capeta raiz de Procesados");
				$fileSystem->mkdir($pathprocesados . " " . $this->id);
				rename($pathNoprocesados,$pathprocesados . " " . $this->id);
			} else {
				$this->trazas->LineaInfo("Procesa","Mueve la carpeta a la capeta raiz de errores");
				$fileSystem->mkdir($pathError . " " . $this->id);
				rename($pathNoprocesados,$pathError . " " . $this->id);
			}
		} catch (Exception $e) {
			$this->trazas->LineaError("Procesa",'Excepción capturada: ' . $e->getMessage());
		}

		$this->trazas->LineaInfo("Procesa","Fin proceso PUBLICA API");
		return;
	}


	function guid() {    
		if (function_exists('com_create_guid')) {        
			return com_create_guid();    
		} else {     
			mt_srand((double)microtime()*10000);
			$charid = strtoupper(md5(uniqid(rand(), true))); 
			$hyphen = chr(45);        
			$uuid   = chr(123)            
					 .substr($charid, 0, 8).$hyphen               
					 .substr($charid, 8, 4).$hyphen            
					 .substr($charid,12, 4).$hyphen            
					 .substr($charid,16, 4).$hyphen            
					 .substr($charid,20,12)            
					 .chr(125);
			return $uuid;    
		}
	}
	
    /**
	 * Función que realiza un doble Loop por cada una de las filas del CSV y cada una de los nodos de la isonomia
	 * Parámetros:
	 *    pilaTriples:      array contador de la las triples  (objetos Triple)
	 *    registrosCSV:      array con los datos del archivo CSV
	 *    pathNoprocesados: path de la carpeta donde está el archivo n3 a procesar
	 *                      /web/publicacion/NoProcesados/{$carpeta}
	 * 
	 */
	function GeneraTriples($pilaTriples,$registrosCSV,$pathNoprocesados)
	{    
		//Establecemos los valores que necesitan un parseado especial por ser del Instituto Aragonés del Agua
		$arrayEspeciales = array("LONSAN", "TAMSAN", "SECPUN", "LONEMI", "TAMEMI", "HAB_EQ_DISEÑO_ADOPT", "LONDIS", "TAMDIS", "VERDEP", "INCDEP",  "CONDEP", "SINDEP", "CAPDEP", "LONCON", "TAMCON", "LONCOL", "TAMCOL");
		$this->trazas->LineaInfo("GeneraTriples","Inicio de generación de triples"); 
		$nombreFichero = $pathNoprocesados . "/datos.n3";    
		//abro fichero para escribir las triples
		$myfile = fopen($nombreFichero, "w+");		
		//$fechaInicio = getdate();
		$fechaInicio =str_replace (" ","T", date('Y-m-d H:i:s'));
		$cuenta=0;
		$primerTriple="";
		//$objetoWasUsedBy = $this->guid();
		$objetoWasUsedBy =str_replace ("}","",str_replace ("{","", "<http://opendata.aragon.es/recurso/procedencia/".$this->guid().">"));
		//$this->trazas->LineaInfo("GeneraTriples","objetoWasUsedBy ". $objetoWasUsedBy);
			//por cada linea del archivo de datos
			foreach ($registrosCSV as $filaCVS) 
			{		
				$boolsujeto=true;			
				//por cada linea a parsear de la isonomia
				foreach ($pilaTriples as $triple) 
				{
					$this->trazas->LineaDebug("GeneraTriples", $triple->getSujetoValor() . $triple->getVerbo() . $triple->getVerbo() . $triple->getPredicado());	
					//parseo y guardo
					$triple->ProcesaDatos($filaCVS);				
					
					//la triple pude ser nula porque algún campo del archivo CSV esta vacío
					if (!empty($triple->getTripleValor())) 
					{
						//$this->trazas->LineaInfo("Procesa","!empty(\$triple->getTripleValor())");
						//obtener el primer triple, contine el sujeto de la entidad principal
						if ($boolsujeto)
						{
							$this->trazas->LineaInfo("Procesa","\$boolsujeto");
							$sujetoprov= $triple->getSujetoValor();
							$this->trazas->LineaDebug("GeneraTriples","cuenta  ". $sujetoprov);
							$boolsujeto=false;
							
						}
						$this->trazas->LineaDebug("GeneraTriples","tripleValor  ". str_replace ("xsd:shape","virtrdf:Geometry",$triple->getTripleValor()));
						fwrite($myfile, str_replace ("xsd:shape","virtrdf:Geometry",$triple->getTripleValor()) ."\n");

						$this->trazas->LineaInfo("Procesa","Añado la URI a UrisActualizar: " . $triple->getSujetoValor());
						array_push($this->UrisActualizar, $triple->getSujetoValor());

						foreach ($triple->getPredicadoParseo() as $valor){
							if (in_array($valor, $arrayEspeciales, false)){
								$valorMapeado = $this->getMapeadoEspeciales($valor);
								if ($valorMapeado != ""){
									$SosaObservaciones =  $triple->getSujetoValor() . " <http://opendata.aragon.es/def/ei2av2#comment> \"" . $valorMapeado . "\"";
									fwrite($myfile,  $SosaObservaciones . " .\n");
								}
							}
						}
					} 
				}
				$this->UrisActualizar = array_unique($this->UrisActualizar);
				$predicadoProv = "<http://www.w3.org/ns/prov#wasUsedBy>";
				
				//$this->trazas->LineaInfo("GeneraTriples"," generar wasUsedBy  ." . $tripleCargaProv);
			}
				


		if (count($this->UrisActualizar) == 0){
			$this->trazas->LineaInfo("Procesa","urisActualizar está vacio");

		}	
		
		$this->trazas->LineaInfo("GeneraTriples"," Introducimos en datos.n3 los triples a actualizar");
		foreach ($this->UrisActualizar as $sujetoProvgeneral) 
		{
			$tripleCargaProv =  $sujetoProvgeneral . " " . $predicadoProv . " " . $objetoWasUsedBy;	
			fwrite($myfile,  $tripleCargaProv . " .\n");
		}


		// Carga de provenance
		// Obtener id parara dataSet de provenance, si no es un numero lo tratamos como una URI
		$this->cargaProvenance($myfile, $fechaInicio, $objetoWasUsedBy);

		
		fclose($myfile);
		$this->trazas->LineaInfo("GeneraTriples","Fin de generación de triples. Triples generadas: ". $cuenta ); 
	}

	function cargaProvenance($myfile, $fechaInicio, $objetoWasUsedBy){
		$this->trazas->LineaInfo("cargaProvenance","Obtenemos el id para el dataset ");
		$identificadorIso= explode (" ",$this->id);
		$idnumerico ="";
		if (is_numeric($identificadorIso[0])) 
		{
        	$idnumerico =$identificadorIso[0];
		}
		else
		{
			$idnumerico = $this->id;
			$idnumerico = str_replace(
				array(' a ', ' de ', ' al ', ' del ', ' y ', ' o ', ' u ', ' e ', 'con ', ' ni ', ' los ', ' en ', ' de ', ' la', ' las ', ' '),
				"-",
				$idnumerico
			);			
			$idnumerico = str_replace(
				array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä','é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë','í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î','ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô','ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü','ñ', 'Ñ', 'ç', 'Ç'),
				array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A','e', 'e', 'e', 'e', 'E', 'E', 'E', 'E','i', 'i', 'i', 'i', 'I', 'I', 'I', 'I','o', 'o', 'o', 'o', 'O', 'O', 'O', 'O','u', 'u', 'u', 'u', 'U', 'U', 'U', 'U','n', 'N', 'c', 'C'),
				$idnumerico
			);								
			$idnumerico = str_replace("-", "aa789bb", $idnumerico);
			$idnumerico = preg_replace('([^A-Za-z0-9])', '', $idnumerico);
			$idnumerico = str_replace( "aa789bb","-", $idnumerico);			
			$idnumerico = strtolower($idnumerico);
		}
		$objetoAodpool = "<http://opendata.aragon.es/recurso/carga/aodpoolv2>";
		$objetoDataset = "<http://opendata.aragon.es/datos/catalogo/dataset/ga-od-core/" . $idnumerico . ">";
		$objetoStartedAtTime = "\"$fechaInicio\"^^xsd:dateTime";

		$tripleRDFType =  $objetoWasUsedBy . " <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/ns/prov#Activity>" ;
		fwrite($myfile,  $tripleRDFType . " .\n");

		$tripleStartedAtTime =  $objetoWasUsedBy . " <http://www.w3.org/ns/prov#startedAtTime> " . $objetoStartedAtTime;
		fwrite($myfile,  $tripleStartedAtTime . " .\n");

		$tripleWasAssociatedWithAodpool =  $objetoWasUsedBy . " <http://www.w3.org/ns/prov#wasAssociatedWith> " . $objetoAodpool;
		fwrite($myfile,  $tripleWasAssociatedWithAodpool . " .\n");
		// solo se carga si es una vista, se identifica porque tiene id
		if (is_numeric($identificadorIso[0])) 
		{
			$tripleWasAssociatedWithDataset =  $objetoWasUsedBy . " <http://www.w3.org/ns/prov#wasAssociatedWith> " . $objetoDataset;
			fwrite($myfile,  $tripleWasAssociatedWithDataset . " .\n");
		}
		else
		{		
			$identificadorcsv = str_replace( "_estructura","", $this->id);		
			$identificadorcsv = str_replace( "_observaciones","", $identificadorcsv);
			$identificadorcsv = str_replace( "-estructura","", $identificadorcsv);		
			$identificadorcsv = str_replace( "-observaciones","", $identificadorcsv);
			$SujetoCkanSinId = $this->ObtenerCKANSinId($identificadorcsv . ".csv");
			if (!empty ($SujetoCkanSinId)) {				
				$this->trazas->LineaInfo("cargaProvenance","SujetoCkanSinId". $SujetoCkanSinId);
				$tripleWasAssociatedWithDataset =  $objetoWasUsedBy . " <http://www.w3.org/ns/prov#wasAssociatedWith> <" . $SujetoCkanSinId . ">";
				fwrite($myfile,  $tripleWasAssociatedWithDataset . " .\n");
			} 
			else
			{
				$SujetoCkanSinIdTitulo = $this->SujetoCkanSinIdTitulo($identificadorcsv);
				if (!empty ($SujetoCkanSinIdTitulo)) {
					$tripleWasAssociatedWithDataset =  $objetoWasUsedBy . " <http://www.w3.org/ns/prov#wasAssociatedWith> <" . $SujetoCkanSinIdTitulo . ">";
					fwrite($myfile,  $tripleWasAssociatedWithDataset . " .\n");
				} 
			}
			
		}

		$tripleRDFTypeAodpool =  $objetoAodpool . " <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/ns/prov#SoftwareAgent>" ;
		fwrite($myfile,  $tripleRDFTypeAodpool . " .\n");


		$tripleNameAodpool =  $objetoAodpool . " <http://xmlns.com/foaf/0.1/name> \"Proceso de carga de Aragón Open Data Pool\"" ;
		fwrite($myfile,  $tripleNameAodpool . " .\n");

		// solo se carga si es una vista, se identifica porque tiene id
		if (is_numeric($identificadorIso[0])) 
		{
			$this->trazas->LineaDebug("cargaProvenance","identificadorIso[0]". $identificadorIso[0]);
			$tripleRDFTypeDataset =  $objetoDataset . " <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/ns/dcat#Dataset>" ;
			fwrite($myfile,  $tripleRDFTypeDataset . " .\n");

			$tripleTitleDataset =  $objetoDataset . " <http://purl.org/dc/elements/1.1/title> \"" . $this->id . "\"" ;
			fwrite($myfile,  $tripleTitleDataset . " .\n");
			
			$tripleTitleDataset =  $objetoDataset . " <http://www.w3.org/ns/dcat#landingPage> <https://opendata.aragon.es/GA_OD_Core/preview?view_id=" . $idnumerico . ">" ;
			fwrite($myfile,  $tripleTitleDataset . " .\n");

			//obtener CKAN con identificador  de carga
			$SujetoCkan = $this->ObtenerCKAN($idnumerico);
			if (!empty ($SujetoCkan)) {
				$this->trazas->LineaDebug("cargaProvenance","$SujetoCkan". $SujetoCkan);
				$tripleTitleDataset =  $objetoDataset . " <http://www.w3.org/ns/dcat#distribution> <" . $SujetoCkan . ">" ;
				fwrite($myfile,  $tripleTitleDataset . " .\n");
			}
			else{ $this->trazas->LineaInfo("cargaProvenance","El CKAN está vacío");}
			

		}
	}

	function getMapeadoEspeciales($valor){
		//método auxiliar para cargar recursos del Instituto Aragonés del Agua
		switch ($valor){
			case "LONSAN":
			case "LONEMI":
			case "LONDIS":
			case "LONCON":
			case "LONCOL":
				return "Longitud";
				break;
			case "TAMSAN":
			case "TAMEMI":
			case "TAMDIS":
			case "TAMCON":
			case "TAMCOL":
				return "Diámetro";
				break;
			case "SECPUN":
				return "Diámetro salida";
				break;
			case "HAB_EQ_DISEÑO_ADOPT":
				return "Capacidad de habitantes equivalente en diseño";
				break;
			case "VERDEP":
				return "Porcentaje lodos vertedero";
				break;
			case "INCDEP": 
				return "Porcentaje lodos incineración";
				break;
			case "CONDEP":
				return "Porcentaje lodos con composaje";
				break;
			case "SINDEP":
				return "Porcentaje lodos sin composaje";
				break;
			case "CAPSEP":
				return "Capacidad";
				break; 
		}
	}

	/**
	 * Función que guarda las triples en servidor Virtuoso mediante la herramienta de lines comando isql
	 * Parámetros:
	 * pathNoprocesados:        path de la carpeta donde está el archivo n3 a procesar
	 *                             /web/publicacion/NoProcesados/{$carpeta}     
	 */
	function GuardaTriplesVirtuoso($pathNoprocesados){

		$this->trazas->LineaDebug("GuardaTriplesVirtuoso", sprintf("%s %s %s %s", $pathNoprocesados, $this->isqlHost, $this->isqlDb, $this->isqlBufferLineas));	
		// inicilizo la url endpoint de virtuoso
		$url = $this->isqlHost;
		$nombrefichero = $pathNoprocesados . "/datos.n3";  
		$this->InsertaTriplesConFichero($nombrefichero);
		if ($this->trazas->SinError()) {
			$this->trazas->LineaInfo("GuardaTriplesVirtuoso", "Se insertaron todos los triples correctamente"); 
		} else {
			$this->trazas->LineaError("GuardaTriplesVirtuoso", "No se insertaron todos los triples correctamente"); 
		}		
	}

	/*
	* Función que lanza un insert, obteniendo los triples de un archivo .n3
	*/
	function InsertaTriplesConFichero($fichero)
	{
		$conn = odbc_connect('VOS', $this->usuVirtuoso, $this->passVirtuoso);
		$ruta=explode("web",$fichero);

		//$fichero = str_replace ($this->dominioAplicacion,"/var/www/html/web/", $fichero);
		$fichero =$this->dominioAplicacion. $ruta[1];
		$comando = "CALL DB.DBA.TTLP_MT(http_get('".$fichero."'), '', '".$this->isqlDb."', 0, 2)";
		$result = odbc_exec($conn, $comando);
		
		$this->trazas->LineaDebug("InsertaTriplesConFichero", "Ejecuto " . $comando); 
		if ($result === FALSE) { 
			$this->trazas->LineaError("InsertaTriplesConFichero", "Se ha producido el siguiente error: " . odbc_errormsg($conn)); 
		} else {
			$this->trazas->LineaDebug("InsertaTriplesConFichero", "Resultado:" . $result);  
		}		
		odbc_close ($conn);		
	}  
	/**
	 * Obtener en virtuoso el sujeto CKAN que corresponde al identificador del xml que se intenta cargar
	 */

	function ObtenerCKAN($id)
	{
		$triples = "";
		$this->trazas->LineaInfo("ObtenerCKAN","Busco el Ckan correspondiente al identificador" . $id); 		
			
		//$query = $this->isqlHost."?default-graph-uri=&query=select+%3Fs+%3Fp+%3Fo+from+%3Chttp%3A%2F%2Fopendata.aragon.es%2Fgraph%2Fpool%3E%0D%0Awhere+%0D%0A%7B%0D%0A%09%3C$uri%3E+dc%3Asource+%3Fsource.+%3Fs+dc%3Asource+%3Fsource.+%3Fs+%3Fp+%3Fo.+%0D%0A%7D%0D%0A&should-sponge=&format=application%2Fsparql-results%2Bxml&timeout=0&debug=on&run=+Run+Query+";
		$query = "select  distinct ?s from <http://opendata.aragon.es/def/ei2av2> where {  ?s <http://www.w3.org/ns/dcat#accessURL> ?o. filter ( contains(str(?o), 'https://opendata.aragon.es/GA_OD_Core/download?view_id=" . $id . "&formato=csv')) }";
		//Inicializo la peticion get para obtener el xml con los triples
		$this->trazas->LineaInfo("ObtenerCKAN", "Query " . $query);
		$xmlTriples = $this->LanzaConsultaRespuesta($this->isqlHost,$query);
		
		if (empty ($xmlTriples)) {
			$this->trazas->LineaError("ObtenerCKAN", "No se ha obtenido ningún triple ");
		}
		if ($this->trazas->SinError()) {
			$listadoTriples = $xmlTriples->{"results"};		
			//Genero los triples
			$elementos = $listadoTriples->{"result"}->count ();
			for ($i = 0; $i < $elementos; $i ++) {
				$result = $listadoTriples->result[$i];				
				for ($x = 0; $x < $result->{"binding"}->count (); $x++) {
					$blindig = $result->binding [$x];
					$uri = $blindig->{'uri'}->__toString ();
				}
			}			
		}	
		$this->trazas->LineaInfo("ObtenerCKAN","Fin consulta a virtuoso"); 
		return $uri;
	}
	/**
	 * Obtener en virtuoso el sujeto de la distribución al nombre del xml incluido en dcat:accessURL  
	 */
	function ObtenerCKANSinId($nombrecsv)
	{
		$triples = "";
		$this->trazas->LineaInfo("ObtenerCKANSinId","Busco el Ckan correspondiente al nombre " . $nombrecsv); 		
			
		$query = "select distinct ?s from <http://opendata.aragon.es/def/ei2av2> where { ?s <http://www.w3.org/ns/dcat#accessURL> ?o.   filter ( contains(str(?o), '/" . $nombrecsv . "'))}";
		//$query = "select  * from <http://opendata.aragon.es/graph/catalogo> where { ?s <http://www.w3.org/ns/dcat#accessURL> ?o }";
		//Inicializo la peticion get para obtener el xml con los triples
		$this->trazas->LineaInfo("ObtenerCKANSinId", "Query -> " . $query);
		$xmlTriples = $this->LanzaConsultaRespuesta($this->isqlHost,$query);
		
		if (empty ($xmlTriples)) {
			$this->trazas->LineaError("ObtenerCKANSinId", "No se ha obtenido ningún triple ");
		}
		if ($this->trazas->SinError()) {
			$listadoTriples = $xmlTriples->{"results"};		
			//Genero los triples
			$elementos = $listadoTriples->{"result"}->count ();
			for ($i = 0; $i < $elementos; $i ++) {
				$result = $listadoTriples->result[$i];				
				for ($x = 0; $x < $result->{"binding"}->count (); $x++) {
					$blindig = $result->binding [$x];
					$uri = $blindig->{'uri'}->__toString ();
				}
			}			
		}	
		$this->trazas->LineaInfo("ObtenerCKANSinId","Fin consulta a virtuoso , el ckan:" . $uri); 
		return $uri;
	}
	/**
	 * Obtener en virtuoso el sujeto de la distribución que corresponde al nombre del xml incluido en dcterms:title 
	 */
	function SujetoCkanSinIdTitulo($nombre)
	{
		$triples = "";
		$this->trazas->LineaInfo("SujetoCkanSinIdTitulo","Busco el Ckan correspondiente al identificador " . $nombre); 		
			
		$query = "select  ?s from <http://opendata.aragon.es/def/ei2av2>  where { ?s a <http://www.w3.org/ns/dcat#Distribution>. ?s <http://purl.org/dc/terms/title> ?o. filter( lang(?o)='') BIND(REPLACE(STR(?o),'\\\\.','') AS ?sinpunto) filter (?sinpunto ='" . $nombre . "')}";
		//Inicializo la peticion get para obtener el xml con los triples
		$this->trazas->LineaInfo("SujetoCkanSinIdTitulo", "Query -> " . $query);
		$xmlTriples = $this->LanzaConsultaRespuesta($this->isqlHost,$query);
		
		if (empty ($xmlTriples)) 
		{
			$this->trazas->LineaError("SujetoCkanSinIdTitulo", "No se ha obtenido ningún triple ". $query);
		}
		if ($this->trazas->SinError()) {
			$listadoTriples = $xmlTriples->{"results"};		
			//Genero los triples
			$elementos = $listadoTriples->{"result"}->count ();
			for ($i = 0; $i < $elementos; $i ++) {
				$result = $listadoTriples->result[$i];				
				for ($x = 0; $x < $result->{"binding"}->count (); $x++) {
					$blindig = $result->binding [$x];
					$uri = $blindig->{'uri'}->__toString ();
				}
			}			
		}	
		$this->trazas->LineaInfo("SujetoCkanSinIdTitulo","Fin consulta a virtuoso " . $uri); 
		return $uri;
	}

	function BorrarConIdentificadorIso($identificadorIso)
	{
		$triples = "";
		$this->trazas->LineaInfo("BorrarConIdentificadorIso","Genero los triples que tengo que borrar de Temas NTI"); 		
		$query = "select ?s ?p ?o from <http://opendata.aragon.es/def/ei2av2> where  {?s ?p ?o. ?s <http://www.w3.org/ns/prov#wasUsedBy> ?wasUsedBy. ?wasUsedBy <http://www.w3.org/ns/prov#wasAssociatedWith> ?wasAssociatedWith.  ?wasAssociatedWith <http://purl.org/dc/elements/1.1/title> '" . $identificadorIso . "'}";
		$this->trazas->LineaInfo("BorrarConIdentificadorIso","Genero los triples que tengo que borrar " . $query); 
		//Inicializo la peticion get para obtener el xml con los triples
		$xmlTriples = $this->LanzaConsultaRespuesta($this->isqlHost,$query);
		
		if (empty ($xmlTriples)) {
			$this->trazas->LineaError("BorrarConIdentificadorIso", "No se ha obtenido ningún triple a borrar". $query);
		}
		if ($this->trazas->SinError()) {
			$listadoTriples = $xmlTriples->{"results"};		
			//Genero los triples
			$elementos = $listadoTriples->{"result"}->count ();
			for ($i = 0; $i < $elementos; $i ++) {
				$result = $listadoTriples->result[$i];
				for ($x = 0; $x < $result->{"binding"}->count (); $x++) {
					$blindig = $result->binding [$x];
			
					$uri = $blindig->{'uri'}->__toString ();           
			
					$literal = $blindig->{'literal'};
				
					if (empty ($literal)) {
						$triples .= " <$uri> ";
					}
					else {
						$atributos = (array) $literal->attributes ();
							
						$typo = "";
						if ((count ($atributos)) > 0) 
						{
							$atributos = $atributos ["@attributes"];
							$typo = "^^<$atributos[datatype]>";
						}
						$literal = str_replace ("'","\'", $literal);							
						$literal = str_replace ("\"","\\\\\\\"", $literal);	
						$triples .= " \"$literal\" ". $typo;
					}   
				}
				$triples .= " .\n";  
			}
			$this->trazas->LineaInfo("BorrarConIdentificadorIso","Guardo los triples en datosBorrar.n3"); 
			$nombreFichero = $pathNoprocesados . "/datosBorrar.n3";    
			//abro fichero para escribir las triples
			$myfile = fopen($nombreFichero, "w+");
			
			fwrite($myfile, $triples);
			
			fclose($myfile);
			$this->trazas->LineaInfo("BorrarConIdentificadorIso","Fin de generación de triples a borrar."); 
			if ($this->trazas->SinError()) {
				$this->trazas->LineaInfo("Procesa","Borro los triples a actualizar Temas NTI");
				$this->BorraTriples($triples);
			}
		}	
		
		
		$this->trazas->LineaInfo("BorrarConIdentificadorIso","Fin de generación de triples a borrar."); 
	}

	/** 16092021 Función que realiza una consulta a Virtuoso para obtener los triples del provenance relacionado con $uris. 
	* Una vez creados los triples, los devuelve en un string 
 	*/
	function ObtenerTriplesProv($uris, $pathNoprocesados)
	{
		$triples = "";
		$this->trazas->LineaInfo("ObtenerTriplesProv","Genero los triples del provenance"); 
		foreach ($uris as $uri) {
			//$query = $this->isqlHost."?default-graph-uri=&query=select+%3Fs+%3Fp+%3Fo+from+%3Chttp%3A%2F%2Fopendata.aragon.es%2Fgraph%2Fpool%3E%0D%0Awhere+%0D%0A%7B%0D%0A%09%3C$uri%3E+dc%3Asource+%3Fsource.+%3Fs+dc%3Asource+%3Fsource.+%3Fs+%3Fp+%3Fo.+%0D%0A%7D%0D%0A&should-sponge=&format=application%2Fsparql-results%2Bxml&timeout=0&debug=on&run=+Run+Query+";
			//16092021 Esta consulta obtiene todos los triples de los recursos relacionados con el mismo provenance que $uri
			$query = "select distinct ?prov ?p ?o from <$this->isqlDb> where  { $uri <http://www.w3.org/ns/prov#wasUsedBy> ?prov .  ?s <http://www.w3.org/ns/prov#wasUsedBy> ?prov. ?prov ?p ?o}";
			$this->trazas->LineaInfo("ObtenerTriplesProv","Genero los triples que tengo que borrar: " . $query); 
			
			//Inicializo la peticion get para obtener el xml con los triples
			$xmlTriples = $this->LanzaConsultaRespuesta($this->isqlHost,$query);
			
			if (empty ($xmlTriples)) {
				$this->trazas->LineaInfo("ObtenerTriplesProv", "No se ha obtenido ningún triple a borrar: " . $query);
			}
			$urisGaOdCore = array();
			if ($this->trazas->SinError()) {
				$listadoTriples = $xmlTriples->{"results"};		
				//Genero los triples
				$elementos = $listadoTriples->{"result"}->count ();
				for ($i = 0; $i < $elementos; $i ++) {
					$result = $listadoTriples->result[$i];
					for ($x = 0; $x < $result->{"binding"}->count (); $x++) {
						$blindig = $result->binding [$x];
						$uri = $blindig->{'uri'}->__toString ();           
						$literal = $blindig->{'literal'};	
						//Si hay algún provenance asociado con un ga-od-core, lo añadimos al array de uris que usaremos con la función generarTriplesBorrar
						if (strpos($uri, "ga-od-core") != false){
							$this->trazas->LineaInfo("ObtenerTriplesProv","Se ha añadido para borrar " . $uri); 	
							$urisGaOdCore[] = "<" . $uri . ">"; 
						}

						if (empty ($literal)) {
							$triples .= " <$uri> ";
						}
						else {
							$atributos = (array) $literal->attributes ();
								
							$typo = "";
							if ((count ($atributos)) > 0) 
							{
								$atributos = $atributos ["@attributes"];
								$typo = "^^<$atributos[datatype]>";
							}
							$literal = str_replace ("'","\'", $literal);							
							$literal = str_replace ("\"","\\\\\\\"", $literal); 	
							$triples .= " \"$literal\" ". $typo;
						}   
					}
					$triples .= " .\n";  
				}
				$this->trazas->LineaInfo("ObtenerTriplesProv","Guardo los triples en datosBorrar.n3"); 
				$nombreFichero = $pathNoprocesados . "/datosBorrar.n3";    
				//abro fichero para escribir las triples
				$myfile = fopen($nombreFichero, "w+");
				$this->GeneraTriplesBorrar($urisGaOdCore, $pathNoprocesados);
				fwrite($myfile, $triples);
				fclose($myfile);
				return $triples;
			}	
			
		}
		
		$this->trazas->LineaInfo("GeneraTriplesBorrar","Fin de generación de triples a borrar."); 
		return $triples;
	}
	
	
	/**
	 * Función que realiza una consulta a Virtuoso para obtener los triples que hay que borrar por dc:type
	 * (falta buscar el nombre del archivo n3, con las triples borradas)
	 * Una vez creado los triples los guarda en un archivo n3 y devuelve el string con todos los triples.
	 * Parámetros:
	 *    uris: Son las uris que hay que modificar
	 *    pathNoprocesados: path de la carpeta donde está el archivo n3 a procesar
	 *                      /web/publicacion/NoProcesados/{$carpeta}
	 * 
	 */
	function GeneraTriplesBorrar($uris, $pathNoprocesados)
	{
		$this->trazas->LineaDebug("GeneraTriplesBorrar","Genero los triples a borrar");
		//16092021 161 Temas y 161 Temas NTI escriben sobre los mismos sujetos, es necesario hacer esto para que se almacenen los recursos como es necesario y lógico
		//Cuando hacemos una actualización, borramos TODOS los triples de la vista que estamos actualizando y cargamos los de la actualización. 
		//Para obtener todos esos triples, los buscamos por provenance, por eso es esencial borrar primero estos y luego los del provenance, porque si hubiera error no podriamos acceder a ellos de nuevo
		if ($this->id == "161 Temas")
		{			 
			$this->BorrarConIdentificadorIso("161 Temas NTI");
		}
		$triples = "";
		$this->trazas->LineaInfo("GeneraTriplesBorrar","Genero los triples que tengo que borrar"); 
		foreach ($uris as $uri) {
			//$query = $this->isqlHost."?default-graph-uri=&query=select+%3Fs+%3Fp+%3Fo+from+%3Chttp%3A%2F%2Fopendata.aragon.es%2Fgraph%2Fpool%3E%0D%0Awhere+%0D%0A%7B%0D%0A%09%3C$uri%3E+dc%3Asource+%3Fsource.+%3Fs+dc%3Asource+%3Fsource.+%3Fs+%3Fp+%3Fo.+%0D%0A%7D%0D%0A&should-sponge=&format=application%2Fsparql-results%2Bxml&timeout=0&debug=on&run=+Run+Query+";
			//16092021 Esta consulta obtiene todos los triples de los recursos relacionados con el mismo provenance que $uri
			
			$this->trazas->LineaInfo("GeneraTriplesBorrar", $uri);
			$query = "select distinct ?s ?p ?o from <$this->isqlDb> where  { $uri <http://www.w3.org/ns/prov#wasUsedBy> ?prov .  ?s <http://www.w3.org/ns/prov#wasUsedBy> ?prov. ?s ?p ?o}";
			$this->trazas->LineaInfo("GeneraTriplesBorrar","Genero los triples que tengo que borrar" . $query); 
			
			//Inicializo la peticion get para obtener el xml con los triples
			$xmlTriples = $this->LanzaConsultaRespuesta($this->isqlHost,$query);
			
			if (empty ($xmlTriples)) {
				$this->trazas->LineaInfo("GeneraTriplesBorrar", "No se ha obtenido ningún triple a borrar" . $query);
			}

			if ($this->trazas->SinError()) {
				$listadoTriples = $xmlTriples->{"results"};		
				//Genero los triples
				$elementos = $listadoTriples->{"result"}->count ();
				for ($i = 0; $i < $elementos; $i ++) {
					$result = $listadoTriples->result[$i];
					for ($x = 0; $x < $result->{"binding"}->count (); $x++) {
						$blindig = $result->binding [$x];
				
						$uri = $blindig->{'uri'}->__toString ();           
				
						$literal = $blindig->{'literal'};
					
						if (empty ($literal)) {
							$triples .= " <$uri> ";
						}
						else {
							$atributos = (array) $literal->attributes ();
							$typo = "";
							if ((count ($atributos)) > 0) 
							{
								$atributos = $atributos ["@attributes"];
								$typo = "^^<$atributos[datatype]>";
							}
							$literal = str_replace ("'","\'", $literal);							
							$literal = str_replace ("\"","\\\\\\\"", $literal);	
							$triples .= " \"$literal\" ". $typo;
						}   
					}
					$triples .= " .\n";  
				}
				$this->trazas->LineaInfo("GeneraTriplesBorrar","Guardo los triples en datosBorrar.n3"); 
				$nombreFichero = $pathNoprocesados . "/datosBorrar.n3";    
				//abro fichero para escribir las triples
				$myfile = fopen($nombreFichero, "w+");
				
				fwrite($myfile, $triples);
				fclose($myfile);
				$this->trazas->LineaInfo("GeneraTriplesBorrar","Fin de generación de triples a borrar."); 
				return $triples;
			}	
		}
		
		$this->trazas->LineaInfo("GeneraTriplesBorrar","Fin de generación de triples a borrar."); 
		return $triples;
	}
	
	//Funcion que manda las consultas de borrado de las triples generas
	//La funcion va lanzando lotes de tripes del tamaño de la configuración MaxCarga
	//La funcion recibe como parametros la cadena con todas las tripes
	function BorraTriples($triples) {		
		$this->trazas->LineaInfo("BorraTriples","Comienzo a borrar");	
        //creo un array de triples
		$tripleArray = explode(" .\n",$triples);
		//recojo el total 
		$total = count($tripleArray);	
		$this->trazas->LineaInfo("BorraTriples",'La variable $triples tiene '. $total);
		//inicializo varibles
		$cuenta=0;
		$maxCarga = $this->isqlBufferLineas;
		$totalborradas=0;
		//diferencio indice de total borradas porque puede que el array de sentencias que no son triples.
		$indice=0;
		//mientras que el total sea mayor que el total de borradas, sigo borrando
		while($total>$totalborradas) {
			$tripleBorrar= "";
			for($i=0;$i<$maxCarga;$i++) {
				if ($total>$totalborradas) {
					if (isset($tripleArray[$indice])) {
						if (!empty($tripleArray[$indice])) {
							$tripleBorrar .= "$tripleArray[$indice].\n"; 
							$indice++;
						}
					}
					$totalborradas++;
				}
			}
			//lanzo las consulta de borrado
			
			$consulta = "delete data from <$this->isqlDb> { $tripleBorrar }";
			$this->trazas->LineaInfo("BorraTriples",'Borro: '. $totalborradas);			
			$this->trazas->LineaDebug("BorraTriples",'Borro consulta: '. $consulta);
			$this->LanzaConsulta($this->isqlHost,$consulta);
		}	
	}

	/**
	 * Función que lanza la consulta POST sobre VIRTUOSO
	 *  Parámetros:
	 *    pathNoprocesados:  path de la carpeta donde está el archivo n3 a procesar
	 *                      /web/publicacion/NoProcesados/{$carpeta}
	 *    trazas:            objeto de trazas
	 *    url:               url endpoint del servicio web virtuoso (http://localhost:8890/sparql)
	 *    query:             spaql de inserción
	 */
	function LanzaConsulta($url,$query)
	{
		$this->trazas->LineaDebug("LanzaConsulta", "Query:" . $query);  		
		$conn = odbc_connect('VOS', $this->usuVirtuoso, $this->passVirtuoso);		
		$result = odbc_exec($conn, 'CALL DB.DBA.SPARQL_EVAL(\'' . $query . '\', NULL, 0)');
		
		if ($result === FALSE) { 
			$this->trazas->LineaError("LanzaConsulta", "Se ha producido un error en la carga:" .$query); 
		} else {
			$this->trazas->LineaDebug("LanzaConsulta", "Resultado:" . $result);  
		}
		odbc_close ($conn);	
	}   

	/**
	 * Función que lanza la consulta POST sobre VIRTUOSO
	 *  Parámetros:
	 *    url:               url endpoint del servicio web virtuoso (http://localhost:8890/sparql)
	 *    query:             spaql de inserción
	 */
	function LanzaConsultaRespuesta($url,&$query)
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
			$this->error400 = "Se ha producido un error en la carga:";
			$this->trazas->LineaError("LanzaConsultaRespuesta",trim($this->error400));
			$this->error = true;
		} else {
			//si no es error
			$this->trazas->LineaDebug("LanzaConsultaRespuesta", sprintf("Se ha realizado la consulta correctamente"));
			$resultArray = simplexml_load_string ($result,'SimpleXMLElement', LIBXML_NOCDATA);
		}
		return $resultArray;	
	}  

	/**
	 * Función que envía correo al administrador adjuntando el archivo n3 si nio hay error o el de trazas 
	 * hay error
	 * Parámetros:
	 *    path:               path de la carpeta donde está el archivo n3 a procesar
	 *                        /web/publicacion/NoProcesados/{$carpeta}
	 *    sinError:           Indica si ha habido error o no
	 *    trazas:             Objeto de trazas
	 * 
	 */
	function EnviaEmail($path,$sinError)
	{
		$trazaTexto = sprintf("path %s email_protocol: %s email_host: %s email_port:%s email_username:%s  email_password:%s  email_from:%s email_to: %s",
								$path, $this->smtpEncryption, $this->smtpHost, $this->smtpPort , $this->smtpUsername , $this->smtpPassword, $this->emailFrom, $this->emailTo );
		$this->trazas->LineaDebug("EnviaEmail",'Envia mail '. $trazaTexto  );
		try 
		{
			$this->trazas->LineaDebug("EnviaEmail",'Envia mail configuro usuario SMTP' );
			if ($this->smtpEncryption !== "none") {
				$transport = (\Swift_SmtpTransport::newInstance($this->smtpHost ,$this->smtpPort , $this->smtpEncryption));
			} else {
				$transport = (\Swift_SmtpTransport::newInstance($this->smtpHost ,$this->smtpPort));
			}
		    if (($this->smtpUsername!="none") && ($this->smtpPassword !=="none")) {
				$transport->setUsername($this->smtpUsername);
				$transport ->setPassword($this->smtpPassword);
			}
			$this->trazas->LineaDebug("EnviaEmail",'Envia mail configuro Swift_Mailer' );
			// Create the Mailer using your created Transport
			$mailer = \Swift_Mailer::newInstance($transport);
			if ($sinError) {
				$body = "El proceso de publicacion ha terminado con éxito \n. Ajuntamos el archivo de triples generadas";
			} else {
				$body = "El proceso de publicacion ha terminado con errores \n. Ajuntamos el archivo de log generado";
			}
			$this->trazas->LineaDebug("EnviaEmail",'Envia mail configuro creo mensaje' );
			// Create a message
			$message = (\Swift_Message::newInstance('AodPool mensaje fin proceso Publicación'));
			$message->setFrom([$this->emailFrom => 'AodPool']);
			$message->setTo([$this->emailTo => 'Administrador AodPool']);
			$message->setBody( $body );
            if ($this->mailFile){
				$this->trazas->LineaDebug("EnviaEmail",'Envia mail configuro adjunto archivo' );
				if ($sinError) {  
					$message->attach(\Swift_Attachment::fromPath($path . '/datos.n3'));
				} else {
					$message->attach(\Swift_Attachment::fromPath($this->trazas->DamePath()));
				}
		    }
			$this->trazas->LineaDebug("EnviaEmail",'Envia mail envío' );
			// Send the message
			$result = $mailer->send($message);
		} catch (Exception $e) {
				$this->trazas->LineaDebug("EnviaEmail",'Error envío mail Excepción capturada: '.  $e->getMessage());
		}
	}
}
